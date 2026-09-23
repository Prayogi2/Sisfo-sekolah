<?php

namespace App\Http\Controllers;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\FamilyStatus;
use App\Enums\GraduationStatus;
use App\Enums\GuardianRelationship;
use App\Enums\PromotionStatus;
use App\Enums\Religion;
use App\Enums\ResidenceType;
use App\Enums\Semester;
use App\Enums\StudentStatus;
use App\Enums\TransportationMode;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\Student;
use App\Models\StudentProgressNote;
use App\Services\ReportExportService;
use App\Services\StudentRecordWriter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentRecordController extends Controller
{
    public function download(Request $request, ?Student $student = null): View
    {
        Gate::authorize('viewAny', Student::class);

        if ($student) {
            Gate::authorize('view', $student);
            $student->load(['classroom', 'profile', 'academicRecord', 'guardians', 'progressNotes']);
            $students = collect([$student]);
        } else {
            $students = Student::query()
                ->with(['classroom', 'profile', 'academicRecord', 'guardians', 'progressNotes'])
                ->orderBy('name')
                ->get();
        }

        return view('admin.buku-induk-print', [
            'students' => $students,
            'singleStudent' => $student !== null,
            'academicReports' => $students->mapWithKeys(fn (Student $item) => [$item->id => $this->academicReport($item)]),
        ]);
    }

    public function exportXlsx(Request $request, ReportExportService $exporter, ?Student $student = null)
    {
        Gate::authorize('viewAny', Student::class);
        if ($student) {
            Gate::authorize('view', $student);
            $student->load(['classroom', 'profile', 'academicRecord', 'guardians', 'progressNotes']);
            $students = collect([$student]);
        } else {
            $students = Student::with(['classroom', 'profile', 'academicRecord', 'guardians', 'progressNotes'])->orderBy('name')->get();
        }

        $rows = $students->flatMap(function (Student $item) {
            $reports = $this->academicReport($item);
            if ($reports->isEmpty()) {
                return [[$item->name, $item->nisn, $item->nis, $item->classroom?->name ?? '-', $item->profile?->nik ?? '-', '-', '-', '-', '-', '-', '-', '-']];
            }

            return $reports->flatMap(fn ($report) => $report['grades']->map(fn ($grade) => [
                $item->name, $item->nisn, $item->nis, $item->classroom?->name ?? '-', $item->profile?->nik ?? '-',
                $report['academic_year'], $report['semester']->label(), $grade['subject'],
                $grade['assignment'] ?? '-', $grade['quiz'] ?? '-', $grade['midterm'] ?? '-', $grade['final'] ?? '-', $grade['final_score'] ?? '-',
            ]));
        });

        return $exporter->xlsx('buku-induk-'.now()->format('Ymd-His').'.xlsx', ['Nama Siswa', 'NISN', 'NIS', 'Kelas', 'NIK', 'Tahun Ajaran', 'Semester', 'Mata Pelajaran', 'Tugas', 'Kuis', 'UTS', 'UAS', 'Nilai Akhir'], $rows);
    }

    /**
     * Form tambah siswa baru memakai form Buku Induk yang sama. Hanya
     * identitas utama yang wajib, sisanya bisa dilengkapi menyusul.
     */
    public function create(): View
    {
        Gate::authorize('create', Student::class);

        $student = (new Student(['status' => StudentStatus::Active]))->setRelations([
            'guardians' => new Collection,
            'progressNotes' => new Collection,
        ]);

        return view('admin.buku-induk-edit', $this->formData($student));
    }

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        $student->load(['profile', 'academicRecord', 'classroom', 'progressNotes', 'guardians']);

        return view('admin.buku-induk-edit', $this->formData($student));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Student $student): array
    {
        return [
            'student' => $student,
            'father' => $student->guardians->firstWhere('relationship', GuardianRelationship::Father),
            'mother' => $student->guardians->firstWhere('relationship', GuardianRelationship::Mother),
            'classrooms' => Classroom::orderBy('name')->get(),
            'religions' => Religion::cases(),
            'familyStatuses' => FamilyStatus::cases(),
            'bloodTypes' => BloodType::cases(),
            'graduationStatuses' => GraduationStatus::cases(),
            'studentStatuses' => StudentStatus::cases(),
            'educationLevels' => EducationLevel::cases(),
            'promotionStatuses' => PromotionStatus::cases(),
            'semesters' => Semester::cases(),
            'residenceTypes' => ResidenceType::cases(),
            'transportationModes' => TransportationMode::cases(),
            'currentProgressNote' => $student->progressNotes->first(
                fn (StudentProgressNote $note) => $note->academic_year === Classroom::currentAcademicYear()
                    && $note->semester === Semester::current()
            ),
        ];
    }

    public function update(Request $request, Student $student, StudentRecordWriter $recordWriter): RedirectResponse
    {
        Gate::authorize('update', $student);

        $data = $request->validate([
            'nisn' => ['required', 'string', 'max:20', Rule::unique('students', 'nisn')->ignore($student)],
            'nis' => ['required', 'string', 'max:20', Rule::unique('students', 'nis')->ignore($student)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'address' => ['nullable', 'string'],
            'parent_name' => ['nullable', 'string', 'max:255'],
            'parent_phone' => ['nullable', 'string', 'max:30'],
            'progress_academic_year' => ['required', 'string', 'max:20'],
            'progress_semester' => ['required', Rule::enum(Semester::class)],
            'promotion_status' => ['required', Rule::enum(PromotionStatus::class)],
            'progress_notes' => ['nullable', 'string', 'max:5000'],
        ] + $recordWriter->rules($student));

        $student->update(collect($data)->only([
            'nisn', 'nis', 'name', 'gender', 'birth_place', 'birth_date',
            'classroom_id', 'status', 'address', 'parent_name', 'parent_phone',
        ])->all());

        $recordWriter->save($student, $data, $request->file('photo'));

        $student->progressNotes()->updateOrCreate(
            ['academic_year' => $data['progress_academic_year'], 'semester' => $data['progress_semester']],
            [
                'recorded_by' => $request->user()->id,
                'promotion_status' => $data['promotion_status'],
                'notes' => $data['progress_notes'] ?? null,
            ],
        );

        return redirect()->route('admin.buku-induk', ['student' => $student->id])
            ->with('success', 'Data Buku Induk siswa berhasil diperbarui.');
    }

    /**
     * Buku induk: rekam jejak lengkap satu peserta didik yang dipilih admin.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $search = $request->input('search');

        $students = Student::query()
            ->with('classroom')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $student = $students->firstWhere('id', $request->integer('student')) ?? $students->first();

        $student?->load(['profile', 'academicRecord', 'guardians', 'progressNotes']);

        $guardians = $student?->guardians ?? collect();

        return view('admin.buku-induk', [
            'students' => $students,
            'student' => $student,
            'search' => $search,
            'father' => $guardians->firstWhere('relationship', GuardianRelationship::Father),
            'mother' => $guardians->firstWhere('relationship', GuardianRelationship::Mother),
            'legalGuardian' => $guardians->firstWhere('relationship', GuardianRelationship::Guardian),
            'academicReports' => $student ? $this->academicReport($student) : collect(),
        ]);
    }

    private function academicReport(Student $student)
    {
        $classmateIds = Student::query()
            ->when($student->classroom_id, fn ($query) => $query->where('classroom_id', $student->classroom_id))
            ->pluck('id');
        $grades = Grade::query()->with('subject')->whereIn('student_id', $classmateIds)->get();
        $weightCache = [];
        $finalScore = function (Grade $grade) use (&$weightCache): ?float {
            $key = $grade->subject_id.'|'.$grade->academic_year.'|'.$grade->semester->value;
            $weightCache[$key] ??= GradeWeight::for($grade->subject_id, $grade->academic_year, $grade->semester);

            return $grade->finalScore($weightCache[$key]);
        };

        return $grades->groupBy(fn (Grade $grade) => $grade->academic_year.'|'.$grade->semester->value)
            ->map(function ($periodGrades, $key) use ($student, $finalScore) {
                [$academicYear, $semesterValue] = explode('|', $key, 2);
                $averages = $periodGrades->groupBy('student_id')->map(function ($items) use ($finalScore) {
                    $scores = $items->map($finalScore)->filter(fn ($score) => $score !== null);

                    return $scores->isEmpty() ? null : round($scores->avg(), 2);
                })->filter(fn ($average) => $average !== null)->sortDesc();
                $studentAverage = $averages->get($student->id);

                return [
                    'academic_year' => $academicYear,
                    'semester' => Semester::from($semesterValue),
                    'average' => $studentAverage,
                    'rank' => $studentAverage === null ? null : $averages->keys()->search($student->id) + 1,
                    'rank_total' => $averages->count(),
                    'grades' => $periodGrades->where('student_id', $student->id)->map(fn (Grade $grade) => [
                        'subject' => $grade->subject?->name ?? '-',
                        'assignment' => $grade->assignment_score,
                        'quiz' => $grade->quiz_score,
                        'midterm' => $grade->midterm_score,
                        'final' => $grade->final_score,
                        'final_score' => $finalScore($grade),
                        'letter' => ($score = $finalScore($grade)) === null ? '-' : Grade::letterFor($score),
                    ])->values(),
                ];
            })->sortByDesc(fn ($period) => $period['academic_year'].'-'.$period['semester']->value)->values();
    }
}
