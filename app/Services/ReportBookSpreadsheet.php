<?php

namespace App\Services;

use App\Models\ReportBookGrade;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export & import Excel tabel nilai buku induk satu siswa. Keduanya memakai
 * tata letak yang sama persis, jadi file hasil export bisa langsung diisi
 * lalu di-import kembali.
 *
 * Tata letak: identitas siswa (baris 3–10), baris Tahun Ajaran (12), header
 * bertingkat Kelas → Sem (13–14), baris mapel (mulai 15), lalu Jumlah
 * Nilai, Nilai Rata-rata, dan Naik ke Kelas.
 */
class ReportBookSpreadsheet
{
    private const TITLE = 'NILAI LAPORAN HASIL BELAJAR PESERTA DIDIK';

    private const NISN_CELL = 'C7';

    private const YEAR_ROW = 12;

    private const HEADER_ROW = 13;

    private const SEMESTER_ROW = 14;

    private const FIRST_SUBJECT_ROW = 15;

    private const TOTAL_LABEL = 'Jumlah Nilai';

    private const AVERAGE_LABEL = 'Nilai Rata-rata';

    private const PROMOTION_LABEL = 'Naik ke Kelas';

    public function __construct(private ReportBook $reportBook, private ReportExportService $exporter) {}

    public function export(Student $student)
    {
        $student->loadMissing('academicRecord');
        $summary = $this->reportBook->summary($student);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Nilai Buku Induk');
        $lastColumn = $this->scoreColumnLetter(6, 2);

        $sheet->setCellValue('A1', self::TITLE)->mergeCells("A1:{$lastColumn}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $identity = [
            'Nama Siswa' => $student->name,
            'Tempat, Tanggal Lahir' => collect([$student->birth_place, $student->birth_date?->translatedFormat('d F Y')])->filter()->join(', '),
            'Jenis Kelamin' => $student->gender?->label() ?? '',
            'NIS' => $student->nis ?? '',
            'NISN' => $student->nisn ?? '',
            'No. Seri Rapor' => $student->academicRecord?->report_book_serial_number ?? '',
            'No. Seri Ijazah' => $student->academicRecord?->graduation_certificate_number ?? '',
            'No. Ujian' => $student->academicRecord?->exam_number ?? '',
        ];
        $row = 3;
        foreach ($identity as $label => $value) {
            $sheet->setCellValue("A{$row}", $label)->mergeCells("A{$row}:B{$row}");
            // Teks eksplisit supaya NISN/NIS berawalan 0 tidak berubah jadi angka.
            $sheet->setCellValueExplicit("C{$row}", (string) $value, DataType::TYPE_STRING)->mergeCells("C{$row}:{$lastColumn}{$row}");
            $row++;
        }
        $sheet->getStyle('A3:A10')->getFont()->setBold(true);

        $sheet->setCellValue('A'.self::YEAR_ROW, 'Tahun Ajaran')->mergeCells('A'.self::YEAR_ROW.':B'.self::YEAR_ROW);
        $sheet->setCellValue('A'.self::HEADER_ROW, 'No.')->mergeCells('A'.self::HEADER_ROW.':A'.self::SEMESTER_ROW);
        $sheet->setCellValue('B'.self::HEADER_ROW, 'Mata Pelajaran')->mergeCells('B'.self::HEADER_ROW.':B'.self::SEMESTER_ROW);

        foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
            [$first, $second] = [$this->scoreColumnLetter($gradeLevel, 1), $this->scoreColumnLetter($gradeLevel, 2)];
            $sheet->setCellValueExplicit($first.self::YEAR_ROW, (string) ($summary['years']->get($gradeLevel)?->academic_year ?? ''), DataType::TYPE_STRING)
                ->mergeCells($first.self::YEAR_ROW.':'.$second.self::YEAR_ROW);
            $sheet->setCellValue($first.self::HEADER_ROW, "Kelas {$gradeLevel}")->mergeCells($first.self::HEADER_ROW.':'.$second.self::HEADER_ROW);
            $sheet->setCellValue($first.self::SEMESTER_ROW, 'Sem 1');
            $sheet->setCellValue($second.self::SEMESTER_ROW, 'Sem 2');
        }

        $row = self::FIRST_SUBJECT_ROW;
        foreach ($summary['subjects'] as $index => $subject) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $subject->subject_name);
            foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
                foreach (ReportBookGrade::SEMESTERS as $semester) {
                    $sheet->setCellValue($this->scoreColumnLetter($gradeLevel, $semester).$row, $subject->{ReportBookGrade::scoreColumn($gradeLevel, $semester)});
                }
            }
            $row++;
        }

        $totalRow = $row;
        $averageRow = $row + 1;
        $promotionRow = $row + 2;
        foreach ([$totalRow => self::TOTAL_LABEL, $averageRow => self::AVERAGE_LABEL, $promotionRow => self::PROMOTION_LABEL] as $summaryRow => $label) {
            $sheet->setCellValue("A{$summaryRow}", $label)->mergeCells("A{$summaryRow}:B{$summaryRow}");
        }
        foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
            foreach (ReportBookGrade::SEMESTERS as $semester) {
                $column = ReportBookGrade::scoreColumn($gradeLevel, $semester);
                $letter = $this->scoreColumnLetter($gradeLevel, $semester);
                $sheet->setCellValue($letter.$totalRow, $summary['totals'][$column]);
                $sheet->setCellValue($letter.$averageRow, $summary['averages'][$column]);
            }
            [$first, $second] = [$this->scoreColumnLetter($gradeLevel, 1), $this->scoreColumnLetter($gradeLevel, 2)];
            $sheet->setCellValueExplicit($first.$promotionRow, (string) ($summary['years']->get($gradeLevel)?->promoted_to ?? ''), DataType::TYPE_STRING)
                ->mergeCells($first.$promotionRow.':'.$second.$promotionRow);
        }

        $this->styleTable($sheet, $lastColumn, $promotionRow, $totalRow);

        $slug = str($student->name)->slug();

        return $this->exporter->downloadSpreadsheet($spreadsheet, "nilai-buku-induk-{$student->nisn}-{$slug}.xlsx");
    }

    /**
     * Baca file Excel hasil export (yang sudah diisi) untuk siswa ini.
     *
     * @return array{subjects: list<array{name: string, scores: array<string, float|string|null>}>, years: array<int, array{academic_year: ?string, promoted_to: ?string}>}
     *
     * @throws ValidationException bila format file / isinya tidak sesuai.
     */
    public function parse(UploadedFile $file, Student $student): array
    {
        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File tidak bisa dibaca. Pastikan file berformat .xlsx.']);
        }

        $this->assertTemplateLayout($sheet);

        $fileNisn = trim((string) $sheet->getCell(self::NISN_CELL)->getValue());
        if ($fileNisn !== (string) $student->nisn) {
            throw ValidationException::withMessages([
                'file' => "File ini berisi nilai siswa ber-NISN {$fileNisn}, bukan {$student->name} (NISN {$student->nisn}). Pastikan file yang di-import milik siswa yang sama.",
            ]);
        }

        $subjects = [];
        $row = self::FIRST_SUBJECT_ROW;
        $highestRow = $sheet->getHighestDataRow();
        while ($row <= $highestRow && $this->cellText($sheet, "A{$row}") !== self::TOTAL_LABEL) {
            $name = $this->cellText($sheet, "B{$row}");
            $scores = [];
            foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
                foreach (ReportBookGrade::SEMESTERS as $semester) {
                    $value = $sheet->getCell($this->scoreColumnLetter($gradeLevel, $semester).$row)->getCalculatedValue();
                    $scores[ReportBookGrade::scoreColumn($gradeLevel, $semester)] = is_string($value)
                        ? (trim($value) === '' ? null : str_replace(',', '.', trim($value)))
                        : $value;
                }
            }

            if ($name !== '' || collect($scores)->contains(fn ($score) => $score !== null)) {
                $subjects[] = ['name' => $name, 'scores' => $scores];
            }
            $row++;
        }

        if ($row > $highestRow) {
            throw ValidationException::withMessages(['file' => 'Baris "'.self::TOTAL_LABEL.'" tidak ditemukan. Jangan menghapus baris ringkasan di bawah tabel mata pelajaran.']);
        }

        $promotionRow = $row + 2;
        $years = [];
        foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
            $letter = $this->scoreColumnLetter($gradeLevel, 1);
            $years[$gradeLevel] = [
                'academic_year' => $this->cellText($sheet, $letter.self::YEAR_ROW) ?: null,
                'promoted_to' => $this->cellText($sheet, "A{$promotionRow}") === self::PROMOTION_LABEL
                    ? ($this->cellText($sheet, $letter.$promotionRow) ?: null)
                    : null,
            ];
        }

        $data = ['subjects' => $subjects, 'years' => $years];
        Validator::make($data, ReportBook::rules(), ReportBook::messages())->validate();

        return $data;
    }

    /**
     * Kolom Excel untuk nilai Kelas N Semester S: Kelas 1 = C/D … Kelas 6 = M/N.
     */
    private function scoreColumnLetter(int $gradeLevel, int $semester): string
    {
        return Coordinate::stringFromColumnIndex(3 + ($gradeLevel - 1) * 2 + ($semester - 1));
    }

    private function cellText(Worksheet $sheet, string $coordinate): string
    {
        return trim((string) $sheet->getCell($coordinate)->getCalculatedValue());
    }

    private function assertTemplateLayout(Worksheet $sheet): void
    {
        $expected = [
            'A'.self::YEAR_ROW => 'Tahun Ajaran',
            'A'.self::HEADER_ROW => 'No.',
            'B'.self::HEADER_ROW => 'Mata Pelajaran',
            'A7' => 'NISN',
        ];
        foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
            $expected[$this->scoreColumnLetter($gradeLevel, 1).self::HEADER_ROW] = "Kelas {$gradeLevel}";
            $expected[$this->scoreColumnLetter($gradeLevel, 1).self::SEMESTER_ROW] = 'Sem 1';
            $expected[$this->scoreColumnLetter($gradeLevel, 2).self::SEMESTER_ROW] = 'Sem 2';
        }

        foreach ($expected as $coordinate => $label) {
            if (strcasecmp($this->cellText($sheet, $coordinate), $label) !== 0) {
                throw ValidationException::withMessages([
                    'file' => "Format kolom tidak sesuai (sel {$coordinate} seharusnya \"{$label}\"). Gunakan file dari tombol Export Excel, jangan mengubah susunan kolom & header-nya.",
                ]);
            }
        }
    }

    private function styleTable(Worksheet $sheet, string $lastColumn, int $lastRow, int $totalRow): void
    {
        $table = 'A'.self::YEAR_ROW.":{$lastColumn}{$lastRow}";
        $sheet->getStyle($table)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('C'.self::YEAR_ROW.":{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.self::YEAR_ROW.":{$lastColumn}".self::SEMESTER_ROW)->getFont()->setBold(true);
        $sheet->getStyle('A'.self::HEADER_ROW.":{$lastColumn}".self::SEMESTER_ROW)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$totalRow}:{$lastColumn}{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle('C'.self::FIRST_SUBJECT_ROW.":{$lastColumn}".($totalRow + 1))->getNumberFormat()->setFormatCode('0.##');

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(32);
        for ($column = 3; $column <= Coordinate::columnIndexFromString($lastColumn); $column++) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(9);
        }
    }
}
