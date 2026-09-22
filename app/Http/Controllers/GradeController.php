<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Requests\Grade\StoreGradeRequest;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Policies\GradePolicy;
use App\Services\ReportExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Grade::class);

        $user = $request->user();

        $subjects = Subject::query()
            ->when(
                $user->hasRole('guru'),
                fn ($query) => $query->whereHas('teachers', fn ($query) => $query->where('user_id', $user->id))
            )
            ->orderBy('name')
            ->get();

        return view('guru.laporan-nilai', $this->gradeSheet($request, $subjects));
    }

    public function report(Request $request): View
    {
        Gate::authorize('viewAny', Grade::class);

        $subjects = Subject::orderBy('name')->get();

        return view('admin.laporan-nilai', $this->gradeSheet($request, $subjects));
    }

    public function exportCsv(Request $request, ReportExportService $exporter)
    {
        $data = $this->gradeSheet($request, Subject::orderBy('name')->get());

        $subject = $data['subjects']->firstWhere('id', $data['subjectId']);
        $classroom = $data['classrooms']->firstWhere('id', $data['classroomId']);
        $rows = $data['students']->map(function ($student) use ($data, $subject, $classroom) {
            $grade = $data['grades']->get($student->id);
            $finalScore = $grade?->finalScore($data['weight']);

            return [
                $data['academicYear'],
                $data['semester']->value,
                $subject?->name ?? '-',
                $classroom?->name ?? '-',
                $student->nisn,
                $student->name,
                $grade?->assignment_score ?? '-',
                $grade?->quiz_score ?? '-',
                $grade?->midterm_score ?? '-',
                $grade?->final_score ?? '-',
                $finalScore ?? '-',
                $finalScore === null ? '-' : Grade::letterFor($finalScore),
                $finalScore === null ? 'Belum Dinilai' : ($finalScore >= $data['passingScore'] ? 'Lulus' : 'Remedial'),
            ];
        });

        return $exporter->xlsx('laporan-nilai-'.now()->format('Ymd-His').'.xlsx', ['Tahun Ajaran', 'Semester', 'Mata Pelajaran', 'Kelas', 'NISN', 'Nama Siswa', 'Tugas', 'Kuis', 'UTS', 'UAS', 'Nilai Akhir', 'Grade', 'Status'], $rows);
    }

    public function exportPdf(Request $request, ReportExportService $exporter)
    {
        return $exporter->pdf('admin.exports.laporan-nilai', $this->gradeSheet($request, Subject::orderBy('name')->get()), 'laporan-nilai-'.now()->format('Ymd-His').'.pdf');
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        abort_unless(app(GradePolicy::class)->canGradeSubject($user, $validated['subject_id']), 403);

        Grade::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'subject_id' => $validated['subject_id'],
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
            ],
            [
                'assignment_score' => $validated['assignment_score'] ?? null,
                'quiz_score' => $validated['quiz_score'] ?? null,
                'midterm_score' => $validated['midterm_score'] ?? null,
                'final_score' => $validated['final_score'] ?? null,
                'recorded_by' => $user->id,
            ],
        );

        return back()->with('success', 'Nilai siswa berhasil disimpan.');
    }

    /**
     * Data bersama untuk lembar nilai guru maupun rekap admin.
     *
     * @param  Collection<int, Subject>  $subjects
     * @return array<string, mixed>
     */
    private function gradeSheet(Request $request, $subjects): array
    {
        $classrooms = Classroom::orderBy('name')->get();

        $academicYear = $request->string('academic_year', Classroom::currentAcademicYear())->toString();
        $semester = Semester::tryFrom($request->string('semester')->toString()) ?? Semester::current();
        $subjectId = $request->integer('subject_id') ?: $subjects->first()?->id;
        $classroomId = $request->integer('classroom_id') ?: $classrooms->first()?->id;

        $students = $classroomId
            ? Student::query()->where('classroom_id', $classroomId)->orderBy('name')->get()
            : Student::query()->whereRaw('1 = 0')->get();

        $grades = $subjectId && $students->isNotEmpty()
            ? Grade::query()
                ->where('subject_id', $subjectId)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id')
            : collect();

        $weight = $subjectId
            ? GradeWeight::for($subjectId, $academicYear, $semester)
            : GradeWeight::default(0, $academicYear, $semester);

        $passingScore = (int) Setting::get('grade_passing_score', 70);

        $finalScores = $grades
            ->map(fn (Grade $grade) => $grade->finalScore($weight))
            ->filter(fn (?float $score) => $score !== null);

        $stats = [
            'dinilai' => $finalScores->count(),
            'rata_rata' => $finalScores->isEmpty() ? null : round($finalScores->avg(), 1),
            'tertinggi' => $finalScores->isEmpty() ? null : $finalScores->max(),
            'remedial' => $finalScores->filter(fn (float $score) => $score < $passingScore)->count(),
        ];

        return compact(
            'subjects',
            'classrooms',
            'students',
            'grades',
            'weight',
            'stats',
            'passingScore',
            'academicYear',
            'semester',
            'subjectId',
            'classroomId',
        );
    }
}
