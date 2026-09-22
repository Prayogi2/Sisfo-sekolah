<?php

namespace App\Models;

use App\Enums\PromotionStatus;
use App\Enums\Semester;
use Database\Factories\StudentProgressNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status kenaikan kelas & catatan perkembangan siswa untuk satu semester.
 * Disimpan terpisah dari student_academic_records karena datanya berulang
 * tiap semester, sementara buku induk akademik hanya menampung satu baris
 * per siswa.
 */
#[Fillable([
    'student_id',
    'recorded_by',
    'academic_year',
    'semester',
    'promotion_status',
    'notes',
])]
class StudentProgressNote extends Model
{
    /** @use HasFactory<StudentProgressNoteFactory> */
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
            'promotion_status' => PromotionStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
