<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'classroom_id', 'created_by', 'title', 'description', 'duration_minutes', 'starts_at', 'ends_at', 'is_published'])]
class Quiz extends Model
{
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_published' => 'boolean', 'duration_minutes' => 'integer'];
    }

    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function questions(): BelongsToMany { return $this->belongsToMany(QuizQuestion::class, 'quiz_quiz_question')->withPivot(['sort_order', 'points'])->orderByPivot('sort_order'); }
    public function attempts(): HasMany { return $this->hasMany(QuizAttempt::class); }

    public function isAvailable(): bool
    {
        return $this->is_published && (! $this->starts_at || now()->gte($this->starts_at)) && (! $this->ends_at || now()->lte($this->ends_at));
    }
}
