<?php

namespace Database\Factories;

use App\Enums\BloodType;
use App\Enums\FamilyStatus;
use App\Enums\Religion;
use App\Models\Student;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
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
            'nickname' => fake()->firstName(),
            'nik' => fake()->unique()->numerify('################'),
            'family_card_number' => fake()->numerify('################'),
            'religion' => Religion::Islam,
            'family_status' => FamilyStatus::BiologicalChild,
            'birth_order' => fake()->numberBetween(1, 5),
            'siblings_count' => fake()->numberBetween(0, 4),
            'weight_kg' => fake()->numberBetween(15, 45),
            'height_cm' => fake()->numberBetween(100, 150),
            'blood_type' => fake()->randomElement(BloodType::cases()),
            'street_address' => fake()->streetAddress(),
            'hamlet' => 'Dusun '.fake()->numberBetween(1, 5),
            'village' => fake()->citySuffix(),
            'district' => fake()->city(),
            'regency' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'photo_path' => null,
        ];
    }
}
