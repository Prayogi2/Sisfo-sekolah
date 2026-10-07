<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Feedback;
use App\Models\Grade;
use App\Models\InventoryItem;
use App\Models\InventoryReport;
use App\Models\LeaveRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\SppBill;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\StudentProfile;
use App\Models\StudentViolation;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Semua halaman dibuka sungguhan untuk tiap peran. Yang dijaga di sini bukan
 * isi halamannya (itu tugas test masing-masing fitur), tapi tidak adanya
 * error 500 — baik saat database masih kosong maupun setelah terisi.
 */
class PageSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('public');
        // Halaman Koneksi WhatsApp memanggil server WA; jangan sampai test
        // menembak server sungguhan.
        Http::fake();
    }

    /**
     * Halaman tanpa parameter, dikelompokkan per prefix peran.
     *
     * @return array<string, list<string>>
     */
    private function plainPages(): array
    {
        return [
            'admin' => [
                'admin.dashboard', 'admin.akun', 'admin.approval-izin', 'admin.bank-soal',
                'admin.buku-induk', 'admin.data-guru', 'admin.data-mapel', 'admin.data-siswa',
                'admin.data-siswa.import', 'admin.data-siswa.create', 'admin.inventaris',
                'admin.kritik-saran', 'admin.laporan', 'admin.laporan-absensi', 'admin.laporan-nilai',
                'admin.laporan-spp', 'admin.notifikasi', 'admin.pembagian-kelas',
                'admin.peserta-presensi', 'admin.prestasi-pelanggaran', 'admin.verifikasi-spp',
                'admin.whatsapp', 'admin.log-aktivitas', 'scan-qr',
            ],
            'guru' => [
                'guru.dashboard', 'guru.absensi-kelas', 'guru.approval-izin', 'guru.bank-soal',
                'guru.bank-soal.tambah', 'guru.data-guru', 'guru.inventaris', 'guru.laporan-nilai',
                'guru.prestasi-pelanggaran',
            ],
            'siswa' => [
                'siswa.dashboard', 'siswa.hasil-kuis', 'siswa.izin', 'siswa.kuis', 'siswa.notifikasi',
                'siswa.spp', 'siswa.prestasi-pelanggaran', 'siswa.absensi', 'siswa.status-spp',
            ],
        ];
    }

    public function test_every_page_opens_for_its_own_role_when_the_database_is_empty(): void
    {
        foreach ($this->plainPages() as $role => $routes) {
            $user = User::factory()->create(['role' => $role]);

            if ($role === 'guru') {
                Teacher::factory()->create(['user_id' => $user->id]);
            }
            if ($role === 'siswa') {
                Student::factory()->create(['user_id' => $user->id]);
            }

            foreach ($routes as $name) {
                $this->actingAs($user)->get(route($name))->assertOk();
            }
        }

        $this->get(route('presensi.scan'))->assertOk();
        $this->get(route('login'))->assertOk();
    }

    public function test_every_page_opens_for_its_own_role_with_data(): void
    {
        $this->seedEverything();

        foreach ($this->plainPages() as $role => $routes) {
            $user = $this->userFor($role);

            foreach ($routes as $name) {
                $this->actingAs($user)->get(route($name))->assertOk();
            }
        }
    }

    /**
     * Data yang "berlubang" — siswa tanpa kelas/akun, guru tanpa akun, kelas
     * tanpa wali, kuis tanpa soal, catatan tanpa tanggal — paling sering
     * memicu error di halaman yang mengakses relasi tanpa pengaman.
     */
    public function test_every_page_opens_when_related_data_is_missing(): void
    {
        $classroomWithoutHomeroom = Classroom::factory()->create(['homeroom_teacher_id' => null]);
        $studentWithoutClassroom = Student::factory()->create(['classroom_id' => null, 'user_id' => null]);
        Teacher::factory()->create(['user_id' => null]);

        StudentAchievement::create(['student_id' => $studentWithoutClassroom->id, 'category' => 'academic', 'title' => 'Tanpa Tanggal', 'achieved_at' => null]);
        SppBill::factory()->create(['student_id' => $studentWithoutClassroom->id]);
        LeaveRequest::factory()->create(['student_id' => $studentWithoutClassroom->id]);
        Announcement::factory()->create(['classroom_id' => null, 'created_by' => null]);
        ActivityLog::factory()->create(['user_id' => null, 'user_name' => null, 'user_role' => null, 'status_code' => 422]);
        Quiz::factory()->create(['classroom_id' => $classroomWithoutHomeroom->id, 'created_by' => null]);
        InventoryReport::factory()->create(['classroom_id' => $classroomWithoutHomeroom->id]);

        $siswaUser = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $siswaUser->id, 'classroom_id' => null]);
        $guruUser = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $guruUser->id]);
        $adminUser = User::factory()->create(['role' => 'admin']);

        foreach ($this->plainPages() as $role => $routes) {
            $user = match ($role) {
                'admin' => $adminUser,
                'guru' => $guruUser,
                default => $siswaUser,
            };

            foreach ($routes as $name) {
                $this->actingAs($user)->get(route($name))->assertOk();
            }
        }
    }

    public function test_every_detail_page_opens(): void
    {
        $this->seedEverything();

        $admin = $this->userFor('admin');
        $student = Student::firstOrFail();
        $announcement = Announcement::firstOrFail();
        $report = InventoryReport::firstOrFail();
        $question = QuizQuestion::firstOrFail();
        $quiz = Quiz::query()->where('mode', '!=', 'live')->firstOrFail();
        $liveQuiz = Quiz::query()->where('mode', 'live')->firstOrFail();

        foreach ([
            route('admin.buku-induk.edit', $student),
            route('admin.buku-induk.nilai.edit', $student),
            route('admin.buku-induk.nilai.pdf', $student),
            route('admin.data-siswa.kartu-qr', $student),
            route('admin.notifikasi.show', $announcement),
            route('admin.inventaris.laporan.show', $report),
            route('admin.buku-induk.download'),
            route('admin.buku-induk.student.download', $student),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $guru = $this->userFor('guru');
        foreach ([
            route('guru.bank-soal.edit', $question),
            route('guru.inventaris.laporan.show', $report),
            route('guru.kuis.koreksi', $quiz),
            route('guru.kuis.live', $liveQuiz),
            route('guru.kuis.live.state', $liveQuiz),
            route('guru.kuis.cetak', $quiz),
        ] as $url) {
            $this->actingAs($guru)->get($url)->assertOk();
        }

        $siswa = $this->userFor('siswa');
        $this->actingAs($siswa)->get(route('siswa.notifikasi.show', $announcement))->assertOk();
    }

    private function userFor(string $role): User
    {
        return User::query()->where('role', $role)->firstOrFail();
    }

    /**
     * Satu set data yang saling terkait: kelas berisi siswa, guru pengampu,
     * kuis beserta soalnya, dan catatan di tiap modul.
     */
    private function seedEverything(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        $guruUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $guruUser->id]);
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);
        $classroom->update(['homeroom_teacher_id' => $teacher->id]);

        User::factory()->create(['role' => 'admin']);

        $siswaUser = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswaUser->id, 'classroom_id' => $classroom->id]);
        StudentProfile::factory()->create(['student_id' => $student->id]);

        Attendance::factory()->create(['student_id' => $student->id, 'date' => now()->toDateString()]);
        ActivityLog::factory()->create(['user_id' => $guruUser->id, 'user_role' => 'guru', 'subject_type' => Student::class, 'subject_id' => $student->id, 'subject_label' => $student->name]);
        Grade::factory()->create(['student_id' => $student->id, 'subject_id' => $subject->id]);
        SppBill::factory()->create(['student_id' => $student->id]);
        LeaveRequest::factory()->create(['student_id' => $student->id]);
        Feedback::factory()->create();
        $announcement = Announcement::factory()->create(['created_by' => $guruUser->id, 'published_at' => now()]);
        $announcement->recipients()->create(['student_id' => $student->id]);
        StudentAchievement::create(['student_id' => $student->id, 'category' => 'academic', 'title' => 'Juara 1 Tahfidz', 'achieved_at' => now()->toDateString()]);
        StudentViolation::create(['student_id' => $student->id, 'severity' => 'light', 'title' => 'Terlambat', 'description' => 'Datang lewat jam masuk.', 'occurred_at' => now()->toDateString()]);

        InventoryItem::factory()->create(['classroom_id' => $classroom->id]);
        InventoryReport::factory()->create(['classroom_id' => $classroom->id, 'reported_by' => $guruUser->id]);

        $quiz = Quiz::factory()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'created_by' => $guruUser->id]);
        QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => $guruUser->id]);
        Quiz::factory()->create(['mode' => 'live', 'subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'created_by' => $guruUser->id]);
        $quiz->questions()->create(['subject_id' => $subject->id, 'created_by' => $guruUser->id, 'type' => 'single', 'question' => 'Ibu kota Indonesia?', 'options' => ['A' => 'Jakarta', 'B' => 'Bandung'], 'correct_answer' => ['A'], 'points' => 1]);
    }
}
