<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentViolation;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Aksi simpan/ubah/hapus yang belum punya test sendiri. Yang dijaga: aksinya
 * benar-benar jalan (bukan error 500) dan datanya berubah seperti seharusnya.
 */
class CrudSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_resets_a_student_password(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $before = $siswa->password;

        $this->actingAs($this->admin())->post(route('admin.akun.reset-password', $siswa))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSame($before, $siswa->fresh()->password);
    }

    public function test_resetting_an_admin_password_is_not_found(): void
    {
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->admin())->post(route('admin.akun.reset-password', $other))->assertNotFound();
    }

    public function test_admin_updates_a_classroom(): void
    {
        $classroom = Classroom::factory()->create(['name' => '1-A', 'capacity' => 20]);
        $teacher = Teacher::factory()->create();

        $this->actingAs($this->admin())->put(route('admin.pembagian-kelas.update', $classroom), [
            'name' => '1-B',
            'grade_level' => 2,
            'homeroom_teacher_id' => $teacher->id,
            'capacity' => 30,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $classroom->refresh();
        $this->assertSame('1-B', $classroom->name);
        $this->assertSame(2, $classroom->grade_level);
        $this->assertSame(30, $classroom->capacity);
        $this->assertSame($teacher->id, $classroom->homeroom_teacher_id);
    }

    public function test_admin_deletes_an_announcement(): void
    {
        $announcement = Announcement::factory()->create();

        $this->actingAs($this->admin())->delete(route('admin.notifikasi.destroy', $announcement))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_admin_deletes_conduct_records_and_their_evidence(): void
    {
        $student = Student::factory()->create();
        $achievement = StudentAchievement::create(['student_id' => $student->id, 'category' => 'academic', 'title' => 'Juara 1', 'evidence_path' => 'student-achievements/bukti.jpg']);
        $violation = StudentViolation::create(['student_id' => $student->id, 'severity' => 'light', 'title' => 'Terlambat', 'description' => 'Lewat jam masuk.', 'occurred_at' => now()->toDateString(), 'evidence_path' => 'student-violations/bukti.jpg']);
        Storage::disk('public')->put($achievement->evidence_path, 'x');
        Storage::disk('public')->put($violation->evidence_path, 'x');

        $admin = $this->admin();
        $this->actingAs($admin)->delete(route('admin.prestasi-pelanggaran.prestasi.destroy', $achievement))->assertRedirect();
        $this->actingAs($admin)->delete(route('admin.prestasi-pelanggaran.pelanggaran.destroy', $violation))->assertRedirect();

        $this->assertDatabaseCount('student_achievements', 0);
        $this->assertDatabaseCount('student_violations', 0);
        Storage::disk('public')->assertMissing('student-achievements/bukti.jpg');
        Storage::disk('public')->assertMissing('student-violations/bukti.jpg');
    }

    public function test_guru_records_and_deletes_an_achievement(): void
    {
        [$guru, $student] = $this->guruWithAStudent();

        $this->actingAs($guru)->post(route('guru.prestasi-pelanggaran.prestasi.store'), [
            'student_id' => $student->id,
            'category' => 'non_academic',
            'title' => 'Juara 2 Futsal',
            'achieved_at' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $achievement = StudentAchievement::sole();
        $this->assertSame('Juara 2 Futsal', $achievement->title);
        $this->assertSame($guru->id, $achievement->recorded_by);

        $this->actingAs($guru)->delete(route('guru.prestasi-pelanggaran.prestasi.destroy', $achievement))->assertRedirect();
        $this->assertDatabaseCount('student_achievements', 0);
    }

    public function test_guru_deletes_a_violation(): void
    {
        [$guru, $student] = $this->guruWithAStudent();
        $violation = StudentViolation::create(['student_id' => $student->id, 'severity' => 'medium', 'title' => 'Gadget', 'description' => 'Main gadget saat pelajaran.', 'occurred_at' => now()->toDateString()]);

        $this->actingAs($guru)->delete(route('guru.prestasi-pelanggaran.pelanggaran.destroy', $violation))->assertRedirect();

        $this->assertDatabaseCount('student_violations', 0);
    }

    public function test_siswa_cannot_touch_conduct_records(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id]);
        $achievement = StudentAchievement::create(['student_id' => $student->id, 'category' => 'academic', 'title' => 'Juara 1']);

        $this->actingAs($siswa)->post(route('guru.prestasi-pelanggaran.prestasi.store'), [
            'student_id' => $student->id, 'category' => 'academic', 'title' => 'Prestasi Palsu',
        ])->assertForbidden();
        $this->actingAs($siswa)->delete(route('guru.prestasi-pelanggaran.prestasi.destroy', $achievement))->assertForbidden();

        $this->assertDatabaseCount('student_achievements', 1);
    }

    public function test_guru_deletes_a_question_from_the_bank(): void
    {
        [$guru, , $subject] = $this->guruWithAStudent();
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => $guru->id, 'media_path' => 'quiz-media/gambar.jpg']);
        Storage::disk('public')->put($question->media_path, 'x');

        $this->actingAs($guru)->delete(route('guru.bank-soal.destroy', $question))->assertRedirect();

        $this->assertDatabaseCount('quiz_questions', 0);
        Storage::disk('public')->assertMissing('quiz-media/gambar.jpg');
    }

    public function test_wali_kelas_adds_the_standard_inventory_items_without_duplicating_them(): void
    {
        [$guru, , , $classroom] = $this->guruWithAStudent();

        $this->actingAs($guru)->post(route('guru.inventaris.items.defaults', $classroom))->assertRedirect();
        $afterFirstRun = $classroom->inventoryItems()->count();
        $this->assertGreaterThan(0, $afterFirstRun);

        $this->actingAs($guru)->post(route('guru.inventaris.items.defaults', $classroom))->assertRedirect();
        $this->assertSame($afterFirstRun, $classroom->inventoryItems()->count());
    }

    public function test_guru_finishes_a_live_quiz_session(): void
    {
        [$guru, , $subject, $classroom] = $this->guruWithAStudent();
        $quiz = Quiz::factory()->create(['mode' => 'live', 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'created_by' => $guru->id]);

        $this->actingAs($guru)->post(route('guru.kuis.live.finish', $quiz))->assertRedirect()->assertSessionHas('success');

        $quiz->refresh();
        $this->assertFalse($quiz->is_open);
        $this->assertSame('finished', $quiz->live_phase);
    }

    /**
     * @return array{0: User, 1: Student, 2: Subject, 3: Classroom}
     */
    private function guruWithAStudent(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create();
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $classroom->update(['homeroom_teacher_id' => $teacher->id]);
        $subject = Subject::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        return [$user, $student, $subject, $classroom];
    }
}
