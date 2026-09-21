<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    /**
     * Admin mengelola data guru; guru bisa melihat daftar rekan guru
     * (halaman guru.data-guru) tapi tidak bisa mengubahnya.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'guru']);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->hasRole(['admin', 'guru']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin');
    }
}
