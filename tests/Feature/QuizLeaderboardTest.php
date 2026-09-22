<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\Semester;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\QuizAttemptFinalizer;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class QuizLeaderboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function waliFor(Student $student): User
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $guardian->students()->attach($student);

        Attendance::factory()->create([
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'check_in_at' => now(),
            'status' => AttendanceStatus::Present,
        ]);

        return $wali;
    }

    public function test_leaderboard_is_ordered_by_average_score(): void
    {
        $classroom = Classroom::factory()->create();
        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id]);

        $best = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siswa Terbaik']);
        $middle = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siswa Tengah']);
        $last = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siswa Terakhir']);

        foreach ([[$best, 95], [$middle, 80], [$last, 60]] as [$student, $score]) {
            QuizAttempt::factory()->create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'status' => 'submitted',
                'score' => $score,
            ]);
        }

        $response = $this->actingAs($this->waliFor($middle))->get(route('siswa.kuis'));

        $response->assertOk();
        $response->assertSeeInOrder(['Siswa Terbaik', 'Siswa Tengah', 'Siswa Terakhir']);
    }

    public function test_students_from_another_classroom_do_not_appear(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        QuizAttempt::factory()->create([
            'quiz_id' => Quiz::factory()->create(['classroom_id' => $classroom->id])->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'score' => 70,
        ]);

        $otherClassroom = Classroom::factory()->create();
        $outsider = Student::factory()->create(['classroom_id' => $otherClassroom->id, 'name' => 'Siswa Kelas Lain']);
        QuizAttempt::factory()->create([
            'quiz_id' => Quiz::factory()->create(['classroom_id' => $otherClassroom->id])->id,
            'student_id' => $outsider->id,
            'status' => 'submitted',
            'score' => 100,
        ]);

        $response = $this->actingAs($this->waliFor($student))->get(route('siswa.kuis'));

        $response->assertOk();
        $response->assertDontSee('Siswa Kelas Lain');
    }

    public function test_unsubmitted_attempts_are_not_ranked(): void
    {
        $classroom = Classroom::factory()->create();
        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $working = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Masih Mengerjakan']);

        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id, 'student_id' => $student->id, 'status' => 'submitted', 'score' => 70,
        ]);
        QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id, 'student_id' => $working->id, 'status' => 'in_progress', 'score' => null,
        ]);

        $response = $this->actingAs($this->waliFor($student))->get(route('siswa.kuis'));

        $response->assertDontSee('Masih Mengerjakan');
    }

    public function test_the_viewing_student_is_marked_on_the_leaderboard(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        QuizAttempt::factory()->create([
            'quiz_id' => Quiz::factory()->create(['classroom_id' => $classroom->id])->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'score' => 88,
        ]);

        $response = $this->actingAs($this->waliFor($student))->get(route('siswa.kuis'));

        $response->assertSee('Anda');
    }

    public function test_submitting_a_quiz_fills_the_quiz_component_of_the_report_card(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'correct_answer' => 'A']);
        $quiz->questions()->attach([$question->id => ['sort_order' => 1, 'points' => 1]]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => null,
        ]);
        $attempt->answers()->create([
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
            'awarded_points' => 1,
        ]);

        app(QuizAttemptFinalizer::class)->finalize($attempt);

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'semester' => Semester::current()->value,
            'quiz_score' => 100,
        ]);
    }

    public function test_the_report_card_uses_the_average_of_all_quizzes_in_that_subject(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        // Kuis pertama sudah dikumpulkan dengan nilai 60.
        $firstQuiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
        QuizAttempt::factory()->create([
            'quiz_id' => $firstQuiz->id,
            'student_id' => $student->id,
            'status' => 'submitted',
            'score' => 60,
        ]);

        // Kuis kedua dikumpulkan sekarang dengan nilai 100.
        $secondQuiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'correct_answer' => 'A']);
        $secondQuiz->questions()->attach([$question->id => ['sort_order' => 1, 'points' => 1]]);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $secondQuiz->id, 'student_id' => $student->id]);
        $attempt->answers()->create([
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
            'awarded_points' => 1,
        ]);

        app(QuizAttemptFinalizer::class)->finalize($attempt);

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'quiz_score' => 80,
        ]);
    }

    public function test_other_grade_components_are_left_untouched(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $grade = Grade::factory()->create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current(),
            'assignment_score' => 85,
            'midterm_score' => 75,
            'final_score' => 90,
            'quiz_score' => null,
        ]);

        $quiz = Quiz::factory()->create(['classroom_id' => $classroom->id, 'subject_id' => $subject->id]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'correct_answer' => 'A']);
        $quiz->questions()->attach([$question->id => ['sort_order' => 1, 'points' => 1]]);
        $attempt = QuizAttempt::factory()->create(['quiz_id' => $quiz->id, 'student_id' => $student->id]);
        $attempt->answers()->create([
            'quiz_question_id' => $question->id,
            'answer' => 'B',
            'is_correct' => false,
            'awarded_points' => 0,
        ]);

        app(QuizAttemptFinalizer::class)->finalize($attempt);

        $grade->refresh();
        $this->assertSame(85, $grade->assignment_score);
        $this->assertSame(75, $grade->midterm_score);
        $this->assertSame(90, $grade->final_score);
        $this->assertSame(0, $grade->quiz_score);
    }

    public function test_auto_submitted_quizzes_also_reach_the_report_card(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $quiz = Quiz::factory()->create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'duration_minutes' => 10,
        ]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'correct_answer' => 'A']);
        $quiz->questions()->attach([$question->id => ['sort_order' => 1, 'points' => 1]]);

        $attempt = QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinutes(30),
        ]);
        $attempt->answers()->create([
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
            'awarded_points' => 1,
        ]);

        $this->artisan('kuis:auto-kumpulkan')->assertSuccessful();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'quiz_score' => 100,
        ]);
    }
}
