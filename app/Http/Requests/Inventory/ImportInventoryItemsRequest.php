<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ImportInventoryItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('addInventoryItem', $this->route('classroom')) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file Excel daftar inventaris.',
            'file.mimes' => 'Daftar inventaris harus berupa file .xlsx.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ];
    }
}
