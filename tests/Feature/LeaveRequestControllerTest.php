<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_wali_can_submit_a_leave_request_with_an_attachment(): void
    {
        Storage::fake('public');

        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        $response = $this->actingAs($wali)->post(route('wali.izin.store'), [
            'type' => 'sakit',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason' => 'Demam tinggi',
            'attachment' => UploadedFile::fake()->create('surat.pdf', 100),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'status' => LeaveRequestStatus::Pending->value,
        ]);

        $leaveRequest = LeaveRequest::first();
        Storage::disk('public')->assertExists($leaveRequest->attachment_path);
    }

    public function test_leave_request_requires_an_attachment(): void
    {
        Storage::fake('public');

        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        $response = $this->actingAs($wali)->post(route('wali.izin.store'), [
            'type' => 'sakit',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Demam tinggi',
        ]);

        $response->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_admin_can_view_all_leave_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        LeaveRequest::factory()->create();

        $this->actingAs($admin)->get(route('admin.approval-izin'))->assertOk();
    }

    public function test_guru_only_sees_leave_requests_for_their_homeroom_class(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $myClassroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $otherClassroom = Classroom::factory()->create();

        $myStudent = Student::factory()->create(['classroom_id' => $myClassroom->id]);
        $otherStudent = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        LeaveRequest::factory()->create(['student_id' => $myStudent->id]);
        LeaveRequest::factory()->create(['student_id' => $otherStudent->id]);

        $response = $this->actingAs($guruUser)->get(route('guru.approval-izin'));

        $response->assertOk();
        $response->assertSee($myStudent->name);
        $response->assertDontSee($otherStudent->name);
    }

    public function test_wali_cannot_approve_a_leave_request(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $leaveRequest = LeaveRequest::factory()->create();

        $response = $this->actingAs($wali)->post(route('admin.approval-izin.approve', $leaveRequest));

        $response->assertForbidden();
    }

    public function test_approving_a_leave_request_marks_attendance_as_excused_for_the_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create([
            'student_id' => $student->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.approval-izin.approve', $leaveRequest));

        $response->assertRedirect();
        $this->assertSame(LeaveRequestStatus::Approved, $leaveRequest->fresh()->status);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => now()->toDateString(),
            'status' => AttendanceStatus::Excused->value,
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'date' => now()->addDays(1)->toDateString(),
            'status' => AttendanceStatus::Excused->value,
        ]);
    }

    public function test_rejecting_a_leave_request_does_not_touch_attendance(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $leaveRequest = LeaveRequest::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.approval-izin.reject', $leaveRequest));

        $response->assertRedirect();
        $this->assertSame(LeaveRequestStatus::Rejected, $leaveRequest->fresh()->status);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_guru_cannot_approve_a_leave_request_for_a_class_they_do_not_manage(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $guruUser->id]);

        $otherClassroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);
        $leaveRequest = LeaveRequest::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($guruUser)->post(route('guru.approval-izin.approve', $leaveRequest));

        $response->assertForbidden();
    }
}
