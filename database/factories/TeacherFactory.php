<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nip' => fake()->unique()->numerify('##################'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
        ];
    }
}
