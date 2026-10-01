<?php

namespace Tests\Feature;

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

class StudentLeaveRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $siswa;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('public');

        $this->siswa = User::factory()->create(['role' => 'siswa']);
        $this->student = Student::factory()->create(['user_id' => $this->siswa->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type' => 'sakit',
            'start_date' => today()->toDateString(),
            'end_date' => today()->addDay()->toDateString(),
            'reason' => 'Demam tinggi',
            'applicant_name' => 'Ibu Sari',
            'attachment' => UploadedFile::fake()->image('surat-dokter.jpg'),
            ...$overrides,
        ];
    }

    public function test_student_submits_a_sick_leave_with_a_letter_photo(): void
    {
        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload())
            ->assertRedirect(route('siswa.izin'))
            ->assertSessionHas('success', 'Pengajuan izin terkirim dan menunggu persetujuan wali kelas.');

        $leaveRequest = LeaveRequest::sole();
        $this->assertSame([$this->student->id, null, 'Ibu Sari', LeaveRequestStatus::Pending], [$leaveRequest->student_id, $leaveRequest->guardian_id, $leaveRequest->applicant_name, $leaveRequest->status]);
        Storage::disk('public')->assertExists($leaveRequest->attachment_path);

        $this->actingAs($this->siswa)->get(route('siswa.izin'))->assertOk()->assertSeeInOrder(['Demam tinggi', 'Menunggu wali kelas']);
    }

    public function test_student_can_pick_a_registered_guardian_as_the_applicant(): void
    {
        $guardian = Guardian::factory()->create(['name' => 'Bapak Joko']);
        $this->student->guardians()->attach($guardian);

        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload(['guardian_id' => $guardian->id, 'applicant_name' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Bapak Joko', LeaveRequest::sole()->applicantLabel());
    }

    public function test_a_guardian_of_another_student_is_rejected(): void
    {
        $otherGuardian = Guardian::factory()->create();

        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload(['guardian_id' => $otherGuardian->id]))
            ->assertSessionHasErrors(['guardian_id' => 'Orang tua/wali yang dipilih tidak terdaftar untuk siswa ini.']);

        $this->assertDatabaseCount('leave_requests', 0);
    }

    public function test_the_letter_and_applicant_are_required(): void
    {
        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload(['attachment' => null, 'applicant_name' => '']))
            ->assertSessionHasErrors([
                'attachment' => 'Lampirkan bukti (foto surat dokter / surat orang tua).',
                'applicant_name' => 'Pilih orang tua/wali yang mengajukan, atau tulis nama pengaju.',
            ]);
    }

    public function test_leave_cannot_be_backdated_more_than_seven_days_or_last_over_fourteen_days(): void
    {
        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload(['start_date' => today()->subDays(8)->toDateString()]))
            ->assertSessionHasErrors(['start_date' => 'Izin hanya bisa diajukan menyusul paling lambat 7 hari.']);

        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload(['end_date' => today()->addDays(14)->toDateString()]))
            ->assertSessionHasErrors(['end_date' => 'Izin maksimal 14 hari sekali pengajuan.']);
    }

    public function test_the_submitted_request_reaches_the_homeroom_teacher(): void
    {
        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $this->student->update(['classroom_id' => Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id])->id]);

        $this->actingAs($this->siswa)->post(route('siswa.izin.store'), $this->payload());

        $this->actingAs($guruUser)->get(route('guru.approval-izin'))
            ->assertOk()
            ->assertSee($this->student->name)
            ->assertSee('Diajukan: Ibu Sari');
    }

    public function test_guru_cannot_submit_through_the_student_form(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('siswa.izin.store'), $this->payload())->assertForbidden();
    }
}
