<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Menilai satu jawaban siswa sesuai jenis soalnya.
 *
 * - Pilihan ganda: benar bila huruf yang dipilih persis sama dengan kunci.
 * - Menjodohkan: poin dibagi rata per pasangan yang benar.
 * - Essay: belum dinilai (graded_at null) sampai dikoreksi guru.
 */
class QuizAnswerGrader
{
    /**
     * @return array{answer: array<mixed>|null, is_correct: bool, awarded_points: int, graded_at: Carbon|null}
     *
     * @throws ValidationException bila bentuk jawaban tidak sesuai jenis soal.
     */
    public function grade(QuizQuestion $question, int $points, mixed $rawAnswer): array
    {
        return match ($question->type) {
            QuizQuestion::TYPE_ESSAY => $this->gradeEssay($rawAnswer),
            QuizQuestion::TYPE_MATCHING => $this->gradeMatching($question, $points, $rawAnswer),
            default => $this->gradeChoice($question, $points, $rawAnswer),
        };
    }

    private function gradeChoice(QuizQuestion $question, int $points, mixed $rawAnswer): array
    {
        $answer = collect(is_array($rawAnswer) ? $rawAnswer : [])
            ->filter(fn ($key) => in_array($key, QuizQuestion::OPTION_KEYS, true))
            ->unique()->sort()->values()->all();

        if ($question->type === QuizQuestion::TYPE_SINGLE && count($answer) > 1) {
            throw ValidationException::withMessages(['answer' => 'Soal ini hanya menerima satu jawaban.']);
        }

        $isCorrect = $answer !== [] && $answer === collect($question->correct_answer)->sort()->values()->all();

        return [
            'answer' => $answer === [] ? null : $answer,
            'is_correct' => $isCorrect,
            'awarded_points' => $isCorrect ? $points : 0,
            'graded_at' => now(),
        ];
    }

    /**
     * Jawaban: {indeks pasangan => teks kanan yang dipilih}.
     */
    private function gradeMatching(QuizQuestion $question, int $points, mixed $rawAnswer): array
    {
        $pairs = $question->matchingPairs();
        $choices = array_column($pairs, 'right');
        $answer = [];

        foreach (is_array($rawAnswer) ? $rawAnswer : [] as $index => $choice) {
            if (isset($pairs[$index]) && is_string($choice) && in_array($choice, $choices, true)) {
                $answer[(string) $index] = $choice;
            }
        }

        $correctCount = collect($answer)->filter(fn (string $choice, string $index) => $pairs[(int) $index]['right'] === $choice)->count();
        $total = count($pairs);

        return [
            'answer' => $answer === [] ? null : $answer,
            'is_correct' => $total > 0 && $correctCount === $total,
            'awarded_points' => $total > 0 ? (int) round($points * $correctCount / $total) : 0,
            'graded_at' => now(),
        ];
    }

    private function gradeEssay(mixed $rawAnswer): array
    {
        $text = is_string($rawAnswer) ? trim($rawAnswer) : '';

        if (mb_strlen($text) > 5000) {
            throw ValidationException::withMessages(['answer' => 'Jawaban essay maksimal 5000 karakter.']);
        }

        return [
            'answer' => $text === '' ? null : ['text' => $text],
            'is_correct' => false,
            'awarded_points' => 0,
            'graded_at' => null,
        ];
    }
}
