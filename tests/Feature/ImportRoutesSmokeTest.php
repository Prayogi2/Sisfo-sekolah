<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Semua jalur import diberi file .xlsx yang lolos validasi mimes tapi isinya
 * rusak. Tidak boleh ada yang berujung error 500 — harus jadi pesan.
 */
class ImportRoutesSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Diawali signature ZIP supaya dikenali sebagai .xlsx, tapi arsipnya rusak.
     */
    private function corruptXlsx(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('rusak.xlsx', "PK\x03\x04 isi arsip rusak");
    }

    public function test_a_corrupt_file_on_the_student_import_is_reported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.data-siswa.import.store'), ['file' => $this->corruptXlsx()])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_a_corrupt_file_on_the_classroom_import_is_reported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)->post(route('admin.pembagian-kelas.import', $classroom), ['file' => $this->corruptXlsx()])
            ->assertSessionHasErrors('file');
    }

    public function test_a_corrupt_file_on_the_report_book_grade_import_is_reported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $this->actingAs($admin)->post(route('admin.buku-induk.nilai.import.xlsx', $student), ['file' => $this->corruptXlsx()])
            ->assertSessionHasErrors('file');
    }

    public function test_a_corrupt_file_on_the_question_bank_import_is_reported(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $subject = Subject::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => Classroom::factory()->create()->id]);

        $this->actingAs($user)->post(route('guru.bank-soal.import'), ['subject_id' => $subject->id, 'file' => $this->corruptXlsx()])
            ->assertSessionHasErrors('file');
    }

    public function test_every_import_template_can_be_downloaded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin)->get(route('admin.data-siswa.import.template'))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('admin.pembagian-kelas.import.template', $classroom))->assertOk()->assertDownload();
        $this->actingAs($admin)->get(route('admin.buku-induk.nilai.export.xlsx', $student))->assertOk()->assertDownload();

        $guru = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guru->id]);
        $teacher->teachingAssignments()->create(['subject_id' => Subject::factory()->create()->id, 'classroom_id' => $classroom->id]);
        $this->actingAs($guru)->get(route('guru.bank-soal.import.template'))->assertOk()->assertDownload();
    }
}
