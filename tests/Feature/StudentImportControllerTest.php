<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
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
        return ['NISN', 'NIS', 'Nama Lengkap', 'Jenis Kelamin (L/P)', 'Kelas', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat', 'Nama Orang Tua', 'No. Telepon Orang Tua'];
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
        $this->assertDatabaseHas('users', ['username' => '1111111111', 'role' => 'siswa']);

        $result = session('import_result');
        $this->assertSame(2, $result->imported);
        $this->assertFalse($result->hasErrors());
    }

    public function test_import_reports_invalid_rows_without_blocking_valid_ones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $existing = Student::factory()->create(['nisn' => '3333333333']);

        $file = $this->xlsxFile($this->templateHeaders(), [
            ['1111111111', '2024001', 'Budi Santoso', 'L', '', '', '', '', '', ''],
            ['3333333333', '2024002', 'NISN Bentrok', 'L', '', '', '', '', '', ''],
            ['4444444444', '2024003', 'Kelas Tidak Ada', 'L', 'Kelas Antah Berantah', '', '', '', '', ''],
            ['5555555555', '2024004', 'Gender Salah', 'X', '', '', '', '', '', ''],
            ['', '', '', '', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $file]);

        $response->assertRedirect(route('admin.data-siswa'));
        $result = session('import_result');
        $this->assertSame(1, $result->imported);
        $this->assertCount(3, $result->errors);
        $this->assertDatabaseHas('students', ['nisn' => '1111111111']);
        $this->assertDatabaseMissing('students', ['nisn' => '4444444444']);
        $this->assertDatabaseMissing('students', ['nisn' => '5555555555']);
        // Siswa yang sudah ada sebelumnya tidak berubah/duplikat.
        $this->assertDatabaseCount('students', 2);
        $this->assertNotNull($existing->fresh());
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
