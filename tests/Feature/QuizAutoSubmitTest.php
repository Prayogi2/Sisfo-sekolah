<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuizAttemptFinalizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizAutoSubmitTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Kuis 2 soal (kunci "A") yang sudah dikerjakan sebagian lalu ditinggal
     * sampai waktunya habis.
     *
     * @return array{0: User, 1: QuizAttempt, 2: Quiz}
     */
    private function abandonedAttempt(int $correctAnswers = 1, int $durationMinutes = 30): array
    {
        $classroom = Classroom::factory()->create();
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id, 'classroom_id' => $classroom->id]);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => AttendanceStatus::Present,
        ]);

        $subject = Subject::factory()->create();
        $quiz = Quiz::factory()->create([
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'duration_minutes' => $durationMinutes,
        ]);
        $questions = QuizQuestion::factory(2)->create([
            'subject_id' => $subject->id,
            'correct_answer' => 'A',
            'points' => 1,
        ]);
        $quiz->questions()->attach(
            $questions->values()->mapWithKeys(fn (QuizQuestion $q, int $i) => [
                $q->id => ['sort_order' => $i + 1, 'points' => 1],
            ])->all()
        );

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes($durationMinutes + 5),
            'total_questions' => 2,
        ]);

        foreach ($questions->take($correctAnswers) as $question) {
            QuizAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'quiz_question_id' => $question->id,
                'answer' => 'A',
                'is_correct' => true,
                'awarded_points' => 1,
            ]);
        }

        return [$siswa, $attempt, $quiz];
    }

    public function test_the_scheduled_command_submits_abandoned_attempts(): void
    {
        [, $attempt] = $this->abandonedAttempt();

        $this->artisan('kuis:auto-kumpulkan')->assertSuccessful();

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame('50.00', $attempt->score);
        $this->assertSame(1, $attempt->correct_answers);
    }

    public function test_auto_submit_records_the_deadline_not_the_moment_it_was_noticed(): void
    {
        [, $attempt, $quiz] = $this->abandonedAttempt();
        $deadline = $attempt->started_at->copy()->addMinutes($quiz->duration_minutes);

        $this->artisan('kuis:auto-kumpulkan');

        $this->assertSame(
            $deadline->format('Y-m-d H:i'),
            $attempt->fresh()->submitted_at->format('Y-m-d H:i'),
        );
    }

    public function test_attempts_still_within_time_are_left_alone(): void
    {
        [, $attempt] = $this->abandonedAttempt(durationMinutes: 30);
        $attempt->update(['started_at' => now()->subMinutes(5)]);

        $this->artisan('kuis:auto-kumpulkan');

        $this->assertSame('in_progress', $attempt->fresh()->status);
        $this->assertNull($attempt->fresh()->score);
    }

    public function test_opening_the_quiz_list_finalises_the_students_expired_attempt(): void
    {
        [$siswa, $attempt] = $this->abandonedAttempt();

        $this->actingAs($siswa)->get(route('siswa.kuis'))->assertOk();

        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertSame('50.00', $attempt->fresh()->score);
    }

    public function test_reopening_an_expired_quiz_submits_it_instead_of_letting_work_continue(): void
    {
        [$siswa, $attempt, $quiz] = $this->abandonedAttempt();

        $response = $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));

        $response->assertRedirect(route('siswa.kuis'));
        $this->assertSame('submitted', $attempt->fresh()->status);
    }

    public function test_submitting_late_is_scored_at_the_deadline(): void
    {
        [$siswa, $attempt] = $this->abandonedAttempt();

        $this->actingAs($siswa)->post(route('siswa.kuis.submit', $attempt))->assertRedirect();

        $this->assertSame('submitted', $attempt->fresh()->status);
        $this->assertSame('50.00', $attempt->fresh()->score);
    }

    public function test_a_quiz_end_time_cuts_the_attempt_short(): void
    {
        [, $attempt, $quiz] = $this->abandonedAttempt(durationMinutes: 120);

        // Durasi pribadi masih sisa, tapi jadwal kuis sudah lewat.
        $attempt->update(['started_at' => now()->subMinutes(10)]);
        $quiz->update(['ends_at' => now()->subMinutes(2)]);

        $this->artisan('kuis:auto-kumpulkan');

        $this->assertSame('submitted', $attempt->fresh()->status);
    }

    public function test_finalising_twice_does_not_change_the_score(): void
    {
        [, $attempt] = $this->abandonedAttempt();

        $this->artisan('kuis:auto-kumpulkan');
        $firstScore = $attempt->fresh()->score;
        $firstSubmittedAt = $attempt->fresh()->submitted_at;

        $this->artisan('kuis:auto-kumpulkan');

        $this->assertSame($firstScore, $attempt->fresh()->score);
        $this->assertEquals($firstSubmittedAt, $attempt->fresh()->submitted_at);
    }

    public function test_an_attempt_with_no_answers_is_scored_zero(): void
    {
        [, $attempt] = $this->abandonedAttempt(correctAnswers: 0);

        $this->artisan('kuis:auto-kumpulkan');

        $this->assertSame('0.00', $attempt->fresh()->score);
        $this->assertSame(0, $attempt->fresh()->correct_answers);
    }

    public function test_the_finaliser_reports_how_many_attempts_it_closed(): void
    {
        $this->abandonedAttempt();
        $this->abandonedAttempt();

        $this->assertSame(2, app(QuizAttemptFinalizer::class)->finalizeExpired());
    }
}
