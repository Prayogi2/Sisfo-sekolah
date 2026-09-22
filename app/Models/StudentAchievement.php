<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'recorded_by', 'category', 'title', 'event', 'level', 'achievement', 'benefit', 'description', 'achieved_at', 'evidence_path'])]
class StudentAchievement extends Model
{
    protected function casts(): array
    {
        return ['achieved_at' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
