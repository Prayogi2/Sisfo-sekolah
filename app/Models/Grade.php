<?php

namespace App\Models;

use App\Enums\Semester;
use Database\Factories\GradeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'subject_id',
    'recorded_by',
    'academic_year',
    'semester',
    'assignment_score',
    'quiz_score',
    'midterm_score',
    'final_score',
])]
class Grade extends Model
{
    /** @use HasFactory<GradeFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => Semester::class,
            'assignment_score' => 'integer',
            'quiz_score' => 'integer',
            'midterm_score' => 'integer',
            'final_score' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Nilai akhir berbobot. Komponen yang belum diisi tidak dianggap nol,
     * tapi bobotnya dikeluarkan dari perhitungan, supaya nilai di tengah
     * semester tetap mencerminkan komponen yang sudah ada. Null berarti
     * belum ada satu pun komponen yang diisi.
     */
    public function finalScore(GradeWeight $weight): ?float
    {
        $components = [
            [$this->assignment_score, $weight->assignment_weight],
            [$this->quiz_score, $weight->quiz_weight],
            [$this->midterm_score, $weight->midterm_weight],
            [$this->final_score, $weight->final_weight],
        ];

        $weightedTotal = 0;
        $usedWeight = 0;

        foreach ($components as [$score, $componentWeight]) {
            if ($score === null) {
                continue;
            }

            $weightedTotal += $score * $componentWeight;
            $usedWeight += $componentWeight;
        }

        if ($usedWeight === 0) {
            return null;
        }

        return round($weightedTotal / $usedWeight, 1);
    }

    public static function letterFor(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 70 => 'B',
            $score >= 60 => 'C',
            default => 'D',
        };
    }
}
