<?php

namespace Tests\Feature\Services;

use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceScanException;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Setting;
use App\Models\Student;
use App\Services\AttendanceScanner;
use Database\Seeders\AttendanceScheduleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceScannerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private AttendanceScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AttendanceScheduleSeeder::class);
        $this->scanner = new AttendanceScanner;
    }

    private function studentInGrade(int $gradeLevel): Student
    {
        $classroom = Classroom::factory()->create(['grade_level' => $gradeLevel]);

        return Student::factory()->create(['classroom_id' => $classroom->id]);
    }

    public function test_unknown_qr_token_is_rejected(): void
    {
        $this->expectException(AttendanceScanException::class);

        $this->scanner->scan('token-tidak-ada');
    }

    public function test_student_without_classroom_is_rejected(): void
    {
        $student = Student::factory()->create(['classroom_id' => null]);

        $this->expectException(AttendanceScanException::class);

        $this->scanner->scan($student->qr_token);
    }

    public function test_on_time_scan_on_monday_is_marked_present(): void
    {
        $student = $this->studentInGrade(3);
        $monday0700 = now()->parse('2024-05-20 07:00:00');

        $result = $this->scanner->scan($student->qr_token, $monday0700);

        $this->assertSame('check_in', $result->event);
        $this->assertSame(AttendanceStatus::Present, $result->attendance->status);
        $this->assertNotNull($result->attendance->check_in_at);
    }

    public function test_late_scan_is_marked_late_by_default(): void
    {
        $student = $this->studentInGrade(3);
        $mondayLate = now()->parse('2024-05-20 07:30:00');

        $result = $this->scanner->scan($student->qr_token, $mondayLate);

        $this->assertSame(AttendanceStatus::Late, $result->attendance->status);
        $this->assertNotNull($result->attendance->check_in_at);
    }

    public function test_late_scan_is_marked_absent_when_blocking_is_enabled(): void
    {
        Setting::set('late_scan_blocking_enabled', true);
        $student = $this->studentInGrade(3);
        $mondayLate = now()->parse('2024-05-20 07:30:00');

        $result = $this->scanner->scan($student->qr_token, $mondayLate);

        $this->assertSame(AttendanceStatus::Absent, $result->attendance->status);
        $this->assertNull($result->attendance->check_in_at);
    }

    public function test_grade_1_and_2_use_the_earlier_dismissal_time_on_weekdays(): void
    {
        $student = $this->studentInGrade(1);

        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 07:00:00'));
        $checkout = $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 13:40:00'));

        $this->assertSame('check_out', $checkout->event);
        $this->assertNotNull($checkout->attendance->check_out_at);
    }

    public function test_friday_uses_the_same_dismissal_time_for_all_grades(): void
    {
        $student = $this->studentInGrade(6);
        $friday = now()->parse('2024-05-24 07:00:00');

        $result = $this->scanner->scan($student->qr_token, $friday);

        $this->assertSame(AttendanceStatus::Present, $result->attendance->status);
    }

    public function test_no_schedule_on_sunday_is_rejected(): void
    {
        $student = $this->studentInGrade(3);
        $sunday = now()->parse('2024-05-26 07:00:00');

        $this->expectException(AttendanceScanException::class);

        $this->scanner->scan($student->qr_token, $sunday);
    }

    public function test_second_scan_of_the_day_records_check_out(): void
    {
        $student = $this->studentInGrade(3);
        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 07:00:00'));

        $result = $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 14:20:00'));

        $this->assertSame('check_out', $result->event);
        $this->assertNotNull($result->attendance->check_out_at);
        $this->assertSame('on_time', $result->attendance->departure_status);
    }

    public function test_scan_before_scheduled_dismissal_is_recorded_as_early_departure(): void
    {
        $student = $this->studentInGrade(3);
        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 07:00:00'));

        $result = $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 14:19:00'));

        $this->assertSame('check_out', $result->event);
        $this->assertSame('early', $result->attendance->departure_status);
    }

    public function test_third_scan_of_the_day_is_treated_as_duplicate(): void
    {
        $student = $this->studentInGrade(3);
        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 07:00:00'));
        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 14:20:00'));

        $result = $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 14:25:00'));

        $this->assertSame('duplicate', $result->event);
    }

    public function test_scan_creates_exactly_one_attendance_row_per_student_per_day(): void
    {
        $student = $this->studentInGrade(3);

        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 07:00:00'));
        $this->scanner->scan($student->qr_token, now()->parse('2024-05-20 14:20:00'));

        $this->assertSame(1, Attendance::where('student_id', $student->id)->count());
    }
}
