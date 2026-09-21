<?php

namespace App\Http\Requests\Grade;

use App\Enums\Semester;
use App\Models\GradeWeight;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGradeWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', GradeWeight::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'max:20'],
            'semester' => ['required', Rule::enum(Semester::class)],
            'assignment_weight' => ['required', 'integer', 'between:0,100'],
            'quiz_weight' => ['required', 'integer', 'between:0,100'],
            'midterm_weight' => ['required', 'integer', 'between:0,100'],
            'final_weight' => ['required', 'integer', 'between:0,100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $total = (int) $this->input('assignment_weight')
                + (int) $this->input('quiz_weight')
                + (int) $this->input('midterm_weight')
                + (int) $this->input('final_weight');

            if ($total !== 100) {
                $validator->errors()->add('assignment_weight', "Total bobot harus tepat 100%, saat ini {$total}%.");
            }
        });
    }

    public function messages(): array
    {
        return [
            'assignment_weight.required' => 'Bobot tugas wajib diisi.',
            'quiz_weight.required' => 'Bobot kuis wajib diisi.',
            'midterm_weight.required' => 'Bobot UTS wajib diisi.',
            'final_weight.required' => 'Bobot UAS wajib diisi.',
        ];
    }
}
