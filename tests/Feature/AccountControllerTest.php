<?php

namespace Tests\Feature;

use App\Http\Controllers\AccountController;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_the_account_list_shows_the_login_id_students_actually_use(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'siswa', 'name' => 'Budi Santoso', 'username' => '2024001']);

        $this->actingAs($admin)->get(route('admin.akun'))
            ->assertOk()
            ->assertSeeInOrder(['Budi Santoso', 'Siswa', '2024001']);
    }

    public function test_admin_can_enter_a_student_account_and_return_to_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $siswa = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $siswa->id]);

        $this->actingAs($admin)->post(route('admin.akun.impersonate', $siswa))
            ->assertRedirect(route('siswa.dashboard'));
        $this->assertAuthenticatedAs($siswa);

        $this->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang masuk sebagai')
            ->assertSee('Kembali ke Admin');

        $this->post(route('account.impersonate.stop'))
            ->assertRedirect(route('admin.akun'))
            ->assertSessionMissing(AccountController::IMPERSONATOR_KEY);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_entering_a_teacher_account_lands_on_the_teacher_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)->post(route('admin.akun.impersonate', $guru))
            ->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($guru);
    }

    public function test_an_account_with_admin_access_cannot_be_entered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guruAdmin = User::factory()->create(['role' => 'guru']);
        $guruAdmin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.akun.impersonate', $guruAdmin))
            ->assertSessionHas('error', "Akun {$guruAdmin->name} juga memiliki akses admin, jadi tidak bisa dimasuki dari sini.");
        $this->assertAuthenticatedAs($admin);
    }

    public function test_guru_cannot_enter_another_account(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($guru)->post(route('admin.akun.impersonate', $siswa))->assertForbidden();
        $this->assertAuthenticatedAs($guru);
    }

    public function test_returning_to_admin_without_entering_an_account_is_forbidden(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->post(route('account.impersonate.stop'))->assertForbidden();
        $this->assertAuthenticatedAs($siswa);
    }

    public function test_password_cannot_be_changed_while_admin_is_inside_the_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru', 'password' => 'password123']);

        $this->actingAs($admin)->post(route('admin.akun.impersonate', $guru));
        $this->put(route('account.password.update'), [
            'current_password' => 'password123',
            'password' => 'PasswordBaru2026',
            'password_confirmation' => 'PasswordBaru2026',
        ])->assertSessionHas('error', 'Password tidak bisa diganti saat admin sedang masuk sebagai pengguna ini.');

        $this->assertTrue(password_verify('password123', $guru->fresh()->password));
    }
}
