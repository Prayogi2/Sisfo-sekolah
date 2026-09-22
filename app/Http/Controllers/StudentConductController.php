<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCurrentStudent;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentViolation;
use App\Services\CurrentStudentResolver;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentConductController extends Controller
{
    use ResolvesCurrentStudent;

    public function index(Request $request): View
    {
        $this->authorizeStaff($request);
        $query = $request->string('q')->trim()->toString();
        $studentId = $request->integer('student_id') ?: null;

        $students = Student::with('classroom')->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query) {
            $builder->where('name', 'like', "%{$query}%")->orWhere('nis', 'like', "%{$query}%")->orWhere('nisn', 'like', "%{$query}%");
        }))->orderBy('name')->get();
        $achievements = StudentAchievement::with('student.classroom')->when($studentId, fn ($builder) => $builder->where('student_id', $studentId))->latest('achieved_at')->latest()->get();
        $violations = StudentViolation::with('student.classroom')->when($studentId, fn ($builder) => $builder->where('student_id', $studentId))->latest('occurred_at')->latest()->get();
        $isGuru = $request->user()->hasRole('guru');

        return view('admin.prestasi-pelanggaran', compact('students', 'achievements', 'violations', 'studentId', 'query', 'isGuru'));
    }

    public function storeAchievement(Request $request): RedirectResponse
    {
        $this->authorizeStaff($request);
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'category' => ['required', 'in:academic,non_academic'],
            'title' => ['required', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:100'],
            'achievement' => ['nullable', 'string', 'max:255'],
            'benefit' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'achieved_at' => ['nullable', 'date'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        $data['recorded_by'] = $request->user()->id;
        $data['evidence_path'] = $request->file('evidence')?->store('student-achievements', 'public');
        unset($data['evidence']);
        StudentAchievement::create($data);

        return back()->with('success', 'Prestasi siswa berhasil dicatat.');
    }

    public function destroyAchievement(Request $request, StudentAchievement $achievement): RedirectResponse
    {
        $this->authorizeStaff($request);
        if ($achievement->evidence_path) {
            Storage::disk('public')->delete($achievement->evidence_path);
        }
        $achievement->delete();

        return back()->with('success', 'Catatan prestasi berhasil dihapus.');
    }

    public function storeViolation(Request $request): RedirectResponse
    {
        $this->authorizeStaff($request);
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'severity' => ['required', 'in:light,medium,severe'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        $data['recorded_by'] = $request->user()->id;
        $data['evidence_path'] = $request->file('evidence')?->store('student-violations', 'public');
        unset($data['evidence']);
        StudentViolation::create($data);

        return back()->with('success', 'Pelanggaran siswa berhasil dicatat.');
    }

    public function destroyViolation(Request $request, StudentViolation $violation): RedirectResponse
    {
        $this->authorizeStaff($request);
        if ($violation->evidence_path) {
            Storage::disk('public')->delete($violation->evidence_path);
        }
        $violation->delete();

        return back()->with('success', 'Catatan pelanggaran berhasil dihapus.');
    }

    public function exportXlsx(Request $request, ReportExportService $exporter)
    {
        $this->authorizeStaff($request);
        [$achievements, $violations] = $this->exportData($request);
        $rows = $achievements->map(fn ($item) => ['Prestasi', $item->achieved_at?->format('Y-m-d') ?: '-', $item->student->name, $item->category, $item->title, $item->event ?: '-', $item->level ?: '-', $item->benefit ?: '-'])
            ->concat($violations->map(fn ($item) => ['Pelanggaran', $item->occurred_at->format('Y-m-d'), $item->student->name, $item->severity, $item->title, $item->description, '-', $item->action_taken ?: '-']));

        return $exporter->xlsx('laporan-prestasi-pelanggaran-'.now()->format('Ymd-His').'.xlsx', ['Jenis', 'Tanggal', 'Nama Siswa', 'Kategori', 'Judul', 'Detail/Event', 'Tingkat', 'Benefit/Pembinaan'], $rows);
    }

    public function exportPdf(Request $request, ReportExportService $exporter)
    {
        $this->authorizeStaff($request);
        [$achievements, $violations] = $this->exportData($request);

        return $exporter->pdf('admin.exports.laporan-prestasi-pelanggaran', compact('achievements', 'violations'), 'laporan-prestasi-pelanggaran-'.now()->format('Ymd-His').'.pdf');
    }

    public function guardianIndex(Request $request, CurrentStudentResolver $resolver): View|RedirectResponse
    {
        $student = $this->resolveStudentOrRedirect($request->user(), $resolver);
        if ($student instanceof RedirectResponse) {
            return $student;
        }
        $student->load('classroom');
        $achievements = $student->achievements()->latest('achieved_at')->latest()->get();
        $violations = $student->violations()->latest('occurred_at')->latest()->get();

        return view('wali-murid.prestasi-pelanggaran', compact('student', 'achievements', 'violations'));
    }

    private function authorizeStaff(Request $request): void
    {
        abort_unless($request->user()->hasRole(['admin', 'guru']), 403);
    }

    private function exportData(Request $request): array
    {
        $query = $request->string('q')->trim()->toString();
        $studentIds = Student::query()->when($query !== '', fn ($builder) => $builder->where(function ($builder) use ($query) {
            $builder->where('name', 'like', "%{$query}%")->orWhere('nis', 'like', "%{$query}%")->orWhere('nisn', 'like', "%{$query}%");
        }))->pluck('id');

        return [
            StudentAchievement::with('student')->whereIn('student_id', $studentIds)->latest('achieved_at')->get(),
            StudentViolation::with('student')->whereIn('student_id', $studentIds)->latest('occurred_at')->get(),
        ];
    }
}
