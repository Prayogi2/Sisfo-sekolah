<?php

namespace Database\Factories;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveType;
use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-14 days', 'now');

        return [
            'student_id' => Student::factory(),
            'guardian_id' => Guardian::factory(),
            'reviewed_by' => null,
            'type' => fake()->randomElement(LeaveType::cases()),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $startDate->format('Y-m-d'),
            'reason' => fake()->sentence(),
            'attachment_path' => 'lampiran-izin/'.fake()->uuid().'.pdf',
            'status' => LeaveRequestStatus::Pending,
            'reviewed_at' => null,
        ];
    }
}
