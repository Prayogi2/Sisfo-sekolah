<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesQuizContent;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Services\QuizAttemptFinalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Guru mengoreksi jawaban essay; nilai kuis & rapor siswa dihitung ulang.
 */
class QuizEssayGradingController extends Controller
{
    use AuthorizesQuizContent;

    public function index(Request $request, Quiz $quiz): View
    {
        $this->authorizeQuiz($request, $quiz);

        $essayQuestions = $this->essayQuestions($quiz);
        $answers = QuizAnswer::query()
            ->whereIn('quiz_question_id', $essayQuestions->pluck('id'))
            ->whereHas('attempt', fn ($query) => $query->where('quiz_id', $quiz->id))
            ->with('attempt.student')
            ->get()
            ->sortBy(fn (QuizAnswer $answer) => $answer->attempt->student->name)
            ->groupBy('quiz_question_id');

        return view('guru.kuis-koreksi', [
            'quiz' => $quiz->load(['subject', 'classroom']),
            'essayQuestions' => $essayQuestions,
            'answers' => $answers,
        ]);
    }

    public function update(Request $request, Quiz $quiz, QuizAttemptFinalizer $finalizer): RedirectResponse
    {
        $this->authorizeQuiz($request, $quiz);

        $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['nullable', 'integer', 'min:0'],
        ], [
            'scores.*.integer' => 'Nilai essay harus berupa angka bulat.',
            'scores.*.min' => 'Nilai essay tidak boleh negatif.',
        ]);

        $maxPoints = $this->essayQuestions($quiz)->mapWithKeys(fn (QuizQuestion $question) => [$question->id => (int) $question->pivot->points]);
        $answers = QuizAnswer::query()
            ->whereIn('id', array_keys($request->input('scores')))
            ->whereIn('quiz_question_id', $maxPoints->keys())
            ->whereHas('attempt', fn ($query) => $query->where('quiz_id', $quiz->id))
            ->get();

        $graded = 0;
        $attemptIds = [];

        DB::transaction(function () use ($request, $answers, $maxPoints, &$graded, &$attemptIds) {
            foreach ($answers as $answer) {
                $score = $request->input("scores.{$answer->id}");
                if ($score === null || $score === '') {
                    continue;
                }

                $max = $maxPoints[$answer->quiz_question_id];
                if ((int) $score > $max) {
                    throw ValidationException::withMessages(["scores.{$answer->id}" => "Nilai essay maksimal {$max} poin."]);
                }

                $answer->update([
                    'awarded_points' => (int) $score,
                    'is_correct' => (int) $score === $max,
                    'graded_at' => now(),
                ]);
                $graded++;
                $attemptIds[] = $answer->quiz_attempt_id;
            }
        });

        QuizAttempt::query()->whereIn('id', array_unique($attemptIds))->get()
            ->each(fn (QuizAttempt $attempt) => $finalizer->recalculate($attempt));

        return back()->with('success', "{$graded} jawaban essay dinilai. Nilai kuis & rapor siswa sudah diperbarui.");
    }

    /**
     * @return Collection<int, QuizQuestion>
     */
    private function essayQuestions(Quiz $quiz): Collection
    {
        return $quiz->questions()->where('type', QuizQuestion::TYPE_ESSAY)->get();
    }
}
