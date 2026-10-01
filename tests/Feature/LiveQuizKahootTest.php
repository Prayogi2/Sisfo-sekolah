<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\User;
use App\Services\LiveQuizSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LiveQuizKahootTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $guru;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->freezeSecond();

        $this->guru = User::factory()->create(['role' => 'guru']);
        $this->quiz = Quiz::factory()->create([
            'created_by' => $this->guru->id,
            'mode' => 'live',
            'question_seconds' => 20,
            'live_phase' => 'question',
            'live_question_index' => 0,
            'live_question_started_at' => now(),
        ]);
        $question = QuizQuestion::factory()->create(['subject_id' => $this->quiz->subject_id, 'correct_answer' => ['A'], 'options' => ['A' => 'Benar', 'B' => 'Salah 1', 'C' => 'Salah 2', 'D' => 'Salah 3']]);
        $this->quiz->questions()->attach([$question->id => ['sort_order' => 1, 'points' => 2]]);
    }

    /**
     * @return array{0: User, 1: QuizAttempt}
     */
    private function player(string $name): array
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $user->id, 'classroom_id' => $this->quiz->classroom_id, 'name' => $name]);
        Attendance::factory()->create(['student_id' => $student->id, 'date' => now()->toDateString(), 'check_in_at' => now(), 'status' => AttendanceStatus::Present]);

        return [$user, QuizAttempt::create(['quiz_id' => $this->quiz->id, 'student_id' => $student->id, 'started_at' => now(), 'total_questions' => 1])];
    }

    private function answer(User $user, QuizAttempt $attempt, string $key)
    {
        return $this->actingAs($user)->postJson(route('siswa.kuis.live.answer', $attempt), ['answer' => [$key]]);
    }

    public function test_faster_correct_answers_earn_more_game_points_but_the_same_grade(): void
    {
        [$fast, $fastAttempt] = $this->player('Cepat');
        [$slow, $slowAttempt] = $this->player('Lambat');

        $this->answer($fast, $fastAttempt, 'A')->assertOk();
        $this->travel(16)->seconds();
        $this->answer($slow, $slowAttempt, 'A')->assertOk();

        // Instan = 1000 × 2 poin; 16 dari 20 detik = 2000 × (1 − 0,8/2) = 1200.
        $this->assertSame([2000, 2], [$fastAttempt->answers()->sole()->game_points, $fastAttempt->answers()->sole()->awarded_points]);
        $this->assertSame([1200, 2], [$slowAttempt->answers()->sole()->game_points, $slowAttempt->answers()->sole()->awarded_points]);
    }

    public function test_answers_after_the_timer_runs_out_are_rejected(): void
    {
        [$user, $attempt] = $this->player('Telat');

        $this->travel(20 + 3)->seconds();

        $this->answer($user, $attempt, 'A')->assertStatus(422)->assertJsonPath('message', 'Waktu menjawab sudah habis.');
        $this->assertDatabaseCount('quiz_answers', 0);
    }

    public function test_essay_and_matching_questions_get_more_time(): void
    {
        $session = app(LiveQuizSession::class);

        $this->assertSame(20, $session->timeLimit($this->quiz, QuizQuestion::factory()->make()));
        $this->assertSame(40, $session->timeLimit($this->quiz, QuizQuestion::factory()->make(['type' => QuizQuestion::TYPE_MATCHING])));
        $this->assertSame(60, $session->timeLimit($this->quiz, QuizQuestion::factory()->make(['type' => QuizQuestion::TYPE_ESSAY])));
    }

    public function test_host_screen_shows_timer_and_answer_count_then_distribution_and_leaderboard(): void
    {
        [$ani, $aniAttempt] = $this->player('Ani');
        [$budi, $budiAttempt] = $this->player('Budi');
        $this->player('Cici');
        $this->answer($ani, $aniAttempt, 'A');
        $this->answer($budi, $budiAttempt, 'B');

        $this->actingAs($this->guru)->getJson(route('guru.kuis.live.state', $this->quiz))
            ->assertOk()
            ->assertJsonPath('participants', 3)
            ->assertJsonPath('answered', 2)
            ->assertJsonPath('seconds_left', 20)
            ->assertJsonPath('question.options.A', 'Benar')
            ->assertJsonPath('question.correct', null)
            ->assertJsonPath('distribution', null);

        $this->quiz->update(['live_phase' => 'reveal']);

        $response = $this->actingAs($this->guru)->getJson(route('guru.kuis.live.state', $this->quiz));
        $response->assertJsonPath('question.correct', ['A']);
        $response->assertJsonPath('distribution.0', ['label' => 'A', 'count' => 1, 'correct' => true]);
        $response->assertJsonPath('distribution.1', ['label' => 'B', 'count' => 1, 'correct' => false]);
        $response->assertJsonPath('leaderboard.0.name', 'Ani');
        $response->assertJsonPath('leaderboard.0.rank', 1);
    }

    public function test_another_teacher_cannot_watch_the_host_screen(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guru']))
            ->getJson(route('guru.kuis.live.state', $this->quiz))
            ->assertForbidden();
    }

    public function test_student_sees_points_gained_and_rank_after_the_answer_is_revealed(): void
    {
        [$ani, $aniAttempt] = $this->player('Ani');
        [$budi, $budiAttempt] = $this->player('Budi');
        $this->answer($ani, $aniAttempt, 'B');
        $this->answer($budi, $budiAttempt, 'A');

        $this->actingAs($ani)->getJson(route('siswa.kuis.live.state', $this->quiz))
            ->assertJsonPath('seconds_left', 20)
            ->assertJsonPath('rank', null);

        $this->quiz->update(['live_phase' => 'reveal']);

        $this->actingAs($ani)->getJson(route('siswa.kuis.live.state', $this->quiz))
            ->assertJsonPath('gained_points', 0)
            ->assertJsonPath('rank', 2)
            ->assertJsonPath('leaderboard.0.name', 'Budi');
    }

    public function test_restarting_the_session_clears_answers_from_the_previous_round(): void
    {
        [$ani, $aniAttempt] = $this->player('Ani');
        $this->answer($ani, $aniAttempt, 'A');

        $this->actingAs($this->guru)->post(route('guru.kuis.live.start', $this->quiz))->assertRedirect();

        $this->assertSame(0, QuizAnswer::count());
        $this->assertSame('in_progress', $aniAttempt->fresh()->status);
        $this->answer($ani, $aniAttempt, 'A')->assertOk();
    }

    public function test_the_printable_sheet_hides_the_answer_key_unless_requested(): void
    {
        $essay = QuizQuestion::factory()->create(['subject_id' => $this->quiz->subject_id, 'type' => QuizQuestion::TYPE_ESSAY, 'options' => [], 'correct_answer' => ['Pedoman rahasia'], 'question' => 'Ceritakan liburanmu!']);
        $this->quiz->questions()->attach([$essay->id => ['sort_order' => 2, 'points' => 5]]);

        $this->actingAs($this->guru)->get(route('guru.kuis.cetak', $this->quiz))
            ->assertOk()
            ->assertSeeInOrder(['Nama', 'Pilihan Ganda', 'Benar', 'Uraian / Essay', 'Ceritakan liburanmu!'])
            ->assertDontSee('Pedoman rahasia')
            ->assertDontSee('KUNCI JAWABAN');

        $this->actingAs($this->guru)->get(route('guru.kuis.cetak', ['quiz' => $this->quiz, 'kunci' => 1]))
            ->assertOk()
            ->assertSee('KUNCI JAWABAN')
            ->assertSee('Pedoman rahasia')
            ->assertSee('class="correct"', false);
    }

    public function test_another_teacher_cannot_print_the_quiz(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guru']))
            ->get(route('guru.kuis.cetak', $this->quiz))
            ->assertForbidden();
    }
}
