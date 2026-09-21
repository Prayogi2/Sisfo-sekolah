<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AttendanceScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['day_of_week', 'grade_min', 'grade_max', 'check_in_time', 'check_out_time'])]
class AttendanceSchedule extends Model
{
    /** @use HasFactory<AttendanceScheduleFactory> */
    use HasFactory;

    /**
     * Cari aturan jadwal yang berlaku untuk hari & tingkat kelas tertentu.
     */
    public static function for(CarbonInterface $date, int $gradeLevel): ?self
    {
        return static::query()
            ->where('day_of_week', $date->dayOfWeekIso)
            ->where('grade_min', '<=', $gradeLevel)
            ->where('grade_max', '>=', $gradeLevel)
            ->first();
    }
}
