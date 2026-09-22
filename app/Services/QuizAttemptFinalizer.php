<?php

namespace App\Services;

use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Menutup & menilai percobaan kuis. Dipakai baik saat siswa menekan
 * "Kumpulkan" maupun saat waktunya habis (otomatis), supaya perhitungan
 * nilainya hanya ada di satu tempat.
 */
class QuizAttemptFinalizer
{
    /**
     * Batas waktu pengerjaan: mana yang lebih dulu antara durasi pribadi
     * siswa (dihitung dari mulai) dan jadwal berakhirnya kuis.
     */
    public function deadlineFor(QuizAttempt $attempt): CarbonInterface
    {
        $attempt->loadMissing('quiz');

        $deadline = $attempt->started_at->copy()->addMinutes($attempt->quiz->duration_minutes);

        if ($attempt->quiz->ends_at && $attempt->quiz->ends_at->lessThan($deadline)) {
            return $attempt->quiz->ends_at;
        }

        return $deadline;
    }

    public function hasExpired(QuizAttempt $attempt): bool
    {
        return $attempt->status === 'in_progress'
            && now()->greaterThan($this->deadlineFor($attempt));
    }

    /**
     * Kumpulkan otomatis kalau waktunya sudah lewat. Waktu pengumpulan
     * dicatat sebesar batas waktunya, bukan saat ketahuan, supaya jam
     * pengumpulan tetap jujur walau baru diproses belakangan.
     */
    public function finalizeIfExpired(QuizAttempt $attempt): bool
    {
        if (! $this->hasExpired($attempt)) {
            return false;
        }

        $this->finalize($attempt, $this->deadlineFor($attempt));

        return true;
    }

    public function finalize(QuizAttempt $attempt, ?CarbonInterface $submittedAt = null): QuizAttempt
    {
        $attempt->loadMissing('quiz');

        $questions = $attempt->quiz->questions()->get();
        $answers = $attempt->answers()->get();

        $totalPoints = $questions->sum(fn (QuizQuestion $question) => $question->pivot->points);
        $earnedPoints = $answers->sum('awarded_points');

        DB::transaction(function () use ($attempt, $submittedAt, $totalPoints, $earnedPoints, $answers, $questions) {
            $attempt->update([
                'status' => 'submitted',
                'submitted_at' => $submittedAt ?? now(),
                'score' => $totalPoints > 0 ? round($earnedPoints / $totalPoints * 100, 2) : 0,
                'correct_answers' => $answers->where('is_correct', true)->count(),
                'total_questions' => $questions->count(),
            ]);

            $this->syncQuizGrade($attempt);
        });

        return $attempt;
    }

    /**
     * Salin hasil kuis CBT ke komponen "Nilai Kuis" di rapor: rata-rata
     * semua kuis mapel itu yang sudah dikumpulkan siswa pada semester
     * berjalan. Komponen lain (tugas/UTS/UAS) tidak disentuh.
     */
    private function syncQuizGrade(QuizAttempt $attempt): void
    {
        $subjectId = $attempt->quiz->subject_id;

        $quizIds = Quiz::query()->where('subject_id', $subjectId)->pluck('id');

        $average = QuizAttempt::query()
            ->where('student_id', $attempt->student_id)
            ->whereIn('quiz_id', $quizIds)
            ->where('status', 'submitted')
            ->avg('score');

        if ($average === null) {
            return;
        }

        Grade::updateOrCreate(
            [
                'student_id' => $attempt->student_id,
                'subject_id' => $subjectId,
                'academic_year' => Classroom::currentAcademicYear(),
                'semester' => Semester::current(),
            ],
            ['quiz_score' => (int) round($average)],
        );
    }

    /**
     * Sapu semua percobaan yang waktunya sudah habis tapi belum dikumpulkan.
     * Dipakai oleh perintah terjadwal, supaya siswa yang perangkatnya mati
     * di tengah kuis tetap dapat nilai.
     */
    public function finalizeExpired(): int
    {
        $finalized = 0;

        QuizAttempt::query()
            ->where('status', 'in_progress')
            ->with('quiz')
            ->chunkById(100, function ($attempts) use (&$finalized) {
                foreach ($attempts as $attempt) {
                    if ($this->finalizeIfExpired($attempt)) {
                        $finalized++;
                    }
                }
            });

        return $finalized;
    }
}
