<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'classroom_id', 'created_by', 'title', 'description', 'duration_minutes', 'mode', 'show_score_per_question', 'starts_at', 'ends_at', 'is_published', 'is_open', 'opened_at', 'live_phase', 'live_question_index', 'live_question_started_at'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'opened_at' => 'datetime', 'live_question_started_at' => 'datetime', 'is_published' => 'boolean', 'is_open' => 'boolean', 'show_score_per_question' => 'boolean', 'duration_minutes' => 'integer', 'live_question_index' => 'integer'];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuizQuestion::class, 'quiz_quiz_question')->withPivot(['sort_order', 'points'])->orderByPivot('sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Kuis siap dikerjakan: sudah dipublikasikan, sedang dibuka guru mapel,
     * dan masih di dalam rentang jadwal (kalau jadwalnya diisi).
     */
    public function isAvailable(): bool
    {
        return $this->is_published && $this->is_open && $this->isWithinSchedule();
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    public function isWithinSchedule(): bool
    {
        return (! $this->starts_at || now()->gte($this->starts_at)) && (! $this->ends_at || now()->lte($this->ends_at));
    }
}
