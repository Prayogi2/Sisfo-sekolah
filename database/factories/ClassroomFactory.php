<?php

namespace Database\Factories;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Nomor urut huruf kelas per tingkat, supaya nama kelas tidak pernah
     * kembar (tabel classrooms punya unique nama + tahun ajaran).
     *
     * @var array<int, int>
     */
    protected static array $letterSequence = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeLevel = fake()->numberBetween(1, 6);
        $startYear = fake()->numberBetween(2020, 2026);
        $letterIndex = static::$letterSequence[$gradeLevel] = (static::$letterSequence[$gradeLevel] ?? -1) + 1;

        return [
            'name' => "{$gradeLevel}-".chr(65 + $letterIndex % 26),
            'grade_level' => $gradeLevel,
            'academic_year' => "{$startYear}/".($startYear + 1),
            'homeroom_teacher_id' => null,
        ];
    }
}
