<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guru_logs_in_with_the_new_password_after_changing_it(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'email' => 'guru@sekolah.test', 'password' => 'password123']);

        $this->actingAs($guru)->put(route('account.password.update'), [
            'current_password' => 'password123',
            'password' => 'PasswordBaru2026',
            'password_confirmation' => 'PasswordBaru2026',
        ])->assertSessionHasNoErrors()->assertSessionHas('success', 'Password berhasil diubah.');
        $this->post(route('logout'));

        $this->post('/login', ['identifier' => 'guru@sekolah.test', 'password' => 'password123'])
            ->assertSessionHas('error', 'Email/NIS atau password salah.');
        $this->assertGuest();

        $this->post('/login', ['identifier' => 'guru@sekolah.test', 'password' => 'PasswordBaru2026'])
            ->assertRedirect(route('guru.dashboard'));
        $this->assertAuthenticatedAs($guru);
    }

    public function test_password_is_not_changed_when_the_current_password_is_wrong(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'password' => 'password123']);

        $this->actingAs($guru)->put(route('account.password.update'), [
            'current_password' => 'salah',
            'password' => 'PasswordBaru2026',
            'password_confirmation' => 'PasswordBaru2026',
        ])->assertSessionHasErrors(['current_password' => 'Password saat ini salah.']);

        $this->assertTrue(password_verify('password123', $guru->fresh()->password));
    }
}
