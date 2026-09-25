<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ClassroomImportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $rows  Nama Siswa, NIS, NISN
     * @param  list<string>  $headers
     */
    private function excel(array $rows, array $headers = ['Nama Siswa', 'NIS', 'NISN']): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers], null, 'A1');
        foreach ($rows as $index => $row) {
            foreach ($row as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $index + 2], $value, DataType::TYPE_STRING);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'kelas-import-').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile($path, 'siswa-kelas.xlsx', null, null, true);
    }

    public function test_valid_rows_are_placed_and_invalid_rows_are_reported_per_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '5-A']);
        $otherClassroom = Classroom::factory()->create(['name' => '3-A']);
        $byNisn = Student::factory()->create(['name' => 'Siti Aminah', 'nisn' => '0012345678', 'classroom_id' => null]);
        $byNis = Student::factory()->create(['name' => 'Budi Santoso', 'nis' => 'NIS-777', 'classroom_id' => null]);
        $inOtherClass = Student::factory()->create(['name' => 'Ahmad', 'nisn' => '2222222222', 'classroom_id' => $otherClassroom->id]);
        $alreadyHere = Student::factory()->create(['name' => 'Rina', 'nisn' => '3333333333', 'classroom_id' => $classroom->id]);

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->excel([
            ['Siti Aminah', '', '0012345678'],
            ['budi  santoso', 'NIS-777', ''],
            ['Tidak Ada', '', '9999999999'],
            ['', '', '1111111111'],
            ['Ahmad', '', '2222222222'],
            ['Rina', '', '3333333333'],
        ])])->assertRedirect(route('admin.pembagian-kelas'));

        $result = session('classroom_import_result');
        $this->assertSame(2, $result->placed);
        $this->assertSame([
            'Baris 4: NISN 9999999999 tidak ditemukan di data siswa.',
            'Baris 5: data tidak lengkap — Nama Siswa wajib diisi.',
            'Baris 6: Ahmad sudah terdaftar di kelas 3-A. Keluarkan dulu lewat Atur Siswa di kelas 3-A.',
        ], $result->errors);
        $this->assertSame(['Baris 7: Rina sudah terdaftar di kelas ini.'], $result->skipped);

        $this->assertSame($classroom->id, $byNisn->fresh()->classroom_id);
        $this->assertSame($classroom->id, $byNis->fresh()->classroom_id);
        $this->assertSame($otherClassroom->id, $inOtherClass->fresh()->classroom_id);
        $this->assertSame($classroom->id, $alreadyHere->fresh()->classroom_id);
    }

    public function test_a_name_that_does_not_match_the_nisn_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['name' => 'Siti Aminah', 'nisn' => '1234567890', 'classroom_id' => null]);

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->excel([
            ['Orang Lain', '', '1234567890'],
        ])]);

        $this->assertSame(0, session('classroom_import_result')->placed);
        $this->assertNull($student->fresh()->classroom_id);
    }

    public function test_rows_beyond_capacity_are_reported_but_earlier_rows_are_kept(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '1-A', 'capacity' => 1]);
        $first = Student::factory()->create(['name' => 'Pertama', 'nisn' => '1111111111', 'classroom_id' => null]);
        $second = Student::factory()->create(['name' => 'Kedua', 'nisn' => '2222222222', 'classroom_id' => null]);

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->excel([
            ['Pertama', '', '1111111111'],
            ['Kedua', '', '2222222222'],
        ])]);

        $result = session('classroom_import_result');
        $this->assertSame(1, $result->placed);
        $this->assertStringContainsString('Baris 3: Kedua tidak dimasukkan karena kelas 1-A sudah penuh', $result->errors[0]);
        $this->assertSame($classroom->id, $first->fresh()->classroom_id);
        $this->assertNull($second->fresh()->classroom_id);
    }

    public function test_file_with_wrong_columns_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->excel([['x', 'y', 'z']], ['A', 'B', 'C'])])
            ->assertSessionHasErrors('file');
    }

    public function test_template_has_the_name_nis_and_nisn_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create(['name' => '5-A']);

        $response = $this->actingAs($admin)->get(route('admin.pembagian-kelas.import.template', $classroom));

        $response->assertOk();
        $this->assertStringContainsString('template-import-siswa-kelas-5-a.xlsx', $response->headers->get('Content-Disposition'));
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $this->assertSame(['Nama Siswa', 'NIS', 'NISN'], $sheet->rangeToArray('A1:C1')[0]);
    }

    public function test_guru_cannot_import_students_into_a_classroom(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $classroom = Classroom::factory()->create();

        $this->actingAs($guru)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->excel([])])->assertForbidden();
        $this->actingAs($guru)->get(route('admin.pembagian-kelas.import.template', $classroom))->assertForbidden();
    }
}
