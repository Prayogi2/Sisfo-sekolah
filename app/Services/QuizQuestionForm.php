<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Illuminate\Validation\Rule;

/**
 * Aturan validasi & penyusunan atribut satu soal, sama untuk form tambah
 * banyak soal, form edit, dan import Excel.
 *
 * Isian satu soal:
 * - type, question, points, explanation, media (opsional)
 * - pilihan ganda: options[A..D], correct_answer[] (huruf kunci)
 * - essay: answer_key (kunci/pedoman jawaban, opsional)
 * - menjodohkan: pairs[][left, right]
 */
class QuizQuestionForm
{
    public const MAX_PAIRS = 10;

    /**
     * @return array<string, mixed>
     */
    public static function rules(string $prefix, ?string $type): array
    {
        $rules = [
            "{$prefix}.type" => ['required', Rule::in(array_keys(QuizQuestion::typeLabels()))],
            "{$prefix}.question" => ['required', 'string', 'max:10000'],
            "{$prefix}.points" => ['nullable', 'integer', 'min:1', 'max:100'],
            "{$prefix}.explanation" => ['nullable', 'string', 'max:5000'],
            "{$prefix}.media" => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:51200'],
        ];

        return $rules + match ($type) {
            QuizQuestion::TYPE_SINGLE, QuizQuestion::TYPE_MULTIPLE => [
                "{$prefix}.options" => ['required', 'array:'.implode(',', QuizQuestion::OPTION_KEYS)],
                ...collect(QuizQuestion::OPTION_KEYS)->mapWithKeys(fn (string $key) => ["{$prefix}.options.{$key}" => ['required', 'string', 'max:1000']])->all(),
                "{$prefix}.correct_answer" => ['required', 'array', 'min:1', 'max:4', self::correctAnswerCountRule($type)],
                "{$prefix}.correct_answer.*" => ['distinct', Rule::in(QuizQuestion::OPTION_KEYS)],
            ],
            QuizQuestion::TYPE_ESSAY => [
                "{$prefix}.answer_key" => ['nullable', 'string', 'max:5000'],
            ],
            QuizQuestion::TYPE_MATCHING => [
                "{$prefix}.pairs" => ['required', 'array', 'min:2', 'max:'.self::MAX_PAIRS],
                "{$prefix}.pairs.*.left" => ['required', 'string', 'max:255'],
                "{$prefix}.pairs.*.right" => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            ],
            default => [],
        };
    }

    /**
     * Pesan error dengan nomor soal, mis. "Soal #2: pertanyaan wajib diisi."
     *
     * @return array<string, string>
     */
    public static function messages(string $prefix, string $label): array
    {
        return [
            "{$prefix}.type.required" => "{$label}: pilih jenis soal.",
            "{$prefix}.question.required" => "{$label}: pertanyaan wajib diisi.",
            "{$prefix}.options.required" => "{$label}: isi keempat pilihan jawaban A–D.",
            "{$prefix}.options.*.required" => "{$label}: isi keempat pilihan jawaban A–D.",
            "{$prefix}.correct_answer.required" => "{$label}: pilih kunci jawaban.",
            "{$prefix}.correct_answer.array" => "{$label}: pilih kunci jawaban.",
            "{$prefix}.pairs.required" => "{$label}: isi minimal 2 pasangan untuk soal menjodohkan.",
            "{$prefix}.pairs.min" => "{$label}: isi minimal 2 pasangan untuk soal menjodohkan.",
            "{$prefix}.pairs.max" => "{$label}: maksimal ".self::MAX_PAIRS.' pasangan.',
            "{$prefix}.pairs.*.left.required" => "{$label}: setiap pasangan harus diisi kiri dan kanannya.",
            "{$prefix}.pairs.*.right.required" => "{$label}: setiap pasangan harus diisi kiri dan kanannya.",
            "{$prefix}.pairs.*.right.distinct" => "{$label}: jawaban di sisi kanan tidak boleh ada yang sama.",
            "{$prefix}.points.min" => "{$label}: poin minimal 1.",
            "{$prefix}.points.max" => "{$label}: poin maksimal 100.",
            "{$prefix}.media.mimes" => "{$label}: media harus gambar (JPG/PNG/WEBP) atau video (MP4/WEBM/MOV).",
            "{$prefix}.media.max" => "{$label}: ukuran media maksimal 50 MB.",
        ];
    }

    /**
     * Atribut QuizQuestion dari isian satu soal yang sudah tervalidasi.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function attributes(array $item): array
    {
        $type = $item['type'];

        return [
            'type' => $type,
            'question' => trim($item['question']),
            'points' => (int) ($item['points'] ?? 1) ?: 1,
            'explanation' => filled($item['explanation'] ?? null) ? trim($item['explanation']) : null,
            ...match ($type) {
                QuizQuestion::TYPE_SINGLE, QuizQuestion::TYPE_MULTIPLE => [
                    'options' => collect(QuizQuestion::OPTION_KEYS)->mapWithKeys(fn (string $key) => [$key => trim($item['options'][$key])])->all(),
                    'correct_answer' => collect($item['correct_answer'])->unique()->sort()->values()->all(),
                ],
                QuizQuestion::TYPE_ESSAY => [
                    'options' => [],
                    'correct_answer' => filled($item['answer_key'] ?? null) ? [trim($item['answer_key'])] : [],
                ],
                QuizQuestion::TYPE_MATCHING => [
                    'options' => collect($item['pairs'])->map(fn (array $pair) => ['left' => trim($pair['left']), 'right' => trim($pair['right'])])->values()->all(),
                    'correct_answer' => null,
                ],
            },
        ];
    }

    /**
     * Isian form dari soal yang sudah tersimpan (untuk form edit).
     *
     * @return array<string, mixed>
     */
    public static function itemFromQuestion(QuizQuestion $question): array
    {
        return [
            'type' => $question->type,
            'question' => $question->question,
            'points' => $question->points,
            'explanation' => $question->explanation,
            'options' => $question->isChoice() ? $question->options : [],
            'correct_answer' => $question->isChoice() ? $question->correct_answer : [],
            'answer_key' => $question->type === QuizQuestion::TYPE_ESSAY ? ($question->correct_answer[0] ?? '') : '',
            'pairs' => $question->matchingPairs(),
        ];
    }

    /**
     * Pilihan ganda biasa tepat satu kunci; kompleks lebih dari satu.
     */
    private static function correctAnswerCountRule(string $type): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($type): void {
            if (! is_array($value)) {
                return;
            }

            if ($type === QuizQuestion::TYPE_SINGLE && count($value) !== 1) {
                $fail('Soal pilihan ganda biasa harus memiliki tepat satu jawaban benar.');
            }

            if ($type === QuizQuestion::TYPE_MULTIPLE && count($value) < 2) {
                $fail('Soal pilihan ganda kompleks harus memiliki lebih dari satu jawaban benar.');
            }
        };
    }
}
