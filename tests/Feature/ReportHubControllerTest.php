<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportHubControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_open_the_report_hub(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.laporan'))->assertOk();
    }

    public function test_guru_is_forbidden_from_the_report_hub(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.laporan'))->assertForbidden();
    }

    public function test_attendance_summary_counts_today_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Attendance::factory()->create(['date' => now()->toDateString(), 'status' => AttendanceStatus::Present]);
        Attendance::factory()->create(['date' => now()->toDateString(), 'status' => AttendanceStatus::Late]);
        Attendance::factory()->create(['date' => now()->subDay()->toDateString(), 'status' => AttendanceStatus::Present]);

        $response = $this->actingAs($admin)->get(route('admin.laporan'));

        // Hadir menggabungkan yang tepat waktu dan yang telat.
        $response->assertViewHas('attendance', fn (array $summary) => $summary['hadir'] === 2 && $summary['telat'] === 1);
    }

    public function test_spp_summary_only_counts_approved_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = SppBill::factory()->create([
            'amount' => 350000,
            'period' => now()->startOfMonth()->toDateString(),
        ]);
        SppPayment::factory()->approved()->create(['spp_bill_id' => $bill->id, 'amount' => 200000]);
        SppPayment::factory()->create(['spp_bill_id' => $bill->id, 'amount' => 150000]);

        $response = $this->actingAs($admin)->get(route('admin.laporan'));

        $response->assertViewHas('spp', fn (array $summary) => $summary['tagihan'] === 350000
            && $summary['diterima'] === 200000
            && $summary['tunggakan'] === 150000
            && $summary['menunggu_verifikasi'] === 1);
    }

    public function test_summary_survives_an_empty_database(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.laporan'));

        $response->assertOk();
        $response->assertViewHas('grades', fn (array $summary) => $summary['rata_rata'] === null);
    }
}
