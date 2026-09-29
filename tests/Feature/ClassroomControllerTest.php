<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClassroomControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_classroom_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Classroom::factory(2)->create();

        $this->actingAs($admin)->get(route('admin.pembagian-kelas'))->assertOk();
    }

    /**
     * Saat semua siswa sudah punya kelas, "Atur Siswa" tetap harus bisa
     * memilih siswa dari kelas lain untuk dipindah — bukan daftar kosong.
     */
    public function test_atur_siswa_offers_students_from_other_classrooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = Classroom::factory()->create(['name' => '3-A']);
        $other = Classroom::factory()->create(['name' => '5-A']);
        Student::factory()->create(['name' => 'Siswa Kelas Lain', 'classroom_id' => $other->id]);
        Student::factory()->create(['name' => 'Siswa Tanpa Kelas', 'classroom_id' => null]);
        Student::factory()->create(['name' => 'Siswa Sudah Lulus', 'classroom_id' => null, 'status' => 'graduated']);

        $html = $this->actingAs($admin)->get(route('admin.pembagian-kelas'))->assertOk()->getContent();

        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $available = (new \DOMXPath($document))->query('//div[@id="modalAturSiswa'.$target->id.'"]//select[contains(@class, "select-available")]')->item(0);

        $this->assertNotNull($available);
        $this->assertStringContainsString('Siswa Tanpa Kelas', $available->textContent);
        $this->assertStringContainsString('Siswa Kelas Lain', $available->textContent);
        $this->assertStringNotContainsString('Siswa Sudah Lulus', $available->textContent);
    }

    public function test_guru_is_forbidden_from_the_classroom_list(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.pembagian-kelas'))->assertForbidden();
    }

    public function test_admin_can_create_a_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.pembagian-kelas.store'), [
            'name' => '6-C',
            'grade_level' => 6,
            'homeroom_teacher_id' => $teacher->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'name' => '6-C',
            'academic_year' => Classroom::currentAcademicYear(),
            'capacity' => Classroom::DEFAULT_CAPACITY,
        ]);
    }

    public function test_creating_a_classroom_can_directly_place_the_listed_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$first, $second] = Student::factory(2)->create(['classroom_id' => null]);

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.store'), [
            'name' => '1-B',
            'grade_level' => 1,
            'student_ids' => [$first->id, $second->id],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $classroom = Classroom::query()->where('name', '1-B')->sole();
        $this->assertSame($classroom->id, $first->fresh()->classroom_id);
        $this->assertSame($classroom->id, $second->fresh()->classroom_id);
    }

    /**
     * Siswa yang sudah punya kelas ditolak (tidak dipindah) — dicek ulang di
     * server walau pencarian AJAX sebelumnya sudah lolos.
     */
    public function test_creating_a_classroom_rejects_a_student_already_in_another_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherClassroom = Classroom::factory()->create(['name' => '5-A']);
        $student = Student::factory()->create(['name' => 'Budi', 'classroom_id' => $otherClassroom->id]);

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.store'), [
            'name' => '1-B',
            'grade_level' => 1,
            'student_ids' => [$student->id],
        ])->assertSessionHasErrors(['student_ids' => 'Budi sudah terdaftar di kelas 5-A.']);

        $this->assertDatabaseMissing('classrooms', ['name' => '1-B']);
        $this->assertSame($otherClassroom->id, $student->fresh()->classroom_id);
    }

    public function test_student_lookup_by_nisn_or_nis(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherClassroom = Classroom::factory()->create(['name' => '5-A']);
        $free = Student::factory()->create(['name' => 'Siti', 'nisn' => '0012345678', 'nis' => 'NIS-01', 'classroom_id' => null]);
        Student::factory()->create(['name' => 'Budi', 'nisn' => '2222222222', 'classroom_id' => $otherClassroom->id]);
        Student::factory()->create(['name' => 'Rina', 'nisn' => '3333333333', 'classroom_id' => null, 'status' => 'graduated']);
        $lookup = fn (string $keyword) => $this->actingAs($admin)->getJson(route('admin.pembagian-kelas.cari-siswa', ['q' => $keyword]));

        $lookup('0012345678')->assertOk()->assertJsonPath('student.id', $free->id)->assertJsonPath('student.name', 'Siti');
        $lookup('12345678')->assertOk()->assertJsonPath('student.id', $free->id);
        $lookup('NIS-01')->assertOk()->assertJsonPath('student.id', $free->id);
        $lookup('1234567890')->assertNotFound()->assertJsonPath('message', 'NISN/NIS 1234567890 tidak ditemukan.');
        $lookup('2222222222')->assertUnprocessable()->assertJsonPath('message', 'Budi sudah terdaftar di kelas 5-A.');
        $lookup('3333333333')->assertUnprocessable()->assertJsonPath('message', 'Rina berstatus Lulus, bukan siswa aktif.');
    }

    public function test_guru_cannot_use_the_student_lookup(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->getJson(route('admin.pembagian-kelas.cari-siswa', ['q' => '1234567890']))->assertForbidden();
    }

    public function test_creating_a_classroom_requires_unique_name_within_the_same_academic_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Classroom::factory()->create(['name' => '6-C', 'academic_year' => Classroom::currentAcademicYear()]);

        $response = $this->actingAs($admin)->post(route('admin.pembagian-kelas.store'), [
            'name' => '6-C',
            'grade_level' => 6,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_delete_a_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.pembagian-kelas.destroy', $classroom));

        $response->assertRedirect();
        $this->assertModelMissing($classroom);
    }

    public function test_admin_can_assign_students_to_a_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['capacity' => 30]);
        $students = Student::factory(3)->create();

        $response = $this->actingAs($admin)->post(
            route('admin.pembagian-kelas.assign-students', $classroom),
            ['student_ids' => $students->pluck('id')->all()]
        );

        $response->assertRedirect();
        foreach ($students as $student) {
            $this->assertDatabaseHas('students', ['id' => $student->id, 'classroom_id' => $classroom->id]);
        }
    }

    public function test_assigning_a_student_unassigns_them_from_their_previous_classroom_membership(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['capacity' => 30]);
        $stayingStudent = Student::factory()->create(['classroom_id' => $classroom->id]);
        $removedStudent = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($admin)->post(
            route('admin.pembagian-kelas.assign-students', $classroom),
            ['student_ids' => [$stayingStudent->id]]
        );

        $this->assertDatabaseHas('students', ['id' => $stayingStudent->id, 'classroom_id' => $classroom->id]);
        $this->assertDatabaseHas('students', ['id' => $removedStudent->id, 'classroom_id' => null]);
    }

    public function test_assigning_more_students_than_capacity_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['capacity' => 1]);
        $students = Student::factory(2)->create();

        $response = $this->actingAs($admin)->post(
            route('admin.pembagian-kelas.assign-students', $classroom),
            ['student_ids' => $students->pluck('id')->all()]
        );

        $response->assertSessionHasErrors('student_ids');
    }
}
