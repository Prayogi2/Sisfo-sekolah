<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectTeachersRequest;
use App\Models\Classroom;
use App\Models\GradeWeight;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Subject::class);

        $subjects = Subject::query()
            ->with(['teachingAssignments.teacher', 'teachingAssignments.classroom'])
            ->orderBy('name')
            ->get();
        // Semua guru (termasuk nonaktif) supaya penugasan lama tidak hilang saat disimpan ulang.
        $teachers = Teacher::query()->orderByDesc('is_active')->orderBy('name')->get();
        $classrooms = Classroom::query()->orderBy('grade_level')->orderBy('name')->get();

        $academicYear = Classroom::currentAcademicYear();
        $semester = Semester::current();

        $weights = GradeWeight::query()
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->get()
            ->keyBy('subject_id');

        return view('admin.data-mapel', compact('subjects', 'weights', 'academicYear', 'semester', 'teachers', 'classrooms'));
    }

    /**
     * Atur ulang seluruh guru pengampu mapel ini (guru + kelas yang diajar).
     */
    public function updateTeachers(UpdateSubjectTeachersRequest $request, Subject $subject): RedirectResponse
    {
        $rows = collect($request->validated('assignments', []))
            ->flatMap(fn (array $assignment) => collect($assignment['classroom_ids'])
                ->map(fn ($classroomId) => ['teacher_id' => (int) $assignment['teacher_id'], 'classroom_id' => (int) $classroomId]))
            ->unique(fn (array $row) => $row['teacher_id'].'-'.$row['classroom_id'])
            ->values()
            ->all();

        DB::transaction(function () use ($subject, $rows) {
            $subject->teachingAssignments()->delete();
            $subject->teachingAssignments()->createMany($rows);
        });

        return back()->with('success', "Guru pengampu {$subject->name} berhasil disimpan.");
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        Subject::create($request->validated());

        return back()->with('success', 'Mata pelajaran baru berhasil ditambahkan.');
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $subject->update($request->validated());

        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        Gate::authorize('delete', $subject);

        $subject->delete();

        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }
}
