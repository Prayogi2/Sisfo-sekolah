<?php

namespace Database\Factories;

use App\Models\AttendanceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSchedule>
 */
class AttendanceScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => fake()->numberBetween(1, 6),
            'grade_min' => 1,
            'grade_max' => 6,
            'check_in_time' => '07:15:00',
            'check_out_time' => '14:20:00',
        ];
    }
}
