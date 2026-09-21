<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['quiz_id', 'student_id', 'status', 'started_at', 'submitted_at', 'score', 'correct_answers', 'total_questions'])]
class QuizAttempt extends Model
{
    protected function casts(): array { return ['started_at' => 'datetime', 'submitted_at' => 'datetime', 'score' => 'decimal:2']; }
    public function quiz(): BelongsTo { return $this->belongsTo(Quiz::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function answers(): HasMany { return $this->hasMany(QuizAnswer::class); }
}
