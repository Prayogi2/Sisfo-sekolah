<?php

namespace Database\Factories;

use App\Enums\GraduationStatus;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAcademicRecord>
 */
class StudentAcademicRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'kindergarten_origin' => 'TK '.fake()->lastName(),
            'kindergarten_certificate_number' => fake()->numerify('TK-####/####'),
            'kindergarten_certificate_date' => fake()->dateTimeBetween('-8 years', '-6 years'),
            'entry_status' => 'Peserta Didik Baru',
            'entry_date' => fake()->dateTimeBetween('-6 years', '-1 years'),
            'transfer_out_date' => null,
            'transfer_out_reason' => null,
            'exit_date' => null,
            'exit_reason' => null,
            'graduation_status' => GraduationStatus::NotYetGraduated,
            'graduation_year' => null,
            'graduation_certificate_number' => null,
            'graduation_certificate_date' => null,
            'continued_to' => null,
            'graduation_notes' => null,
        ];
    }
}
