<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_attendance_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.laporan-absensi'))->assertOk();
    }

    public function test_guru_is_forbidden_from_the_attendance_report(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.laporan-absensi'))->assertForbidden();
    }

    public function test_report_shows_correct_status_counts_within_the_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $today = now()->toDateString();

        Attendance::factory()->create(['date' => $today, 'status' => AttendanceStatus::Present]);
        Attendance::factory()->create(['date' => $today, 'status' => AttendanceStatus::Present]);
        Attendance::factory()->create(['date' => $today, 'status' => AttendanceStatus::Late]);
        Attendance::factory()->create(['date' => $today, 'status' => AttendanceStatus::Absent]);
        Attendance::factory()->create(['date' => now()->subDays(60)->toDateString(), 'status' => AttendanceStatus::Present]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-absensi', [
            'date_from' => $today,
            'date_to' => $today,
        ]));

        $response->assertOk();
        $response->assertViewHas('stats', [
            'hadir' => 2,
            'telat' => 1,
            'izin' => 0,
            'alpa' => 1,
        ]);
    }

    public function test_report_can_be_filtered_by_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentInTargetClass = Student::factory()->create(['classroom_id' => Classroom::factory()]);
        $studentInOtherClass = Student::factory()->create(['classroom_id' => Classroom::factory()]);
        Attendance::factory()->create(['student_id' => $studentInTargetClass->id, 'date' => now()->toDateString()]);
        Attendance::factory()->create(['student_id' => $studentInOtherClass->id, 'date' => now()->toDateString()]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-absensi', [
            'classroom_id' => $studentInTargetClass->classroom_id,
        ]));

        $response->assertOk();
        $response->assertSee($studentInTargetClass->name);
        $response->assertDontSee($studentInOtherClass->name);
    }
}
