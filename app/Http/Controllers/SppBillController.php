<?php

namespace App\Http\Controllers;

use App\Enums\SppBillStatus;
use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Setting;
use App\Models\SppBill;
use App\Models\Student;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SppBillController extends Controller
{
    public function report(Request $request): View
    {
        Gate::authorize('viewAny', SppBill::class);

        $period = $request->string('period', now()->startOfMonth()->toDateString())->toString();
        $classroomId = $request->integer('classroom_id') ?: null;
        $status = $request->string('status')->toString();

        $bills = SppBill::query()
            ->with(['student.classroom', 'payments'])
            ->where('period', $period)
            ->when($classroomId, fn ($query) => $query->whereHas(
                'student',
                fn ($query) => $query->where('classroom_id', $classroomId)
            ))
            ->get()
            ->sortBy(fn (SppBill $bill) => $bill->student->name)
            ->values();

        if ($status !== '') {
            $bills = $bills->filter(fn (SppBill $bill) => $bill->status()->value === $status)->values();
        }

        $totalBilled = $bills->sum('amount');
        $totalReceived = $bills->sum(fn (SppBill $bill) => $bill->paidAmount());

        $stats = [
            'total_tagihan' => $totalBilled,
            'diterima' => $totalReceived,
            'tunggakan' => $totalBilled - $totalReceived,
            'lunas' => $bills->filter(fn (SppBill $bill) => $bill->status() === SppBillStatus::Paid)->count(),
            'cicil' => $bills->filter(fn (SppBill $bill) => $bill->status() === SppBillStatus::Partial)->count(),
            'pending' => $bills->filter(fn (SppBill $bill) => $bill->status() === SppBillStatus::Pending)->count(),
        ];

        $periods = SppBill::query()
            ->select('period')
            ->distinct()
            ->orderByDesc('period')
            ->pluck('period');

        $classrooms = Classroom::orderBy('name')->get();

        return view('admin.laporan-spp', compact(
            'bills',
            'stats',
            'periods',
            'classrooms',
            'period',
            'classroomId',
            'status',
        ));
    }

    /**
     * Membuat tagihan SPP bulan berjalan untuk semua siswa aktif yang
     * belum punya tagihan di periode tersebut.
     */
    public function generate(): RedirectResponse
    {
        Gate::authorize('create', SppBill::class);

        $period = now()->startOfMonth();
        $amount = (int) Setting::get('spp_amount', 350000);

        $alreadyBilled = SppBill::query()
            ->where('period', $period->toDateString())
            ->pluck('student_id');

        $rows = Student::query()
            ->where('status', StudentStatus::Active)
            ->whereNotIn('id', $alreadyBilled)
            ->pluck('id')
            ->map(fn (int $studentId) => [
                'student_id' => $studentId,
                'period' => $period->toDateString(),
                'amount' => $amount,
                'due_date' => $period->copy()->day(10)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows !== []) {
            SppBill::query()->insert($rows);
        }

        return back()->with('success', count($rows).' tagihan SPP periode '.$period->translatedFormat('F Y').' berhasil dibuat.');
    }

    public function exportCsv(Request $request, ReportExportService $exporter)
    {
        $bills = $this->filteredBills($request);

        $rows = $bills->map(fn (SppBill $bill) => [
            $bill->period->format('Y-m-d'), $bill->student->nisn, $bill->student->name,
            $bill->student->classroom?->name ?? '-', $bill->amount, $bill->paidAmount(),
            $bill->remainingAmount(), $bill->status()->value,
        ]);

        return $exporter->xlsx('laporan-spp-'.now()->format('Ymd-His').'.xlsx', ['Periode', 'NISN', 'Nama Siswa', 'Kelas', 'Tagihan', 'Dibayar', 'Sisa', 'Status'], $rows);
    }

    public function exportPdf(Request $request, ReportExportService $exporter)
    {
        return $exporter->pdf('admin.exports.laporan-spp', [
            'bills' => $this->filteredBills($request),
            'period' => $request->string('period', now()->startOfMonth()->toDateString())->toString(),
        ], 'laporan-spp-'.now()->format('Ymd-His').'.pdf');
    }

    private function filteredBills(Request $request)
    {
        $period = $request->string('period', now()->startOfMonth()->toDateString())->toString();
        $classroomId = $request->integer('classroom_id') ?: null;
        $status = $request->string('status')->toString();

        $bills = SppBill::query()
            ->with(['student.classroom', 'payments'])
            ->where('period', $period)
            ->when($classroomId, fn ($query) => $query->whereHas(
                'student', fn ($query) => $query->where('classroom_id', $classroomId)
            ))
            ->get()
            ->sortBy(fn (SppBill $bill) => $bill->student->name)
            ->values();

        return $status === ''
            ? $bills
            : $bills->filter(fn (SppBill $bill) => $bill->status()->value === $status)->values();
    }
}
