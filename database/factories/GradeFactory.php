<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
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
            'subject_id' => Subject::factory(),
            'recorded_by' => null,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current(),
            'assignment_score' => fake()->numberBetween(60, 100),
            'quiz_score' => fake()->numberBetween(60, 100),
            'midterm_score' => fake()->numberBetween(60, 100),
            'final_score' => fake()->numberBetween(60, 100),
        ];
    }
}
