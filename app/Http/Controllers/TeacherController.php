<?php

namespace App\Http\Controllers;

use App\Http\Requests\Teacher\StoreTeacherRequest;
use App\Http\Requests\Teacher\UpdateTeacherRequest;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\UserPasswordResetter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Teacher::class);

        $teachers = Teacher::query()
            ->with(['homeroomClassrooms', 'subjects'])
            ->orderBy('name')
            ->get();

        $subjects = Subject::orderBy('name')->get();

        $view = auth()->user()->hasRole('admin') ? 'admin.data-guru' : 'guru.data-guru';

        return view($view, compact('teachers', 'subjects'));
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $password = Str::password(12);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? "{$validated['nip']}@guru.local",
            'password' => $password,
            'role' => 'guru',
        ]);

        $teacher = Teacher::create($validated + ['user_id' => $user->id]);
        $teacher->subjects()->sync($subjectIds);

        return back()->with(
            'success',
            "Guru berhasil ditambahkan. Username login: {$teacher->nip}, Password: {$password}"
        );
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $validated = $request->validated();
        $subjectIds = $validated['subject_ids'] ?? [];
        unset($validated['subject_ids']);

        $teacher->update($validated);
        $teacher->subjects()->sync($subjectIds);

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
}
