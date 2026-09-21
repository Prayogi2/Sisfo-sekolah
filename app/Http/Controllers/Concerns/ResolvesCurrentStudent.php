<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use App\Models\User;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;

/**
 * Dipakai controller yang menampilkan data seorang anak dari sesi wali
 * (siswa.* / wali.*). Kalau wali punya >1 anak dan belum memilih, minta
 * pilih dulu lewat halaman "Pilih Anak" (ChildSelectionController).
 */
trait ResolvesCurrentStudent
{
    protected function resolveStudentOrRedirect(User $user, CurrentStudentResolver $resolver): Student|RedirectResponse
    {
        $student = $resolver->resolve($user);

        if ($student) {
            return $student;
        }

        session(['url.intended' => url()->current()]);

        return redirect()->route('pilih-anak.index');
    }
}
