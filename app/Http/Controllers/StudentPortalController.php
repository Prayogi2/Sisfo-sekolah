<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Models\Attendance;
use App\Models\AttendanceSchedule;
use App\Models\Student;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    use ResolvesCurrentStudent;

    public function digitalCard(CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect(auth()->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $today = Attendance::query()
            ->where('student_id', $student->id)
            ->where('date', now()->toDateString())
            ->first();

        $history = $this->lastSevenDaysAttendance($student);

        return view('siswa.kartu-digital', compact('student', 'today', 'history'));
    }

    public function attendanceHistory(CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect(auth()->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $history = $this->lastSevenDaysAttendance($student, days: 30);

        return view('siswa.absensi', compact('student', 'history'));
    }

    public function guardianAttendance(CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect(auth()->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $history = $this->lastSevenDaysAttendance($student);

        return view('wali-murid.absensi-izin', compact('student', 'history'));
    }

    /**
     * @return Collection<int, array{date: Carbon, attendance: ?Attendance, is_school_day: bool}>
     */
    private function lastSevenDaysAttendance(Student $student, int $days = 7): Collection
    {
        $attendances = Attendance::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [now()->subDays($days - 1)->toDateString(), now()->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->date->toDateString());

        return collect(range(0, $days - 1))
            ->map(function (int $daysAgo) use ($attendances, $student) {
                $date = now()->subDays($daysAgo);
                $schedule = $student->classroom
                    ? AttendanceSchedule::for($date, $student->classroom->grade_level)
                    : null;

                return [
                    'date' => $date,
                    'attendance' => $attendances->get($date->toDateString()),
                    'is_school_day' => $schedule !== null,
                ];
            });
    }
}
