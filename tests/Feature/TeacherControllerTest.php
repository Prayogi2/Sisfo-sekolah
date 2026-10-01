<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Student;
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
        $this->assertFalse($teacher->user->hasRole('admin'));
    }

    public function test_admin_can_create_a_teacher_who_is_also_an_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '9876543210',
            'name' => 'Operator Sekolah',
            'gender' => 'P',
            'email' => 'operator@guru.local',
            'is_admin' => '1',
        ])->assertSessionHas('success', fn (string $message) => str_starts_with($message, 'Guru berhasil ditambahkan sebagai guru sekaligus administrator. Login: operator@guru.local'));

        $user = Teacher::where('nip', '9876543210')->sole()->user;
        $this->assertTrue($user->hasRole('guru'));
        $this->assertTrue($user->hasRole('admin'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
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

    /**
     * @return array{0: User, 1: Teacher}
     */
    private function teacherWithAccount(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, $teacher];
    }

    public function test_admin_can_make_a_teacher_an_admin_who_keeps_the_guru_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $teacher] = $this->teacherWithAccount();

        $this->actingAs($admin)->post(route('admin.data-guru.admin-access.grant', $teacher))
            ->assertSessionHas('success', "{$teacher->name} sekarang juga memiliki akses admin. Menu admin bisa dibuka lewat Akses Role di pojok kanan atas.");

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('guru'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_teacher_without_a_login_account_cannot_be_made_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create(['user_id' => null]);

        $this->actingAs($admin)->post(route('admin.data-guru.admin-access.grant', $teacher))
            ->assertSessionHas('error', "{$teacher->name} belum memiliki akun login, jadi belum bisa dijadikan admin.");
    }

    public function test_guru_cannot_grant_admin_access(): void
    {
        [$guru] = $this->teacherWithAccount();
        [$otherUser, $otherTeacher] = $this->teacherWithAccount();

        $this->actingAs($guru)->post(route('admin.data-guru.admin-access.grant', $otherTeacher))->assertForbidden();

        $this->assertFalse($otherUser->fresh()->hasRole('admin'));
    }

    public function test_admin_can_revoke_admin_access_from_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');

        $this->actingAs($admin)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('success', "Akses admin {$teacher->name} dicabut. Akunnya kembali hanya sebagai guru.");

        $user->refresh();
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('guru'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_a_teacher_admin_cannot_revoke_their_own_admin_access(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');

        $this->actingAs($user)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('error', 'Anda tidak bisa mencabut akses admin akun Anda sendiri.');

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_the_primary_admin_account_cannot_be_revoked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $primaryAdmin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create(['user_id' => $primaryAdmin->id]);

        $this->actingAs($admin)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('error', "Akun {$teacher->name} adalah akun admin utama, aksesnya tidak bisa dicabut dari sini.");

        $this->assertTrue($primaryAdmin->fresh()->hasRole('admin'));
    }

    public function test_a_teacher_with_admin_access_still_gets_the_guru_pages_under_guru_urls(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');
        $homeroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $ownStudent = Student::factory()->create(['classroom_id' => $homeroom->id]);
        $otherStudent = Student::factory()->create();
        LeaveRequest::factory()->create(['student_id' => $ownStudent->id]);
        LeaveRequest::factory()->create(['student_id' => $otherStudent->id]);

        $this->actingAs($user)->get(route('guru.data-guru'))->assertViewIs('guru.data-guru');
        $this->actingAs($user)->get(route('guru.bank-soal'))->assertViewIs('guru.bank-soal');
        $this->actingAs($user)->get(route('guru.approval-izin'))
            ->assertViewIs('guru.approval-izin')
            ->assertSee($ownStudent->name)
            ->assertDontSee($otherStudent->name);

        // Di halaman admin, dia melihat semua data seperti admin.
        $this->actingAs($user)->get(route('admin.approval-izin'))
            ->assertViewIs('admin.approval-izin')
            ->assertSee($otherStudent->name);
    }
}
