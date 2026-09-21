<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Http\Requests\LeaveRequest\StoreLeaveRequestRequest;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Services\CurrentStudentResolver;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    use ResolvesCurrentStudent;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $user = $request->user();

        $leaveRequests = LeaveRequest::query()
            ->with(['student.classroom', 'guardian'])
            ->when($user->hasRole('guru'), function ($query) use ($user) {
                $classroomIds = Classroom::query()
                    ->whereHas('homeroomTeacher', fn ($query) => $query->where('user_id', $user->id))
                    ->pluck('id');

                $query->whereHas('student', fn ($query) => $query->whereIn('classroom_id', $classroomIds));
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        $view = $user->hasRole('admin') ? 'admin.approval-izin' : 'guru.approval-izin';

        return view($view, compact('leaveRequests'));
    }

    public function store(StoreLeaveRequestRequest $request, CurrentStudentResolver $resolver): RedirectResponse
    {
        $user = $request->user();
        $student = $this->resolveStudentOrRedirect($user, $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $path = $request->file('attachment')->store('lampiran-izin', 'public');

        LeaveRequest::create([
            ...$request->validated(),
            'student_id' => $student->id,
            'guardian_id' => $user->guardian->id,
            'attachment_path' => $path,
            'status' => LeaveRequestStatus::Pending,
        ]);

        return back()->with('success', 'Pengajuan izin berhasil dikirim, menunggu persetujuan wali kelas.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('update', $leaveRequest);

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->markAttendanceAsExcused($leaveRequest);

        return back()->with('success', 'Pengajuan izin disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('update', $leaveRequest);

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan izin ditolak.');
    }

    private function markAttendanceAsExcused(LeaveRequest $leaveRequest): void
    {
        $period = CarbonPeriod::create($leaveRequest->start_date, $leaveRequest->end_date);

        foreach ($period as $date) {
            /** @var CarbonInterface $date */
            Attendance::query()->updateOrCreate(
                ['student_id' => $leaveRequest->student_id, 'date' => $date->toDateString()],
                ['status' => AttendanceStatus::Excused],
            );
        }
    }
}
