<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\GuardianRelationship;
use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleQrStudentSeeder extends Seeder
{
    public const QR_TOKEN = 'NURFA-DEMO-QR-001';

    public function run(): void
    {
        $classroom = Classroom::query()->firstOrFail();
        $studentUser = User::query()->where('username', 'siswa')->firstOrFail();

        $guardian = Guardian::updateOrCreate(
            ['name' => 'Wali Murid Demo', 'relationship' => GuardianRelationship::Guardian],
            ['phone' => '081234567890'],
        );

        $student = Student::updateOrCreate(
            ['nis' => 'NURFA-QR-001'],
            [
                'classroom_id' => $classroom->id,
                'nisn' => '9999000001',
                'qr_token' => self::QR_TOKEN,
                'name' => $studentUser->name,
                'gender' => Gender::Male,
                'birth_place' => 'Kota Demo',
                'birth_date' => '2015-01-01',
                'address' => 'Alamat Demo NURFA.ID',
                'parent_name' => $guardian->name,
                'parent_phone' => $guardian->phone,
                'status' => StudentStatus::Active,
            ],
        );

        $guardian->students()->syncWithoutDetaching([$student->id]);
        $student->update(['user_id' => $studentUser->id]);

        $this->command?->info("Siswa demo QR siap: {$student->name}");
        $this->command?->info('Token QR: '.self::QR_TOKEN);
        $this->command?->info("Login siswa: {$student->name} / password123");
    }
}
