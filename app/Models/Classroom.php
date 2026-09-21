<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'grade_level', 'academic_year', 'homeroom_teacher_id'])]
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
}
