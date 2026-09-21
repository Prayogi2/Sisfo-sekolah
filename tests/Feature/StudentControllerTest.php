<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_student_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.data-siswa'));

        $response->assertOk();
        $response->assertViewIs('admin.data-siswa');
        $response->assertViewHas('students');
    }

    public function test_guru_is_forbidden_from_the_student_list(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.data-siswa'))->assertForbidden();
    }

    public function test_search_filters_students_by_name_or_nisn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = Student::factory()->create(['name' => 'Ahmad Fauzi', 'nisn' => '1111111111']);
        Student::factory()->create(['name' => 'Siti Aminah', 'nisn' => '2222222222']);

        $response = $this->actingAs($admin)->get(route('admin.data-siswa', ['search' => 'Ahmad']));

        $response->assertOk();
        $response->assertSee($target->name);
        $response->assertDontSee('Siti Aminah');
    }

    public function test_admin_can_create_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '54321',
            'name' => 'Budi Santoso',
            'gender' => 'L',
            'classroom_id' => $classroom->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', ['nisn' => '1234567890', 'name' => 'Budi Santoso']);
    }

    public function test_creating_a_student_requires_unique_nisn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['nisn' => '1234567890']);

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '99999',
            'name' => 'Siswa Baru',
            'gender' => 'L',
        ]);

        $response->assertSessionHasErrors('nisn');
    }

    public function test_guru_cannot_create_a_student(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->post(route('admin.data-siswa.store'), [
            'nisn' => '1234567890',
            'nis' => '54321',
            'name' => 'Budi Santoso',
            'gender' => 'L',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('students', ['nisn' => '1234567890']);
    }

    public function test_admin_can_update_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['name' => 'Nama Lama']);

        $response = $this->actingAs($admin)->put(route('admin.data-siswa.update', $student), [
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'name' => 'Nama Baru',
            'gender' => $student->gender->value,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Nama Baru']);
    }

    public function test_admin_can_delete_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.data-siswa.destroy', $student));

        $response->assertRedirect();
        $this->assertModelMissing($student);
    }

    public function test_guru_cannot_delete_a_student(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $student = Student::factory()->create();

        $response = $this->actingAs($guru)->delete(route('admin.data-siswa.destroy', $student));

        $response->assertForbidden();
        $this->assertModelExists($student);
    }
}
