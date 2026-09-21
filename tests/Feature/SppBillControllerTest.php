<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SppSettingSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SppBillControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(SppSettingSeeder::class);
    }

    public function test_admin_can_view_the_spp_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.laporan-spp'))->assertOk();
    }

    public function test_guru_is_forbidden_from_the_spp_report(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.laporan-spp'))->assertForbidden();
    }

    public function test_generating_bills_creates_one_bill_per_active_student(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory(3)->create(['status' => StudentStatus::Active]);
        Student::factory()->create(['status' => StudentStatus::Graduated]);

        $response = $this->actingAs($admin)->post(route('admin.laporan-spp.generate'));

        $response->assertRedirect();
        $this->assertDatabaseCount('spp_bills', 3);
        $this->assertDatabaseHas('spp_bills', [
            'period' => now()->startOfMonth()->toDateString(),
            'amount' => 350000,
        ]);
    }

    public function test_generating_bills_twice_does_not_duplicate_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory(2)->create(['status' => StudentStatus::Active]);

        $this->actingAs($admin)->post(route('admin.laporan-spp.generate'));
        $this->actingAs($admin)->post(route('admin.laporan-spp.generate'));

        $this->assertDatabaseCount('spp_bills', 2);
    }

    public function test_report_totals_only_count_approved_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = SppBill::factory()->create(['amount' => 350000]);
        SppPayment::factory()->approved()->create(['spp_bill_id' => $bill->id, 'amount' => 200000]);
        SppPayment::factory()->create(['spp_bill_id' => $bill->id, 'amount' => 150000]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-spp', [
            'period' => $bill->period->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['total_tagihan'] === 350000
            && $stats['diterima'] === 200000
            && $stats['tunggakan'] === 150000
            && $stats['cicil'] === 1);
    }

    public function test_report_can_be_filtered_by_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetClassroom = Classroom::factory()->create();
        $studentInTargetClass = Student::factory()->create(['classroom_id' => $targetClassroom->id]);
        $studentInOtherClass = Student::factory()->create(['classroom_id' => Classroom::factory()]);

        SppBill::factory()->create(['student_id' => $studentInTargetClass->id]);
        SppBill::factory()->create(['student_id' => $studentInOtherClass->id]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-spp', [
            'classroom_id' => $targetClassroom->id,
        ]));

        $response->assertOk();
        $response->assertSee($studentInTargetClass->name);
        $response->assertDontSee($studentInOtherClass->name);
    }

    public function test_report_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paidStudent = Student::factory()->create();
        $unpaidStudent = Student::factory()->create();

        $paidBill = SppBill::factory()->create(['student_id' => $paidStudent->id, 'amount' => 350000]);
        SppPayment::factory()->approved()->create(['spp_bill_id' => $paidBill->id, 'amount' => 350000]);
        SppBill::factory()->create(['student_id' => $unpaidStudent->id, 'amount' => 350000]);

        $response = $this->actingAs($admin)->get(route('admin.laporan-spp', ['status' => 'lunas']));

        $response->assertOk();
        $response->assertSee($paidStudent->name);
        $response->assertDontSee($unpaidStudent->name);
    }
}
