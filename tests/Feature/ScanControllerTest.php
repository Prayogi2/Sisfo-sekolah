<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\AttendanceScheduleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScanControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(AttendanceScheduleSeeder::class);
    }

    public function test_admin_and_guru_can_view_the_scan_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)->get(route('scan-qr'))->assertOk();
        $this->actingAs($guru)->get(route('scan-qr'))->assertOk();
    }

    public function test_siswa_is_forbidden_from_the_scan_page(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($siswa)->get(route('scan-qr'))->assertForbidden();
    }

    public function test_scanning_a_valid_token_records_attendance(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create(['grade_level' => 3]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $response = $this->actingAs($guru)->postJson(route('scan-qr.store'), [
            'qr_token' => $student->qr_token,
        ]);

        $response->assertOk();
        $response->assertJsonPath('student.name', $student->name);
        $response->assertJsonPath('student.photo_url', null);
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id]);
    }

    public function test_scanning_returns_the_student_photo_url_when_available(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create(['grade_level' => 3]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        StudentProfile::factory()->create([
            'student_id' => $student->id,
            'photo_path' => 'student-photos/foto.jpg',
        ]);

        $response = $this->actingAs($guru)->postJson(route('scan-qr.store'), [
            'qr_token' => $student->qr_token,
        ]);

        $response->assertOk();
        $response->assertJsonPath('student.photo_url', Storage::url('student-photos/foto.jpg'));
    }

    public function test_scanning_an_unknown_token_returns_a_friendly_error(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->postJson(route('scan-qr.store'), [
            'qr_token' => 'tidak-ada',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message']);
    }

    /**
     * Pos presensi di gerbang memakai /presensi/scan tanpa login, jadi jalur
     * itu harus tetap berfungsi dan tidak dibatasi peran.
     */
    public function test_the_gate_scanner_works_without_logging_in(): void
    {
        $classroom = Classroom::factory()->create(['grade_level' => 3]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->get(route('presensi.scan'))->assertOk();

        $this->postJson(route('presensi.scan.store'), ['qr_token' => $student->qr_token])
            ->assertOk()
            ->assertJsonPath('student.name', $student->name);

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id]);
    }

    public function test_the_gate_scanner_rejects_an_unknown_token(): void
    {
        $this->postJson(route('presensi.scan.store'), ['qr_token' => 'bukan-token'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'QR Code tidak dikenali.');
    }

    public function test_admin_can_toggle_late_scan_blocking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.laporan-absensi.toggle-late-blocking'), ['enabled' => '1']);

        $this->assertTrue((bool) Setting::get('late_scan_blocking_enabled'));
    }

    public function test_guru_cannot_toggle_late_scan_blocking(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->post(route('admin.laporan-absensi.toggle-late-blocking'), ['enabled' => '1']);

        $response->assertForbidden();
    }
}
