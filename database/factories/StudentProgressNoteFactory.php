<?php

namespace Database\Factories;

use App\Enums\PromotionStatus;
use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentProgressNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProgressNote>
 */
class StudentProgressNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'recorded_by' => null,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current(),
            'promotion_status' => PromotionStatus::Undecided,
            'notes' => fake()->sentence(),
        ];
    }

    public function promoted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'promotion_status' => PromotionStatus::Promoted,
        ]);
    }

    public function retained(): static
    {
        return $this->state(fn (array $attributes): array => [
            'promotion_status' => PromotionStatus::Retained,
        ]);
    }
}
