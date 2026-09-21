<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Student::class);

        $students = Student::query()
            ->with('classroom')
            ->when(request('search'), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            })
            ->when(request('classroom_id'), fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $classrooms = Classroom::orderBy('name')->get();

        return view('admin.data-siswa', compact('students', 'classrooms'));
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        Student::create($request->validated());

        return back()->with('success', 'Siswa baru berhasil ditambahkan.');
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }
}
