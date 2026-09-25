<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Import siswa ke SATU kelas dari Excel. Siswa harus sudah terdaftar
 * (dicari lewat NISN/NIS) — fitur ini tidak membuat siswa baru.
 *
 * Sebagian berhasil: baris yang benar tetap disimpan, baris yang salah
 * dilewati dan dilaporkan per baris. Siswa yang sudah terdaftar di kelas
 * lain ditolak (bukan dipindah otomatis) — keluarkan dulu dari kelas lamanya.
 */
class ClassroomStudentImporter
{
    /**
     * Judul kolom template => kunci internal.
     *
     * @var array<string, string>
     */
    private const COLUMNS = ['Nama Siswa' => 'name', 'NIS' => 'nis', 'NISN' => 'nisn'];

    private const MAX_ROWS = 1000;

    public function __construct(private ReportExportService $exporter) {}

    public function template(Classroom $classroom)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Import '.Str::limit($classroom->name, 20, ''));
        $sheet->fromArray([array_keys(self::COLUMNS)], null, 'A1');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        // Kolom NIS & NISN diformat teks supaya angka 0 di depan tidak hilang saat diketik.
        $sheet->getStyle('B2:C'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);

        foreach (['A' => 32, 'B' => 16, 'C' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $this->exporter->downloadSpreadsheet($spreadsheet, 'template-import-siswa-kelas-'.Str::slug($classroom->name).'.xlsx');
    }

    /**
     * @throws ValidationException bila file tidak bisa dibaca / format kolomnya salah.
     */
    public function import(UploadedFile $file, Classroom $classroom): ClassroomImportResult
    {
        try {
            $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Gunakan file .xlsx dari tombol Download Template.']);
        }

        $columns = $this->mapColumns(array_shift($rows) ?? []);
        $rows = array_filter($rows, fn (array $row) => ! $this->isBlankRow($row));

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'File tidak berisi baris data siswa.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'File berisi lebih dari '.self::MAX_ROWS.' baris. Pecah menjadi beberapa file.']);
        }

        $cell = fn (array $row, string $key) => trim((string) ($row[$columns[$key]] ?? ''));
        $studentsByNisn = Student::query()->with('classroom')->whereIn('nisn', collect($rows)->map(fn ($row) => $this->normalizeNisn($cell($row, 'nisn')))->filter()->all())->get()->keyBy('nisn');
        $studentsByNis = Student::query()->with('classroom')->whereIn('nis', collect($rows)->map(fn ($row) => $cell($row, 'nis'))->filter()->all())->get()->keyBy('nis');

        $memberCount = Student::query()->where('classroom_id', $classroom->id)->count();
        $placeIds = [];
        $seenOnRow = [];
        $skipped = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = 'Baris '.($index + 2);
            $name = $cell($row, 'name');
            $nis = $cell($row, 'nis');
            $nisn = $this->normalizeNisn($cell($row, 'nisn'));

            $missing = array_filter(['Nama Siswa' => $name === '', 'NIS atau NISN' => $nis === '' && $nisn === '']);
            if ($missing !== []) {
                $errors[] = "{$line}: data tidak lengkap — ".implode(' dan ', array_keys($missing)).' wajib diisi.';

                continue;
            }

            $byNisn = $nisn !== '' ? $studentsByNisn->get($nisn) : null;
            $byNis = $nis !== '' ? $studentsByNis->get($nis) : null;
            if ($nisn !== '' && ! $byNisn) {
                $errors[] = "{$line}: NISN {$nisn} tidak ditemukan di data siswa.";

                continue;
            }
            if ($nis !== '' && ! $byNis) {
                $errors[] = "{$line}: NIS {$nis} tidak ditemukan di data siswa.";

                continue;
            }
            if ($byNisn && $byNis && ! $byNisn->is($byNis)) {
                $errors[] = "{$line}: NISN {$nisn} dan NIS {$nis} milik dua siswa yang berbeda.";

                continue;
            }

            /** @var Student $student */
            $student = $byNisn ?? $byNis;
            if ($this->normalizeName($name) !== $this->normalizeName($student->name)) {
                $errors[] = "{$line}: nama \"{$name}\" tidak cocok dengan data siswa ber-".($byNisn ? "NISN {$nisn}" : "NIS {$nis}")." (\"{$student->name}\"). Periksa lagi NIS/NISN-nya.";

                continue;
            }
            if (isset($seenOnRow[$student->id])) {
                $errors[] = "{$line}: {$student->name} sudah tercantum di baris {$seenOnRow[$student->id]}.";

                continue;
            }
            $seenOnRow[$student->id] = $index + 2;

            if ($student->status !== StudentStatus::Active) {
                $errors[] = "{$line}: {$student->name} berstatus {$student->status->label()}, bukan siswa aktif.";

                continue;
            }
            if ($student->classroom_id === $classroom->id) {
                $skipped[] = "{$line}: {$student->name} sudah terdaftar di kelas ini.";

                continue;
            }
            if ($student->classroom_id !== null) {
                $errors[] = "{$line}: {$student->name} sudah terdaftar di kelas {$student->classroom->name}. Keluarkan dulu lewat Atur Siswa di kelas {$student->classroom->name}.";

                continue;
            }
            if ($memberCount >= $classroom->capacity) {
                $errors[] = "{$line}: {$student->name} tidak dimasukkan karena kelas {$classroom->name} sudah penuh (kapasitas {$classroom->capacity}).";

                continue;
            }

            $placeIds[] = $student->id;
            $memberCount++;
        }

        if ($placeIds !== []) {
            DB::transaction(fn () => Student::query()->whereIn('id', $placeIds)->whereNull('classroom_id')->update(['classroom_id' => $classroom->id]));
        }

        return new ClassroomImportResult($classroom->name, count($placeIds), $skipped, $errors);
    }

    /**
     * @param  array<int, mixed>  $headerRow
     * @return array<string, int>
     */
    private function mapColumns(array $headerRow): array
    {
        $columns = [];
        foreach ($headerRow as $index => $header) {
            $header = trim((string) $header);
            foreach (self::COLUMNS + ['Nama' => 'name'] as $title => $key) {
                if (strcasecmp($header, $title) === 0) {
                    $columns[$key] = $index;
                }
            }
        }

        if (count($columns) < count(self::COLUMNS)) {
            throw ValidationException::withMessages(['file' => 'Format kolom tidak sesuai. Baris pertama harus berisi judul kolom: '.implode(', ', array_keys(self::COLUMNS)).'. Gunakan file dari tombol Download Template.']);
        }

        return $columns;
    }

    /**
     * Excel sering mengubah NISN "0012345678" menjadi angka 12345678.
     */
    private function normalizeNisn(string $nisn): string
    {
        return ctype_digit($nisn) && strlen($nisn) < 10 ? str_pad($nisn, 10, '0', STR_PAD_LEFT) : $nisn;
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->squish()->lower()->toString();
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
