<?php

namespace Tests\Feature;

use App\Models\ReportBookGrade;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportBookControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Unduh file export Excel nilai buku induk siswa, lalu kembalikan
     * sebagai file upload supaya bisa di-import ulang.
     */
    private function exportedFile(User $admin, Student $student): UploadedFile
    {
        $response = $this->actingAs($admin)->get(route('admin.buku-induk.nilai.export.xlsx', $student));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'nilai-test-').'.xlsx';
        copy($response->baseResponse->getFile()->getPathname(), $path);

        return new UploadedFile($path, 'nilai.xlsx', null, null, true);
    }

    public function test_admin_can_save_subjects_scores_and_year_rows(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();
        ReportBookGrade::factory()->create(['student_id' => $student->id, 'subject_name' => 'Mapel Lama']);

        $this->actingAs($admin)->put(route('admin.buku-induk.nilai.update', $student), [
            'subjects' => [
                ['name' => 'Matematika', 'scores' => ['grade_1_semester_1' => '80', 'grade_1_semester_2' => '85.5']],
                ['name' => 'Fikih', 'scores' => ['grade_1_semester_1' => '90']],
            ],
            'years' => [
                1 => ['academic_year' => '2024/2025', 'promoted_to' => 'Kelas 2'],
            ],
        ])->assertRedirect(route('admin.buku-induk', ['student' => $student->id, 'tab' => 'akademik']));

        $subjects = $student->reportBookGrades()->get();
        $this->assertSame(['Matematika', 'Fikih'], $subjects->pluck('subject_name')->all());
        $this->assertSame(85.5, $subjects->first()->grade_1_semester_2);
        $this->assertNull($subjects->last()->grade_1_semester_2);
        $this->assertDatabaseHas('report_book_years', ['student_id' => $student->id, 'grade_level' => 1, 'academic_year' => '2024/2025', 'promoted_to' => 'Kelas 2']);
        $this->assertDatabaseMissing('report_book_grades', ['subject_name' => 'Mapel Lama']);
    }

    public function test_buku_induk_page_shows_the_table_with_totals_and_averages(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();
        ReportBookGrade::factory()->create(['student_id' => $student->id, 'subject_name' => 'Matematika', 'grade_1_semester_1' => 80]);
        ReportBookGrade::factory()->create(['student_id' => $student->id, 'subject_name' => 'Fikih', 'grade_1_semester_1' => 91, 'sort_order' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk', ['student' => $student->id]));

        $response->assertOk();
        $response->assertSeeInOrder(['NILAI LAPORAN HASIL BELAJAR PESERTA DIDIK', 'Tahun Ajaran', 'Kelas 1', 'Kelas 2', 'Kelas 3', 'Sem 1', 'Sem 2']);
        $response->assertSeeInOrder(['Kelas 4', 'Kelas 5', 'Kelas 6']);
        // Jumlah 80 + 91 = 171, rata-rata 85,5.
        $response->assertSeeInOrder(['Jumlah Nilai', '171', 'Nilai Rata-rata', '85,5', 'Naik ke Kelas']);
    }

    public function test_invalid_scores_and_academic_years_are_rejected(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();

        $this->actingAs($admin)->put(route('admin.buku-induk.nilai.update', $student), [
            'subjects' => [
                ['name' => 'Matematika', 'scores' => ['grade_1_semester_1' => '101']],
                ['name' => '', 'scores' => ['grade_1_semester_1' => '80']],
            ],
            'years' => [1 => ['academic_year' => '2024-2025']],
        ])->assertSessionHasErrors(['subjects.0.scores.grade_1_semester_1', 'subjects.1.name', 'years.1.academic_year']);

        $this->assertDatabaseCount('report_book_grades', 0);
    }

    public function test_exported_excel_can_be_imported_back_into_the_same_student(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create(['nisn' => '0012345678']);
        ReportBookGrade::factory()->create(['student_id' => $student->id, 'subject_name' => 'Matematika', 'grade_2_semester_1' => 88.5, 'grade_6_semester_2' => 92]);
        $student->reportBookYears()->create(['grade_level' => 2, 'academic_year' => '2025/2026', 'promoted_to' => 'Kelas 3']);

        $file = $this->exportedFile($admin, $student);
        $student->reportBookGrades()->delete();
        $student->reportBookYears()->delete();

        $this->actingAs($admin)->post(route('admin.buku-induk.nilai.import.xlsx', $student), ['file' => $file])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $subject = $student->reportBookGrades()->sole();
        $this->assertSame('Matematika', $subject->subject_name);
        $this->assertSame(88.5, $subject->grade_2_semester_1);
        $this->assertSame(92.0, $subject->grade_6_semester_2);
        $this->assertDatabaseHas('report_book_years', ['student_id' => $student->id, 'grade_level' => 2, 'academic_year' => '2025/2026', 'promoted_to' => 'Kelas 3']);
    }

    public function test_import_rejects_invalid_scores_without_touching_existing_data(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();
        ReportBookGrade::factory()->create(['student_id' => $student->id, 'subject_name' => 'Matematika']);

        $file = $this->exportedFile($admin, $student);
        $spreadsheet = IOFactory::load($file->getRealPath());
        $spreadsheet->getActiveSheet()->setCellValue('C15', 'sembilan puluh');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($file->getRealPath());

        $this->actingAs($admin)->post(route('admin.buku-induk.nilai.import.xlsx', $student), ['file' => $file])
            ->assertSessionHasErrors('subjects.0.scores.grade_1_semester_1');

        $this->assertSame(['Matematika'], $student->reportBookGrades()->pluck('subject_name')->all());
    }

    public function test_import_rejects_a_file_belonging_to_another_student(): void
    {
        $admin = $this->admin();
        $owner = Student::factory()->create();
        $otherStudent = Student::factory()->create();
        ReportBookGrade::factory()->create(['student_id' => $owner->id]);

        $this->actingAs($admin)->post(route('admin.buku-induk.nilai.import.xlsx', $otherStudent), ['file' => $this->exportedFile($admin, $owner)])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('report_book_grades', 1);
    }

    public function test_import_rejects_a_spreadsheet_with_a_different_layout(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create();

        $template = $this->actingAs($admin)->get(route('admin.data-siswa.import.template'));
        $path = tempnam(sys_get_temp_dir(), 'nilai-test-').'.xlsx';
        copy($template->baseResponse->getFile()->getPathname(), $path);

        $this->actingAs($admin)->post(route('admin.buku-induk.nilai.import.xlsx', $student), ['file' => new UploadedFile($path, 'salah.xlsx', null, null, true)])
            ->assertSessionHasErrors('file');
    }

    public function test_pdf_is_generated_per_student(): void
    {
        $admin = $this->admin();
        $student = Student::factory()->create(['nisn' => '0099887766', 'name' => 'Siti Aminah']);
        ReportBookGrade::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($admin)->get(route('admin.buku-induk.nilai.pdf', $student));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('nilai-buku-induk-0099887766-siti-aminah.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_guru_and_siswa_cannot_access_report_book_routes(): void
    {
        $student = Student::factory()->create();

        foreach (['guru', 'siswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.buku-induk.nilai.edit', $student))->assertForbidden();
            $this->actingAs($user)->put(route('admin.buku-induk.nilai.update', $student), ['subjects' => [['name' => 'Palsu']]])->assertForbidden();
            $this->actingAs($user)->get(route('admin.buku-induk.nilai.export.xlsx', $student))->assertForbidden();
            $this->actingAs($user)->get(route('admin.buku-induk.nilai.pdf', $student))->assertForbidden();
        }

        $this->assertDatabaseCount('report_book_grades', 0);
    }
}
