<?php

namespace App\Models;

use Database\Factories\FeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guardian_id', 'message'])]
class Feedback extends Model
{
    /** @use HasFactory<FeedbackFactory> */
    use HasFactory;

    /**
     * Eloquent's pluralizer treats "feedback" as uncountable; the table
     * is named "feedbacks" to match the plural naming used elsewhere.
     */
    protected $table = 'feedbacks';

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }
}
