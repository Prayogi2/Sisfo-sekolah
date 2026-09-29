<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\InventoryReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryReport>
 */
class InventoryReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'reported_by' => User::factory()->state(['role' => 'guru']),
            'notes' => null,
        ];
    }
}
