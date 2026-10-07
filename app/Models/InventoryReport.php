<?php

namespace App\Models;

use Database\Factories\InventoryReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Satu kali kiriman laporan kondisi inventaris kelas oleh wali kelas.
 */
#[Fillable(['classroom_id', 'reported_by', 'notes'])]
class InventoryReport extends Model
{
    /** @use HasFactory<InventoryReportFactory> */
    use HasFactory;

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryReportItem::class);
    }

    /**
     * Nama pelapor hanya ditampilkan ke admin. Guru cuma perlu tahu apakah
     * laporan itu kiriman dirinya sendiri — identitas rekan guru lain bukan
     * haknya untuk dilihat.
     */
    public function reporterLabelFor(?User $viewer): string
    {
        if ($viewer?->hasRole('admin')) {
            return $this->reporter?->name ?? '-';
        }

        return $this->reported_by !== null && $this->reported_by === $viewer?->id
            ? 'Anda'
            : 'Petugas lain';
    }
}
