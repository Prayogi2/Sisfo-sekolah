<?php

namespace Tests\Feature;

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

    public function test_siswa_sees_their_own_digital_card(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id]);

        $response = $this->actingAs($siswa)->get(route('siswa.kartu-digital'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee($student->qr_token);
    }

    public function test_siswa_without_student_data_is_sent_back_to_the_dashboard(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->get(route('siswa.kartu-digital'))->assertRedirect(route('siswa.dashboard'));
        $this->actingAs($siswa)->get(route('siswa.dashboard'))->assertOk()->assertSee('belum terhubung ke data siswa');
    }

    public function test_admin_is_forbidden_from_the_student_portal(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('siswa.kartu-digital'))->assertForbidden();
    }
}
