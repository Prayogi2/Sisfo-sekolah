<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    /**
     * Daftar seluruh guru hanya untuk admin. Guru tidak boleh melihat data
     * atau akun rekannya — halaman "Data Saya" miliknya hanya berisi
     * datanya sendiri, yang diizinkan lewat view() di bawah.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin') || $teacher->user_id === $user->id;
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

    /**
     * Memberi atau mencabut akses admin untuk akun guru.
     */
    public function manageAdminAccess(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('admin');
    }
}
