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
    // A. Pendidikan sebelumnya
    'kindergarten_origin',
    'kindergarten_address',
    'kindergarten_npsn',
    'kindergarten_certificate_number',
    'kindergarten_certificate_date',
    // B. Status peserta didik
    'entry_status',
    'entry_year',
    'entry_date',
    'entry_classroom',
    // C. Lulus
    'graduation_status',
    'graduation_year',
    'graduation_certificate_number',
    'graduation_certificate_date',
    'graduation_skl_number',
    'continued_to',
    'continued_to_district',
    'continued_to_province',
    'graduation_notes',
    // D. Meninggalkan sekolah (pindah)
    'transfer_out_letter_number',
    'transfer_out_date',
    'transfer_out_classroom',
    'transfer_out_reason',
    'transfer_out_nsm',
    'transfer_out_npsn',
    'transfer_out_village',
    'transfer_out_district',
    'transfer_out_province',
    // E. Putus sekolah / dropout
    'exit_date',
    'exit_classroom',
    'exit_reason',
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
