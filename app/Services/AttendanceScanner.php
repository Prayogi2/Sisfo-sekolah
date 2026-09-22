<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceScanException;
use App\Models\Attendance;
use App\Models\AttendanceSchedule;
use App\Models\Setting;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Memproses satu kali scan QR dari pos presensi (alat scanner fisik).
 * Scan pertama hari itu = check-in, scan kedua = check-out.
 */
class AttendanceScanner
{
    /**
     * @throws AttendanceScanException
     */
    public function scan(string $qrToken, ?CarbonInterface $now = null): AttendanceScanResult
    {
        $now ??= Date::now();

        $student = Student::query()->with('classroom')->where('qr_token', $qrToken)->first();

        if (! $student) {
            throw new AttendanceScanException('QR Code tidak dikenali.');
        }

        if (! $student->classroom) {
            throw new AttendanceScanException("{$student->name} belum terdaftar di kelas manapun.");
        }

        $attendance = Attendance::firstOrNew([
            'student_id' => $student->id,
            'date' => $now->toDateString(),
        ]);

        if ($attendance->exists) {
            return $this->recordCheckOutOrDuplicate($student, $attendance, $now);
        }

        return $this->recordCheckIn($student, $attendance, $now);
    }

    private function recordCheckIn(Student $student, Attendance $attendance, CarbonInterface $now): AttendanceScanResult
    {
        $schedule = AttendanceSchedule::for($now, $student->classroom->grade_level);

        if (! $schedule) {
            throw new AttendanceScanException('Tidak ada jadwal presensi untuk hari ini.');
        }

        $isLate = $now->format('H:i:s') > $schedule->check_in_time;
        $blockLateScan = (bool) Setting::get('late_scan_blocking_enabled', false);

        if ($isLate && $blockLateScan) {
            $attendance->status = AttendanceStatus::Absent;
        } else {
            $attendance->check_in_at = $now;
            $attendance->status = $isLate ? AttendanceStatus::Late : AttendanceStatus::Present;
        }

        $attendance->save();

        return new AttendanceScanResult($student, $attendance, 'check_in');
    }

    private function recordCheckOutOrDuplicate(Student $student, Attendance $attendance, CarbonInterface $now): AttendanceScanResult
    {
        if ($attendance->check_in_at && ! $attendance->check_out_at) {
            $schedule = AttendanceSchedule::for($now, $student->classroom->grade_level);
            $checkoutTime = $schedule?->check_out_time;
            $departureStatus = $checkoutTime && $now->format('H:i:s') < $checkoutTime
                ? 'early'
                : 'on_time';

            $attendance->update([
                'check_out_at' => $now,
                'departure_status' => $departureStatus,
            ]);

            return new AttendanceScanResult($student, $attendance, 'check_out');
        }

        return new AttendanceScanResult($student, $attendance, 'duplicate');
    }
}
