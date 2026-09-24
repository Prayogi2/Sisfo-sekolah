<?php

namespace Database\Factories;

use App\Models\QuizQuestion;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
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
            'created_by' => null,
            'type' => QuizQuestion::TYPE_SINGLE,
            'question' => fake()->sentence().'?',
            'options' => [
                'A' => fake()->word(),
                'B' => fake()->word(),
                'C' => fake()->word(),
                'D' => fake()->word(),
            ],
            'correct_answer' => ['A'],
            'explanation' => null,
            'points' => 1,
            'is_active' => true,
        ];
    }

    public function multiple(): static
    {
        return $this->state(fn () => ['type' => QuizQuestion::TYPE_MULTIPLE, 'correct_answer' => ['A', 'C']]);
    }
}
