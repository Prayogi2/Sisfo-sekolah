<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kondisi satu barang di dalam satu laporan. Nama & kategori disalin dari
 * barangnya supaya riwayat tetap terbaca walau barang sudah dihapus.
 */
#[Fillable([
    'inventory_report_id', 'inventory_item_id', 'category', 'name',
    'good_quantity', 'damaged_quantity', 'photo_path', 'notes',
])]
class InventoryReportItem extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => InventoryCategory::class,
            'good_quantity' => 'integer',
            'damaged_quantity' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(InventoryReport::class, 'inventory_report_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
