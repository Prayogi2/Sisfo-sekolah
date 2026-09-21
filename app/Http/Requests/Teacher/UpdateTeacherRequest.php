<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('teacher'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'nip' => ['required', 'string', 'max:30', Rule::unique('teachers', 'nip')->ignore($teacher)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('teachers', 'email')->ignore($teacher)],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
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
