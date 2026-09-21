<?php

namespace App\Http\Requests\Classroom;

use App\Models\Classroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Classroom::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('classrooms')->where('academic_year', Classroom::currentAcademicYear()),
            ],
            'grade_level' => ['required', 'integer', 'min:1', 'max:6'],
            'homeroom_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'capacity' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique' => 'Nama kelas sudah dipakai pada tahun ajaran ini.',
            'grade_level.required' => 'Tingkat kelas wajib diisi.',
            'homeroom_teacher_id.exists' => 'Wali kelas yang dipilih tidak valid.',
            'capacity.required' => 'Kapasitas siswa wajib diisi.',
        ];
    }
}
