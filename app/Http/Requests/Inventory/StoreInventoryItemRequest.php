<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Tambah satu atau beberapa barang inventaris sekaligus ke satu kelas.
 */
class StoreInventoryItemRequest extends FormRequest
{
    public const MAX_ITEMS = 50;

    public function authorize(): bool
    {
        return $this->user()->can('addInventoryItem', $this->route('classroom'));
    }

    /**
     * Baris yang nama barangnya dikosongkan dianggap tidak dipakai.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(fn ($item) => [
                'category' => $item['category'] ?? null,
                'name' => trim((string) ($item['name'] ?? '')),
                'quantity' => $item['quantity'] ?? null,
            ])
            ->filter(fn (array $item) => $item['name'] !== '')
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:'.self::MAX_ITEMS],
            'items.*.category' => ['required', Rule::enum(InventoryCategory::class)],
            'items.*.name' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * Nama barang tidak boleh kembar dalam satu kategori, baik sesama baris
     * yang dikirim maupun dengan barang yang sudah ada di kelas.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $key = fn (string $category, string $name) => $category.'|'.mb_strtolower($name);
            $existing = $this->route('classroom')->inventoryItems()->get(['category', 'name'])
                ->map(fn ($item) => $key($item->category->value, $item->name));
            $seen = [];

            foreach ($this->input('items') as $index => $item) {
                $itemKey = $key($item['category'], $item['name']);

                if ($existing->contains($itemKey) || in_array($itemKey, $seen, true)) {
                    $validator->errors()->add("items.{$index}.name", "Barang \"{$item['name']}\" sudah ada di kategori ".InventoryCategory::from($item['category'])->label().'.');
                }
                $seen[] = $itemKey;
            }
        }];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Isi minimal satu nama barang.',
            'items.max' => 'Maksimal '.self::MAX_ITEMS.' barang sekali simpan.',
            'items.*.category.required' => 'Kategori barang wajib dipilih.',
            'items.*.name.max' => 'Nama barang maksimal 100 karakter.',
            'items.*.quantity.required' => 'Jumlah barang wajib diisi.',
            'items.*.quantity.integer' => 'Jumlah barang harus berupa angka bulat.',
            'items.*.quantity.min' => 'Jumlah barang tidak boleh negatif.',
            'items.*.quantity.max' => 'Jumlah barang maksimal 9999.',
        ];
    }
}
