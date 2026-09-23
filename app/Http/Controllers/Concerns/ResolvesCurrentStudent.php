<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use App\Models\User;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;

/**
 * Dipakai controller halaman siswa.*. Kalau akun siswa belum terhubung ke
 * data siswa, arahkan kembali ke dashboard yang menampilkan pesannya.
 */
trait ResolvesCurrentStudent
{
    protected function resolveStudentOrRedirect(User $user, CurrentStudentResolver $resolver): Student|RedirectResponse
    {
        $student = $resolver->resolve($user);

        if ($student) {
            return $student;
        }

        return redirect()->route('siswa.dashboard');
    }
}
