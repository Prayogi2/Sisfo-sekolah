<?php

namespace Tests\Feature;

use App\Enums\Semester;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\GradeSettingSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class GradeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(GradeSettingSeeder::class);
    }

    /**
     * @return array{0: User, 1: Teacher, 2: Subject}
     */
    private function guruTeaching(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $subject = Subject::factory()->create();
        $teacher->subjects()->attach($subject);

        return [$user, $teacher, $subject];
    }

    public function test_guru_can_open_the_grade_sheet(): void
    {
        [$user, , $subject] = $this->guruTeaching();
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $response = $this->actingAs($user)->get(route('guru.laporan-nilai'));

        $response->assertOk();
        $response->assertSee($student->name);
        $response->assertSee($subject->name);
    }

    public function test_guru_only_sees_subjects_they_teach(): void
    {
        [$user, , $ownSubject] = $this->guruTeaching();
        $otherSubject = Subject::factory()->create(['name' => 'Mapel Orang Lain']);

        $response = $this->actingAs($user)->get(route('guru.laporan-nilai'));

        $response->assertOk();
        $response->assertSee($ownSubject->name);
        $response->assertDontSee($otherSubject->name);
    }

    public function test_guru_can_save_a_grade(): void
    {
        [$user, , $subject] = $this->guruTeaching();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('guru.laporan-nilai.simpan'), [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current()->value,
            'assignment_score' => 85,
            'quiz_score' => 90,
            'midterm_score' => 80,
            'final_score' => 88,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'assignment_score' => 85,
            'recorded_by' => $user->id,
        ]);
    }

    public function test_saving_a_grade_twice_updates_instead_of_duplicating(): void
    {
        [$user, , $subject] = $this->guruTeaching();
        $student = Student::factory()->create();

        $payload = [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current()->value,
            'assignment_score' => 70,
        ];

        $this->actingAs($user)->post(route('guru.laporan-nilai.simpan'), $payload);
        $this->actingAs($user)->post(route('guru.laporan-nilai.simpan'), [...$payload, 'assignment_score' => 95]);

        $this->assertDatabaseCount('grades', 1);
        $this->assertDatabaseHas('grades', ['student_id' => $student->id, 'assignment_score' => 95]);
    }

    public function test_guru_cannot_grade_a_subject_they_do_not_teach(): void
    {
        [$user] = $this->guruTeaching();
        $otherSubject = Subject::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('guru.laporan-nilai.simpan'), [
            'student_id' => $student->id,
            'subject_id' => $otherSubject->id,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current()->value,
            'assignment_score' => 85,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('grades', 0);
    }

    public function test_scores_must_be_within_zero_to_one_hundred(): void
    {
        [$user, , $subject] = $this->guruTeaching();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->post(route('guru.laporan-nilai.simpan'), [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'academic_year' => Classroom::currentAcademicYear(),
            'semester' => Semester::current()->value,
            'assignment_score' => 120,
        ]);

        $response->assertSessionHasErrors('assignment_score');
    }

    public function test_wali_is_forbidden_from_the_grade_sheet(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);

        $this->actingAs($wali)->get(route('guru.laporan-nilai'))->assertForbidden();
    }

    public function test_admin_can_view_the_grade_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        $subject = Subject::factory()->create();
        Grade::factory()->create(['student_id' => $student->id, 'subject_id' => $subject->id]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-nilai'));

        $response->assertOk();
        $response->assertSee($student->name);
    }

    public function test_final_score_uses_the_default_weights(): void
    {
        $grade = Grade::factory()->make([
            'assignment_score' => 85,
            'quiz_score' => 90,
            'midterm_score' => 80,
            'final_score' => 88,
        ]);
        $weight = GradeWeight::default(1, Classroom::currentAcademicYear(), Semester::current());

        // 85*20 + 90*20 + 80*30 + 88*30 = 8540 / 100 = 85.4
        $this->assertSame(85.4, $grade->finalScore($weight));
    }

    public function test_final_score_follows_custom_weights(): void
    {
        $grade = Grade::factory()->make([
            'assignment_score' => 100,
            'quiz_score' => 0,
            'midterm_score' => 0,
            'final_score' => 0,
        ]);
        $weight = GradeWeight::factory()->make([
            'assignment_weight' => 70,
            'quiz_weight' => 10,
            'midterm_weight' => 10,
            'final_weight' => 10,
        ]);

        $this->assertSame(70.0, $grade->finalScore($weight));
    }

    public function test_missing_components_are_excluded_instead_of_counted_as_zero(): void
    {
        $grade = Grade::factory()->make([
            'assignment_score' => 80,
            'quiz_score' => 90,
            'midterm_score' => null,
            'final_score' => null,
        ]);
        $weight = GradeWeight::default(1, Classroom::currentAcademicYear(), Semester::current());

        // Hanya tugas & kuis yang dihitung: (80*20 + 90*20) / 40 = 85
        $this->assertSame(85.0, $grade->finalScore($weight));
    }

    public function test_final_score_is_null_when_nothing_has_been_entered(): void
    {
        $grade = Grade::factory()->make([
            'assignment_score' => null,
            'quiz_score' => null,
            'midterm_score' => null,
            'final_score' => null,
        ]);
        $weight = GradeWeight::default(1, Classroom::currentAcademicYear(), Semester::current());

        $this->assertNull($grade->finalScore($weight));
    }

    public function test_grade_letters_follow_the_score_bands(): void
    {
        $this->assertSame('A+', Grade::letterFor(91.1));
        $this->assertSame('A', Grade::letterFor(85.1));
        $this->assertSame('B', Grade::letterFor(70.0));
        $this->assertSame('C', Grade::letterFor(65.0));
        $this->assertSame('D', Grade::letterFor(59.0));
    }
}
