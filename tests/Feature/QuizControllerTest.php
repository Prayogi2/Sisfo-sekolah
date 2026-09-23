<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Akun siswa yang sudah punya kelas. Secara bawaan siswanya dianggap
     * sudah scan presensi hari ini, karena kuis memang hanya bisa
     * dikerjakan setelah absen.
     *
     * @return array{0: User, 1: Student}
     */
    private function siswaInClassroom(?Classroom $classroom = null, bool $checkedIn = true): array
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create([
            'user_id' => $siswa->id,
            'classroom_id' => ($classroom ?? Classroom::factory()->create())->id,
        ]);

        if ($checkedIn) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'date' => now()->toDateString(),
                'check_in_at' => now(),
                'status' => AttendanceStatus::Present,
            ]);
        }

        return [$siswa, $student];
    }

    /**
     * Kuis berisi sejumlah soal, semuanya dengan kunci jawaban "A".
     */
    private function quizWithQuestions(Classroom $classroom, int $questionCount = 2, int $pointsEach = 1): Quiz
    {
        $subject = Subject::factory()->create();
        $quiz = Quiz::factory()->create([
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
        ]);

        $questions = QuizQuestion::factory($questionCount)->create([
            'subject_id' => $subject->id,
            'correct_answer' => 'A',
            'points' => $pointsEach,
        ]);

        $quiz->questions()->attach(
            $questions->values()->mapWithKeys(fn (QuizQuestion $question, int $index) => [
                $question->id => ['sort_order' => $index + 1, 'points' => $pointsEach],
            ])->all()
        );

        return $quiz;
    }

    public function test_guru_can_open_the_question_bank(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $subject = Subject::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => Classroom::factory()->create()->id]);

        $this->actingAs($guruUser)->get(route('guru.bank-soal'))->assertOk();
    }

    public function test_guru_can_add_a_question_for_a_subject_they_teach(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $subject = Subject::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => Classroom::factory()->create()->id]);

        $response = $this->actingAs($guruUser)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $subject->id,
            'question' => 'Berapa hasil 2 + 2?',
            'options' => ['A' => '4', 'B' => '3', 'C' => '5', 'D' => '6'],
            'correct_answer' => 'A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quiz_questions', [
            'question' => 'Berapa hasil 2 + 2?',
            'correct_answer' => 'A',
            'created_by' => $guruUser->id,
        ]);
    }

    public function test_guru_cannot_add_a_question_for_a_subject_they_do_not_teach(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $guruUser->id]);
        $otherSubject = Subject::factory()->create();

        $response = $this->actingAs($guruUser)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $otherSubject->id,
            'question' => 'Soal titipan.',
            'options' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_answer' => 'A',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('quiz_questions', 0);
    }

    public function test_guru_without_a_teacher_profile_sees_no_subjects_or_classrooms_in_the_question_bank(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        Subject::factory()->create(['name' => 'Mapel Siapapun']);
        Classroom::factory()->create(['name' => 'Kelas Siapapun']);

        $response = $this->actingAs($guruUser)->get(route('guru.bank-soal'));

        $response->assertOk();
        $response->assertDontSee('Mapel Siapapun');
        $response->assertDontSee('Kelas Siapapun');
    }

    public function test_guru_without_a_teacher_profile_cannot_add_a_question_for_any_subject(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($guruUser)->post(route('guru.bank-soal.simpan'), [
            'subject_id' => $subject->id,
            'question' => 'Soal titipan.',
            'options' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'],
            'correct_answer' => 'A',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('quiz_questions', 0);
    }

    public function test_guru_without_a_teacher_profile_cannot_create_any_quiz(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($guruUser)->post(route('guru.kuis.store'), [
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Titipan',
            'duration_minutes' => 30,
            'question_ids' => [$question->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('quizzes', ['title' => 'Kuis Titipan']);
    }

    public function test_student_sees_published_quizzes_for_their_own_classroom_only(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom);

        $ownQuiz = $this->quizWithQuestions($classroom);
        $otherQuiz = $this->quizWithQuestions(Classroom::factory()->create());
        $draftQuiz = Quiz::factory()->draft()->create([
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Draft Rahasia',
        ]);

        $response = $this->actingAs($siswa)->get(route('siswa.kuis'));

        $response->assertOk();
        $response->assertSee($ownQuiz->title);
        $response->assertDontSee($otherQuiz->title);
        $response->assertDontSee($draftQuiz->title);
    }

    public function test_starting_a_quiz_creates_one_attempt_and_reusing_it_does_not_duplicate(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertOk();
        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertOk();

        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_student_cannot_start_a_quiz_from_another_classroom(): void
    {
        [$siswa] = $this->siswaInClassroom();
        $foreignQuiz = $this->quizWithQuestions(Classroom::factory()->create());

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $foreignQuiz))->assertForbidden();
    }

    public function test_answering_saves_the_answer_and_marks_it_correct(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $question = $quiz->questions()->first();

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();

        $response = $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $question->id,
            'answer' => 'A',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
            'awarded_points' => 1,
        ]);
    }

    public function test_a_wrong_answer_earns_no_points(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $question = $quiz->questions()->first();

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();

        $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $question->id,
            'answer' => 'B',
        ]);

        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'is_correct' => false,
            'awarded_points' => 0,
        ]);
    }

    public function test_submitting_scores_the_attempt(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom, questionCount: 2);
        $questions = $quiz->questions()->get();

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();

        $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $questions[0]->id,
            'answer' => 'A',
        ]);
        $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $questions[1]->id,
            'answer' => 'C',
        ]);

        $this->actingAs($siswa)->post(route('siswa.kuis.submit', $attempt))->assertRedirect();

        $attempt->refresh();
        $this->assertSame('submitted', $attempt->status);
        $this->assertSame('50.00', $attempt->score);
        $this->assertSame(1, $attempt->correct_answers);
        $this->assertSame(2, $attempt->total_questions);
    }

    public function test_a_submitted_quiz_cannot_be_submitted_again(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();

        $this->actingAs($siswa)->post(route('siswa.kuis.submit', $attempt));
        $response = $this->actingAs($siswa)->post(route('siswa.kuis.submit', $attempt));

        $response->assertStatus(422);
    }

    public function test_a_siswa_cannot_answer_another_students_attempt(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $question = $quiz->questions()->first();

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();

        [$otherSiswa] = $this->siswaInClassroom($classroom);

        $response = $this->actingAs($otherSiswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $question->id,
            'answer' => 'A',
        ]);

        $response->assertForbidden();
    }

    public function test_answers_are_rejected_after_the_time_limit_has_passed(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $quiz->update(['duration_minutes' => 10]);
        $question = $quiz->questions()->first();

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();
        $attempt->update(['started_at' => now()->subMinutes(11)]);

        $response = $this->actingAs($siswa)->post(route('siswa.kuis.answer', $attempt), [
            'quiz_question_id' => $question->id,
            'answer' => 'A',
        ]);

        // Waktu habis: jawaban baru ditolak dan kuis langsung dikumpulkan.
        $response->assertRedirect(route('siswa.kuis'));
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertSame('submitted', $attempt->fresh()->status);
    }

    public function test_student_cannot_start_a_quiz_the_teacher_has_not_opened(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $quiz->update(['is_open' => false]);

        $response = $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));

        $response->assertRedirect(route('siswa.kuis'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_guru_can_create_a_quiz_for_a_subject_and_classroom_they_teach(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($guruUser)->post(route('guru.kuis.store'), [
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Harian',
            'duration_minutes' => 30,
            'question_ids' => [$question->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quizzes', ['title' => 'Kuis Harian', 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'mode' => 'live']);
    }

    public function test_every_created_quiz_is_kahoot_mode_even_if_a_different_mode_is_submitted(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id]);

        $this->actingAs($guruUser)->post(route('guru.kuis.store'), [
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Mandiri Titipan',
            'duration_minutes' => 30,
            'mode' => 'async',
            'question_ids' => [$question->id],
        ]);

        $quiz = Quiz::where('title', 'Kuis Mandiri Titipan')->firstOrFail();
        $this->assertTrue($quiz->isLive());
    }

    public function test_guru_cannot_create_a_quiz_for_a_classroom_they_do_not_teach(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $subject = Subject::factory()->create();
        $taughtClassroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $taughtClassroom->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($guruUser)->post(route('guru.kuis.store'), [
            'subject_id' => $subject->id,
            'classroom_id' => $otherClassroom->id,
            'title' => 'Kuis Titipan',
            'duration_minutes' => 30,
            'question_ids' => [$question->id],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('quizzes', ['title' => 'Kuis Titipan']);
    }

    public function test_admin_can_create_a_quiz_for_any_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id]);

        $response = $this->actingAs($admin)->post(route('guru.kuis.store'), [
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Admin',
            'duration_minutes' => 30,
            'question_ids' => [$question->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quizzes', ['title' => 'Kuis Admin']);
    }

    public function test_guru_can_open_and_close_their_quiz(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create();
        $quiz = $this->quizWithQuestions($classroom);
        $quiz->update(['created_by' => $guruUser->id, 'is_open' => false]);

        $this->actingAs($guruUser)->post(route('guru.kuis.toggle-open', $quiz))->assertRedirect();
        $this->assertTrue($quiz->fresh()->is_open);

        $this->actingAs($guruUser)->post(route('guru.kuis.toggle-open', $quiz))->assertRedirect();
        $this->assertFalse($quiz->fresh()->is_open);
    }

    public function test_guru_cannot_open_a_quiz_made_by_another_teacher(): void
    {
        $owner = User::factory()->create(['role' => 'guru']);
        $otherGuru = User::factory()->create(['role' => 'guru']);
        $quiz = $this->quizWithQuestions(Classroom::factory()->create());
        $quiz->update(['created_by' => $owner->id, 'is_open' => false]);

        $this->actingAs($otherGuru)->post(route('guru.kuis.toggle-open', $quiz))->assertForbidden();
        $this->assertFalse($quiz->fresh()->is_open);
    }

    public function test_a_draft_quiz_cannot_be_opened(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $quiz = Quiz::factory()->draft()->create(['created_by' => $guruUser->id]);

        $this->actingAs($guruUser)->post(route('guru.kuis.toggle-open', $quiz))->assertStatus(422);
        $this->assertFalse($quiz->fresh()->is_open);
    }

    public function test_opening_the_quiz_alone_is_not_enough_without_attendance(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        // Kuis dibuka guru, tapi siswanya belum absen pagi.
        $this->assertTrue($quiz->fresh()->is_open);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertRedirect(route('siswa.kuis'));
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_quiz_list_shows_waiting_for_teacher_when_not_opened(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);
        $quiz->update(['is_open' => false]);

        $response = $this->actingAs($siswa)->get(route('siswa.kuis'));

        $response->assertOk();
        $response->assertSee('Belum Dibuka Guru');
        $response->assertDontSee('Mulai Kuis');
    }

    public function test_student_cannot_start_a_quiz_before_checking_in_today(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        $response = $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));

        $response->assertRedirect(route('siswa.kuis'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_student_can_start_a_quiz_after_checking_in_today(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => AttendanceStatus::Present,
        ]);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertOk();
        $this->assertDatabaseCount('quiz_attempts', 1);
    }

    public function test_being_late_still_counts_as_checked_in(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => AttendanceStatus::Late,
        ]);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertOk();
    }

    public function test_an_excused_absence_does_not_unlock_the_quiz(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        // Izin yang disetujui tidak punya jam scan masuk.
        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'check_in_at' => null,
            'check_out_at' => null,
            'status' => AttendanceStatus::Excused,
        ]);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertRedirect(route('siswa.kuis'));
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_yesterdays_attendance_does_not_unlock_todays_quiz(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa, $student] = $this->siswaInClassroom($classroom, checkedIn: false);
        $quiz = $this->quizWithQuestions($classroom);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->subDay()->toDateString(),
            'check_in_at' => now()->subDay(),
            'status' => AttendanceStatus::Present,
        ]);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz))->assertRedirect(route('siswa.kuis'));
    }

    public function test_quiz_list_warns_when_the_student_has_not_checked_in(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom, checkedIn: false);
        $this->quizWithQuestions($classroom);

        $response = $this->actingAs($siswa)->get(route('siswa.kuis'));

        $response->assertOk();
        $response->assertSee('Belum absen hari ini.');
        $response->assertSee('Absen Dulu');
        $response->assertDontSee('Mulai Kuis');
    }

    public function test_siswa_can_see_their_submitted_quiz_results(): void
    {
        $classroom = Classroom::factory()->create();
        [$siswa] = $this->siswaInClassroom($classroom);
        $quiz = $this->quizWithQuestions($classroom);

        $this->actingAs($siswa)->get(route('siswa.kuis.start', $quiz));
        $attempt = QuizAttempt::first();
        $this->actingAs($siswa)->post(route('siswa.kuis.submit', $attempt));

        $response = $this->actingAs($siswa)->get(route('siswa.hasil-kuis'));

        $response->assertOk();
        $response->assertSee($quiz->title);
    }
}
