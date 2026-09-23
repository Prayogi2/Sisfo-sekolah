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
            'kindergarten_address' => fake()->address(),
            'kindergarten_npsn' => fake()->numerify('##########'),
            'kindergarten_certificate_number' => fake()->numerify('TK-####/####'),
            'kindergarten_certificate_date' => fake()->dateTimeBetween('-8 years', '-6 years'),
            'entry_status' => 'Peserta Didik Baru',
            'entry_year' => fake()->numberBetween(2018, 2025),
            'entry_date' => fake()->dateTimeBetween('-6 years', '-1 years'),
            'entry_classroom' => 'Kelas 1',
            'transfer_out_letter_number' => null,
            'transfer_out_date' => null,
            'transfer_out_classroom' => null,
            'transfer_out_reason' => null,
            'transfer_out_nsm' => null,
            'transfer_out_npsn' => null,
            'transfer_out_village' => null,
            'transfer_out_district' => null,
            'transfer_out_province' => null,
            'exit_date' => null,
            'exit_classroom' => null,
            'exit_reason' => null,
            'graduation_status' => GraduationStatus::NotYetGraduated,
            'graduation_year' => null,
            'graduation_certificate_number' => null,
            'graduation_certificate_date' => null,
            'graduation_skl_number' => null,
            'continued_to' => null,
            'continued_to_district' => null,
            'continued_to_province' => null,
            'graduation_notes' => null,
        ];
    }
}
