<?php

namespace Tests\Feature\Services;

use App\Models\Student;
use App\Models\User;
use App\Services\CurrentStudentResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CurrentStudentResolverTest extends TestCase
{
    use LazilyRefreshDatabase;

    private CurrentStudentResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->resolver = new CurrentStudentResolver;
    }

    public function test_resolves_the_student_linked_to_a_siswa_account(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $user->id]);

        $this->assertSame($student->id, $this->resolver->resolve($user)?->id);
    }

    public function test_returns_null_for_a_siswa_account_without_student_data(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);

        $this->assertNull($this->resolver->resolve($user));
    }

    public function test_returns_null_for_non_siswa_accounts(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Student::factory()->create(['user_id' => $user->id]);

        $this->assertNull($this->resolver->resolve($user));
    }
}
