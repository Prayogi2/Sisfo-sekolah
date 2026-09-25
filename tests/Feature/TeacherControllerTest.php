<?php

namespace Tests\Feature;

use App\Models\Classroom;
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
        $classroomA = Classroom::factory()->create();
        $classroomB = Classroom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
            'email' => 'fulan@guru.local',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroomA->id, $classroomB->id]],
            ],
        ]);

        $response->assertRedirect();
        $teacher = Teacher::where('nip', '1234567890123456')->firstOrFail();

        $this->assertNotNull($teacher->user_id);
        $this->assertSame('fulan@guru.local', $teacher->user->email);
        $this->assertTrue($teacher->user->hasRole('guru'));
        $this->assertTrue($teacher->subjects->contains($subject));
        $this->assertTrue($teacher->teaches($subject->id, $classroomA->id));
        $this->assertTrue($teacher->teaches($subject->id, $classroomB->id));
    }

    public function test_admin_can_save_the_teacher_biodata(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '198001012005011001',
            'name' => 'Ustadzah Aminah, S.Pd.',
            'gender' => 'P',
            'email' => 'aminah@gmail.com',
            'phone' => '081234567890',
            'nik' => '3203014501800001',
            'birth_place' => 'Cianjur',
            'birth_date' => '1980-01-05',
            'address' => 'Kp. Sukamaju RT 01/02',
            'village' => 'Sukamaju',
            'district' => 'Cibeber',
            'province' => 'Jawa Barat',
            'last_education' => 's1',
            'blood_type' => 'O',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teachers', [
            'nik' => '3203014501800001',
            'birth_place' => 'Cianjur',
            'village' => 'Sukamaju',
            'district' => 'Cibeber',
            'province' => 'Jawa Barat',
            'last_education' => 's1',
            'blood_type' => 'O',
            'email' => 'aminah@gmail.com',
            'phone' => '081234567890',
        ]);
    }

    public function test_teacher_biodata_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();

        $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nip' => $teacher->nip,
            'name' => $teacher->name,
            'gender' => 'L',
            'nik' => '12345',
            'birth_date' => now()->addDay()->toDateString(),
            'last_education' => 'profesor',
            'blood_type' => 'Z',
        ])->assertSessionHasErrors(['nik', 'birth_date', 'last_education', 'blood_type']);
    }

    public function test_creating_a_teacher_rejects_the_same_subject_chosen_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroom->id]],
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroom->id]],
            ],
        ]);

        $response->assertSessionHasErrors('assignments');
        $this->assertDatabaseMissing('teachers', ['nip' => '1234567890123456']);
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

    public function test_admin_can_update_a_teacher_and_resync_assignments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $oldSubject = Subject::factory()->create();
        $newSubject = Subject::factory()->create();
        $oldClassroom = Classroom::factory()->create();
        $newClassroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $oldSubject->id, 'classroom_id' => $oldClassroom->id]);

        $response = $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nip' => $teacher->nip,
            'name' => 'Nama Baru',
            'gender' => $teacher->gender->value,
            'assignments' => [
                ['subject_id' => $newSubject->id, 'classroom_ids' => [$newClassroom->id]],
            ],
        ]);

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertSame('Nama Baru', $teacher->name);
        $this->assertTrue($teacher->subjects->contains($newSubject));
        $this->assertFalse($teacher->subjects->contains($oldSubject));
        $this->assertTrue($teacher->teaches($newSubject->id, $newClassroom->id));
        $this->assertFalse($teacher->teaches($oldSubject->id, $oldClassroom->id));
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
