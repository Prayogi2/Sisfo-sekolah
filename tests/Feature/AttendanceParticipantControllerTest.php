<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceParticipantControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_page_lists_the_eight_attendance_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $homeroom = Teacher::factory()->create(['name' => 'Ustadz Wali']);
        $classroom = Classroom::factory()->create(['name' => '2-A', 'academic_year' => '2026/2027', 'homeroom_teacher_id' => $homeroom->id]);
        $student = Student::factory()->create(['name' => 'Siti Aminah', 'nisn' => '0098761234', 'nis' => 'NIS-01', 'classroom_id' => $classroom->id, 'address' => 'Kp. Sukamaju']);

        $this->actingAs($admin)->get(route('admin.peserta-presensi'))
            ->assertOk()
            ->assertSeeInOrder(['Siti Aminah', '2-A', 'Ustadz Wali', '2026/2027', substr($student->qr_token, 0, 10), '0098761234', 'NIS-01', 'Kp. Sukamaju', 'Aktif']);
    }

    public function test_adding_a_participant_creates_one_student_with_qr_and_login(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)->post(route('admin.peserta-presensi.store'), [
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'nisn' => '1234567890',
            'nis' => '2026001',
            'classroom_id' => $classroom->id,
            'address' => 'Jl. Melati 1',
            'status' => 'active',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $student = Student::query()->where('nisn', '1234567890')->sole();
        $this->assertSame($classroom->id, $student->classroom_id);
        $this->assertNotEmpty($student->qr_token);
        $this->assertNotNull($student->user_id);
        $this->assertSame(1, Student::query()->count());
    }

    /**
     * Edit dari halaman presensi memakai data siswa yang sama, dan tidak
     * boleh menghapus data Buku Induk yang tidak ada di form ringkas ini.
     */
    public function test_editing_from_the_attendance_page_keeps_buku_induk_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['birth_place' => 'Cianjur']);
        StudentProfile::factory()->create(['student_id' => $student->id, 'nickname' => 'Nana']);

        $this->actingAs($admin)->from(route('admin.peserta-presensi'))->put(route('admin.data-siswa.update', $student), [
            'name' => 'Nama Diperbarui',
            'gender' => $student->gender->value,
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'address' => 'Alamat Baru',
            'status' => StudentStatus::Inactive->value,
        ])->assertRedirect(route('admin.peserta-presensi'));

        $student->refresh();
        $this->assertSame('Nama Diperbarui', $student->name);
        $this->assertSame(StudentStatus::Inactive, $student->status);
        $this->assertSame('Cianjur', $student->birth_place);
        $this->assertDatabaseHas('student_profiles', ['student_id' => $student->id, 'nickname' => 'Nana']);
    }

    public function test_guru_cannot_open_or_add_attendance_participants(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.peserta-presensi'))->assertForbidden();
        $this->actingAs($guru)->post(route('admin.peserta-presensi.store'), ['name' => 'Palsu'])->assertForbidden();
        $this->assertSame(0, Student::query()->count());
    }
}
