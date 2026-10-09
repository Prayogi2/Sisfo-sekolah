<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryCategory;
use App\Services\InventoryItemListValidator;
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

            foreach (InventoryItemListValidator::duplicateErrors($this->input('items'), $this->route('classroom')) as $error) {
                $validator->errors()->add("items.{$error['index']}.name", $error['message']);
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
