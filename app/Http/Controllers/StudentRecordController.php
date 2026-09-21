<?php

namespace App\Http\Controllers;

use App\Enums\GuardianRelationship;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentRecordController extends Controller
{
    /**
     * Buku induk: rekam jejak lengkap satu peserta didik yang dipilih admin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $search = $request->input('search');

        $students = Student::query()
            ->with('classroom')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $student = $students->firstWhere('id', $request->integer('student')) ?? $students->first();

        $student?->load(['profile', 'academicRecord', 'guardians']);

        $guardians = $student?->guardians ?? collect();

        return view('admin.buku-induk', [
            'students' => $students,
            'student' => $student,
            'search' => $search,
            'father' => $guardians->firstWhere('relationship', GuardianRelationship::Father),
            'mother' => $guardians->firstWhere('relationship', GuardianRelationship::Mother),
            'legalGuardian' => $guardians->firstWhere('relationship', GuardianRelationship::Guardian),
        ]);
    }
}
