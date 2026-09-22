<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Models\Attendance;
use App\Models\AttendanceSchedule;
use App\Models\LeaveRequest;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\SppBill;
use App\Models\Student;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    use ResolvesCurrentStudent;

    public function dashboard(CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $resolver->resolve(auth()->user());

        if (! $student) {
            return view('siswa.dashboard', [
                'student' => null,
                'today' => null,
                'monthAttendances' => collect(),
                'bills' => collect(),
                'quizzes' => collect(),
            ]);
        }

        $today = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', today())
            ->first();
        $monthAttendances = Attendance::query()
            ->where('student_id', $student->id)
            ->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();
        $bills = SppBill::with('payments')
            ->where('student_id', $student->id)
            ->latest('period')
            ->get();
        $quizzes = Quiz::with(['subject', 'attempts' => fn ($query) => $query->where('student_id', $student->id)])
            ->where('classroom_id', $student->classroom_id)
            ->where('is_published', true)
            ->latest()
            ->limit(5)
            ->get();

        return view('siswa.dashboard', compact('student', 'today', 'monthAttendances', 'bills', 'quizzes'));
    }

    public function guardianDashboard(CurrentStudentResolver $resolver): View
    {
        $student = $resolver->resolve(auth()->user());

        if (! $student) {
            return view('wali-murid.dashboard', [
                'student' => null,
                'history' => collect(),
                'bills' => collect(),
                'attempts' => collect(),
            ]);
        }

        $history = $this->lastSevenDaysAttendance($student, days: 30);
        $bills = SppBill::with('payments')->where('student_id', $student->id)->latest('period')->get();
        $attempts = QuizAttempt::with('quiz.subject')
            ->where('student_id', $student->id)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->get();

        return view('wali-murid.dashboard', compact('student', 'history', 'bills', 'attempts'));
    }

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

        $leaveRequests = LeaveRequest::query()
            ->where('student_id', $student->id)
            ->latest()
            ->get();

        return view('wali-murid.absensi-izin', compact('student', 'history', 'leaveRequests'));
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
