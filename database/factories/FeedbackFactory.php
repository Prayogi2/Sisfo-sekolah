<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'guardian_id' => Guardian::factory(),
            'message' => fake()->paragraph(),
        ];
    }
}
