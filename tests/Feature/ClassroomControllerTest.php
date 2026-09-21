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
            'capacity' => 30,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'name' => '6-C',
            'academic_year' => Classroom::currentAcademicYear(),
        ]);
    }

    public function test_creating_a_classroom_requires_unique_name_within_the_same_academic_year(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Classroom::factory()->create(['name' => '6-C', 'academic_year' => Classroom::currentAcademicYear()]);

        $response = $this->actingAs($admin)->post(route('admin.pembagian-kelas.store'), [
            'name' => '6-C',
            'grade_level' => 6,
            'capacity' => 30,
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
