<?php

namespace App\Http\Requests\Classroom;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignStudentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageStudents', $this->route('classroom'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_ids' => ['array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $classroom = $this->route('classroom');
            $studentIds = $this->input('student_ids', []);

            if (count($studentIds) > $classroom->capacity) {
                $validator->errors()->add(
                    'student_ids',
                    "Jumlah siswa ({$this->countStudents()}) melebihi kapasitas kelas ({$classroom->capacity})."
                );
            }
        });
    }

    private function countStudents(): int
    {
        return count($this->input('student_ids', []));
    }
}
