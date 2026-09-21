<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\Subject;
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
        return $this->canGradeSubject($user, $grade->subject_id);
    }

    public function canGradeSubject(User $user, int $subjectId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('guru')) {
            return false;
        }

        return Subject::query()
            ->whereKey($subjectId)
            ->whereHas('teachers', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
