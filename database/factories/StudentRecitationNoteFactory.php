<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\StudentRecitationNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentRecitationNote>
 */
class StudentRecitationNoteFactory extends Factory
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
            'type' => fake()->randomElement(['memorization', 'reading']),
            'material' => 'Surah Al-Mulk',
            'achievement' => 'Ayat 1-10 lancar',
            'recorded_at' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
