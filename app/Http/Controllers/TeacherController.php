<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\UserPasswordResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::query()
            ->with(['homeroomClassrooms', 'subjects', 'teachingAssignments.subject', 'teachingAssignments.classroom'])
            ->orderBy('name')
            ->get();

        $subjects = Subject::orderBy('name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        $view = auth()->user()->hasRole('admin') ? 'admin.data-guru' : 'guru.data-guru';

        return view($view, compact('teachers', 'subjects', 'classrooms'));
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $assignments = $validated['assignments'] ?? [];
        unset($validated['assignments']);

        $password = Str::password(12);

        $user = DB::transaction(function () use ($validated, $assignments, $password) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? "{$validated['nip']}@guru.local",
                'password' => $password,
                'role' => 'guru',
            ]);

            $teacher = Teacher::create($validated + ['user_id' => $user->id]);
            $teacher->teachingAssignments()->createMany($this->flattenAssignments($assignments));

            return $user;
        });

        return back()->with(
            'success',
            "Guru berhasil ditambahkan. Login: {$user->email}, Password: {$password}"
        );
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validated();
        $assignments = $validated['assignments'] ?? [];
        unset($validated['assignments']);

        DB::transaction(function () use ($teacher, $validated, $assignments) {
            $teacher->update($validated);
            $teacher->teachingAssignments()->delete();
            $teacher->teachingAssignments()->createMany($this->flattenAssignments($assignments));
        });

        return back()->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $teacher->delete();

        return back()->with('success', 'Data guru berhasil dihapus.');
    }

    public function resetPassword(Teacher $teacher, UserPasswordResetter $resetter): RedirectResponse
    {
        Gate::authorize('update', $teacher);

        if (! $teacher->user) {
            return back()->with('error', 'Guru ini belum memiliki akun login.');
        }

        $newPassword = $resetter->reset($teacher->user);

        return back()->with('success', "Password baru untuk {$teacher->name}: {$newPassword}");
    }

    /**
     * Ratakan input form "satu baris per mapel, banyak kelas" menjadi satu
     * baris teaching_assignments per pasangan mapel+kelas.
     *
     * @param  array<int, array{subject_id: int, classroom_ids: array<int, int>}>  $assignments
     * @return array<int, array{subject_id: int, classroom_id: int}>
     */
    private function flattenAssignments(array $assignments): array
    {
        return collect($assignments)
            ->flatMap(fn (array $assignment) => collect($assignment['classroom_ids'])
                ->map(fn ($classroomId) => [
                    'subject_id' => $assignment['subject_id'],
                    'classroom_id' => $classroomId,
                ]))
            ->all();
    }
}
