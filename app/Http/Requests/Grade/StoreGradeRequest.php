<?php

namespace App\Http\Requests\Grade;

use App\Enums\Semester;
use App\Models\Grade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Grade::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::enum(Semester::class)],
            'assignment_score' => ['nullable', 'integer', 'between:0,100'],
            'quiz_score' => ['nullable', 'integer', 'between:0,100'],
            'midterm_score' => ['nullable', 'integer', 'between:0,100'],
            'final_score' => ['nullable', 'integer', 'between:0,100'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Siswa wajib dipilih.',
            'subject_id.required' => 'Mata pelajaran wajib dipilih.',
            'semester.required' => 'Semester wajib dipilih.',
            'assignment_score.between' => 'Nilai tugas harus antara 0 sampai 100.',
            'quiz_score.between' => 'Nilai kuis harus antara 0 sampai 100.',
            'midterm_score.between' => 'Nilai UTS harus antara 0 sampai 100.',
            'final_score.between' => 'Nilai UAS harus antara 0 sampai 100.',
        ];
    }
}
