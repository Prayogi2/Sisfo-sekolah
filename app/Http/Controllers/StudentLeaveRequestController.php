<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Http\Requests\LeaveRequest\StoreStudentLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Siswa / orang tua lewat akun siswa mengajukan izin atau sakit; pengajuan
 * diproses wali kelas di menu Approval Izin & Sakit.
 */
class StudentLeaveRequestController extends Controller
{
    use ResolvesCurrentStudent;

    public function index(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        return view('siswa.izin', [
            'student' => $student->load(['guardians', 'classroom.homeroomTeacher']),
            'leaveRequests' => LeaveRequest::query()->with('reviewer')->where('student_id', $student->id)->latest()->get(),
            'types' => LeaveType::cases(),
        ]);
    }

    public function store(StoreStudentLeaveRequest $request, CurrentStudentResolver $resolver): RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $guardianId = $request->integer('guardian_id') ?: null;
        if ($guardianId && ! $student->guardians()->whereKey($guardianId)->exists()) {
            throw ValidationException::withMessages(['guardian_id' => 'Orang tua/wali yang dipilih tidak terdaftar untuk siswa ini.']);
        }

        LeaveRequest::create([
            'student_id' => $student->id,
            'guardian_id' => $guardianId,
            'applicant_name' => $guardianId ? null : $request->string('applicant_name')->trim()->toString(),
            'type' => $request->enum('type', LeaveType::class),
            'start_date' => $request->date('start_date'),
            'end_date' => $request->date('end_date'),
            'reason' => $request->string('reason')->trim()->toString(),
            'attachment_path' => $request->file('attachment')->store('lampiran-izin', 'public'),
            'status' => LeaveRequestStatus::Pending,
        ]);

        return redirect()->route('siswa.izin')->with('success', 'Pengajuan izin terkirim dan menunggu persetujuan wali kelas.');
    }
}
