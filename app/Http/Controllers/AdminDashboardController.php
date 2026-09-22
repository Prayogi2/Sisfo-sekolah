<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\SppPaymentStatus;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SppPayment;
use App\Models\Student;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthAttendances = Attendance::whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()])->get();
        $present = $monthAttendances->whereIn('status', [AttendanceStatus::Present, AttendanceStatus::Late])->count();
        $attendanceRate = $monthAttendances->count() > 0 ? round($present / $monthAttendances->count() * 100, 1) : 0;

        return view('admin.dashboard', [
            'studentCount' => Student::count(),
            'attendanceRate' => $attendanceRate,
            'pendingPayments' => SppPayment::where('status', SppPaymentStatus::Pending)->count(),
            'pendingLeaves' => LeaveRequest::where('status', LeaveRequestStatus::Pending)->count(),
            'students' => Student::with('classroom')->withCount(['attendances', 'achievements'])->latest()->limit(5)->get(),
        ]);
    }
}
