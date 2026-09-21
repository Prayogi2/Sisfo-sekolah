<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('subject'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('subjects')->ignore($this->route('subject')->id)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Kode mapel wajib diisi.',
            'code.unique' => 'Kode mapel sudah dipakai.',
            'code.max' => 'Kode mapel maksimal 20 karakter.',
            'name.required' => 'Nama mapel wajib diisi.',
        ];
    }
}
