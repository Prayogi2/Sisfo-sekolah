<?php

namespace App\Models;

use App\Enums\AnnouncementCategory;
use App\Enums\AnnouncementTarget;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Notifikasi in-app yang dikirim admin ke siswa.
 */
#[Fillable(['created_by', 'title', 'message', 'category', 'target', 'classroom_id', 'published_at'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AnnouncementCategory::class,
            'target' => AnnouncementTarget::class,
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    /**
     * Hanya notifikasi yang waktu kirimnya sudah tiba. Notifikasi terjadwal
     * tetap tersimpan, tapi baru terlihat siswa setelah published_at lewat.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('published_at', '<=', now());
    }

    public function isScheduled(): bool
    {
        return $this->published_at->isFuture();
    }
}
