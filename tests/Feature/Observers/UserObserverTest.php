<?php

namespace Tests\Feature\Observers;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_creating_a_user_assigns_the_matching_spatie_role(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->assertTrue($user->fresh()->hasRole('guru'));
    }

    public function test_changing_a_users_role_resyncs_the_spatie_role(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $user->update(['role' => 'admin']);

        $user = $user->fresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('guru'));
    }

    public function test_saving_a_user_without_changing_role_does_not_duplicate_roles(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $user->update(['name' => 'Nama Baru']);

        $this->assertSame(['admin'], $user->fresh()->getRoleNames()->all());
    }
}
