<?php

namespace Database\Seeders;

use App\Enums\GuardianRelationship;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data akademik contoh (mapel, guru, kelas, siswa, wali) yang saling
 * terhubung — dipakai untuk pengujian manual & otomatis di fase-fase
 * berikutnya (absensi, izin, SPP, kuis, dst).
 */
class DemoAcademicDataSeeder extends Seeder
{
    public function run(): void
    {
        Subject::factory(3)->create();

        $teachers = collect(range(1, 2))->map(
            fn () => Teacher::factory()
                ->for(User::factory()->state(['role' => 'guru']))
                ->create()
        );

        $classrooms = $teachers->map(
            fn (Teacher $teacher) => Classroom::factory()->create([
                'homeroom_teacher_id' => $teacher->id,
            ])
        );

        $studentsByClassroom = $classrooms->map(
            fn (Classroom $classroom) => Student::factory(3)->create([
                'classroom_id' => $classroom->id,
            ])
        )->flatten();

        // Keluarga 1: ayah & ibu sama-sama terhubung ke anak yang sama.
        $ayah = Guardian::factory()
            ->for(User::factory()->state(['role' => 'wali']))
            ->create(['relationship' => GuardianRelationship::Father]);
        $ibu = Guardian::factory()
            ->for(User::factory()->state(['role' => 'wali']))
            ->create(['relationship' => GuardianRelationship::Mother]);
        $ayah->students()->attach($studentsByClassroom[0]->id);
        $ibu->students()->attach($studentsByClassroom[0]->id);

        // Keluarga 2: satu wali untuk dua anak (kakak-adik beda kelas).
        $wali = Guardian::factory()
            ->for(User::factory()->state(['role' => 'wali']))
            ->create(['relationship' => GuardianRelationship::Guardian]);
        $wali->students()->attach([
            $studentsByClassroom[1]->id,
            $studentsByClassroom[3]->id,
        ]);
    }
}
