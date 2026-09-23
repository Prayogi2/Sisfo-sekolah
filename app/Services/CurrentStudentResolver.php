<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;

/**
 * Menentukan data siswa (Student) milik akun siswa yang sedang login.
 */
class CurrentStudentResolver
{
    /**
     * Null berarti akun ini belum terhubung ke data siswa mana pun.
     */
    public function resolve(User $user): ?Student
    {
        if (! $user->hasRole('siswa')) {
            return null;
        }

        return $user->student?->load('classroom');
    }
}
