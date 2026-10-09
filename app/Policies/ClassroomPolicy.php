<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\Teacher;
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

    /**
     * Melihat inventaris kelas: admin untuk semua kelas, guru hanya untuk
     * kelas yang dia jadi wali kelasnya.
     */
    public function viewInventory(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin') || $this->isHomeroomTeacher($user, $classroom);
    }

    /**
     * Menambah barang inventaris: admin dan wali kelas yang bersangkutan.
     */
    public function addInventoryItem(User $user, Classroom $classroom): bool
    {
        return $this->viewInventory($user, $classroom);
    }

    /**
     * Menghapus barang inventaris hanya boleh admin.
     */
    public function manageInventory(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Mengirim laporan kondisi inventaris hanya wali kelasnya.
     */
    public function reportInventory(User $user, Classroom $classroom): bool
    {
        return $this->isHomeroomTeacher($user, $classroom);
    }

    public function manageRecitationNotes(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin')
            || $this->isHomeroomTeacher($user, $classroom)
            || ($user->hasRole('guru') && Teacher::query()
                ->where('user_id', $user->id)
                ->whereHas('teachingAssignments', fn ($query) => $query->where('classroom_id', $classroom->id))
                ->exists());
    }

    /**
     * Melihat & mengoreksi absensi harian kelas: hanya wali kelasnya.
     */
    public function manageAttendance(User $user, Classroom $classroom): bool
    {
        return $this->isHomeroomTeacher($user, $classroom);
    }

    private function isHomeroomTeacher(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('guru')
            && $classroom->homeroomTeacher()->where('user_id', $user->id)->exists();
    }
}
