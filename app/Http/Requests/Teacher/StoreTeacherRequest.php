<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Gender;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Teacher::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nip' => ['required', 'string', 'max:30', 'unique:teachers,nip'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', 'unique:teachers,email'],
            'address' => ['nullable', 'string'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nip.required' => 'NIP/NUPTK wajib diisi.',
            'nip.unique' => 'NIP/NUPTK sudah terdaftar.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.unique' => 'Email sudah dipakai guru lain.',
        ];
    }
}
