<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Teacher;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'guru']);
    }

    /**
     * Guru hanya boleh mengisi nilai mapel yang dia ampu.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'guru']);
    }

    public function update(User $user, Grade $grade): bool
    {
        return $this->canGradeSubject($user, $grade->subject_id, $grade->student->classroom_id);
    }

    /**
     * Guru hanya boleh mengisi nilai mapel & kelas yang benar-benar ia
     * ajarkan (lihat teaching_assignments). $classroomId null berarti
     * siswanya belum punya kelas, sehingga tidak bisa cocok dengan
     * penugasan mana pun.
     */
    public function canGradeSubject(User $user, int $subjectId, ?int $classroomId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('guru')) {
            return false;
        }

        $teacher = Teacher::where('user_id', $user->id)->first();

        if ($teacher === null) {
            return true;
        }

        return $classroomId !== null && $teacher->teaches($subjectId, $classroomId);
    }
}
