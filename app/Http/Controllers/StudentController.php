<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Services\StudentEnroller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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

    public function store(StoreStudentRequest $request, StudentEnroller $enroller): RedirectResponse
    {
        $student = $enroller->enroll($request->validated(), $request->file('photo'));

        return redirect()->route('admin.data-siswa')
            ->with('success', "Siswa berhasil ditambahkan. Login: {$student->name} / password123. Data Buku Induk lainnya bisa dilengkapi menyusul.");
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $student->update($request->validated());
        $student->user?->update(['name' => $student->name]);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    public function qrCard(Student $student): View
    {
        Gate::authorize('view', $student);

        if (! $student->qr_token) {
            $student->forceFill(['qr_token' => Str::random(40)])->save();
        }

        $student->load('classroom');

        return view('admin.kartu-siswa', compact('student'));
    }
}
