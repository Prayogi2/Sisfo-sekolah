<?php

namespace Tests\Feature;

use App\Enums\GuardianRelationship;
use App\Enums\Religion;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentRecordControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guru_is_forbidden_from_the_buku_induk(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.buku-induk'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_the_buku_induk(): void
    {
        $this->get(route('admin.buku-induk'))->assertRedirect(route('login'));
    }

    public function test_renders_an_empty_state_when_no_student_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk'));

        $response->assertOk();
        $response->assertSee('Tidak ada data siswa yang cocok dengan pencarian.');
    }

    public function test_selects_the_first_student_alphabetically_when_none_is_requested(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['name' => 'Zulkifli']);
        Student::factory()->create(['name' => 'Ahmad Fauzi']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk'));

        $response->assertViewHas('student', fn (Student $student): bool => $student->name === 'Ahmad Fauzi');
    }

    public function test_renders_the_identity_of_the_requested_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '6-A']);
        $student = Student::factory()->create([
            'name' => 'Siti Aminah',
            'nisn' => '0098761234',
            'classroom_id' => $classroom->id,
            'birth_place' => 'Cianjur',
        ]);
        StudentProfile::factory()->create([
            'student_id' => $student->id,
            'nik' => '3201234567890001',
            'village' => 'Sukamaju',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSee('Siti Aminah');
        $response->assertSee('0098761234');
        $response->assertSee('3201234567890001');
        $response->assertSee('Cianjur');
        $response->assertSee('Sukamaju');
        $response->assertSee('6-A');
    }

    public function test_renders_the_academic_record_and_graduation_data_of_the_selected_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['name' => 'Budi Santoso']);
        StudentAcademicRecord::factory()->create([
            'student_id' => $student->id,
            'kindergarten_origin' => 'TK Nurul Huda',
            'kindergarten_npsn' => 'NPSN-001122',
            'entry_status' => 'Peserta Didik Baru',
            'entry_classroom' => 'Kelas 1',
            'graduation_certificate_number' => 'IJZ-2024-077',
            'graduation_skl_number' => 'SKL-2024-055',
            'graduation_year' => 2024,
            'continued_to' => 'MTs Nurul Falaq',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSee('TK Nurul Huda');
        $response->assertSee('NPSN-001122');
        $response->assertSee('Peserta Didik Baru');
        $response->assertSee('Kelas 1');
        $response->assertSee('IJZ-2024-077');
        $response->assertSee('SKL-2024-055');
        $response->assertSee('2024');
        $response->assertSee('MTs Nurul Falaq');
    }

    public function test_renders_the_father_and_mother_of_the_selected_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();
        $father = Guardian::factory()->create([
            'name' => 'Bapak Udin',
            'relationship' => GuardianRelationship::Father,
            'occupation' => 'Petani',
            'birth_place' => 'Garut',
            'religion' => Religion::Islam,
        ]);
        $mother = Guardian::factory()->create([
            'name' => 'Ibu Aminah',
            'relationship' => GuardianRelationship::Mother,
            'occupation' => 'Ibu Rumah Tangga',
        ]);
        $student->guardians()->attach([$father->id, $mother->id]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSee('Bapak Udin');
        $response->assertSee('Petani');
        $response->assertSee('Garut');
        $response->assertSee('Islam');
        $response->assertSee('Ibu Aminah');
        $response->assertSee('Ibu Rumah Tangga');
    }

    public function test_reports_a_student_without_a_linked_guardian(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['parent_name' => 'Bapak Sopian']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSee('Siswa ini belum memiliki data wali murid terhubung.');
        $response->assertSee('Bapak Sopian');
    }

    public function test_reports_a_student_whose_buku_induk_profile_is_incomplete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSee('Data identitas buku induk siswa ini belum dilengkapi.');
        $response->assertSee('Riwayat pendidikan siswa ini belum dilengkapi.');
    }

    public function test_search_narrows_the_student_picker_to_the_matching_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['name' => 'Ahmad Fauzi']);
        Student::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['search' => 'Siti']));

        $response->assertViewHas('students', fn ($students): bool => $students->pluck('name')->all() === ['Siti Aminah']);
        $response->assertViewHas('student', fn (Student $student): bool => $student->name === 'Siti Aminah');
    }

    public function test_search_matches_a_student_by_nisn(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['name' => 'Ahmad Fauzi', 'nisn' => '1111111111']);
        Student::factory()->create(['name' => 'Siti Aminah', 'nisn' => '2222222222']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['search' => '2222222222']));

        $response->assertViewHas('student', fn (Student $student): bool => $student->name === 'Siti Aminah');
    }

    public function test_falls_back_to_the_first_student_when_the_requested_id_is_filtered_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ahmad = Student::factory()->create(['name' => 'Ahmad Fauzi']);
        Student::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', [
            'search' => 'Siti',
            'student' => $ahmad->id,
        ]));

        $response->assertViewHas('student', fn (Student $student): bool => $student->name === 'Siti Aminah');
    }

    public function test_admin_can_update_the_buku_induk_of_a_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.buku-induk.update', $student), [
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'name' => 'Nama Baru',
            'gender' => 'P',
            'status' => 'active',
            'nickname' => 'Nana',
            'residence_type' => 'orang_tua',
            'transportation' => 'sepeda_motor',
            'distance_km' => 3,
            'travel_duration_minutes' => 15,
            'kindergarten_origin' => 'TK Melati',
            'entry_classroom' => 'Kelas 1',
            'mother_name' => 'Ibu Sari',
            'mother_birth_place' => 'Bandung',
            'mother_religion' => 'islam',
            'progress_academic_year' => '2026/2027',
            'progress_semester' => 'ganjil',
            'promotion_status' => 'naik',
        ]);

        $response->assertRedirect(route('admin.buku-induk', ['student' => $student->id]));
        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Nama Baru']);
        $this->assertDatabaseHas('student_profiles', [
            'student_id' => $student->id,
            'nickname' => 'Nana',
            'residence_type' => 'orang_tua',
            'transportation' => 'sepeda_motor',
            'distance_km' => 3,
            'travel_duration_minutes' => 15,
        ]);
        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $student->id,
            'kindergarten_origin' => 'TK Melati',
            'entry_classroom' => 'Kelas 1',
        ]);
        $this->assertDatabaseHas('guardians', ['name' => 'Ibu Sari', 'birth_place' => 'Bandung', 'religion' => 'islam']);
        $this->assertSame(['Ibu Sari'], $student->guardians()->pluck('name')->all());
        $this->assertDatabaseHas('student_progress_notes', ['student_id' => $student->id, 'academic_year' => '2026/2027', 'promotion_status' => 'naik']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bukuIndukPayload(Student $student, array $overrides = []): array
    {
        return [
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'name' => $student->name,
            'gender' => 'L',
            'status' => 'active',
            'progress_academic_year' => '2026/2027',
            'progress_semester' => 'ganjil',
            'promotion_status' => 'naik',
            ...$overrides,
        ];
    }

    public function test_admin_can_record_report_book_exam_and_certificate_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $this->actingAs($admin)->put(route('admin.buku-induk.update', $student), $this->bukuIndukPayload($student, [
            'report_book_serial_number' => 'RPT-0042',
            'exam_number' => '2-26-05-01-001-002-3',
            'graduation_certificate_number' => 'MI-26-0012345',
        ]))->assertRedirect(route('admin.buku-induk', ['student' => $student->id]));

        $this->assertDatabaseHas('student_academic_records', [
            'student_id' => $student->id,
            'report_book_serial_number' => 'RPT-0042',
            'exam_number' => '2-26-05-01-001-002-3',
            'graduation_certificate_number' => 'MI-26-0012345',
        ]);
    }

    public function test_buku_induk_rejects_a_non_numeric_nisn_and_a_future_birth_date(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $this->actingAs($admin)->put(route('admin.buku-induk.update', $student), $this->bukuIndukPayload($student, [
            'nisn' => '12AB567890',
            'birth_date' => now()->addDay()->toDateString(),
        ]))->assertSessionHasErrors(['nisn', 'birth_date']);

        $this->assertDatabaseHas('students', ['id' => $student->id, 'nisn' => $student->nisn]);
    }

    /**
     * Guru & siswa tidak cukup hanya disembunyikan menunya: akses langsung
     * lewat URL ke form maupun proses simpan Buku Induk harus ditolak.
     */
    public function test_guru_and_siswa_cannot_open_or_submit_the_buku_induk_form(): void
    {
        $student = Student::factory()->create(['name' => 'Nama Asli']);

        foreach (['guru', 'siswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.buku-induk.edit', $student))->assertForbidden();
            $this->actingAs($user)->put(route('admin.buku-induk.update', $student), $this->bukuIndukPayload($student, [
                'name' => 'Nama Palsu',
                'exam_number' => 'PALSU-001',
            ]))->assertForbidden();
        }

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Nama Asli']);
        $this->assertDatabaseMissing('student_academic_records', ['exam_number' => 'PALSU-001']);
    }

    public function test_the_printable_buku_induk_shows_the_new_riwayat_pendidikan_sections(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();
        StudentAcademicRecord::factory()->create([
            'student_id' => $student->id,
            'kindergarten_npsn' => 'NPSN-998877',
            'entry_classroom' => 'Kelas 1',
            'graduation_skl_number' => 'SKL-2024-099',
            'transfer_out_letter_number' => 'SRT-01/2025',
            'exit_classroom' => 'Kelas 4',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk.student.download', $student));

        $response->assertOk();
        $response->assertSee('A. Pendidikan Sebelumnya', false);
        $response->assertSee('NPSN-998877');
        $response->assertSee('B. Status Peserta Didik', false);
        $response->assertSee('Kelas 1');
        $response->assertSee('C. Lulus', false);
        $response->assertSee('SKL-2024-099');
        $response->assertSee('D. Meninggalkan Sekolah', false);
        $response->assertSee('SRT-01/2025');
        $response->assertSee('E. Putus Sekolah', false);
        $response->assertSee('Kelas 4');
        $response->assertDontSee('No. Kartu Keluarga');
    }

    public function test_escapes_a_dangerous_student_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['name' => "<script>alert('xss')</script>"]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk'));

        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }
}
