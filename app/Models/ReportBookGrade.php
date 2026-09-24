<?php

namespace App\Models;

use Database\Factories\ReportBookGradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris mata pelajaran di tabel "Nilai Laporan Hasil Belajar" buku
 * induk, berisi nilai tiap Kelas 1–6 × Semester 1–2.
 */
#[Fillable([
    'student_id', 'subject_name', 'sort_order',
    'grade_1_semester_1', 'grade_1_semester_2', 'grade_2_semester_1', 'grade_2_semester_2',
    'grade_3_semester_1', 'grade_3_semester_2', 'grade_4_semester_1', 'grade_4_semester_2',
    'grade_5_semester_1', 'grade_5_semester_2', 'grade_6_semester_1', 'grade_6_semester_2',
])]
class ReportBookGrade extends Model
{
    /** @use HasFactory<ReportBookGradeFactory> */
    use HasFactory;

    public const GRADE_LEVELS = [1, 2, 3, 4, 5, 6];

    public const SEMESTERS = [1, 2];

    /**
     * Nama kolom nilai untuk satu kelas & semester, mis. grade_4_semester_2.
     */
    public static function scoreColumn(int $gradeLevel, int $semester): string
    {
        return "grade_{$gradeLevel}_semester_{$semester}";
    }

    /**
     * Semua kolom nilai berurutan Kelas 1 Sem 1 … Kelas 6 Sem 2.
     *
     * @return list<string>
     */
    public static function scoreColumns(): array
    {
        $columns = [];
        foreach (self::GRADE_LEVELS as $gradeLevel) {
            foreach (self::SEMESTERS as $semester) {
                $columns[] = self::scoreColumn($gradeLevel, $semester);
            }
        }

        return $columns;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return collect(self::scoreColumns())->mapWithKeys(fn (string $column) => [$column => 'float'])->all();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
