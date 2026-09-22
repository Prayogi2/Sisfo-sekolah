<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AttendanceScheduleSeeder::class,
            SppSettingSeeder::class,
            GradeSettingSeeder::class,
            SubjectSeeder::class,
            DemoAcademicDataSeeder::class,
            MathQuestionBankSeeder::class,
            SampleQrStudentSeeder::class,
        ]);
    }
}
