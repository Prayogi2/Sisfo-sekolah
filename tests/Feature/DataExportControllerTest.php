<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class DataExportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return list<list<mixed>>
     */
    private function sheetRows(BinaryFileResponse $response): array
    {
        return IOFactory::load($response->getFile()->getPathname())->getActiveSheet()->toArray();
    }

    public function test_admin_downloads_the_teacher_list_as_excel_and_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Teacher::factory()->create(['name' => 'Ustadzah Aminah', 'nip' => '1987001']);

        $response = $this->actingAs($admin)->get(route('admin.data-guru.unduh', 'xlsx'))->assertOk()->assertDownload();
        $rows = $this->sheetRows($response->baseResponse);
        $this->assertSame(['NIP/NUPTK', 'Nama', 'L/P', 'Mapel & Kelas', 'Wali Kelas', 'No. WhatsApp', 'Email', 'Akses Admin'], $rows[0]);
        $this->assertSame(['1987001', 'Ustadzah Aminah'], array_slice($rows[1], 0, 2));

        $this->actingAs($admin)->get(route('admin.data-guru.unduh', 'pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_student_download_follows_the_classroom_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siswa Dipilih']);
        Student::factory()->create(['name' => 'Siswa Kelas Lain']);

        $response = $this->actingAs($admin)->get(route('admin.data-siswa.unduh', ['format' => 'xlsx', 'classroom_id' => $classroom->id]))->assertOk();
        $names = array_column(array_slice($this->sheetRows($response->baseResponse), 1), 2);

        $this->assertSame(['Siswa Dipilih'], $names);
    }

    public function test_homeroom_teacher_downloads_their_class_inventory_but_not_another_class(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Kursi Siswa', 'good_quantity' => 26, 'damaged_quantity' => 0, 'sort_order' => 1]);

        $response = $this->actingAs($user)->get(route('guru.inventaris.unduh', [$classroom, 'xlsx']))->assertOk();
        // Angka 0 harus tetap tertulis, bukan sel kosong.
        $this->assertSame(['Kursi Siswa', '26', '26', '0'], array_slice($this->sheetRows($response->baseResponse)[1], 1, 4));

        $this->actingAs($user)->get(route('guru.inventaris.unduh', [Classroom::factory()->create(), 'pdf']))->assertForbidden();
    }

    public function test_an_unknown_format_is_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.data-guru.unduh', 'csv'))->assertNotFound();
    }

    public function test_guru_cannot_download_admin_data(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.data-siswa.unduh', 'xlsx'))->assertForbidden();
    }
}
