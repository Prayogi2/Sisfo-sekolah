<?php

namespace Tests\Feature;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentPortalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_wali_with_a_single_child_sees_the_digital_card_directly(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        $response = $this->actingAs($wali)->get(route('siswa.kartu-digital'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee($student->qr_token);
    }

    public function test_wali_with_multiple_children_is_redirected_to_pilih_anak(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create([
            'user_id' => $wali->id,
            'relationship' => GuardianRelationship::Guardian,
        ]);
        $guardian->students()->attach(Student::factory(2)->create());

        $response = $this->actingAs($wali)->get(route('siswa.kartu-digital'));

        $response->assertRedirect(route('pilih-anak.index'));
    }

    public function test_after_selecting_a_child_the_wali_can_access_the_page(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create([
            'user_id' => $wali->id,
            'relationship' => GuardianRelationship::Guardian,
        ]);
        $students = Student::factory(2)->create();
        $guardian->students()->attach($students);

        $this->actingAs($wali)->get(route('siswa.kartu-digital'));
        $this->actingAs($wali)->get(route('pilih-anak.select', $students->first()));
        $response = $this->actingAs($wali)->get(route('siswa.kartu-digital'));

        $response->assertOk();
        $response->assertSee($students->first()->name);
    }

    public function test_wali_cannot_select_a_child_that_is_not_theirs(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        Guardian::factory()->create(['user_id' => $wali->id]);
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($wali)->get(route('pilih-anak.select', $otherStudent));

        $response->assertForbidden();
    }

    public function test_guardian_attendance_page_shows_the_current_childs_history(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        $response = $this->actingAs($wali)->get(route('wali.izin'));

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_admin_is_forbidden_from_the_student_portal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('siswa.kartu-digital'))->assertForbidden();
    }
}
