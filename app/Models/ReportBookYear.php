<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "Tahun Ajaran" & "Naik ke Kelas" untuk satu tingkat kelas di tabel nilai
 * buku induk.
 */
#[Fillable(['student_id', 'grade_level', 'academic_year', 'promoted_to'])]
class ReportBookYear extends Model
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
