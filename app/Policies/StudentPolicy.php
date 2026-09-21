<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Hanya admin yang punya route ke data induk siswa saat ini.
     * Akses baca untuk guru (nilai/kuis/absensi) menyusul di fase
     * yang benar-benar butuh & menyediakan route-nya.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole('admin');
    }
}
