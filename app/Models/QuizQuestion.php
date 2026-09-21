<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['subject_id', 'created_by', 'type', 'question', 'options', 'correct_answer', 'explanation', 'points', 'is_active'])]
class QuizQuestion extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['options' => 'array', 'points' => 'integer', 'is_active' => 'boolean'];
    }

    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function quizzes(): BelongsToMany { return $this->belongsToMany(Quiz::class, 'quiz_quiz_question')->withPivot(['sort_order', 'points']); }
}
