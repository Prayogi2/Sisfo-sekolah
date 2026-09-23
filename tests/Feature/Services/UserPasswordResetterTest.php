<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\UserPasswordResetter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordResetterTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_reset_sets_a_new_hashed_password_and_returns_the_plain_value(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $oldHash = $user->password;

        $newPassword = (new UserPasswordResetter)->reset($user);

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check($newPassword, $user->password));
    }

    public function test_reset_never_reveals_the_previous_password(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $newPassword = (new UserPasswordResetter)->reset($user);

        $this->assertNotSame($user->password, $newPassword);
    }
}
