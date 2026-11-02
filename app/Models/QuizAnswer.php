<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'answer', 'is_correct', 'awarded_points', 'response_ms', 'game_points', 'graded_at'])]
class QuizAnswer extends Model
{
    protected function casts(): array
    {
        return ['answer' => 'array', 'is_correct' => 'boolean', 'awarded_points' => 'integer', 'response_ms' => 'integer', 'game_points' => 'integer', 'graded_at' => 'datetime'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    /**
     * Jawaban essay berisi yang masih menunggu dikoreksi guru. Jawaban
     * kosong tidak termasuk karena otomatis bernilai 0.
     */
    #[Scope]
    protected function awaitingEssayGrading(Builder $query): void
    {
        $query->whereNull('quiz_answers.graded_at')
            ->whereNotNull('quiz_answers.answer')
            ->whereHas('question', fn (Builder $query) => $query->where('type', QuizQuestion::TYPE_ESSAY));
    }
}
