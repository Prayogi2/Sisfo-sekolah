<?php

namespace App\Http\Requests\Subject;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Guru pengampu satu mapel: tiap baris = satu guru + kelas-kelas yang ia
 * ajar untuk mapel ini (1 mapel bisa diajar guru berbeda di kelas berbeda).
 */
class UpdateSubjectTeachersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('subject'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignments' => ['array'],
            'assignments.*.teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'assignments.*.classroom_ids' => ['required', 'array', 'min:1'],
            'assignments.*.classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'assignments.*.teacher_id.required' => 'Guru wajib dipilih di setiap baris guru pengampu.',
            'assignments.*.classroom_ids.required' => 'Pilih minimal satu kelas untuk setiap guru pengampu.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $teacherIds = collect($this->input('assignments', []))->pluck('teacher_id')->filter();

            if ($teacherIds->count() !== $teacherIds->unique()->count()) {
                $validator->errors()->add('assignments', 'Setiap guru hanya boleh dipilih sekali. Gabungkan kelasnya dalam satu baris.');
            }
        });
    }
}
