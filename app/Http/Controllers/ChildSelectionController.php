<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\CurrentStudentResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Saat wali punya lebih dari satu anak, ini yang menampilkan & memproses
 * pilihan "anak yang sedang dilihat" sebelum halaman siswa.* bisa dibuka.
 */
class ChildSelectionController extends Controller
{
    public function index(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $children = $resolver->choices($request->user());

        if ($children->count() <= 1) {
            return redirect()->intended(route('siswa.dashboard'));
        }

        return view('siswa.pilih-anak', compact('children'));
    }

    public function select(Request $request, Student $student, CurrentStudentResolver $resolver): RedirectResponse
    {
        $resolver->select($request->user(), $student->id);

        return redirect()->intended(route('siswa.dashboard'));
    }
}
