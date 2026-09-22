<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceSchedule;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class MarkAbsentStudents extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : Tanggal YYYY-MM-DD, default hari ini}';

    protected $description = 'Menandai siswa aktif yang tidak melakukan scan sebagai alpa setelah jam masuk';

    public function handle(): int
    {
        $date = $this->option('date') ? Date::parse($this->option('date')) : Date::now();
        $marked = 0;

        $students = Student::query()
            ->with('classroom')
            ->where('status', 'active')
            ->whereNotNull('classroom_id')
            ->get();

        foreach ($students as $student) {
            $schedule = AttendanceSchedule::for($date, $student->classroom->grade_level);

            if (! $schedule || $date->copy()->setTimeFromTimeString($schedule->check_in_time)->isFuture()) {
                continue;
            }

            $attendance = Attendance::firstOrCreate(
                ['student_id' => $student->id, 'date' => $date->toDateString()],
                ['status' => AttendanceStatus::Absent],
            );

            $marked += $attendance->wasRecentlyCreated ? 1 : 0;
        }

        $this->info("{$marked} siswa ditandai alpa untuk {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
