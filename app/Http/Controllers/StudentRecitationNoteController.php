<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRecitation\StoreStudentRecitationNoteRequest;
use App\Models\Classroom;
use App\Models\StudentRecitationNote;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentRecitationNoteController extends Controller
{
    public function index(Request $request): View
    {
        $isAdmin = $request->user()->hasRole('admin');
        $teacher = $isAdmin ? null : Teacher::query()->where('user_id', $request->user()->id)->first();
        $teachingClassroomIds = $teacher?->teachingAssignments()->pluck('classroom_id')->all() ?? [];
        $classrooms = Classroom::query()
            ->with('homeroomTeacher')
            ->when(! $isAdmin, fn ($query) => $query->where(function ($query) use ($request, $teachingClassroomIds) {
                $query->whereIn('id', $teachingClassroomIds)
                    ->orWhereHas('homeroomTeacher', fn ($query) => $query->where('user_id', $request->user()->id));
            }))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
        $classroom = $classrooms->firstWhere('id', $request->integer('classroom')) ?? $classrooms->first();
        $classroomIds = $classrooms->modelKeys();
        $students = $classroom?->students()->orderBy('name')->get() ?? collect();
        $notes = StudentRecitationNote::query()
            ->with(['student.classroom', 'recorder'])
            ->when(! $isAdmin, fn ($query) => $query->whereHas('student', fn ($query) => $query->whereIn('classroom_id', $classroomIds)))
            ->when($classroom, fn ($query) => $query->whereHas('student', fn ($query) => $query->where('classroom_id', $classroom->id)))
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('guru.catatan-hafalan-bacaan', compact('classrooms', 'classroom', 'students', 'notes'));
    }

    public function store(StoreStudentRecitationNoteRequest $request, Classroom $classroom): RedirectResponse
    {
        StudentRecitationNote::create([
            ...$request->validated(),
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('guru.catatan-hafalan-bacaan', ['classroom' => $classroom->id])
            ->with('success', 'Catatan hafalan atau bacaan berhasil disimpan.');
    }
}
