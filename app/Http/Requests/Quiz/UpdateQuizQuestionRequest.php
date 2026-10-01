<?php

namespace App\Http\Requests\Quiz;

use App\Services\QuizQuestionForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edit satu soal; isiannya memakai bentuk yang sama dengan form tambah
 * (questions[0][...]) supaya kartu soalnya bisa dipakai ulang.
 */
class UpdateQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'guru']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'questions' => ['required', 'array', 'size:1'],
            'questions.0.remove_media' => ['sometimes', 'boolean'],
        ] + QuizQuestionForm::rules('questions.0', $this->input('questions.0.type'));
    }

    public function messages(): array
    {
        return ['subject_id.required' => 'Pilih mata pelajaran untuk soal ini.'] + QuizQuestionForm::messages('questions.0', 'Soal');
    }
}
