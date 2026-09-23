<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Status baca satu notifikasi untuk satu siswa.
 */
#[Fillable(['announcement_id', 'student_id', 'read_at'])]
class AnnouncementRecipient extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Notifikasi milik siswa ini yang sudah waktunya tampil.
     */
    #[Scope]
    protected function visibleTo(Builder $query, Student $student): void
    {
        $query->where('student_id', $student->id)
            ->whereHas('announcement', fn (Builder $query) => $query->published());
    }

    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->orderByDesc(
            Announcement::select('published_at')->whereColumn('announcements.id', 'announcement_recipients.announcement_id')
        )->orderByDesc('id');
    }

    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
