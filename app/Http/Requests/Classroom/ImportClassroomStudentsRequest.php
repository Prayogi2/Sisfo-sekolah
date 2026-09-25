<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportClassroomStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStudents', $this->route('classroom'));
    }

    /**
     * Validasi isi tiap baris dilakukan per baris oleh ClassroomStudentImporter.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
