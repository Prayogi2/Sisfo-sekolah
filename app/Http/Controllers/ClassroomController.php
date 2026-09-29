<?php

namespace App\Http\Controllers;

use App\Enums\StudentStatus;
use App\Http\Requests\Classroom\AssignStudentsRequest;
use App\Http\Requests\Classroom\StoreClassroomRequest;
use App\Http\Requests\Classroom\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::query()
            ->with(['homeroomTeacher', 'students' => fn ($query) => $query->orderBy('name')])
            ->withCount('students')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $teachers = Teacher::where('is_active', true)->orderBy('name')->get();
        // Pilihan di "Atur Siswa": siswa aktif tanpa kelas maupun dari kelas lain (akan dipindah).
        $activeStudents = Student::query()
            ->with('classroom')
            ->where('status', StudentStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'nisn', 'classroom_id']);

        // Daftar siswa di form Tambah Kelas yang gagal disimpan, supaya tidak perlu diketik ulang.
        // Hanya dari form Tambah Kelas (Atur Siswa juga mengirim student_ids).
        $oldNewClassStudents = old('_form') === 'tambah-kelas'
            ? Student::query()->whereIn('id', (array) old('student_ids', []))->orderBy('name')->get(['id', 'name', 'nisn', 'nis'])
            : collect();

        return view('admin.data-kelas', compact('classrooms', 'teachers', 'activeStudents', 'oldNewClassStudents'));
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        $studentIds = $request->validated('student_ids', []);

        $classroom = DB::transaction(function () use ($request, $studentIds) {
            $classroom = Classroom::create($request->safe()->except('student_ids') + [
                'academic_year' => Classroom::currentAcademicYear(),
                'capacity' => Classroom::DEFAULT_CAPACITY,
            ]);

            if ($studentIds !== []) {
                Student::query()->whereIn('id', $studentIds)->whereNull('classroom_id')->update(['classroom_id' => $classroom->id]);
            }

            return $classroom;
        });

        return back()->with('success', "Kelas {$classroom->name} berhasil ditambahkan".($studentIds !== [] ? ' dengan '.count($studentIds).' siswa.' : '.'));
    }

    /**
     * Pencarian AJAX banyak siswa sekaligus lewat NISN/NIS untuk form Tambah
     * Kelas. Semua dicari dalam satu query; hasilnya dipisah jadi siswa yang
     * boleh ditambahkan (`students`) dan per-NISN yang gagal (`errors`).
     */
    public function lookupStudents(Request $request): JsonResponse
    {
        Gate::authorize('create', Classroom::class);

        $request->validate(
            ['keywords' => ['required', 'string', 'max:5000']],
            ['keywords.required' => 'Ketik minimal satu NISN atau NIS siswa.'],
        );

        // Pisah baris baru, koma, titik koma, tab, atau spasi; buang duplikat.
        $keywords = collect(preg_split('/[\s,;]+/', $request->string('keywords')->toString(), -1, PREG_SPLIT_NO_EMPTY))->unique()->values();
        if ($keywords->count() > 100) {
            return response()->json(['message' => 'Maksimal 100 NISN/NIS sekali proses.'], 422);
        }

        // Excel/salin-tempel sering menghilangkan angka 0 di depan NISN.
        $padded = fn (string $keyword) => ctype_digit($keyword) && strlen($keyword) < 10 ? str_pad($keyword, 10, '0', STR_PAD_LEFT) : $keyword;
        $candidates = Student::query()->with('classroom')
            ->where(fn ($query) => $query->whereIn('nisn', $keywords->map($padded)->merge($keywords)->unique())->orWhereIn('nis', $keywords))
            ->get();

        $students = [];
        $errors = [];
        foreach ($keywords as $keyword) {
            $student = $candidates->first(fn (Student $student) => in_array($student->nisn, [$keyword, $padded($keyword)], true) || $student->nis === $keyword);

            if (! $student) {
                $errors[] = ['keyword' => $keyword, 'message' => "NISN/NIS {$keyword} tidak ditemukan."];
            } elseif ($issue = $student->newClassroomPlacementIssue()) {
                $errors[] = ['keyword' => $keyword, 'message' => $issue];
            } elseif (! isset($students[$student->id])) {
                // NISN & NIS siswa yang sama sama-sama diketik → cukup sekali.
                $students[$student->id] = $student->only(['id', 'name', 'nisn', 'nis']);
            }
        }

        return response()->json(['students' => array_values($students), 'errors' => $errors]);
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $classroom->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * "Atur Siswa" — tentukan ulang seluruh anggota kelas ini.
     */
    public function assignStudents(AssignStudentsRequest $request, Classroom $classroom): RedirectResponse
    {
        $studentIds = $request->validated('student_ids', []);

        Student::where('classroom_id', $classroom->id)
            ->whereNotIn('id', $studentIds)
            ->update(['classroom_id' => null]);

        Student::whereIn('id', $studentIds)->update(['classroom_id' => $classroom->id]);

        return back()->with('success', 'Anggota kelas berhasil diperbarui.');
    }
}
