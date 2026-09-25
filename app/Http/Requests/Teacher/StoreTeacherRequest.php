<?php

namespace App\Http\Requests\Teacher;

use App\Enums\Gender;
use App\Http\Requests\Teacher\Concerns\ValidatesTeacherProfile;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTeacherRequest extends FormRequest
{
    use ValidatesTeacherProfile;

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
            ...$this->profileRules(),
            'assignments' => ['array'],
            'assignments.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'assignments.*.classroom_ids' => ['required', 'array', 'min:1'],
            'assignments.*.classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return $this->profileMessages() + [
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
