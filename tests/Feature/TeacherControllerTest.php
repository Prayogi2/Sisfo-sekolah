<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_sees_the_admin_teacher_view(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.data-guru'))
            ->assertOk()
            ->assertViewIs('admin.data-guru');
    }

    public function test_guru_sees_the_read_only_guru_teacher_view(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('guru.data-guru'))
            ->assertOk()
            ->assertViewIs('guru.data-guru');
    }

    public function test_admin_can_create_a_teacher_with_a_login_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
            'subject_ids' => [$subject->id],
        ]);

        $response->assertRedirect();
        $teacher = Teacher::where('nip', '1234567890123456')->firstOrFail();

        $this->assertNotNull($teacher->user_id);
        $this->assertSame('1234567890123456', $teacher->user->username);
        $this->assertTrue($teacher->user->hasRole('guru'));
        $this->assertTrue($teacher->subjects->contains($subject));
    }

    public function test_guru_cannot_create_a_teacher(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teachers', ['nip' => '1234567890123456']);
    }

    public function test_admin_can_update_a_teacher_and_resync_subjects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $oldSubject = Subject::factory()->create();
        $newSubject = Subject::factory()->create();
        $teacher->subjects()->attach($oldSubject);

        $response = $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nip' => $teacher->nip,
            'name' => 'Nama Baru',
            'gender' => $teacher->gender->value,
            'subject_ids' => [$newSubject->id],
        ]);

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertSame('Nama Baru', $teacher->name);
        $this->assertTrue($teacher->subjects->contains($newSubject));
        $this->assertFalse($teacher->subjects->contains($oldSubject));
    }

    public function test_admin_can_reset_a_teachers_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->for(User::factory()->state(['role' => 'guru']))->create();
        $oldHash = $teacher->user->password;

        $response = $this->actingAs($admin)->post(route('admin.data-guru.reset-password', $teacher));

        $response->assertRedirect();
        $this->assertNotSame($oldHash, $teacher->user->fresh()->password);
    }

    public function test_admin_can_delete_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.data-guru.destroy', $teacher));

        $response->assertRedirect();
        $this->assertModelMissing($teacher);
    }
}
