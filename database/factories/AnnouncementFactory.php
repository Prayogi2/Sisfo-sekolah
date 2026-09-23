<?php

namespace Database\Factories;

use App\Enums\AnnouncementCategory;
use App\Enums\AnnouncementTarget;
use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => null,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'category' => AnnouncementCategory::Info,
            'target' => AnnouncementTarget::AllStudents,
            'classroom_id' => null,
            'published_at' => now(),
        ];
    }

    /**
     * Notifikasi yang baru tampil ke siswa besok.
     */
    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addDay()]);
    }
}
