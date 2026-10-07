<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\InventoryReport;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Student;
use App\Models\StudentViolation;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Data dan akun guru lain hanya boleh dilihat admin. Test ini membuka
 * seluruh halaman portal guru dan memastikan nama, NIP, maupun email rekan
 * guru tidak muncul di mana pun.
 */
class GuruPortalPrivacyTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const OTHER_NAME = 'Ustadzah Rahma Rekan';

    private const OTHER_NIP = '1987000999';

    private const OTHER_EMAIL = 'rahma.rekan@guru.test';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    public function test_no_guru_page_shows_another_teachers_name_nip_or_email(): void
    {
        ['user' => $user, 'quiz' => $quiz, 'question' => $question, 'report' => $report] = $this->schoolWithTwoTeachers();

        $urls = [
            route('guru.dashboard'),
            route('guru.data-guru'),
            route('guru.absensi-kelas'),
            route('guru.approval-izin'),
            route('guru.bank-soal'),
            route('guru.bank-soal.tambah'),
            route('guru.bank-soal.edit', $question),
            route('guru.inventaris'),
            route('guru.inventaris.laporan.show', $report),
            route('guru.laporan-nilai'),
            route('guru.prestasi-pelanggaran'),
            route('guru.kuis.koreksi', $quiz),
            route('guru.kuis.cetak', $quiz),
            route('guru.data-guru'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);
            $this->assertSame(200, $response->getStatusCode(), "Halaman {$url} tidak terbuka");

            $body = $response->getContent();
            $this->assertStringNotContainsString(self::OTHER_NAME, $body, "Nama guru lain muncul di {$url}");
            $this->assertStringNotContainsString(self::OTHER_NIP, $body, "NIP guru lain muncul di {$url}");
            $this->assertStringNotContainsString(self::OTHER_EMAIL, $body, "Email guru lain muncul di {$url}");
        }
    }

    /**
     * Riwayat laporan inventaris memuat nama pelapor. Untuk admin nama itu
     * tetap tampil; untuk guru diganti penanda, supaya identitas rekannya
     * tidak terbaca.
     */
    public function test_the_inventory_reporter_name_is_shown_to_admin_but_hidden_from_guru(): void
    {
        ['user' => $user, 'report' => $report] = $this->schoolWithTwoTeachers();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->get(route('guru.inventaris'))
            ->assertOk()
            ->assertSee('Petugas lain')
            ->assertDontSee(self::OTHER_NAME);

        $this->actingAs($user)->get(route('guru.inventaris.laporan.show', $report))
            ->assertOk()
            ->assertDontSee(self::OTHER_NAME);

        $this->actingAs($admin)->get(route('admin.inventaris.laporan.show', $report))
            ->assertOk()
            ->assertSee(self::OTHER_NAME);
    }

    public function test_a_guru_sees_their_own_inventory_report_marked_as_their_own(): void
    {
        ['user' => $user, 'report' => $report] = $this->schoolWithTwoTeachers();
        $report->update(['reported_by' => $user->id]);

        $this->actingAs($user)->get(route('guru.inventaris'))
            ->assertOk()
            ->assertSee('Anda');
    }

    /**
     * Soal & kuis milik guru lain memang sudah tertutup; dipastikan di sini
     * supaya batasan itu tidak hilang tanpa sengaja.
     */
    public function test_a_guru_cannot_open_another_teachers_question_or_quiz(): void
    {
        ['user' => $user, 'otherQuestion' => $otherQuestion, 'otherQuiz' => $otherQuiz] = $this->schoolWithTwoTeachers();

        $this->actingAs($user)->get(route('guru.bank-soal.edit', $otherQuestion))->assertForbidden();
        $this->actingAs($user)->get(route('guru.kuis.koreksi', $otherQuiz))->assertForbidden();
        $this->actingAs($user)->get(route('guru.kuis.cetak', $otherQuiz))->assertForbidden();
    }

    public function test_the_guru_portal_never_lists_more_than_the_logged_in_teacher(): void
    {
        ['user' => $user] = $this->schoolWithTwoTeachers();

        $response = $this->actingAs($user)->get(route('guru.data-guru'));

        $this->assertSame($user->id, $response->viewData('teacher')->user_id);
    }

    /**
     * Satu sekolah berisi dua guru yang sama-sama mengajar & jadi wali kelas,
     * supaya kebocoran lewat relasi (wali kelas, pembuat soal, pelapor
     * inventaris) ikut terjaring.
     *
     * @return array{user: User, quiz: Quiz, question: QuizQuestion, report: InventoryReport, otherQuiz: Quiz, otherQuestion: QuizQuestion}
     */
    private function schoolWithTwoTeachers(): array
    {
        $subject = Subject::factory()->create();

        $user = User::factory()->create(['role' => 'guru', 'name' => 'Ustadz Ali']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id, 'name' => 'Ustadz Ali', 'nip' => '1987001']);
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $teacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id]);

        $otherUser = User::factory()->create(['role' => 'guru', 'name' => self::OTHER_NAME, 'email' => self::OTHER_EMAIL]);
        $otherTeacher = Teacher::factory()->create([
            'user_id' => $otherUser->id,
            'name' => self::OTHER_NAME,
            'nip' => self::OTHER_NIP,
            'email' => self::OTHER_EMAIL,
        ]);
        $otherClassroom = Classroom::factory()->create(['homeroom_teacher_id' => $otherTeacher->id]);
        $otherTeacher->teachingAssignments()->create(['subject_id' => $subject->id, 'classroom_id' => $otherClassroom->id]);

        $student = Student::factory()->create(['classroom_id' => $classroom->id]);
        StudentViolation::create(['student_id' => $student->id, 'severity' => 'light', 'title' => 'Terlambat', 'description' => 'Lewat jam masuk.', 'occurred_at' => now()->toDateString(), 'recorded_by' => $otherUser->id]);

        $item = InventoryItem::factory()->create(['classroom_id' => $classroom->id]);
        $report = InventoryReport::factory()->create(['classroom_id' => $classroom->id, 'reported_by' => $otherUser->id]);

        // Milik guru yang login: halamannya harus terbuka dan bersih.
        $quiz = Quiz::factory()->create(['subject_id' => $subject->id, 'classroom_id' => $classroom->id, 'created_by' => $user->id]);
        $quiz->questions()->create(['subject_id' => $subject->id, 'created_by' => $user->id, 'type' => 'essay', 'question' => 'Jelaskan!', 'options' => [], 'correct_answer' => [], 'points' => 1]);
        $question = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => $user->id]);

        // Milik guru lain: ikut tersimpan di database supaya kebocoran lewat
        // daftar/relasi tetap terjaring, tapi halamannya harus tertutup.
        $otherQuiz = Quiz::factory()->create(['subject_id' => $subject->id, 'classroom_id' => $otherClassroom->id, 'created_by' => $otherUser->id]);
        $otherQuestion = QuizQuestion::factory()->create(['subject_id' => $subject->id, 'created_by' => $otherUser->id]);

        return compact('user', 'quiz', 'question', 'report', 'otherQuiz', 'otherQuestion');
    }
}
