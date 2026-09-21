<?php

namespace App\Models;

use App\Enums\GraduationStatus;
use Database\Factories\StudentAcademicRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_id',
    'kindergarten_origin',
    'kindergarten_certificate_number',
    'kindergarten_certificate_date',
    'entry_status',
    'entry_date',
    'transfer_out_date',
    'transfer_out_reason',
    'exit_date',
    'exit_reason',
    'graduation_status',
    'graduation_year',
    'graduation_certificate_number',
    'graduation_certificate_date',
    'continued_to',
    'graduation_notes',
])]
class StudentAcademicRecord extends Model
{
    /** @use HasFactory<StudentAcademicRecordFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kindergarten_certificate_date' => 'date',
            'entry_date' => 'date',
            'transfer_out_date' => 'date',
            'exit_date' => 'date',
            'graduation_certificate_date' => 'date',
            'graduation_status' => GraduationStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
