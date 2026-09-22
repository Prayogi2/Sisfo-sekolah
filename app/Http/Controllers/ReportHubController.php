<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\SppPaymentStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\Student;
use Illuminate\View\View;

/**
 * Pusat laporan: ringkasan angka terkini dari tiap modul, supaya admin
 * tahu kondisi hari ini tanpa harus membuka satu per satu.
 */
class ReportHubController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.laporan', [
            'attendance' => $this->attendanceSummary(),
            'spp' => $this->sppSummary(),
            'grades' => $this->gradeSummary(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function attendanceSummary(): array
    {
        $counts = Attendance::query()
            ->where('date', now()->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = ($counts[AttendanceStatus::Present->value] ?? 0) + ($counts[AttendanceStatus::Late->value] ?? 0);

        return [
            'hadir' => $present,
            'telat' => $counts[AttendanceStatus::Late->value] ?? 0,
            'izin' => $counts[AttendanceStatus::Excused->value] ?? 0,
            'total_siswa' => Student::query()->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function sppSummary(): array
    {
        $period = now()->startOfMonth()->toDateString();

        $bills = SppBill::query()->with('payments')->where('period', $period)->get();

        $billed = (int) $bills->sum('amount');
        $received = (int) $bills->sum(fn (SppBill $bill) => $bill->paidAmount());

        return [
            'tagihan' => $billed,
            'diterima' => $received,
            'tunggakan' => $billed - $received,
            'menunggu_verifikasi' => SppPayment::query()
                ->where('status', SppPaymentStatus::Pending)
                ->count(),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function gradeSummary(): array
    {
        $grades = Grade::query()
            ->with('subject')
            ->where('academic_year', Classroom::currentAcademicYear())
            ->get();

        $finalScores = $grades
            ->map(function (Grade $grade) {
                $weight = GradeWeight::for($grade->subject_id, $grade->academic_year, $grade->semester);

                return $grade->finalScore($weight);
            })
            ->filter(fn (?float $score) => $score !== null);

        return [
            'dinilai' => $finalScores->count(),
            'rata_rata' => $finalScores->isEmpty() ? null : round($finalScores->avg(), 1),
            'mapel' => $grades->pluck('subject_id')->unique()->count(),
        ];
    }
}
