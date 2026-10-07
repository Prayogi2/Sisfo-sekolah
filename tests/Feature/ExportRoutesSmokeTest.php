<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\InventoryItem;
use App\Models\SppBill;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentProfile;
use App\Models\StudentViolation;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Semua jalur unduh/ekspor dipanggil sungguhan — sekali dengan data kosong
 * (kondisi paling sering memicu error) dan sekali dengan data terisi.
 */
class ExportRoutesSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return list<string>
     */
    private function exportUrls(): array
    {
        return [
            route('admin.buku-induk.export.xlsx'),
            route('admin.laporan-absensi.export.csv'),
            route('admin.laporan-absensi.export.pdf'),
            route('admin.laporan-nilai.export.csv'),
            route('admin.laporan-nilai.export.pdf'),
            route('admin.laporan-spp.export.csv'),
            route('admin.laporan-spp.export.pdf'),
            route('admin.prestasi-pelanggaran.export.xlsx'),
            route('admin.prestasi-pelanggaran.export.pdf'),
        ];
    }

    public function test_every_export_works_when_there_is_no_data_yet(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $urls = [...$this->exportUrls()];
        foreach (['xlsx', 'pdf'] as $format) {
            $urls[] = route('admin.data-guru.unduh', $format);
            $urls[] = route('admin.data-siswa.unduh', $format);
            $urls[] = route('admin.inventaris.unduh', ['classroom' => $classroom, 'format' => $format]);
        }

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_an_unknown_download_format_is_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.data-siswa.unduh', 'docx'))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.data-guru.unduh', 'docx'))->assertNotFound();
    }

    public function test_every_export_works_with_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        StudentProfile::factory()->create(['student_id' => $student->id]);
        Attendance::factory()->create(['student_id' => $student->id, 'date' => now()->toDateString()]);
        Grade::factory()->create(['student_id' => $student->id, 'subject_id' => Subject::factory()->create()->id]);
        SppBill::factory()->create(['student_id' => $student->id]);
        StudentAchievement::create(['student_id' => $student->id, 'category' => 'academic', 'title' => 'Juara 1 Tahfidz', 'achieved_at' => now()->toDateString()]);
        StudentViolation::create(['student_id' => $student->id, 'severity' => 'light', 'title' => 'Terlambat', 'description' => 'Datang lewat jam masuk.', 'occurred_at' => now()->toDateString()]);
        InventoryItem::factory()->create(['classroom_id' => $classroom->id]);

        foreach ([...$this->exportUrls(), route('admin.buku-induk.student.export.xlsx', $student), route('admin.inventaris.unduh', ['classroom' => $classroom, 'format' => 'xlsx']), route('admin.inventaris.unduh', ['classroom' => $classroom, 'format' => 'pdf'])] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_guru_cannot_reach_the_admin_exports(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        foreach ($this->exportUrls() as $url) {
            $this->actingAs($guru)->get($url)->assertForbidden();
        }
    }
}
