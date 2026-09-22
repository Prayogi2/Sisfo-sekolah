<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Setting;
use App\Services\ReportExportService;
use Illuminate\Http\Request;
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

    public function exportCsv(Request $request, ReportExportService $exporter)
    {
        $attendances = $this->filteredAttendances($request)->get();

        $rows = $attendances->map(fn ($attendance) => [
            $attendance->date->format('Y-m-d'), $attendance->student->nisn,
            $attendance->student->name, $attendance->student->classroom?->name ?? '-',
            $attendance->check_in_at?->format('H:i:s') ?? '-', $attendance->status->value,
        ]);

        return $exporter->xlsx('laporan-absensi-'.now()->format('Ymd-His').'.xlsx', ['Tanggal', 'NISN', 'Nama Siswa', 'Kelas', 'Jam Scan Masuk', 'Status'], $rows);
    }

    public function exportPdf(Request $request, ReportExportService $exporter)
    {
        return $exporter->pdf('admin.exports.laporan-absensi', [
            'attendances' => $this->filteredAttendances($request)->get(),
            'dateFrom' => $request->input('date_from', now()->startOfMonth()->toDateString()),
            'dateTo' => $request->input('date_to', now()->toDateString()),
        ], 'laporan-absensi-'.now()->format('Ymd-His').'.pdf');
    }

    private function filteredAttendances(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $classroomId = $request->input('classroom_id');

        return Attendance::query()
            ->with(['student.classroom'])
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->when($classroomId, fn ($query) => $query->whereHas(
                'student', fn ($query) => $query->where('classroom_id', $classroomId)
            ))
            ->orderByDesc('date')
            ->orderBy('student_id');
    }
}
