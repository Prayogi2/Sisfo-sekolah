<?php

namespace App\Http\Controllers;

use App\Enums\SppPaymentStatus;
use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Http\Requests\Spp\StoreSppPaymentRequest;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Services\CurrentStudentResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SppPaymentController extends Controller
{
    use ResolvesCurrentStudent;

    /**
     * Daftar bukti bayar untuk diverifikasi admin.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', SppPayment::class);

        $payments = SppPayment::query()
            ->with(['bill.student.classroom', 'guardian'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [SppPaymentStatus::Pending->value])
            ->orderByDesc('created_at')
            ->paginate(20);

        $pendingCount = SppPayment::query()->where('status', SppPaymentStatus::Pending)->count();

        return view('admin.verifikasi-spp', compact('payments', 'pendingCount'));
    }

    public function approve(Request $request, SppPayment $sppPayment): RedirectResponse
    {
        Gate::authorize('update', $sppPayment);

        $sppPayment->update([
            'status' => SppPaymentStatus::Approved,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, SppPayment $sppPayment): RedirectResponse
    {
        Gate::authorize('update', $sppPayment);

        $sppPayment->update([
            'status' => SppPaymentStatus::Rejected,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Pembayaran ditolak.');
    }

    /**
     * Halaman pembayaran SPP siswa: tagihan, riwayat, & form upload bukti.
     */
    public function create(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $bills = $this->billsFor($student->id);

        return view('siswa.pembayaran-spp', compact('student', 'bills'));
    }

    public function store(StoreSppPaymentRequest $request, CurrentStudentResolver $resolver): RedirectResponse
    {
        $user = $request->user();
        $student = $this->resolveStudentOrRedirect($user, $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        // Pembayaran tetap dicatat atas nama orang tua/wali pertama siswa
        // di Buku Induk.
        $guardian = $student->guardians()->first();
        abort_unless($guardian, 422, 'Akun ini belum terhubung dengan data wali. Hubungi admin untuk melengkapi data orang tua/wali di Buku Induk terlebih dahulu.');

        // Tagihan harus benar-benar milik anak yang sedang dipilih.
        $bill = SppBill::query()
            ->where('student_id', $student->id)
            ->findOrFail($request->integer('spp_bill_id'));

        $path = $request->file('proof')->store('bukti-spp', 'public');

        SppPayment::create([
            'spp_bill_id' => $bill->id,
            'guardian_id' => $guardian->id,
            'amount' => $request->integer('amount'),
            'proof_path' => $path,
            'status' => SppPaymentStatus::Pending,
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil dikirim, menunggu verifikasi admin.');
    }

    /**
     * Halaman status SPP siswa — hanya menampilkan, tanpa form upload.
     */
    public function status(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $bills = $this->billsFor($student->id);

        return view('siswa.status-spp', compact('student', 'bills'));
    }

    /**
     * @return Collection<int, SppBill>
     */
    private function billsFor(int $studentId): Collection
    {
        return SppBill::query()
            ->with('payments')
            ->where('student_id', $studentId)
            ->orderByDesc('period')
            ->get();
    }
}
