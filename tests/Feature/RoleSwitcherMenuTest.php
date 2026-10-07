<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Dropdown "Akses Role" di pojok kanan atas.
 */
class RoleSwitcherMenuTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_plain_admin_gets_kelola_akun_and_no_guru_shortcut(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Akses Role')
            ->assertSee('Kelola Akun')
            ->assertDontSee('Kelola Password')
            ->assertDontSee(route('guru.dashboard'), false);
    }

    /**
     * Guru yang diangkat jadi admin tetap butuh jalan kembali ke portal
     * gurunya — tanpa tautan ini ia terkunci di menu admin.
     */
    public function test_a_teacher_promoted_to_admin_keeps_the_way_back_to_the_guru_portal(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);
        $user->assignRole('admin');

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Portal Guru Saya')
            ->assertSee(route('guru.dashboard'), false);
    }

    public function test_a_guru_without_admin_access_sees_no_role_switcher(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('guru.dashboard'))
            ->assertOk()
            ->assertDontSee('Akses Role')
            ->assertDontSee('Kelola Akun')
            ->assertDontSee(route('admin.dashboard'), false);
    }

    public function test_a_siswa_sees_no_role_switcher(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $siswa->id]);

        $this->actingAs($siswa)->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertDontSee('Akses Role')
            ->assertDontSee(route('admin.akun'), false);
    }
}
