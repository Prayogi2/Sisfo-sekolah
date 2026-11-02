<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizEssayGradingControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Kuis berisi 1 soal PG (2 poin, dijawab benar) & 1 essay (8 poin, belum dikoreksi).
     *
     * @return array{0: User, 1: Quiz, 2: QuizAnswer, 3: QuizAttempt}
     */
    private function submittedQuizWithEssay(): array
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create();
        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'created_by' => $guru->id]);
        $choice = QuizQuestion::factory()->create(['subject_id' => $quiz->subject_id]);
        $essay = QuizQuestion::factory()->create(['subject_id' => $quiz->subject_id, 'type' => QuizQuestion::TYPE_ESSAY, 'options' => [], 'correct_answer' => ['Penguapan, pengembunan, hujan'], 'question' => 'Jelaskan daur air!']);
        $quiz->questions()->attach([$choice->id => ['sort_order' => 1, 'points' => 2], $essay->id => ['sort_order' => 2, 'points' => 8]]);

        $student = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Ani Rajin']);
        $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student->id, 'status' => 'submitted', 'started_at' => now(), 'submitted_at' => now(), 'score' => 20, 'total_questions' => 2]);
        QuizAnswer::create(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $choice->id, 'answer' => ['A'], 'is_correct' => true, 'awarded_points' => 2, 'graded_at' => now()]);
        $essayAnswer = QuizAnswer::create(['quiz_attempt_id' => $attempt->id, 'quiz_question_id' => $essay->id, 'answer' => ['text' => 'Air menguap lalu turun jadi hujan'], 'is_correct' => false, 'awarded_points' => 0, 'graded_at' => null]);

        return [$guru, $quiz, $essayAnswer, $attempt];
    }

    public function test_guru_sees_the_essay_answers_to_grade(): void
    {
        [$guru, $quiz] = $this->submittedQuizWithEssay();

        $this->actingAs($guru)->get(route('guru.kuis.koreksi', $quiz))
            ->assertOk()
            ->assertSeeInOrder(['Jelaskan daur air!', 'Pedoman: Penguapan, pengembunan, hujan', 'Ani Rajin', 'Air menguap lalu turun jadi hujan', 'Belum dinilai']);
    }

    public function test_grading_an_essay_recalculates_the_quiz_score_and_report_grade(): void
    {
        [$guru, $quiz, $essayAnswer, $attempt] = $this->submittedQuizWithEssay();

        $this->actingAs($guru)->put(route('guru.kuis.koreksi.simpan', $quiz), ['scores' => [$essayAnswer->id => 6]])
            ->assertSessionHas('success', '1 jawaban essay dinilai. Nilai kuis & rapor siswa sudah diperbarui.');

        $essayAnswer->refresh();
        $this->assertSame(6, $essayAnswer->awarded_points);
        $this->assertNotNull($essayAnswer->graded_at);
        // (2 + 6) / (2 + 8) = 80.
        $this->assertEquals(80, $attempt->fresh()->score);
        $this->assertSame(80, Grade::where('student_id', $attempt->student_id)->where('subject_id', $quiz->subject_id)->value('quiz_score'));
    }

    public function test_an_essay_score_above_the_question_points_is_rejected(): void
    {
        [$guru, $quiz, $essayAnswer] = $this->submittedQuizWithEssay();

        $this->actingAs($guru)->put(route('guru.kuis.koreksi.simpan', $quiz), ['scores' => [$essayAnswer->id => 9]])
            ->assertSessionHasErrors(["scores.{$essayAnswer->id}" => 'Nilai essay maksimal 8 poin.']);

        $this->assertNull($essayAnswer->fresh()->graded_at);
    }

    public function test_guru_cannot_grade_a_quiz_made_by_another_teacher(): void
    {
        [, $quiz, $essayAnswer] = $this->submittedQuizWithEssay();
        $otherGuru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($otherGuru)->get(route('guru.kuis.koreksi', $quiz))->assertForbidden();
        $this->actingAs($otherGuru)->put(route('guru.kuis.koreksi.simpan', $quiz), ['scores' => [$essayAnswer->id => 8]])->assertForbidden();

        $this->assertNull($essayAnswer->fresh()->graded_at);
    }

    public function test_the_question_bank_flags_quizzes_with_ungraded_essays(): void
    {
        [$guru, $quiz] = $this->submittedQuizWithEssay();

        $this->actingAs($guru)->get(route('guru.bank-soal'))
            ->assertOk()
            ->assertSee(route('guru.kuis.koreksi', $quiz))
            ->assertSeeInOrder(['Koreksi Essay', '1']);
    }

    public function test_answers_of_students_still_working_are_hidden_and_cannot_be_graded(): void
    {
        [$guru, $quiz, $essayAnswer, $attempt] = $this->submittedQuizWithEssay();
        $attempt->update(['status' => 'in_progress', 'submitted_at' => null]);

        $this->actingAs($guru)->get(route('guru.kuis.koreksi', $quiz))
            ->assertOk()
            ->assertDontSee('Air menguap lalu turun jadi hujan');

        $this->actingAs($guru)->put(route('guru.kuis.koreksi.simpan', $quiz), ['scores' => [$essayAnswer->id => 8]]);

        $this->assertNull($essayAnswer->fresh()->graded_at);
        $this->actingAs($guru)->get(route('guru.bank-soal'))->assertDontSee('<span class="badge bg-danger">', false);
    }

    public function test_a_blank_essay_answer_is_graded_zero_automatically(): void
    {
        $classroom = Classroom::factory()->create();
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $siswa->id]);
        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'is_published' => true, 'is_open' => true]);
        $essay = QuizQuestion::factory()->create(['subject_id' => $quiz->subject_id, 'type' => QuizQuestion::TYPE_ESSAY, 'options' => [], 'correct_answer' => ['']]);
        $quiz->questions()->attach([$essay->id => ['sort_order' => 1, 'points' => 8]]);
        $attempt = QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student->id, 'status' => 'in_progress', 'started_at' => now()]);

        $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), ['quiz_question_id' => $essay->id, 'answer' => '   ']);

        $answer = QuizAnswer::where('quiz_attempt_id', $attempt->id)->sole();
        $this->assertNull($answer->answer);
        $this->assertSame(0, $answer->awarded_points);
        $this->assertNotNull($answer->graded_at);
    }

    public function test_siswa_sees_a_provisional_score_until_the_essay_is_graded(): void
    {
        [$guru, $quiz, $essayAnswer, $attempt] = $this->submittedQuizWithEssay();
        $siswa = User::factory()->create(['role' => 'siswa']);
        $attempt->student->update(['user_id' => $siswa->id]);

        $this->actingAs($siswa)->get(route('siswa.hasil-kuis'))
            ->assertOk()
            ->assertSee('Menunggu koreksi guru');

        $this->actingAs($guru)->put(route('guru.kuis.koreksi.simpan', $quiz), ['scores' => [$essayAnswer->id => 8]]);

        $this->actingAs($siswa)->get(route('siswa.hasil-kuis'))
            ->assertOk()
            ->assertDontSee('Menunggu koreksi guru');
    }
}
