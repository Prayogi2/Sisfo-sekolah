<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Aturan sesi kuis model Kahoot: batas waktu tiap soal, poin permainan
 * (bonus kecepatan), data layar guru, dan papan peringkat.
 *
 * Poin permainan hanya untuk papan peringkat; nilai kuis & rapor tetap
 * memakai awarded_points supaya siswa yang lambat tapi benar tidak rugi.
 */
class LiveQuizSession
{
    /**
     * Kelonggaran untuk jeda jaringan setelah hitung mundur habis.
     */
    public const GRACE_SECONDS = 2;

    public const POINTS_PER_QUESTION_POINT = 1000;

    /**
     * Essay & menjodohkan butuh waktu lebih lama dari pilihan ganda.
     */
    public function timeLimit(Quiz $quiz, QuizQuestion $question): int
    {
        $multiplier = match ($question->type) {
            QuizQuestion::TYPE_ESSAY => 3,
            QuizQuestion::TYPE_MATCHING => 2,
            default => 1,
        };

        return max(5, (int) $quiz->question_seconds) * $multiplier;
    }

    public function currentQuestion(Quiz $quiz): ?QuizQuestion
    {
        return $quiz->live_question_index === null ? null : $quiz->questions()->get()->get($quiz->live_question_index);
    }

    /**
     * Sisa detik soal yang sedang tampil (null bila tidak ada hitung mundur).
     */
    public function secondsLeft(Quiz $quiz, QuizQuestion $question): ?int
    {
        if ($quiz->live_phase !== 'question' || ! $quiz->live_question_started_at) {
            return null;
        }

        $elapsed = $quiz->live_question_started_at->diffInSeconds(now());

        return (int) max(0, ceil($this->timeLimit($quiz, $question) - $elapsed));
    }

    public function acceptsAnswers(Quiz $quiz, QuizQuestion $question): bool
    {
        if ($quiz->live_phase !== 'question') {
            return false;
        }

        return ! $quiz->live_question_started_at
            || $quiz->live_question_started_at->diffInSeconds(now()) <= $this->timeLimit($quiz, $question) + self::GRACE_SECONDS;
    }

    /**
     * Lama siswa menjawab sejak soal tampil, dalam milidetik.
     */
    public function responseMs(Quiz $quiz): ?int
    {
        return $quiz->live_question_started_at ? (int) max(0, $quiz->live_question_started_at->diffInMilliseconds(now())) : null;
    }

    /**
     * Seperti Kahoot: jawaban benar paling cepat dapat poin penuh, makin
     * lambat makin berkurang sampai separuhnya. Benar sebagian (menjodohkan)
     * dapat poin sebanding. Essay baru dinilai guru, jadi 0 di permainan.
     */
    public function gamePoints(Quiz $quiz, QuizQuestion $question, int $awardedPoints, int $maxPoints, ?int $responseMs): int
    {
        if ($awardedPoints <= 0 || $maxPoints <= 0) {
            return 0;
        }

        $limitMs = $this->timeLimit($quiz, $question) * 1000;
        $speedFactor = 1 - min(1, ($responseMs ?? $limitMs) / $limitMs) / 2;

        return (int) round(self::POINTS_PER_QUESTION_POINT * $awardedPoints * $speedFactor);
    }

    /**
     * Papan peringkat permainan: total poin permainan per siswa.
     *
     * @return Collection<int, array{student_id: int, name: string, points: int, rank: int}>
     */
    public function leaderboard(Quiz $quiz, ?int $limit = 5): Collection
    {
        $rows = $quiz->attempts()
            ->with('student:id,name')
            ->withSum('answers as game_total', 'game_points')
            ->get()
            ->sortBy([['game_total', 'desc'], [fn ($attempt) => $attempt->student->name, 'asc']])
            ->values()
            ->map(fn ($attempt, int $index) => [
                'student_id' => $attempt->student_id,
                'name' => $attempt->student->name,
                'points' => (int) $attempt->game_total,
                'rank' => $index + 1,
            ]);

        return $limit ? $rows->take($limit)->values() : $rows;
    }

    /**
     * Data layar proyektor guru.
     *
     * @return array<string, mixed>
     */
    public function hostState(Quiz $quiz): array
    {
        $questions = $quiz->questions()->get();
        $question = $quiz->live_question_index === null ? null : $questions->get($quiz->live_question_index);
        $answers = $question
            ? QuizAnswer::query()->where('quiz_question_id', $question->id)->whereHas('attempt', fn ($query) => $query->where('quiz_id', $quiz->id))->get()
            : collect();
        $reveal = in_array($quiz->live_phase, ['reveal', 'finished'], true);

        return [
            'phase' => $quiz->live_phase,
            'is_open' => $quiz->is_open,
            'index' => $quiz->live_question_index,
            'total' => $questions->count(),
            'participants' => $quiz->attempts()->count(),
            'answered' => $answers->whereNotNull('answer')->count(),
            'time_limit' => $question ? $this->timeLimit($quiz, $question) : null,
            'seconds_left' => $question ? $this->secondsLeft($quiz, $question) : null,
            'question' => $question ? [
                'id' => $question->id,
                'type' => $question->type,
                'type_label' => $question->typeLabel(),
                'text' => $question->question,
                'options' => $question->isChoice() ? $question->options : null,
                'matching_left' => $question->type === QuizQuestion::TYPE_MATCHING ? array_column($question->matchingPairs(), 'left') : null,
                'media_url' => $question->media_path ? Storage::disk('public')->url($question->media_path) : null,
                'media_type' => $question->media_type,
                'correct' => $reveal ? $this->correctForDisplay($question) : null,
            ] : null,
            'distribution' => $question && $reveal ? $this->distribution($question, $answers) : null,
            'leaderboard' => $quiz->live_phase === 'question' ? [] : $this->leaderboard($quiz)->all(),
        ];
    }

    /**
     * @return list<string>|null
     */
    public function correctForDisplay(QuizQuestion $question): ?array
    {
        return match ($question->type) {
            QuizQuestion::TYPE_MATCHING => array_map(fn (array $pair) => $pair['left'].' → '.$pair['right'], $question->matchingPairs()),
            default => $question->correct_answer,
        };
    }

    /**
     * Sebaran jawaban untuk grafik setelah soal ditutup.
     *
     * @param  Collection<int, QuizAnswer>  $answers
     * @return list<array{label: string, count: int, correct: bool}>
     */
    private function distribution(QuizQuestion $question, Collection $answers): array
    {
        if ($question->isChoice()) {
            return collect(QuizQuestion::OPTION_KEYS)->map(fn (string $key) => [
                'label' => $key,
                'count' => $answers->filter(fn (QuizAnswer $answer) => in_array($key, $answer->answer ?? [], true))->count(),
                'correct' => in_array($key, $question->correct_answer ?? [], true),
            ])->all();
        }

        if ($question->type === QuizQuestion::TYPE_MATCHING) {
            $answered = $answers->whereNotNull('answer');

            return [
                ['label' => 'Semua benar', 'count' => $answered->where('is_correct', true)->count(), 'correct' => true],
                ['label' => 'Sebagian benar', 'count' => $answered->filter(fn (QuizAnswer $answer) => ! $answer->is_correct && $answer->awarded_points > 0)->count(), 'correct' => false],
                ['label' => 'Salah', 'count' => $answered->filter(fn (QuizAnswer $answer) => $answer->awarded_points === 0)->count(), 'correct' => false],
            ];
        }

        return [['label' => 'Menjawab (dinilai guru)', 'count' => $answers->whereNotNull('answer')->count(), 'correct' => true]];
    }
}
