<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('addInventoryItem', $this->route('classroom'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(InventoryCategory::class)],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('inventory_items')
                    ->where('classroom_id', $this->route('classroom')->id)
                    ->where('category', $this->input('category')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori barang wajib dipilih.',
            'name.required' => 'Nama barang wajib diisi.',
            'name.unique' => 'Barang dengan nama ini sudah ada di kategori tersebut.',
        ];
    }
}
