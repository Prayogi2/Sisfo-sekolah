<?php

namespace Database\Seeders;

use App\Enums\GuardianRelationship;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
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
        // Mapel asli madrasah (lihat SubjectSeeder), bukan mapel acak.
        $subjects = Subject::orderBy('id')->get();

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

        // Tiap guru mengampu beberapa mapel, masing-masing di kedua kelas
        // demo ini (contoh guru yang mengajar lebih dari satu kelas).
        $teachers->each(
            fn (Teacher $teacher) => $teacher->teachingAssignments()->createMany(
                $subjects->random(3)->crossJoin($classrooms)
                    ->map(fn (array $pair) => ['subject_id' => $pair[0]->id, 'classroom_id' => $pair[1]->id])
            )
        );

        // Akun demo guru@gmail.com (dibuat UserSeeder, login pakai email)
        // ditugaskan mengajar hanya satu mapel di satu kelas, supaya benar-
        // benar mendemokan pembatasan akses guru (bank soal, kuis, nilai)
        // per mapel+kelas — bukan akun tanpa batasan sama sekali. NIP diisi
        // eksplisit (bukan acak dari factory) karena TeacherObserver
        // menyamakan username login guru dengan NIP-nya.
        if ($demoGuruUser = User::where('email', 'guru@gmail.com')->first()) {
            $demoTeacher = Teacher::factory()->create([
                'user_id' => $demoGuruUser->id,
                'nip' => '1000000001',
                'name' => $demoGuruUser->name,
                'email' => $demoGuruUser->email,
            ]);
            $demoTeacher->teachingAssignments()->create([
                'subject_id' => $subjects->first()->id,
                'classroom_id' => $classrooms->first()->id,
            ]);
        }

        $studentsByClassroom = $classrooms->map(
            fn (Classroom $classroom) => Student::factory(3)->create([
                'classroom_id' => $classroom->id,
            ])
        )->flatten();

        // Lengkapi data buku induk (profil & riwayat akademik) tiap siswa.
        $studentsByClassroom->each(function (Student $student) {
            StudentProfile::factory()->create(['student_id' => $student->id]);
            StudentAcademicRecord::factory()->create(['student_id' => $student->id]);
            $student->update(['user_id' => User::create([
                'name' => $student->name,
                'email' => $student->nisn.'@siswa.local',
                'username' => $student->nisn,
                'password' => 'password123',
                'role' => 'siswa',
            ])->id]);
        });

        // Keluarga 1: ayah & ibu sama-sama terhubung ke anak yang sama.
        $ayah = Guardian::factory()->create(['relationship' => GuardianRelationship::Father]);
        $ibu = Guardian::factory()->create(['relationship' => GuardianRelationship::Mother]);
        $ayah->students()->attach($studentsByClassroom[0]->id);
        $ibu->students()->attach($studentsByClassroom[0]->id);

        // Keluarga 2: satu wali untuk dua anak (kakak-adik beda kelas).
        $wali = Guardian::factory()->create(['relationship' => GuardianRelationship::Guardian]);
        $wali->students()->attach([
            $studentsByClassroom[1]->id,
            $studentsByClassroom[3]->id,
        ]);
    }
}
