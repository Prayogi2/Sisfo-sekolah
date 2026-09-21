<?php

namespace Database\Factories;

use App\Models\SppBill;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SppBill>
 */
class SppBillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $period = now()->startOfMonth();

        return [
            'student_id' => Student::factory(),
            'period' => $period->toDateString(),
            'amount' => 350000,
            'due_date' => $period->copy()->day(10)->toDateString(),
        ];
    }
}
