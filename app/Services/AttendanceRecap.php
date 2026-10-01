<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\Semester;
use App\Enums\StudentStatus;
use App\Models\Attendance;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap kehadiran per siswa untuk satu bulan atau satu semester.
 */
class AttendanceRecap
{
    /**
     * Periode dari query string: ?periode=bulan&bulan=2026-09 atau
     * ?periode=semester&semester=ganjil&tahun_ajaran=2026/2027.
     *
     * @return array{type: string, start: Carbon, end: Carbon, label: string, month: string, semester: Semester, academic_year: string}
     */
    public function period(Request $request): array
    {
        $type = $request->query('periode') === 'semester' ? 'semester' : 'bulan';
        $semester = Semester::tryFrom((string) $request->query('semester')) ?? Semester::current();
        $academicYear = preg_match('/^\d{4}\/\d{4}$/', (string) $request->query('tahun_ajaran'))
            ? (string) $request->query('tahun_ajaran')
            : Classroom::currentAcademicYear();

        try {
            $month = Carbon::createFromFormat('Y-m', (string) $request->query('bulan'))->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        if ($type === 'semester') {
            $startYear = (int) substr($academicYear, 0, 4);
            $start = $semester === Semester::Odd ? Carbon::create($startYear, 7, 1) : Carbon::create($startYear + 1, 1, 1);
            $end = $start->copy()->addMonths(5)->endOfMonth();
            $label = 'Semester '.$semester->label().' '.$academicYear;
        } else {
            $start = $month->copy();
            $end = $month->copy()->endOfMonth();
            $label = $month->translatedFormat('F Y');
        }

        return [
            'type' => $type,
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
            'label' => $label,
            'month' => $month->format('Y-m'),
            'semester' => $semester,
            'academic_year' => $academicYear,
        ];
    }

    /**
     * @return Collection<int, array{name: string, nis: string, hadir: int, telat: int, izin: int, alpa: int, total: int, percent: float|null}>
     */
    public function rows(Classroom $classroom, Carbon $start, Carbon $end): Collection
    {
        $students = $classroom->students()->where('status', StudentStatus::Active)->orderBy('name')->get();
        $counts = Attendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get(['student_id', 'status'])
            ->groupBy('student_id');

        return $students->map(function ($student) use ($counts) {
            $records = $counts->get($student->id, collect());
            $count = fn (AttendanceStatus $status) => $records->where('status', $status)->count();
            $present = $count(AttendanceStatus::Present) + $count(AttendanceStatus::Late);

            return [
                'name' => $student->name,
                'nis' => (string) ($student->nis ?? ''),
                'hadir' => $count(AttendanceStatus::Present),
                'telat' => $count(AttendanceStatus::Late),
                'izin' => $count(AttendanceStatus::Excused),
                'alpa' => $count(AttendanceStatus::Absent),
                'total' => $records->count(),
                'percent' => $records->count() > 0 ? round($present / $records->count() * 100, 1) : null,
            ];
        });
    }

    /**
     * Tahun ajaran yang bisa dipilih: berjalan & dua sebelumnya.
     *
     * @return list<string>
     */
    public function academicYearOptions(): array
    {
        $startYear = (int) substr(Classroom::currentAcademicYear(), 0, 4);

        return array_map(fn (int $year) => $year.'/'.($year + 1), range($startYear, $startYear - 2));
    }
}
