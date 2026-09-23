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
     * Approve/reject: admin bisa untuk semua, guru hanya untuk siswa
     * di kelas yang dia jadi wali kelasnya.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

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
