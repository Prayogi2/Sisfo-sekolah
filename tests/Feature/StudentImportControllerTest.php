<?php

namespace Tests\Feature;

use App\Enums\GuardianRelationship;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImporter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StudentImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Bikin file .xlsx sungguhan (bukan cuma isi acak) supaya bisa
     * benar-benar dibaca ulang oleh PhpSpreadsheet saat diimpor.
     *
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function xlsxFile(array $headers, array $rows, string $filename = 'siswa.xlsx'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'nurfa-import-test-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * @return list<string>
     */
    private function templateHeaders(): array
    {
        return StudentImporter::templateHeaders();
    }

    public function test_admin_can_view_the_import_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.data-siswa.import'))
            ->assertOk()
            ->assertViewIs('admin.data-siswa-import');
    }

    public function test_guru_is_forbidden_from_the_import_page(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.data-siswa.import'))->assertForbidden();
    }

    public function test_admin_can_download_the_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.data-siswa.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_import_students_from_a_valid_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '3-A']);

        $file = $this->xlsxFile($this->templateHeaders(), [
            ['1111111111', '2024001', 'Budi Santoso', 'L', '3-A', 'Jakarta', '2016-05-14', 'Jl. Contoh', 'Slamet', '081200000001'],
            ['2222222222', '2024002', 'Siti Aminah', 'p', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.data-siswa'));
        $this->assertDatabaseHas('students', ['nisn' => '1111111111', 'name' => 'Budi Santoso', 'classroom_id' => $classroom->id]);
        $this->assertDatabaseHas('students', ['nisn' => '2222222222', 'name' => 'Siti Aminah', 'gender' => 'P', 'classroom_id' => null]);
        // Username akun login pakai NIS (bukan NISN) kalau NIS-nya terisi.
        $this->assertDatabaseHas('users', ['username' => '2024001', 'role' => 'siswa']);

        $result = session('import_result');
        $this->assertSame(2, $result->imported);
        $this->assertFalse($result->hasErrors());
    }

    public function test_import_reports_invalid_rows_without_blocking_valid_ones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Student::factory()->create(['nisn' => '3333333333', 'nis' => '2024999']);

        $file = $this->xlsxFile($this->templateHeaders(), [
            ['1111111111', '2024001', 'Budi Santoso', 'L', '', '', '', '', '', ''],
            ['6666666666', '2024999', 'NIS Bentrok Milik Siswa Lain', 'L', '', '', '', '', '', ''],
            ['4444444444', '2024003', 'Kelas Tidak Ada', 'L', 'Kelas Antah Berantah', '', '', '', '', ''],
            ['5555555555', '2024004', 'Gender Salah', 'X', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.data-siswa'));
        $result = session('import_result');
        $this->assertSame(1, $result->imported);
        $this->assertSame(0, $result->updated);
        $this->assertCount(3, $result->errors);
        $this->assertDatabaseHas('students', ['nisn' => '1111111111']);
        $this->assertDatabaseMissing('students', ['nisn' => '6666666666']);
        $this->assertDatabaseMissing('students', ['nisn' => '4444444444']);
        $this->assertDatabaseMissing('students', ['nisn' => '5555555555']);
        // Siswa yang sudah ada sebelumnya tidak berubah/duplikat.
        $this->assertDatabaseCount('students', 2);
        $this->assertNotNull($existing->fresh());
    }

    /**
     * Kalau NISN di baris sudah terdaftar, importer tidak menganggapnya
     * gagal/duplikat — data Buku Induk siswa itu justru dilengkapi. Ini yang
     * memungkinkan file yang sama dipakai untuk mengisi Buku Induk siswa
     * yang sudah ada, bukan cuma menambah siswa baru.
     */
    public function test_importing_an_existing_students_nisn_fills_in_their_buku_induk_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Student::factory()->create(['nisn' => '1111111111', 'nis' => '2024001', 'name' => 'Budi Santoso']);

        $headers = $this->templateHeaders();
        $row = array_fill(0, count($headers), '');
        $row[array_search('NISN', $headers, true)] = '1111111111';
        $row[array_search('NIS', $headers, true)] = '2024001';
        $row[array_search('Nama Lengkap', $headers, true)] = 'Budi Santoso';
        $row[array_search('Jenis Kelamin (L/P)', $headers, true)] = 'L';
        $row[array_search('Nama Panggilan', $headers, true)] = 'Budi';
        $row[array_search('Agama', $headers, true)] = 'Islam';
        $row[array_search('Golongan Darah', $headers, true)] = 'O';
        $row[array_search('Ayah - Nama', $headers, true)] = 'Slamet Santoso';
        $row[array_search('Ayah - Jenis Kelamin (L/P)', $headers, true)] = 'L';
        $row[array_search('B. Status Peserta Didik', $headers, true)] = 'Peserta Didik Baru';

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $this->xlsxFile($headers, [$row])]);

        $response->assertRedirect(route('admin.data-siswa'));
        $result = session('import_result');
        $this->assertSame(0, $result->imported);
        $this->assertSame(1, $result->updated);
        $this->assertFalse($result->hasErrors());
        // Tidak membuat siswa/akun login baru — ini pembaruan siswa yang sudah ada.
        $this->assertDatabaseCount('students', 1);

        $existing->refresh();
        $this->assertSame('Budi', $existing->profile->nickname);
        $this->assertSame('islam', $existing->profile->religion->value);
        $this->assertSame('O', $existing->profile->blood_type->value);
        $this->assertSame('Peserta Didik Baru', $existing->academicRecord->entry_status);
        $father = $existing->guardians->firstWhere('relationship', GuardianRelationship::Father);
        $this->assertNotNull($father);
        $this->assertSame('Slamet Santoso', $father->name);
    }

    public function test_import_rejects_a_file_missing_required_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $file = $this->xlsxFile(['NISN', 'Nama Lengkap'], [
            ['1111111111', 'Budi Santoso'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('students', 0);
    }

    public function test_import_rejects_a_non_spreadsheet_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = UploadedFile::fake()->create('siswa.pdf', 10, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('students', 0);
    }

    public function test_guru_cannot_import_students(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $file = $this->xlsxFile($this->templateHeaders(), [
            ['1111111111', '2024001', 'Budi Santoso', 'L', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($guru)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertForbidden();
        $this->assertDatabaseCount('students', 0);
    }
}
