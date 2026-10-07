<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris jejak aktivitas: siapa melakukan apa, kapan, dari mana.
 * Dicatat otomatis oleh middleware LogActivity untuk setiap aksi yang
 * mengubah data, jadi baris di sini tidak pernah diubah/dihapus lewat UI.
 */
#[Fillable([
    'user_id', 'user_name', 'user_role', 'impersonator_id',
    'action', 'description',
    'subject_type', 'subject_id', 'subject_label',
    'method', 'url', 'status_code', 'ip_address', 'user_agent',
])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    /**
     * Aksi yang ditolak (403/401) atau gagal di server (5xx) ditandai
     * supaya mudah dipisahkan dari aktivitas normal.
     */
    public function isRejected(): bool
    {
        return in_array($this->status_code, [401, 403, 419], true);
    }

    public function isFailed(): bool
    {
        return $this->status_code !== null && $this->status_code >= 500;
    }

    /**
     * Aksi yang ditolak validasi: pengguna menekan simpan, tapi datanya
     * tidak tersimpan karena isiannya belum benar.
     */
    public function isInvalid(): bool
    {
        return $this->status_code === 422;
    }

    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->latest('created_at')->latest('id');
    }

    /**
     * Pencarian bebas atas pelaku, deskripsi, objek, dan alamat IP.
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            foreach (['user_name', 'description', 'subject_label', 'ip_address', 'action'] as $column) {
                $query->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
