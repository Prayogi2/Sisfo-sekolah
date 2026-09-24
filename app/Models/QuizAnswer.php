<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quiz_attempt_id', 'quiz_question_id', 'answer', 'is_correct', 'awarded_points'])]
class QuizAnswer extends Model
{
    protected function casts(): array
    {
        return ['answer' => 'array', 'is_correct' => 'boolean', 'awarded_points' => 'integer'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
