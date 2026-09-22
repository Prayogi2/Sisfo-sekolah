<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'assignments' => ['array'],
            'assignments.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'assignments.*.classroom_ids' => ['required', 'array', 'min:1'],
            'assignments.*.classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nip.required' => 'NIP/NUPTK wajib diisi.',
            'nip.unique' => 'NIP/NUPTK sudah terdaftar.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.unique' => 'Email sudah dipakai guru lain.',
            'assignments.*.subject_id.required' => 'Mata pelajaran wajib dipilih untuk setiap baris penugasan.',
            'assignments.*.classroom_ids.required' => 'Pilih minimal satu kelas untuk setiap mata pelajaran yang diajarkan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $subjectIds = collect($this->input('assignments', []))->pluck('subject_id')->filter();

            if ($subjectIds->count() !== $subjectIds->unique()->count()) {
                $validator->errors()->add('assignments', 'Setiap mata pelajaran hanya boleh dipilih sekali. Gabungkan kelasnya dalam satu baris.');
            }
        });
    }
}
