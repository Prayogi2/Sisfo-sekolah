<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Setting;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function report(): View
    {
        $dateFrom = request('date_from', now()->startOfMonth()->toDateString());
        $dateTo = request('date_to', now()->toDateString());
        $classroomId = request('classroom_id');

        $attendances = Attendance::query()
            ->with(['student.classroom'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($classroomId, fn ($query) => $query->whereHas(
                'student',
                fn ($query) => $query->where('classroom_id', $classroomId)
            ))
            ->orderByDesc('date')
            ->orderBy('student_id')
            ->paginate(20)
            ->withQueryString();

        $statCounts = Attendance::query()
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($classroomId, fn ($query) => $query->whereHas(
                'student',
                fn ($query) => $query->where('classroom_id', $classroomId)
            ))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'hadir' => $statCounts[AttendanceStatus::Present->value] ?? 0,
            'telat' => $statCounts[AttendanceStatus::Late->value] ?? 0,
            'izin' => $statCounts[AttendanceStatus::Excused->value] ?? 0,
            'alpa' => $statCounts[AttendanceStatus::Absent->value] ?? 0,
        ];

        $classrooms = Classroom::orderBy('name')->get();
        $lateScanBlockingEnabled = (bool) Setting::get('late_scan_blocking_enabled', false);

        return view('admin.laporan-absensi', compact(
            'attendances',
            'stats',
            'classrooms',
            'dateFrom',
            'dateTo',
            'classroomId',
            'lateScanBlockingEnabled',
        ));
    }
}
