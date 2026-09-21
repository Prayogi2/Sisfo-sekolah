<?php

namespace Tests\Feature;

use App\Enums\SppBillStatus;
use App\Enums\SppPaymentStatus;
use App\Models\Guardian;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SppPaymentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: Guardian, 2: Student}
     */
    private function waliWithChild(): array
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $student = Student::factory()->create();
        $guardian->students()->attach($student);

        return [$wali, $guardian, $student];
    }

    public function test_wali_can_upload_a_payment_proof(): void
    {
        Storage::fake('public');

        [$wali, $guardian, $student] = $this->waliWithChild();
        $bill = SppBill::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($wali)->post(route('siswa.spp.store'), [
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('spp_payments', [
            'spp_bill_id' => $bill->id,
            'guardian_id' => $guardian->id,
            'amount' => 350000,
            'status' => SppPaymentStatus::Pending->value,
        ]);

        Storage::disk('public')->assertExists(SppPayment::first()->proof_path);
    }

    public function test_payment_proof_is_required(): void
    {
        Storage::fake('public');

        [$wali, , $student] = $this->waliWithChild();
        $bill = SppBill::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($wali)->post(route('siswa.spp.store'), [
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
        ]);

        $response->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('spp_payments', 0);
    }

    public function test_wali_cannot_pay_a_bill_belonging_to_another_student(): void
    {
        Storage::fake('public');

        [$wali] = $this->waliWithChild();
        $otherBill = SppBill::factory()->create();

        $response = $this->actingAs($wali)->post(route('siswa.spp.store'), [
            'spp_bill_id' => $otherBill->id,
            'amount' => 350000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('spp_payments', 0);
    }

    public function test_admin_can_approve_a_payment_and_the_bill_becomes_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = SppBill::factory()->create(['amount' => 350000]);
        $payment = SppPayment::factory()->create([
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.verifikasi-spp.approve', $payment));

        $response->assertRedirect();
        $this->assertSame(SppPaymentStatus::Approved, $payment->fresh()->status);
        $this->assertSame(SppBillStatus::Paid, $bill->fresh()->load('payments')->status());
    }

    public function test_a_partial_approved_payment_makes_the_bill_cicil(): void
    {
        $bill = SppBill::factory()->create(['amount' => 350000]);
        SppPayment::factory()->approved()->create([
            'spp_bill_id' => $bill->id,
            'amount' => 150000,
        ]);

        $bill->load('payments');

        $this->assertSame(SppBillStatus::Partial, $bill->status());
        $this->assertSame(150000, $bill->paidAmount());
        $this->assertSame(200000, $bill->remainingAmount());
    }

    public function test_a_pending_payment_does_not_count_towards_the_bill(): void
    {
        $bill = SppBill::factory()->create(['amount' => 350000]);
        SppPayment::factory()->create([
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
        ]);

        $bill->load('payments');

        $this->assertSame(SppBillStatus::Pending, $bill->status());
        $this->assertSame(0, $bill->paidAmount());
    }

    public function test_rejecting_a_payment_keeps_the_bill_unpaid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = SppBill::factory()->create(['amount' => 350000]);
        $payment = SppPayment::factory()->create([
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
        ]);

        $this->actingAs($admin)->post(route('admin.verifikasi-spp.reject', $payment));

        $this->assertSame(SppPaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame(SppBillStatus::Pending, $bill->fresh()->load('payments')->status());
    }

    public function test_wali_cannot_verify_payments(): void
    {
        [$wali] = $this->waliWithChild();
        $payment = SppPayment::factory()->create();

        $this->actingAs($wali)->post(route('admin.verifikasi-spp.approve', $payment))->assertForbidden();
    }

    public function test_admin_can_view_the_verification_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SppPayment::factory()->create();

        $this->actingAs($admin)->get(route('admin.verifikasi-spp'))->assertOk();
    }

    public function test_wali_sees_the_spp_pages_for_their_child(): void
    {
        [$wali, , $student] = $this->waliWithChild();
        SppBill::factory()->create(['student_id' => $student->id]);

        $this->actingAs($wali)->get(route('siswa.spp'))->assertOk()->assertSee($student->name);
        $this->actingAs($wali)->get(route('wali.spp'))->assertOk()->assertSee($student->name);
    }
}
