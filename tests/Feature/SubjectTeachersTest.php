<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SubjectTeachersTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_set_different_teachers_for_different_classrooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        [$teacherA, $teacherB, $oldTeacher] = Teacher::factory(3)->create();
        [$classroom1, $classroom2, $classroom3] = Classroom::factory(3)->create();
        $oldTeacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom1->id]);

        $this->actingAs($admin)->put(route('admin.data-mapel.guru-pengampu', $subject), [
            'assignments' => [
                ['teacher_id' => $teacherA->id, 'classroom_ids' => [$classroom1->id, $classroom2->id]],
                ['teacher_id' => $teacherB->id, 'classroom_ids' => [$classroom3->id]],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertTrue($teacherA->teaches($subject->id, $classroom1->id));
        $this->assertTrue($teacherA->teaches($subject->id, $classroom2->id));
        $this->assertTrue($teacherB->teaches($subject->id, $classroom3->id));
        $this->assertFalse($oldTeacher->teaches($subject->id, $classroom1->id));
        $this->assertSame(3, $subject->teachingAssignments()->count());
    }

    public function test_mapel_page_lists_the_teachers_with_their_classrooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $teacher = Teacher::factory()->create(['name' => 'Ustadz Pengampu']);
        $classroom = Classroom::factory()->create(['name' => '4-B']);
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);

        $this->actingAs($admin)->get(route('admin.data-mapel'))
            ->assertOk()
            ->assertSeeInOrder(['Guru Pengampu', 'Ustadz Pengampu', '(4-B)']);
    }

    public function test_the_same_teacher_cannot_be_listed_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $teacher = Teacher::factory()->create();
        [$classroom1, $classroom2] = Classroom::factory(2)->create();

        $this->actingAs($admin)->put(route('admin.data-mapel.guru-pengampu', $subject), [
            'assignments' => [
                ['teacher_id' => $teacher->id, 'classroom_ids' => [$classroom1->id]],
                ['teacher_id' => $teacher->id, 'classroom_ids' => [$classroom2->id]],
            ],
        ])->assertSessionHasErrors('assignments');

        $this->assertSame(0, $subject->teachingAssignments()->count());
    }

    public function test_guru_cannot_change_subject_teachers(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $subject = Subject::factory()->create();

        $this->actingAs($guru)->put(route('admin.data-mapel.guru-pengampu', $subject), ['assignments' => []])->assertForbidden();
    }
}
