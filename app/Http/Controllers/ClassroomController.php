<?php

namespace App\Http\Controllers;

use App\Http\Requests\Classroom\AssignStudentsRequest;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::query()
            ->with(['homeroomTeacher', 'students' => fn ($query) => $query->orderBy('name')])
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $teachers = Teacher::where('is_active', true)->orderBy('name')->get();
        $unassignedStudents = Student::whereNull('classroom_id')->orderBy('name')->get();

        return view('admin.data-kelas', compact('classrooms', 'teachers', 'unassignedStudents'));
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        Classroom::create($request->validated() + [
            'academic_year' => Classroom::currentAcademicYear(),
        ]);

        return back()->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * "Atur Siswa" — tentukan ulang seluruh anggota kelas ini.
     */
    public function assignStudents(AssignStudentsRequest $request, Classroom $classroom): RedirectResponse
    {
        $studentIds = $request->validated('student_ids', []);

        Student::where('classroom_id', $classroom->id)
            ->whereNotIn('id', $studentIds)
            ->update(['classroom_id' => null]);

        Student::whereIn('id', $studentIds)->update(['classroom_id' => $classroom->id]);

        return back()->with('success', 'Anggota kelas berhasil diperbarui.');
    }
}
