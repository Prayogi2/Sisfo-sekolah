<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reportInventory', $this->route('classroom'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.good_quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.damaged_quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Belum ada barang inventaris untuk dilaporkan.',
            'items.*.good_quantity.required' => 'Jumlah barang kondisi baik wajib diisi (isi 0 bila tidak ada).',
            'items.*.damaged_quantity.required' => 'Jumlah barang rusak wajib diisi (isi 0 bila tidak ada).',
            'items.*.good_quantity.min' => 'Jumlah barang tidak boleh negatif.',
            'items.*.damaged_quantity.min' => 'Jumlah barang tidak boleh negatif.',
            'photos.*.image' => 'Foto kondisi harus berupa gambar.',
            'photos.*.max' => 'Ukuran foto kondisi maksimal 5 MB.',
        ];
    }
}
