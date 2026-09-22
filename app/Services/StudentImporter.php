<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor massal Data Siswa dari file Excel/CSV. Tiap baris divalidasi &
 * disimpan satu per satu (bukan semua-atau-tidak-sama-sekali), supaya
 * baris yang salah dilaporkan tapi tidak menggagalkan baris lain yang
 * benar. Sama seperti form Tambah Siswa, hanya identitas inti yang
 * wajib — data Buku Induk lainnya tetap bisa dilengkapi menyusul.
 */
class StudentImporter
{
    /**
     * Urutan kolom di template: kunci field => judul kolom.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'nisn' => 'NISN',
        'nis' => 'NIS',
        'name' => 'Nama Lengkap',
        'gender' => 'Jenis Kelamin (L/P)',
        'classroom' => 'Kelas',
        'birth_place' => 'Tempat Lahir',
        'birth_date' => 'Tanggal Lahir (YYYY-MM-DD)',
        'address' => 'Alamat',
        'parent_name' => 'Nama Orang Tua',
        'parent_phone' => 'No. Telepon Orang Tua',
    ];

    private const REQUIRED_COLUMNS = ['nisn', 'nis', 'name', 'gender'];

    private const MAX_ROWS = 1000;

    public function __construct(private StudentEnroller $enroller) {}

    /**
     * @return list<string>
     */
    public static function templateHeaders(): array
    {
        return array_values(self::COLUMNS);
    }

    /**
     * @return list<string>
     */
    public static function templateExampleRow(): array
    {
        return ['1234567890', '2024001', 'Budi Santoso', 'L', '1-A', 'Jakarta', '2016-05-14', 'Jl. Contoh No. 1', 'Slamet Santoso', '081234567890'];
    }

    public function import(UploadedFile $file): StudentImportResult
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        $headerRow = array_shift($rows) ?? [];

        $columnIndexes = $this->mapColumns($headerRow);

        if (count($rows) > self::MAX_ROWS) {
            throw new \InvalidArgumentException('File berisi lebih dari '.self::MAX_ROWS.' baris data. Pecah menjadi beberapa file terlebih dahulu.');
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            $sheetRowNumber = $index + 2;
            $value = fn (string $field) => isset($columnIndexes[$field]) ? trim((string) ($row[$columnIndexes[$field]] ?? '')) : null;

            $classroomName = $value('classroom');
            $classroom = $classroomName ? $this->findClassroom($classroomName) : null;

            $payload = [
                'nisn' => $value('nisn'),
                'nis' => $value('nis'),
                'name' => $value('name'),
                'gender' => $this->normalizeGender($value('gender')),
                'classroom_id' => $classroom?->id,
                'birth_place' => $value('birth_place') ?: null,
                'birth_date' => $value('birth_date') ?: null,
                'address' => $value('address') ?: null,
                'parent_name' => $value('parent_name') ?: null,
                'parent_phone' => $value('parent_phone') ?: null,
            ];

            $validator = Validator::make($payload, [
                'nisn' => ['required', 'string', 'max:20', 'unique:students,nisn'],
                'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
                'name' => ['required', 'string', 'max:255'],
                'gender' => ['required', 'in:L,P'],
                'classroom_id' => ['nullable', 'integer'],
                'birth_place' => ['nullable', 'string', 'max:255'],
                'birth_date' => ['nullable', 'date'],
                'address' => ['nullable', 'string'],
                'parent_name' => ['nullable', 'string', 'max:255'],
                'parent_phone' => ['nullable', 'string', 'max:30'],
            ], [
                'gender.in' => 'Jenis kelamin harus diisi L atau P.',
            ]);

            // Panggil fails() dulu untuk menjalankan validasi & mengisi
            // message bag-nya. Menambah error manual sebelum fails()
            // percuma: fails() memanggil passes(), yang selalu membuat
            // message bag baru dan membuang tambahan manual itu.
            $rulesFailed = $validator->fails();

            if ($classroomName && $classroom === null) {
                $validator->errors()->add('classroom', "Kelas \"{$classroomName}\" tidak ditemukan.");
            }

            if ($rulesFailed || ($classroomName && $classroom === null)) {
                $errors[] = "Baris {$sheetRowNumber}: ".implode(' ', $validator->errors()->all());

                continue;
            }

            try {
                $this->enroller->enroll($validator->validated());
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Baris {$sheetRowNumber}: gagal disimpan ({$e->getMessage()}).";
            }
        }

        return new StudentImportResult($imported, $errors);
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int>
     */
    private function mapColumns(array $headerRow): array
    {
        $normalized = array_map(fn ($header) => Str::lower(trim((string) $header)), $headerRow);
        $indexes = [];

        foreach (self::COLUMNS as $field => $label) {
            $position = array_search(Str::lower($label), $normalized, true);
            if ($position !== false) {
                $indexes[$field] = $position;
            }
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, array_keys($indexes));
        if ($missing !== []) {
            $missingLabels = collect($missing)->map(fn ($field) => self::COLUMNS[$field])->implode(', ');
            throw new \InvalidArgumentException("Kolom wajib tidak ditemukan: {$missingLabels}. Gunakan template yang disediakan.");
        }

        return $indexes;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) ($value ?? '')) === '');
    }

    private function normalizeGender(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return match (Str::lower($value)) {
            'l', 'laki-laki', 'laki laki', 'laki' => 'L',
            'p', 'perempuan' => 'P',
            default => $value,
        };
    }

    private function findClassroom(string $name): ?Classroom
    {
        return Classroom::query()->whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
    }
}
