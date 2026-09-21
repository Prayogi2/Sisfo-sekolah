<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Subject::class);

        $subjects = Subject::query()
            ->withCount('teachers')
            ->orderBy('name')
            ->get();

        return view('admin.data-mapel', compact('subjects'));
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
