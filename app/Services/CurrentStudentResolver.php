<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Menentukan anak (Student) mana yang sedang dilihat oleh akun Wali Murid
 * yang sedang login, karena satu wali bisa terhubung ke lebih dari satu anak.
 */
class CurrentStudentResolver
{
    private const SESSION_KEY = 'current_student_id';

    /**
     * Semua anak yang terhubung ke akun wali ini.
     *
     * @return Collection<int, Student>
     */
    public function choices(User $user): Collection
    {
        return $user->guardian?->students()->with('classroom')->get()
            ?? new Collection;
    }

    /**
     * Anak yang sedang aktif dipilih. Null berarti wali punya lebih dari
     * satu anak dan belum memilih salah satunya (caller harus tampilkan
     * selector "Pilih Anak").
     */
    public function resolve(User $user): ?Student
    {
        $choices = $this->choices($user);

        if ($choices->isEmpty()) {
            return null;
        }

        if ($choices->count() === 1) {
            return $choices->first();
        }

        $selectedId = Session::get(self::SESSION_KEY);

        return $choices->firstWhere('id', $selectedId);
    }

    /**
     * Pilih salah satu anak sebagai anak yang sedang aktif dilihat.
     *
     * @throws HttpException (403) jika student tersebut bukan anak dari wali yang sedang login.
     */
    public function select(User $user, int $studentId): Student
    {
        $student = $this->choices($user)->firstWhere('id', $studentId);

        abort_unless($student !== null, 403);

        Session::put(self::SESSION_KEY, $studentId);

        return $student;
    }
}
