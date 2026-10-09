<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;
use App\Notifications\NewFeedbackSubmitted;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_feedback_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Feedback::factory()->create();

        $this->actingAs($admin)->get(route('admin.kritik-saran'))->assertOk();
    }

    public function test_guru_is_forbidden_from_viewing_the_feedback_list(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.kritik-saran'))->assertForbidden();
    }

    public function test_viewing_the_feedback_list_marks_notifications_as_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new NewFeedbackSubmitted(Feedback::factory()->create()));

        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());

        $this->actingAs($admin)->get(route('admin.kritik-saran'));

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_student_can_submit_feedback_and_admin_sees_the_student_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '6A']);
        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'classroom_id' => $classroom->id,
            'name' => 'Aisyah Putri',
        ]);

        $this->actingAs($studentUser)->get(route('siswa.kritik-saran'))
            ->assertOk()
            ->assertSee('Sampaikan masukan kepada admin sekolah');

        $this->actingAs($studentUser)->post(route('siswa.kritik-saran.store'), [
            'message' => 'Mohon menambah tempat sampah di kelas.',
        ])->assertRedirect(route('siswa.kritik-saran'));

        $this->assertDatabaseHas('feedbacks', [
            'student_id' => $student->id,
            'guardian_id' => null,
            'message' => 'Mohon menambah tempat sampah di kelas.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => NewFeedbackSubmitted::class,
        ]);

        $this->actingAs($admin)->get(route('admin.kritik-saran'))
            ->assertOk()
            ->assertSee('Aisyah Putri')
            ->assertSee('6A')
            ->assertSee('Mohon menambah tempat sampah di kelas.');
    }

    public function test_student_feedback_requires_a_message(): void
    {
        $studentUser = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $studentUser->id]);

        $this->actingAs($studentUser)->post(route('siswa.kritik-saran.store'), [])
            ->assertSessionHasErrors(['message' => 'Pesan kritik atau saran wajib diisi.']);

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_student_without_a_linked_student_record_cannot_submit_feedback(): void
    {
        $studentUser = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($studentUser)->post(route('siswa.kritik-saran.store'), [
            'message' => 'Pesan tanpa data siswa.',
        ])->assertRedirect(route('siswa.dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('feedbacks', 0);
    }
}
