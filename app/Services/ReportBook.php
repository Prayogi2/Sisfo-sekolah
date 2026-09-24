<?php

namespace App\Services;

use App\Models\ReportBookGrade;
use App\Models\ReportBookYear;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tabel "Nilai Laporan Hasil Belajar Peserta Didik" di buku induk: data
 * per siswa, ringkasan Jumlah Nilai & Nilai Rata-rata per kolom, serta
 * aturan validasi yang dipakai bersama oleh form edit & import Excel.
 */
class ReportBook
{
    /**
     * Form fisik memuat 3 kelas per tabel, jadi Kelas 1–6 dibagi dua tabel.
     */
    public const GRADE_GROUPS = [[1, 2, 3], [4, 5, 6]];

    public const MAX_SUBJECTS = 50;

    /**
     * @return array{
     *     subjects: Collection<int, ReportBookGrade>,
     *     years: Collection<int, ReportBookYear>,
     *     totals: array<string, float|null>,
     *     averages: array<string, float|null>
     * }
     */
    public function summary(Student $student): array
    {
        $student->loadMissing(['reportBookGrades', 'reportBookYears']);
        $subjects = $student->reportBookGrades;

        $totals = [];
        $averages = [];
        foreach (ReportBookGrade::scoreColumns() as $column) {
            $scores = $subjects->pluck($column)->filter(fn (?float $score) => $score !== null);
            $totals[$column] = $scores->isEmpty() ? null : round($scores->sum(), 2);
            $averages[$column] = $scores->isEmpty() ? null : round($scores->avg(), 2);
        }

        return [
            'subjects' => $subjects,
            'years' => $student->reportBookYears->keyBy('grade_level'),
            'totals' => $totals,
            'averages' => $averages,
        ];
    }

    /**
     * Ganti seluruh isi tabel nilai buku induk siswa dengan data baru.
     * Dipakai form edit maupun import, supaya urutan & baris yang dihapus
     * admin ikut tersimpan persis seperti yang terlihat.
     *
     * @param  list<array{name: string, scores?: array<string, float|int|string|null>}>  $subjects
     * @param  array<int, array{academic_year?: ?string, promoted_to?: ?string}>  $years
     */
    public function replace(Student $student, array $subjects, array $years): void
    {
        DB::transaction(function () use ($student, $subjects, $years) {
            ReportBookGrade::query()->where('student_id', $student->id)->delete();

            foreach (array_values($subjects) as $index => $subject) {
                $scores = collect(ReportBookGrade::scoreColumns())
                    ->mapWithKeys(fn (string $column) => [$column => $this->normalizeScore($subject['scores'][$column] ?? null)])
                    ->all();

                $student->reportBookGrades()->create([
                    'subject_name' => trim($subject['name']),
                    'sort_order' => $index + 1,
                    ...$scores,
                ]);
            }

            foreach (ReportBookGrade::GRADE_LEVELS as $gradeLevel) {
                $academicYear = $this->blankToNull($years[$gradeLevel]['academic_year'] ?? null);
                $promotedTo = $this->blankToNull($years[$gradeLevel]['promoted_to'] ?? null);

                if ($academicYear === null && $promotedTo === null) {
                    $student->reportBookYears()->where('grade_level', $gradeLevel)->delete();

                    continue;
                }

                $student->reportBookYears()->updateOrCreate(
                    ['grade_level' => $gradeLevel],
                    ['academic_year' => $academicYear, 'promoted_to' => $promotedTo],
                );
            }
        });

        $student->unsetRelation('reportBookGrades')->unsetRelation('reportBookYears');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'subjects' => ['nullable', 'array', 'max:'.self::MAX_SUBJECTS],
            'subjects.*.name' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            'subjects.*.scores' => ['nullable', 'array'],
            'subjects.*.scores.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'years' => ['nullable', 'array'],
            'years.*.academic_year' => ['nullable', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'years.*.promoted_to' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'subjects.max' => 'Maksimal '.self::MAX_SUBJECTS.' mata pelajaran.',
            'subjects.*.name.required' => 'Nama mata pelajaran di baris ke-:position wajib diisi.',
            'subjects.*.name.max' => 'Nama mata pelajaran di baris ke-:position maksimal 100 karakter.',
            'subjects.*.name.distinct' => 'Mata pelajaran di baris ke-:position tercatat lebih dari sekali.',
            'subjects.*.scores.*.numeric' => 'Nilai di baris mata pelajaran ke-:position harus berupa angka.',
            'subjects.*.scores.*.min' => 'Nilai di baris mata pelajaran ke-:position tidak boleh kurang dari 0.',
            'subjects.*.scores.*.max' => 'Nilai di baris mata pelajaran ke-:position tidak boleh lebih dari 100.',
            'years.*.academic_year.regex' => 'Tahun ajaran harus berformat YYYY/YYYY, mis. 2024/2025.',
            'years.*.promoted_to.max' => '"Naik ke Kelas" maksimal 50 karakter.',
        ];
    }

    /**
     * Tampilan nilai ala rapor: 85 atau 85,5 (koma desimal), kosong bila belum ada.
     */
    public static function formatScore(?float $score): string
    {
        if ($score === null) {
            return '';
        }

        return rtrim(rtrim(number_format($score, 2, ',', '.'), '0'), ',');
    }

    private function normalizeScore(float|int|string|null $score): ?float
    {
        $score = is_string($score) ? str_replace(',', '.', trim($score)) : $score;

        return $score === null || $score === '' ? null : (float) $score;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
