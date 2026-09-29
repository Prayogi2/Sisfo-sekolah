<?php

namespace App\Http\Requests\Classroom;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Classroom::class);
    }

    /**
     * Kapasitas tidak lagi diisi di form Tambah Kelas (otomatis
     * Classroom::DEFAULT_CAPACITY, bisa diubah lewat Edit Kelas).
     *
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
            'student_ids' => ['nullable', 'array', 'max:'.Classroom::DEFAULT_CAPACITY],
            'student_ids.*' => ['integer', 'distinct', 'exists:students,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kelas wajib diisi.',
            'name.unique' => 'Nama kelas sudah dipakai pada tahun ajaran ini.',
            'grade_level.required' => 'Tingkat kelas wajib diisi.',
            'homeroom_teacher_id.exists' => 'Wali kelas yang dipilih tidak valid.',
            'student_ids.max' => 'Maksimal '.Classroom::DEFAULT_CAPACITY.' siswa (kapasitas kelas baru).',
            'student_ids.*.exists' => 'Ada siswa yang tidak ditemukan.',
        ];
    }

    /**
     * Cek ulang di server (bukan hanya di pencarian AJAX): siswa bisa saja
     * sudah dimasukkan ke kelas lain setelah ia ditambahkan ke daftar.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            Student::query()->with('classroom')->whereIn('id', $this->input('student_ids', []))->get()
                ->each(function (Student $student) use ($validator) {
                    if ($issue = $student->newClassroomPlacementIssue()) {
                        $validator->errors()->add('student_ids', $issue);
                    }
                });
        });
    }
}
