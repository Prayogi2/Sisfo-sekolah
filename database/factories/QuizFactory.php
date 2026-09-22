<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
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
            'classroom_id' => Classroom::factory(),
            'created_by' => null,
            'title' => 'Kuis '.fake()->words(2, true),
            'description' => null,
            'duration_minutes' => 30,
            'starts_at' => null,
            'ends_at' => null,
            'is_published' => true,
            'is_open' => true,
            'opened_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false, 'is_open' => false, 'opened_at' => null]);
    }

    /**
     * Kuis sudah dipublikasikan tapi belum dibuka guru di jam pelajaran.
     */
    public function closed(): static
    {
        return $this->state(fn () => ['is_open' => false, 'opened_at' => null]);
    }
}
