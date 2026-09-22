<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'date', 'check_in_at', 'check_out_at', 'departure_status', 'status'])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'status' => AttendanceStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Siswa sudah scan presensi masuk hari ini. Dipakai sebagai syarat
     * mengerjakan kuis CBT — status izin/alpa tidak lolos karena tidak
     * ada jam scan masuk.
     */
    public static function hasCheckedInToday(int $studentId): bool
    {
        return static::query()
            ->where('student_id', $studentId)
            ->where('date', now()->toDateString())
            ->whereNotNull('check_in_at')
            ->exists();
    }
}
