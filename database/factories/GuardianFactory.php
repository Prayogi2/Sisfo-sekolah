<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\GuardianRelationship;
use App\Enums\Religion;
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
            'nik' => fake()->unique()->numerify('################'),
            'family_card_number' => fake()->numerify('################'),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-25 years'),
            'religion' => Religion::Islam,
            'blood_type' => fake()->randomElement(BloodType::cases()),
            'last_education' => fake()->randomElement(EducationLevel::cases()),
            'address' => fake()->address(),
        ];
    }
}
