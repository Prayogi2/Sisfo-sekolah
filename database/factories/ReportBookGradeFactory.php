<?php

namespace Database\Factories;

use App\Models\ReportBookGrade;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportBookGrade>
 */
class ReportBookGradeFactory extends Factory
{
    /**
     * Mapel dengan nilai terisi untuk semua kelas & semester.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'subject_name' => fake()->randomElement(['Al-Qur\'an Hadis', 'Akidah Akhlak', 'Fikih', 'Bahasa Indonesia', 'Matematika', 'IPAS']),
            'sort_order' => 0,
            ...collect(ReportBookGrade::scoreColumns())->mapWithKeys(fn (string $column) => [$column => fake()->numberBetween(70, 95)])->all(),
        ];
    }
}
