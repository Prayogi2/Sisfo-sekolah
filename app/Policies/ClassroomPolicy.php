<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Mengatur (assign/unassign) siswa ke dalam kelas ini.
     */
    public function manageStudents(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin');
    }
}
