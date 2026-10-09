<?php

namespace App\Services;

use App\Enums\InventoryCategory;
use App\Models\Classroom;

class InventoryItemListValidator
{
    /**
     * @param  array<int, array{category: string, name: string, quantity?: int|string}>  $items
     * @return list<array{index: int, message: string}>
     */
    public static function duplicateErrors(array $items, Classroom $classroom): array
    {
        $key = fn (string $category, string $name): string => $category.'|'.mb_strtolower($name);
        $existing = $classroom->inventoryItems()->get(['category', 'name'])
            ->map(fn ($item) => $key($item->category->value, $item->name));
        $seen = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $itemKey = $key($item['category'], $item['name']);

            if ($existing->contains($itemKey) || in_array($itemKey, $seen, true)) {
                $category = InventoryCategory::from($item['category']);
                $errors[] = [
                    'index' => $index,
                    'message' => "Barang \"{$item['name']}\" sudah ada di kategori ".$category->label().'.',
                ];
            }

            $seen[] = $itemKey;
        }

        return $errors;
    }
}
