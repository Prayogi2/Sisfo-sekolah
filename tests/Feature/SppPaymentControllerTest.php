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
     * Akun siswa beserta data orang tua/wali-nya di Buku Induk.
     *
     * @return array{0: User, 1: Guardian, 2: Student}
     */
    private function siswaWithGuardian(): array
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id]);
        $guardian = Guardian::factory()->create();
        $guardian->students()->attach($student);

        return [$siswa, $guardian, $student];
    }

    /**
     * Pembayaran tetap dicatat atas nama orang tua/wali siswa di Buku Induk.
     */
    public function test_siswa_can_upload_a_payment_proof_recorded_under_their_guardian(): void
    {
        Storage::fake('public');

        [$siswa, $guardian, $student] = $this->siswaWithGuardian();
        $bill = SppBill::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($siswa)->post(route('siswa.spp.store'), [
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

        [$siswa, , $student] = $this->siswaWithGuardian();
        $bill = SppBill::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($siswa)->post(route('siswa.spp.store'), [
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
        ]);

        $response->assertSessionHasErrors('proof');
        $this->assertDatabaseCount('spp_payments', 0);
    }

    public function test_siswa_cannot_pay_a_bill_belonging_to_another_student(): void
    {
        Storage::fake('public');

        [$siswa] = $this->siswaWithGuardian();
        $otherBill = SppBill::factory()->create();

        $response = $this->actingAs($siswa)->post(route('siswa.spp.store'), [
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

    public function test_siswa_cannot_verify_payments(): void
    {
        [$siswa] = $this->siswaWithGuardian();
        $payment = SppPayment::factory()->create();

        $this->actingAs($siswa)->post(route('admin.verifikasi-spp.approve', $payment))->assertForbidden();
    }

    public function test_admin_can_view_the_verification_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SppPayment::factory()->create();

        $this->actingAs($admin)->get(route('admin.verifikasi-spp'))->assertOk();
    }

    public function test_siswa_sees_their_own_spp_pages(): void
    {
        [$siswa, , $student] = $this->siswaWithGuardian();
        SppBill::factory()->create(['student_id' => $student->id]);

        $this->actingAs($siswa)->get(route('siswa.spp'))->assertOk()->assertSee($student->name);
        $this->actingAs($siswa)->get(route('siswa.status-spp'))->assertOk()->assertSee($student->name);
    }

    public function test_status_spp_page_links_to_the_upload_page(): void
    {
        [$siswa, , $student] = $this->siswaWithGuardian();
        SppBill::factory()->create(['student_id' => $student->id]);

        $this->actingAs($siswa)->get(route('siswa.status-spp'))->assertSee(route('siswa.spp'), false);
    }

    public function test_student_account_without_any_guardian_gets_a_friendly_error_instead_of_a_crash(): void
    {
        Storage::fake('public');

        $student = Student::factory()->create();
        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student->update(['user_id' => $studentUser->id]);
        $bill = SppBill::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($studentUser)->post(route('siswa.spp.store'), [
            'spp_bill_id' => $bill->id,
            'amount' => 350000,
            'proof' => UploadedFile::fake()->image('bukti.jpg'),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('spp_payments', 0);
    }
}
