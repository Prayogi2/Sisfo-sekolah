<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * Admin boleh semua; guru hanya mapel yang ia ajarkan dan soal buatannya.
 */
trait AuthorizesQuizContent
{
    protected function authorizeSubject(Request $request, int $subjectId): void
    {
        abort_unless(
            $request->user()->hasRole('admin')
            || Subject::whereKey($subjectId)->whereHas('teachers', fn ($query) => $query->where('user_id', $request->user()->id))->exists(),
            403
        );
    }

    protected function authorizeQuestion(Request $request, QuizQuestion $question): void
    {
        abort_unless($request->user()->hasRole('admin') || $question->created_by === $request->user()->id, 403);
    }

    protected function authorizeQuiz(Request $request, Quiz $quiz): void
    {
        abort_unless($request->user()->hasRole('admin') || $quiz->created_by === $request->user()->id, 403);
    }
}
