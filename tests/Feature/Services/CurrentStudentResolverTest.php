<?php

namespace Tests\Feature\Services;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\CurrentStudentResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    private function waliUser(): User
    {
        return User::factory()->create(['role' => 'wali']);
    }

    public function test_returns_empty_choices_for_wali_without_children(): void
    {
        $user = $this->waliUser();

        $this->assertTrue($this->resolver->choices($user)->isEmpty());
        $this->assertNull($this->resolver->resolve($user));
    }

    public function test_auto_resolves_the_only_child_without_needing_a_selection(): void
    {
        $user = $this->waliUser();
        $guardian = Guardian::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        $resolved = $this->resolver->resolve($user);

        $this->assertNotNull($resolved);
        $this->assertSame($student->id, $resolved->id);
    }

    public function test_returns_null_for_multiple_children_until_one_is_selected(): void
    {
        $user = $this->waliUser();
        $guardian = Guardian::factory()->create([
            'user_id' => $user->id,
            'relationship' => GuardianRelationship::Guardian,
        ]);
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();
        $guardian->students()->attach([$studentA->id, $studentB->id]);

        $this->assertNull($this->resolver->resolve($user));

        $selected = $this->resolver->select($user, $studentB->id);

        $this->assertSame($studentB->id, $selected->id);
        $this->assertSame($studentB->id, $this->resolver->resolve($user)->id);
    }

    public function test_cannot_select_a_student_that_does_not_belong_to_the_guardian(): void
    {
        $user = $this->waliUser();
        Guardian::factory()->create(['user_id' => $user->id]);
        $otherStudent = Student::factory()->create();

        try {
            $this->resolver->select($user, $otherStudent->id);
            $this->fail('Expected an HttpException with status 403 to be thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
