<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\GradeWeight;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeWeight>
 */
class GradeWeightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current(),
            ...GradeWeight::DEFAULT_WEIGHTS,
        ];
    }
}
