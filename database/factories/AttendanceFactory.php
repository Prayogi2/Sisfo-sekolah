<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-14 days', 'now');

        return [
            'student_id' => Student::factory(),
            'date' => $date->format('Y-m-d'),
            'check_in_at' => $date->format('Y-m-d').' 07:10:00',
            'check_out_at' => $date->format('Y-m-d').' 14:20:00',
            'status' => AttendanceStatus::Present,
        ];
    }
}
