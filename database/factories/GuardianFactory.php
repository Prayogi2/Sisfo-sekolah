<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
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
            'name' => fake()->name(),
            'relationship' => fake()->randomElement(GuardianRelationship::cases()),
            'phone' => fake()->phoneNumber(),
            'occupation' => fake()->jobTitle(),
            'monthly_income' => fake()->randomElement([
                '< Rp1.000.000',
                'Rp1.000.000 - Rp3.000.000',
                'Rp3.000.000 - Rp5.000.000',
                '> Rp5.000.000',
            ]),
        ];
    }
}
