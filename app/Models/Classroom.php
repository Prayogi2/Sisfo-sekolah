<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'grade_level', 'academic_year', 'capacity', 'homeroom_teacher_id'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * The homeroom teacher (wali kelas) for this classroom.
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * The students enrolled in this classroom.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Tahun ajaran berjalan, mis. "2026/2027". Tahun ajaran baru dimulai Juli.
     */
    public static function currentAcademicYear(): string
    {
        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        return "{$startYear}/".($startYear + 1);
    }
}
