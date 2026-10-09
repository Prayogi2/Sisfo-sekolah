<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentRecitationNote;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentRecitationNoteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_homeroom_teacher_can_record_a_student_memorization_or_reading_note(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacherUser)->get(route('guru.catatan-hafalan-bacaan', ['classroom' => $classroom->id]))
            ->assertOk()
            ->assertSee('Catatan Hafalan & Bacaan')
            ->assertSee($student->name);

        $this->actingAs($teacherUser)->post(route('guru.catatan-hafalan-bacaan.store', $classroom), [
            'student_id' => $student->id,
            'type' => 'memorization',
            'material' => 'Surah Al-Mulk',
            'achievement' => 'Ayat 1-10 lancar',
            'recorded_at' => '2026-10-09',
            'notes' => 'Perlu mengulang makhraj.',
        ])->assertRedirect(route('guru.catatan-hafalan-bacaan', ['classroom' => $classroom->id]));

        $this->assertDatabaseHas('student_recitation_notes', [
            'student_id' => $student->id,
            'recorded_by' => $teacherUser->id,
            'type' => 'memorization',
            'material' => 'Surah Al-Mulk',
            'achievement' => 'Ayat 1-10 lancar',
            'notes' => 'Perlu mengulang makhraj.',
        ]);
        $this->assertSame('2026-10-09', StudentRecitationNote::sole()->recorded_at->toDateString());
    }

    public function test_teacher_cannot_record_notes_for_a_class_they_do_not_manage(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacherUser)->post(route('guru.catatan-hafalan-bacaan.store', $classroom), [
            'student_id' => $student->id,
            'type' => 'reading',
            'material' => 'Iqra 3 halaman 12',
            'achievement' => 'Lancar',
            'recorded_at' => '2026-10-09',
        ])->assertForbidden();

        $this->assertDatabaseCount('student_recitation_notes', 0);
    }

    public function test_subject_teacher_can_record_notes_for_a_class_they_teach(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $classroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create([
            'subject_id' => Subject::factory()->create()->id,
            'classroom_id' => $classroom->id,
        ]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacherUser)->get(route('guru.catatan-hafalan-bacaan', ['classroom' => $classroom->id]))
            ->assertOk()
            ->assertSee($student->name);
        $this->actingAs($teacherUser)->post(route('guru.catatan-hafalan-bacaan.store', $classroom), [
            'student_id' => $student->id,
            'type' => 'reading',
            'material' => 'Iqra 3 halaman 12',
            'achievement' => 'Lancar',
            'recorded_at' => '2026-10-09',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_recitation_notes', [
            'student_id' => $student->id,
            'recorded_by' => $teacherUser->id,
            'type' => 'reading',
        ]);
    }

    public function test_student_cannot_open_the_teacher_recitation_notes_page(): void
    {
        $studentUser = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($studentUser)->get(route('guru.catatan-hafalan-bacaan'))
            ->assertForbidden();
    }

    public function test_recitation_note_requires_a_material_and_achievement(): void
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($teacherUser)->post(route('guru.catatan-hafalan-bacaan.store', $classroom), [
            'student_id' => $student->id,
            'type' => 'reading',
            'recorded_at' => '2026-10-09',
        ])->assertSessionHasErrors(['material', 'achievement']);

        $this->assertDatabaseCount('student_recitation_notes', 0);
    }
}
