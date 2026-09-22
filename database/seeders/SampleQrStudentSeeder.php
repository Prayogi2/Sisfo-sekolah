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
        $waliUser = User::query()->where('username', 'wali')->firstOrFail();

        $guardian = Guardian::updateOrCreate(
            ['user_id' => $waliUser->id],
            [
                'name' => $waliUser->name,
                'relationship' => GuardianRelationship::Guardian,
                'phone' => '081234567890',
            ],
        );

        $student = Student::updateOrCreate(
            ['nis' => 'NURFA-QR-001'],
            [
                'classroom_id' => $classroom->id,
                'nisn' => '9999000001',
                'qr_token' => self::QR_TOKEN,
                'name' => 'Siswa Demo QR',
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
        $studentUser = User::updateOrCreate(
            ['email' => $student->nisn.'@siswa.local'],
            ['name' => $student->name, 'username' => $student->nisn, 'password' => 'password123', 'role' => 'siswa']
        );
        $student->update(['user_id' => $studentUser->id]);

        $this->command?->info("Siswa demo QR siap: {$student->name}");
        $this->command?->info('Token QR: '.self::QR_TOKEN);
        $this->command?->info("Login siswa: {$student->name} / password123");
        $this->command?->info('Login wali: wali@gmail.com / password123');
    }
}
