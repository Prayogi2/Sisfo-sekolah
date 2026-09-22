<?php

namespace Tests\Feature;

use App\Enums\LeaveRequestStatus;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeacherDashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guru_sees_their_own_quizzes_with_the_open_control(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);
        $subject = Subject::factory()->create(['name' => 'Matematika']);
        $classroom = Classroom::factory()->create();

        Quiz::factory()->closed()->create([
            'created_by' => $user->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'title' => 'Kuis Perkalian',
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertSee('Kuis Perkalian');
        $response->assertSee('Matematika');
        $response->assertSee('Buka');
    }

    public function test_another_teachers_quiz_is_not_listed(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);

        Quiz::factory()->create([
            'created_by' => User::factory()->create(['role' => 'guru'])->id,
            'title' => 'Kuis Guru Lain',
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Kuis Guru Lain');
    }

    public function test_question_bank_and_open_quiz_counts_are_per_teacher(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);
        $otherGuru = User::factory()->create(['role' => 'guru']);

        QuizQuestion::factory(3)->create(['created_by' => $user->id]);
        QuizQuestion::factory(2)->create(['created_by' => $otherGuru->id]);

        Quiz::factory()->create(['created_by' => $user->id]);
        Quiz::factory()->closed()->create(['created_by' => $user->id]);
        Quiz::factory()->create(['created_by' => $otherGuru->id]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertViewHas('stats', fn (array $stats) => $stats['bank_soal'] === 3
            && $stats['kuis_dibuka'] === 1
            && $stats['kuis_total'] === 2);
    }

    public function test_pending_leave_requests_are_counted_for_homeroom_class_only(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $homeroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $otherClass = Classroom::factory()->create();

        LeaveRequest::factory()->create([
            'student_id' => Student::factory()->create(['classroom_id' => $homeroom->id]),
            'status' => LeaveRequestStatus::Pending,
        ]);
        LeaveRequest::factory()->create([
            'student_id' => Student::factory()->create(['classroom_id' => $otherClass->id]),
            'status' => LeaveRequestStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertViewHas('stats', fn (array $stats) => $stats['izin_pending'] === 1);
    }

    public function test_a_guru_without_a_teacher_record_still_gets_a_working_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($user)->get(route('guru.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['izin_pending'] === 0);
    }

    public function test_wali_cannot_open_the_teacher_dashboard(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);

        $this->actingAs($wali)->get(route('guru.dashboard'))->assertForbidden();
    }
}
