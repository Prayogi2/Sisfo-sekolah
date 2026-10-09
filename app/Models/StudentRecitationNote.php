<?php

namespace App\Models;

use Database\Factories\StudentRecitationNoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'recorded_by', 'type', 'material', 'achievement', 'recorded_at', 'notes'])]
class StudentRecitationNote extends Model
{
    /** @use HasFactory<StudentRecitationNoteFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['recorded_at' => 'date'];
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
