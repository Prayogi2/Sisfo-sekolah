<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentCardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_open_a_students_printable_qr_card(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['qr_token' => null]);

        $response = $this->actingAs($admin)->get(route('admin.data-siswa.kartu-qr', $student));

        $response->assertOk()->assertSee($student->name)->assertSee($student->fresh()->qr_token);
        $this->assertNotNull($student->fresh()->qr_token);
    }

    public function test_non_admin_cannot_open_the_admin_qr_card_page(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $student = Student::factory()->create();

        $this->actingAs($guru)->get(route('admin.data-siswa.kartu-qr', $student))->assertForbidden();
    }

    /**
     * Unduhan PNG di halaman ini hanya mengambil gambar QR-nya saja (lewat
     * fetch ke #qrImage), bukan merender seluruh kartu seperti fitur kartu
     * digital siswa yang sudah dihapus.
     */
    public function test_admin_qr_card_page_offers_a_qr_only_png_download(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.data-siswa.kartu-qr', $student));

        $response->assertOk();
        $response->assertSee('id="downloadQrBtn"', false);
        $response->assertSee('id="qrImage"', false);
    }
}
