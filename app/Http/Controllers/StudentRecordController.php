<?php

namespace App\Http\Controllers;

use App\Enums\BloodType;
use App\Enums\EducationLevel;
use App\Enums\FamilyStatus;
use App\Enums\GraduationStatus;
use App\Enums\GuardianRelationship;
use App\Enums\PromotionStatus;
use App\Enums\Religion;
use App\Enums\Semester;
use App\Enums\StudentStatus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\Student;
use App\Models\StudentProgressNote;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        $student->load(['profile', 'academicRecord', 'classroom', 'progressNotes', 'guardians']);

        return view('admin.buku-induk-edit', [
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
            'currentProgressNote' => $student->progressNotes->first(
                fn (StudentProgressNote $note) => $note->academic_year === Classroom::currentAcademicYear()
                    && $note->semester === Semester::current()
            ),
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:30', Rule::unique('student_profiles', 'nik')->ignore($student->profile?->id)],
            'family_card_number' => ['nullable', 'string', 'max:30'],
            'religion' => ['nullable', Rule::enum(Religion::class)],
            'family_status' => ['nullable', Rule::enum(FamilyStatus::class)],
            'birth_order' => ['nullable', 'integer', 'min:1'],
            'siblings_count' => ['nullable', 'integer', 'min:0'],
            'weight_kg' => ['nullable', 'integer', 'min:0'],
            'height_cm' => ['nullable', 'integer', 'min:0'],
            'blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'street_address' => ['nullable', 'string'],
            'hamlet' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'regency' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'entry_status' => ['nullable', 'string', 'max:100'],
            'entry_date' => ['nullable', 'date'],
            'kindergarten_origin' => ['nullable', 'string', 'max:255'],
            'kindergarten_certificate_number' => ['nullable', 'string', 'max:100'],
            'kindergarten_certificate_date' => ['nullable', 'date'],
            'transfer_out_date' => ['nullable', 'date'],
            'transfer_out_reason' => ['nullable', 'string'],
            'exit_date' => ['nullable', 'date'],
            'exit_reason' => ['nullable', 'string'],
            'graduation_status' => ['nullable', Rule::enum(GraduationStatus::class)],
            'graduation_year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'graduation_certificate_number' => ['nullable', 'string', 'max:100'],
            'graduation_certificate_date' => ['nullable', 'date'],
            'continued_to' => ['nullable', 'string', 'max:255'],
            'graduation_notes' => ['nullable', 'string'],
            'progress_academic_year' => ['required', 'string', 'max:20'],
            'progress_semester' => ['required', Rule::enum(Semester::class)],
            'promotion_status' => ['required', Rule::enum(PromotionStatus::class)],
            'progress_notes' => ['nullable', 'string', 'max:5000'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_gender' => ['nullable', 'in:L,P'],
            'father_nik' => ['nullable', 'string', 'max:30'],
            'father_family_card_number' => ['nullable', 'string', 'max:30'],
            'father_birth_place' => ['nullable', 'string', 'max:255'],
            'father_birth_date' => ['nullable', 'date'],
            'father_religion' => ['nullable', Rule::enum(Religion::class)],
            'father_blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'father_last_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_monthly_income' => ['nullable', 'string', 'max:100'],
            'father_phone' => ['nullable', 'string', 'max:30'],
            'father_address' => ['nullable', 'string'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_gender' => ['nullable', 'in:L,P'],
            'mother_nik' => ['nullable', 'string', 'max:30'],
            'mother_family_card_number' => ['nullable', 'string', 'max:30'],
            'mother_birth_place' => ['nullable', 'string', 'max:255'],
            'mother_birth_date' => ['nullable', 'date'],
            'mother_religion' => ['nullable', Rule::enum(Religion::class)],
            'mother_blood_type' => ['nullable', Rule::enum(BloodType::class)],
            'mother_last_education' => ['nullable', Rule::enum(EducationLevel::class)],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_monthly_income' => ['nullable', 'string', 'max:100'],
            'mother_phone' => ['nullable', 'string', 'max:30'],
            'mother_address' => ['nullable', 'string'],
        ]);

        $student->update(collect($data)->only([
            'nisn', 'nis', 'name', 'gender', 'birth_place', 'birth_date',
            'classroom_id', 'status', 'address', 'parent_name', 'parent_phone',
        ])->all());

        $student->profile()->updateOrCreate(
            ['student_id' => $student->id],
            collect($data)->only([
                'nickname', 'nik', 'family_card_number', 'religion', 'family_status',
                'birth_order', 'siblings_count', 'weight_kg', 'height_cm', 'blood_type',
                'street_address', 'hamlet', 'village', 'district', 'regency', 'province', 'postal_code',
            ])->all(),
        );

        if ($request->hasFile('photo')) {
            if ($student->profile?->photo_path) {
                Storage::disk('public')->delete($student->profile->photo_path);
            }

            $student->profile()->update(['photo_path' => $request->file('photo')->store('student-photos', 'public')]);
        }

        $student->academicRecord()->updateOrCreate(
            ['student_id' => $student->id],
            collect($data)->only([
                'kindergarten_origin', 'kindergarten_certificate_number', 'kindergarten_certificate_date',
                'entry_status', 'entry_date', 'transfer_out_date', 'transfer_out_reason',
                'exit_date', 'exit_reason', 'graduation_status', 'graduation_year',
                'graduation_certificate_number', 'graduation_certificate_date', 'continued_to', 'graduation_notes',
            ])->all(),
        );

        $student->progressNotes()->updateOrCreate(
            ['academic_year' => $data['progress_academic_year'], 'semester' => $data['progress_semester']],
            [
                'recorded_by' => $request->user()->id,
                'promotion_status' => $data['promotion_status'],
                'notes' => $data['progress_notes'] ?? null,
            ],
        );

        foreach ([GuardianRelationship::Father, GuardianRelationship::Mother] as $relationship) {
            $prefix = $relationship === GuardianRelationship::Father ? 'father' : 'mother';
            $guardian = $student->guardians()->where('relationship', $relationship)->first();
            $guardianData = collect([
                'name' => $data[$prefix.'_name'] ?? null,
                'gender' => $data[$prefix.'_gender'] ?? null,
                'nik' => $data[$prefix.'_nik'] ?? null,
                'family_card_number' => $data[$prefix.'_family_card_number'] ?? null,
                'birth_place' => $data[$prefix.'_birth_place'] ?? null,
                'birth_date' => $data[$prefix.'_birth_date'] ?? null,
                'religion' => $data[$prefix.'_religion'] ?? null,
                'blood_type' => $data[$prefix.'_blood_type'] ?? null,
                'last_education' => $data[$prefix.'_last_education'] ?? null,
                'occupation' => $data[$prefix.'_occupation'] ?? null,
                'monthly_income' => $data[$prefix.'_monthly_income'] ?? null,
                'phone' => $data[$prefix.'_phone'] ?? null,
                'address' => $data[$prefix.'_address'] ?? null,
            ])->filter(fn ($value) => $value !== null && $value !== '')->all();

            if ($guardian) {
                $guardian->update($guardianData);
            } elseif ($guardianData !== []) {
                $student->guardians()->create($guardianData + ['relationship' => $relationship]);
            }
        }

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
