<?php

namespace App\Http\Requests\Quiz;

use App\Services\QuizQuestionForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Simpan beberapa soal sekaligus untuk satu mata pelajaran.
 */
class StoreQuizQuestionsRequest extends FormRequest
{
    public const MAX_QUESTIONS = 50;

    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'guru']);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'questions' => ['required', 'array', 'min:1', 'max:'.self::MAX_QUESTIONS],
        ];

        foreach (array_keys($this->input('questions', [])) as $index) {
            $rules += QuizQuestionForm::rules("questions.{$index}", $this->input("questions.{$index}.type"));
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'subject_id.required' => 'Pilih mata pelajaran untuk soal-soal ini.',
            'questions.required' => 'Tambahkan minimal satu soal.',
            'questions.max' => 'Maksimal '.self::MAX_QUESTIONS.' soal sekali simpan.',
        ];

        foreach (array_keys($this->input('questions', [])) as $position => $index) {
            $messages += QuizQuestionForm::messages("questions.{$index}", 'Soal #'.($position + 1));
        }

        return $messages;
    }
}
