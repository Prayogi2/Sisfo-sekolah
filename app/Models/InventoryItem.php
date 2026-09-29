<?php

namespace App\Models;

use App\Enums\InventoryCategory;
use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu barang inventaris kelas beserta kondisi terakhir yang dilaporkan
 * wali kelas.
 */
#[Fillable([
    'classroom_id', 'category', 'name', 'sort_order', 'good_quantity', 'damaged_quantity',
    'photo_path', 'notes', 'last_reported_at', 'created_by',
])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

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
            'last_reported_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function totalQuantity(): int
    {
        return $this->good_quantity + $this->damaged_quantity;
    }
}
