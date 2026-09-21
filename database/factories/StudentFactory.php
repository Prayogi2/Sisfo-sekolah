<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
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
            'classroom_id' => null,
            'nisn' => fake()->unique()->numerify('##########'),
            'nis' => fake()->unique()->numerify('#####'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-17 years', '-15 years'),
            'address' => fake()->address(),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->phoneNumber(),
            'status' => StudentStatus::Active,
        ];
    }
}
