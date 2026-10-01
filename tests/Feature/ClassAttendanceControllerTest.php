<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ClassAttendanceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: Classroom}
     */
    private function homeroomTeacher(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id])];
    }

    public function test_homeroom_teacher_sees_how_many_students_came_to_school_today(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $present = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Ani Hadir']);
        $late = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Beni Telat']);
        $excused = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Cici Izin']);
        Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Dodi Belum']);
        Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Eko Lulus', 'status' => StudentStatus::Graduated]);
        Attendance::factory()->create(['student_id' => $present->id, 'date' => today(), 'check_in_at' => today()->setTime(7, 5), 'status' => AttendanceStatus::Present]);
        Attendance::factory()->create(['student_id' => $late->id, 'date' => today(), 'check_in_at' => today()->setTime(7, 40), 'status' => AttendanceStatus::Late]);
        Attendance::factory()->create(['student_id' => $excused->id, 'date' => today(), 'check_in_at' => null, 'status' => AttendanceStatus::Excused]);

        $response = $this->actingAs($user)->get(route('guru.absensi-kelas'));

        $response->assertOk();
        $response->assertSeeInOrder(['Masuk:', '2', 'dari 4 siswa'], false);
        $response->assertSee('Hadir: 1');
        $response->assertSee('Telat: 1');
        $response->assertSee('Izin/Sakit: 1');
        $response->assertSee('Belum Absen: 1');
        $response->assertSeeInOrder(['Ani Hadir', '07:05', 'Beni Telat', '07:40']);
        $response->assertDontSee('Eko Lulus');
    }

    public function test_guru_only_sees_the_class_they_are_homeroom_teacher_of(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siswa Kelasku']);
        $otherClassroom = Classroom::factory()->create();
        Student::factory()->create(['classroom_id' => $otherClassroom->id, 'name' => 'Siswa Kelas Lain']);

        $this->actingAs($user)->get(route('guru.absensi-kelas', ['classroom' => $otherClassroom->id]))
            ->assertOk()
            ->assertSee('Siswa Kelasku')
            ->assertDontSee('Siswa Kelas Lain');
    }

    public function test_guru_without_a_homeroom_class_sees_an_explanation(): void
    {
        $user = User::factory()->create(['role' => 'guru']);

        $this->actingAs($user)->get(route('guru.absensi-kelas'))
            ->assertOk()
            ->assertSee('Anda belum ditugaskan sebagai wali kelas');
    }

    public function test_homeroom_teacher_can_correct_statuses_leaving_blank_ones_untouched(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $forgotCard = Student::factory()->create(['classroom_id' => $classroom->id]);
        $alreadyScanned = Student::factory()->create(['classroom_id' => $classroom->id]);
        $scan = Attendance::factory()->create(['student_id' => $alreadyScanned->id, 'date' => today(), 'check_in_at' => today()->setTime(7, 0), 'status' => AttendanceStatus::Present]);
        $otherStudent = Student::factory()->create();

        $this->actingAs($user)->put(route('guru.absensi-kelas.simpan', $classroom), [
            'date' => today()->toDateString(),
            'statuses' => [
                $forgotCard->id => AttendanceStatus::Present->value,
                $alreadyScanned->id => '',
                $otherStudent->id => AttendanceStatus::Absent->value,
            ],
        ])->assertRedirect(route('guru.absensi-kelas', ['classroom' => $classroom->id, 'date' => today()->toDateString()]))
            ->assertSessionHas('success', '1 status absensi diperbarui.');

        $this->assertDatabaseHas('attendances', ['student_id' => $forgotCard->id, 'date' => today()->toDateString(), 'status' => AttendanceStatus::Present->value, 'check_in_at' => null]);
        $this->assertSame(AttendanceStatus::Present, $scan->fresh()->status);
        $this->assertDatabaseMissing('attendances', ['student_id' => $otherStudent->id]);
    }

    public function test_attendance_cannot_be_filled_for_a_future_date(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($user)->put(route('guru.absensi-kelas.simpan', $classroom), [
            'date' => today()->addDay()->toDateString(),
            'statuses' => [$student->id => AttendanceStatus::Present->value],
        ])->assertSessionHasErrors(['date' => 'Absensi tidak bisa diisi untuk tanggal yang belum terjadi.']);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_monthly_recap_counts_each_status_per_student(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $student = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Ani Rekap']);
        foreach ([['2026-09-01', AttendanceStatus::Present], ['2026-09-02', AttendanceStatus::Present], ['2026-09-03', AttendanceStatus::Late], ['2026-09-04', AttendanceStatus::Absent], ['2026-08-31', AttendanceStatus::Absent]] as [$date, $status]) {
            Attendance::factory()->create(['student_id' => $student->id, 'date' => $date, 'status' => $status]);
        }

        $this->actingAs($user)->get(route('guru.absensi-kelas', ['tampilan' => 'rekap', 'periode' => 'bulan', 'bulan' => '2026-09']))
            ->assertOk()
            ->assertSee('September 2026')
            // Hadir 2, Telat 1, Izin 0, Alpa 1, tercatat 4, kehadiran 75%. Alpa 31 Agustus tidak ikut.
            ->assertSeeInOrder(['Ani Rekap', '>2<', '>1<', '>0<', '>1<', '>4<', '75%'], false);
    }

    public function test_semester_recap_covers_july_to_december_for_the_odd_semester(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $student = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Budi Semester']);
        Attendance::factory()->create(['student_id' => $student->id, 'date' => '2026-07-15', 'status' => AttendanceStatus::Present]);
        Attendance::factory()->create(['student_id' => $student->id, 'date' => '2026-12-20', 'status' => AttendanceStatus::Excused]);
        Attendance::factory()->create(['student_id' => $student->id, 'date' => '2027-01-05', 'status' => AttendanceStatus::Absent]);

        $this->actingAs($user)->get(route('guru.absensi-kelas', ['tampilan' => 'rekap', 'periode' => 'semester', 'semester' => 'ganjil', 'tahun_ajaran' => '2026/2027']))
            ->assertOk()
            ->assertSee('Semester Ganjil 2026/2027')
            ->assertSeeInOrder(['Budi Semester', '>1<', '>0<', '>1<', '>0<', '>2<', '50%'], false);
    }

    public function test_daily_attendance_and_recap_can_be_downloaded_as_excel_and_pdf(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($user)->get(route('guru.absensi-kelas.unduh', ['classroom' => $classroom->id, 'format' => 'xlsx']))
            ->assertOk()->assertDownload();
        $this->actingAs($user)->get(route('guru.absensi-kelas.unduh', ['classroom' => $classroom->id, 'format' => 'pdf', 'tampilan' => 'rekap', 'periode' => 'bulan', 'bulan' => '2026-09']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guru_cannot_download_the_attendance_of_another_class(): void
    {
        [$user] = $this->homeroomTeacher();

        $this->actingAs($user)->get(route('guru.absensi-kelas.unduh', ['classroom' => Classroom::factory()->create()->id, 'format' => 'xlsx']))
            ->assertForbidden();
    }

    public function test_guru_cannot_change_attendance_of_a_class_they_do_not_teach(): void
    {
        [$user] = $this->homeroomTeacher();
        $otherClassroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $otherClassroom->id]);

        $this->actingAs($user)->put(route('guru.absensi-kelas.simpan', $otherClassroom), [
            'date' => today()->toDateString(),
            'statuses' => [$student->id => AttendanceStatus::Absent->value],
        ])->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }
}
