<?php

namespace App\Http\Controllers;

use App\Http\Requests\Grade\UpdateGradeWeightRequest;
use App\Models\GradeWeight;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;

class GradeWeightController extends Controller
{
    public function update(UpdateGradeWeightRequest $request, Subject $subject): RedirectResponse
    {
        $validated = $request->validated();

        GradeWeight::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
            ],
            [
                'assignment_weight' => $validated['assignment_weight'],
                'quiz_weight' => $validated['quiz_weight'],
                'midterm_weight' => $validated['midterm_weight'],
                'final_weight' => $validated['final_weight'],
            ],
        );

        return back()->with('success', "Bobot nilai {$subject->name} berhasil diperbarui.");
    }
}
