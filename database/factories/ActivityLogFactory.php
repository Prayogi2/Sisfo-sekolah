<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'user_role' => 'admin',
            'action' => 'admin.data-siswa.store',
            'description' => 'Menambah Data Siswa',
            'method' => 'POST',
            'url' => 'http://localhost/admin/data-siswa',
            'status_code' => 302,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Mozilla/5.0',
        ];
    }

    public function byRole(string $role): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory()->state(['role' => $role]),
            'user_role' => $role,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status_code' => 403]);
    }
}
