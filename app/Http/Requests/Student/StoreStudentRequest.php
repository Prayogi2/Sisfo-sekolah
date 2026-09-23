<?php

namespace App\Http\Requests\Student;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Student;
use App\Services\StudentRecordWriter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nisn' => ['required', 'digits:10', 'unique:students,nisn'],
            'nis' => ['required', 'string', 'max:20', 'unique:students,nis'],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', Rule::enum(StudentStatus::class)],
        ] + app(StudentRecordWriter::class)->rules();
    }

    public function messages(): array
    {
        return app(StudentRecordWriter::class)->messages() + [
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'name.required' => 'Nama lengkap wajib diisi.',
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'classroom_id.exists' => 'Kelas yang dipilih tidak valid.',
        ];
    }
}
