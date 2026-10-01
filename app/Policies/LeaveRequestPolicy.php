<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\User;

class LeaveRequestPolicy
{
    /**
     * Admin melihat semua pengajuan; guru (wali kelas) hanya melihat
     * pengajuan siswa di kelasnya sendiri (difilter di controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('guru');
    }

    /**
     * Approve/reject hanya oleh wali kelas siswa tersebut. Admin cukup
     * memantau di halaman admin (atau masuk ke akun wali kelasnya).
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $user->hasRole('guru')) {
            return false;
        }

        $classroomId = Student::whereKey($leaveRequest->student_id)->value('classroom_id');

        return Classroom::query()
            ->whereKey($classroomId)
            ->whereHas('homeroomTeacher', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
