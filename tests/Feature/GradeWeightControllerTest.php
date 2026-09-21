<?php

namespace Tests\Feature;

use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\GradeWeight;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GradeWeightControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current()->value,
            'assignment_weight' => 25,
            'quiz_weight' => 25,
            'midterm_weight' => 25,
            'final_weight' => 25,
            ...$overrides,
        ];
    }

    public function test_admin_can_set_custom_weights_for_a_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.data-mapel.bobot-nilai', $subject), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseHas('grade_weights', [
            'subject_id' => $subject->id,
            'assignment_weight' => 25,
            'final_weight' => 25,
        ]);
    }

    public function test_weights_must_total_exactly_one_hundred(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($admin)->put(
            route('admin.data-mapel.bobot-nilai', $subject),
            $this->payload(['assignment_weight' => 50])
        );

        $response->assertSessionHasErrors('assignment_weight');
        $this->assertDatabaseCount('grade_weights', 0);
    }

    public function test_guru_cannot_change_weights(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $subject = Subject::factory()->create();

        $this->actingAs($guru)
            ->put(route('admin.data-mapel.bobot-nilai', $subject), $this->payload())
            ->assertForbidden();
    }

    public function test_subjects_without_custom_weights_fall_back_to_the_default(): void
    {
        $subject = Subject::factory()->create();

        $weight = GradeWeight::for($subject->id, Classroom::currentAcademicYear(), Semester::current());

        $this->assertFalse($weight->exists);
        $this->assertSame(20, $weight->assignment_weight);
        $this->assertSame(30, $weight->final_weight);
        $this->assertSame(100, $weight->total());
    }
}
