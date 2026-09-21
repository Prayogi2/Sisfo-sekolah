<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeLevel = fake()->numberBetween(10, 12);
        $startYear = fake()->numberBetween(2020, 2026);

        return [
            'name' => "{$gradeLevel} ".fake()->randomElement(['IPA', 'IPS']).' '.fake()->numberBetween(1, 3),
            'grade_level' => $gradeLevel,
            'academic_year' => "{$startYear}/".($startYear + 1),
            'homeroom_teacher_id' => null,
        ];
    }
}
