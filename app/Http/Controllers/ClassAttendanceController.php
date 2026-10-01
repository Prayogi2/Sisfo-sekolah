<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\StudentStatus;
use App\Http\Requests\Attendance\UpdateClassAttendanceRequest;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Services\AttendanceRecap;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Rekap absensi harian kelas untuk wali kelas: siapa saja yang sudah
 * masuk, telat, izin, alpa, atau belum absen, plus koreksi status manual
 * (mis. siswa lupa membawa kartu).
 */
class ClassAttendanceController extends Controller
{
    public function index(Request $request, AttendanceRecap $recap): View
    {
        $classrooms = $this->homeroomClassrooms($request);
        $classroom = $classrooms->firstWhere('id', $request->integer('classroom')) ?? $classrooms->first();
        $date = $this->selectedDate($request);
        $isRecap = $request->query('tampilan') === 'rekap';
        $period = $recap->period($request);

        if (! $classroom) {
            return view('guru.absensi-kelas', ['classrooms' => $classrooms, 'classroom' => null, 'date' => $date, 'isRecap' => $isRecap]);
        }

        Gate::authorize('manageAttendance', $classroom);

        if ($isRecap) {
            return view('guru.absensi-kelas', [
                'classrooms' => $classrooms,
                'classroom' => $classroom,
                'date' => $date,
                'isRecap' => true,
                'period' => $period,
                'recapRows' => $recap->rows($classroom, $period['start'], $period['end']),
                'academicYears' => $recap->academicYearOptions(),
            ]);
        }

        $students = $classroom->students()->where('status', StudentStatus::Active)->orderBy('name')->get();
        $attendances = Attendance::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        $summary = collect(AttendanceStatus::cases())
            ->mapWithKeys(fn (AttendanceStatus $status) => [$status->value => $attendances->where('status', $status)->count()])
            ->put('belum', $students->count() - $attendances->count());

        return view('guru.absensi-kelas', [
            'classrooms' => $classrooms,
            'classroom' => $classroom,
            'date' => $date,
            'students' => $students,
            'attendances' => $attendances,
            'summary' => $summary,
            'inSchoolCount' => $attendances->filter(fn (Attendance $attendance) => $attendance->status->isInSchool())->count(),
            'statuses' => AttendanceStatus::cases(),
            'isRecap' => false,
            'period' => $period,
        ]);
    }

    /**
     * Unduh absensi harian (?tampilan=harian&date=) atau rekap
     * (?tampilan=rekap&periode=...) sebagai Excel/PDF.
     */
    public function export(Request $request, Classroom $classroom, string $format, AttendanceRecap $recap, ReportExportService $exporter)
    {
        Gate::authorize('manageAttendance', $classroom);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);

        if ($request->query('tampilan') === 'rekap') {
            $period = $recap->period($request);
            $rows = $recap->rows($classroom, $period['start'], $period['end'])->map(fn (array $row) => [
                $row['name'], $row['nis'], $row['hadir'], $row['telat'], $row['izin'], $row['alpa'], $row['total'], $row['percent'] === null ? '-' : $row['percent'].'%',
            ]);

            return $exporter->table($format, 'rekap-absensi-'.str($classroom->name.' '.$period['label'])->slug(), 'Rekap Kehadiran Kelas '.$classroom->name, ['Nama Siswa', 'NIS', 'Hadir', 'Telat', 'Izin/Sakit', 'Alpa', 'Hari Tercatat', '% Kehadiran'], $rows, $period['label']);
        }

        $date = $this->selectedDate($request);
        $students = $classroom->students()->where('status', StudentStatus::Active)->orderBy('name')->get();
        $attendances = Attendance::query()->whereIn('student_id', $students->pluck('id'))->whereDate('date', $date->toDateString())->get()->keyBy('student_id');
        $rows = $students->map(fn ($student) => [
            $student->name,
            (string) $student->nis,
            $attendances->get($student->id)?->check_in_at?->format('H:i') ?? '-',
            $attendances->get($student->id)?->check_out_at?->format('H:i') ?? '-',
            $attendances->get($student->id)?->status->label() ?? 'Belum Absen',
        ]);

        return $exporter->table($format, 'absensi-'.str($classroom->name)->slug().'-'.$date->toDateString(), 'Absensi Harian Kelas '.$classroom->name, ['Nama Siswa', 'NIS', 'Jam Masuk', 'Jam Pulang', 'Status'], $rows, $date->translatedFormat('l, d F Y'));
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function homeroomClassrooms(Request $request)
    {
        return Classroom::query()
            ->whereHas('homeroomTeacher', fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }

    public function update(UpdateClassAttendanceRequest $request, Classroom $classroom): RedirectResponse
    {
        $date = $request->validated('date');
        $studentIds = $classroom->students()->where('status', StudentStatus::Active)->pluck('id');
        $changed = 0;

        foreach ($request->validated('statuses') as $studentId => $status) {
            // Kosong = tidak diubah; siswa kelas lain diabaikan.
            if ($status === null || ! $studentIds->contains((int) $studentId)) {
                continue;
            }

            $attendance = Attendance::firstOrNew(['student_id' => $studentId, 'date' => $date]);
            $attendance->status = AttendanceStatus::from($status);

            if ($attendance->isDirty('status')) {
                $attendance->save();
                $changed++;
            }
        }

        return redirect()
            ->route('guru.absensi-kelas', ['classroom' => $classroom->id, 'date' => $date])
            ->with('success', $changed > 0 ? "{$changed} status absensi diperbarui." : 'Tidak ada status absensi yang berubah.');
    }

    private function selectedDate(Request $request): Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $request->query('date'))->startOfDay();
        } catch (\Throwable) {
            return today();
        }

        return $date->isFuture() ? today() : $date;
    }
}
