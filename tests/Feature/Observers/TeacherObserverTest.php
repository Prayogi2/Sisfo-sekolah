<?php

namespace Tests\Feature\Observers;

use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TeacherObserverTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_creating_a_teacher_syncs_the_linked_users_username_to_the_nip(): void
    {
        $user = User::factory()->create(['role' => 'guru', 'username' => 'temp']);

        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
            'nip' => '1234567890123456',
        ]);

        $this->assertSame('1234567890123456', $teacher->user->fresh()->username);
    }

    public function test_changing_the_nip_resyncs_the_username(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id, 'nip' => '1111111111111111']);

        $teacher->update(['nip' => '2222222222222222']);

        $this->assertSame('2222222222222222', $user->fresh()->username);
    }

    public function test_teacher_without_a_linked_user_does_not_error(): void
    {
        $teacher = Teacher::factory()->create(['user_id' => null]);

        $this->assertNull($teacher->user_id);
    }
}
