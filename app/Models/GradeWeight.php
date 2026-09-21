<?php

namespace App\Models;

use App\Enums\Semester;
use Database\Factories\GradeWeightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bobot komponen nilai per mapel per semester. Kalau sebuah mapel belum
 * pernah diatur, dipakai bobot bawaan 20/20/30/30 (lihat default()).
 */
#[Fillable([
    'subject_id',
    'academic_year',
    'semester',
    'assignment_weight',
    'quiz_weight',
    'midterm_weight',
    'final_weight',
])]
class GradeWeight extends Model
{
    /** @use HasFactory<GradeWeightFactory> */
    use HasFactory;

    public const DEFAULT_WEIGHTS = [
        'assignment_weight' => 20,
        'quiz_weight' => 20,
        'midterm_weight' => 30,
        'final_weight' => 30,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'assignment_weight' => 'integer',
            'quiz_weight' => 'integer',
            'midterm_weight' => 'integer',
            'final_weight' => 'integer',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Bobot yang berlaku untuk sebuah mapel di satu semester. Tidak
     * disimpan ke database kalau belum pernah diatur admin.
     */
    public static function for(int $subjectId, string $academicYear, Semester $semester): self
    {
        return static::query()
            ->where('subject_id', $subjectId)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->first() ?? static::default($subjectId, $academicYear, $semester);
    }

    public static function default(int $subjectId, string $academicYear, Semester $semester): self
    {
        return new static([
            'subject_id' => $subjectId,
            'academic_year' => $academicYear,
            'semester' => $semester,
            ...self::DEFAULT_WEIGHTS,
        ]);
    }

    public function total(): int
    {
        return $this->assignment_weight + $this->quiz_weight + $this->midterm_weight + $this->final_weight;
    }
}
