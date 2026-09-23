<?php

namespace Tests\Feature;

use App\Enums\StudentStatus;
use App\Jobs\SendAnnouncementWhatsApp;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AnnouncementControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: Student}
     */
    private function siswaWithStudent(array $studentAttributes = []): array
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id, ...$studentAttributes]);

        return [$siswa, $student];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => 'Libur Hari Raya',
            'message' => 'Sekolah libur mulai Senin.',
            'category' => 'pengumuman',
            'target' => 'semua',
            ...$overrides,
        ];
    }

    private function sendAnnouncementTo(Student ...$students): Announcement
    {
        $announcement = Announcement::factory()->create();
        foreach ($students as $student) {
            AnnouncementRecipient::create(['announcement_id' => $announcement->id, 'student_id' => $student->id]);
        }

        return $announcement;
    }

    public function test_admin_can_broadcast_to_all_active_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory(2)->create();
        $graduate = Student::factory()->create(['status' => StudentStatus::Graduated]);

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload())
            ->assertRedirect(route('admin.notifikasi'));

        $announcement = Announcement::sole();
        $this->assertSame($admin->id, $announcement->created_by);
        $this->assertSame(2, $announcement->recipients()->count());
        $this->assertDatabaseMissing('announcement_recipients', ['student_id' => $graduate->id]);
    }

    public function test_admin_can_send_to_one_classroom_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $inClass = Student::factory()->create(['classroom_id' => $classroom->id]);
        $otherClass = Student::factory()->create();

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload([
            'target' => 'kelas',
            'classroom_id' => $classroom->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('announcement_recipients', ['student_id' => $inClass->id]);
        $this->assertDatabaseMissing('announcement_recipients', ['student_id' => $otherClass->id]);
    }

    public function test_admin_can_send_to_specific_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$chosen, $notChosen] = Student::factory(2)->create();

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload([
            'target' => 'siswa',
            'student_ids' => [$chosen->id],
        ]))->assertRedirect();

        $this->assertDatabaseHas('announcement_recipients', ['student_id' => $chosen->id]);
        $this->assertDatabaseMissing('announcement_recipients', ['student_id' => $notChosen->id]);
    }

    public function test_targeting_a_classroom_or_students_requires_choosing_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload(['target' => 'kelas']))
            ->assertSessionHasErrors('classroom_id');
        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload(['target' => 'siswa']))
            ->assertSessionHasErrors('student_ids');
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_checking_send_whatsapp_queues_a_job_per_recipient(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory(2)->create(['parent_phone' => '081234567890']);

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload(['send_whatsapp' => '1']))
            ->assertRedirect();

        $announcement = Announcement::sole();
        $recipientIds = $announcement->recipients()->pluck('id')->all();
        Queue::assertPushed(SendAnnouncementWhatsApp::class, count($recipientIds));
        Queue::assertPushed(fn (SendAnnouncementWhatsApp $job) => in_array($job->recipientId, $recipientIds, true));
    }

    public function test_whatsapp_is_not_queued_when_the_checkbox_is_left_unchecked(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create();

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload())->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_whatsapp_is_not_queued_for_a_scheduled_announcement(): void
    {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        Student::factory()->create(['parent_phone' => '081234567890']);

        $this->actingAs($admin)->post(route('admin.notifikasi.store'), $this->payload([
            'send_whatsapp' => '1',
            'published_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ]))->assertRedirect();

        Queue::assertNothingPushed();
    }

    public function test_non_admins_cannot_send_notifications(): void
    {
        [$siswa] = $this->siswaWithStudent();
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($siswa)->post(route('admin.notifikasi.store'), $this->payload())->assertForbidden();
        $this->actingAs($guru)->get(route('admin.notifikasi'))->assertForbidden();
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_admin_detail_page_shows_who_has_read_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = Student::factory()->create(['name' => 'Sudah Baca']);
        $announcement = $this->sendAnnouncementTo($reader);
        $announcement->recipients()->update(['read_at' => now()]);

        $this->actingAs($admin)->get(route('admin.notifikasi.show', $announcement))
            ->assertOk()
            ->assertSee('Sudah Baca')
            ->assertSee('Dibaca');
    }

    public function test_siswa_sees_the_unread_badge_and_opening_marks_it_read(): void
    {
        [$siswa, $student] = $this->siswaWithStudent();
        $announcement = $this->sendAnnouncementTo($student);

        $this->actingAs($siswa)->get(route('siswa.dashboard'))->assertSee($announcement->title);
        $this->assertSame(1, AnnouncementRecipient::query()->visibleTo($student)->unread()->count());

        $this->actingAs($siswa)->get(route('siswa.notifikasi.show', $announcement))
            ->assertOk()
            ->assertSee($announcement->message);

        $this->assertNotNull(AnnouncementRecipient::sole()->read_at);
    }

    public function test_scheduled_notifications_stay_hidden_until_their_time(): void
    {
        [$siswa, $student] = $this->siswaWithStudent();
        $announcement = Announcement::factory()->scheduled()->create(['title' => 'Pengumuman Besok']);
        AnnouncementRecipient::create(['announcement_id' => $announcement->id, 'student_id' => $student->id]);

        $this->actingAs($siswa)->get(route('siswa.notifikasi'))->assertDontSee('Pengumuman Besok');
        $this->actingAs($siswa)->get(route('siswa.notifikasi.show', $announcement))->assertNotFound();

        $this->travel(2)->days();

        $this->actingAs($siswa)->get(route('siswa.notifikasi'))->assertSee('Pengumuman Besok');
    }

    public function test_siswa_cannot_open_a_notification_not_addressed_to_them(): void
    {
        [$siswa] = $this->siswaWithStudent();
        $announcement = $this->sendAnnouncementTo(Student::factory()->create());

        $this->actingAs($siswa)->get(route('siswa.notifikasi.show', $announcement))->assertNotFound();
    }

    public function test_mark_all_as_read_only_touches_the_siswas_own_notifications(): void
    {
        [$siswa, $student] = $this->siswaWithStudent();
        $otherStudent = Student::factory()->create();
        $this->sendAnnouncementTo($student, $otherStudent);
        $this->sendAnnouncementTo($student);

        $this->actingAs($siswa)->post(route('siswa.notifikasi.read-all'))->assertRedirect();

        $this->assertSame(0, AnnouncementRecipient::query()->where('student_id', $student->id)->whereNull('read_at')->count());
        $this->assertNull(AnnouncementRecipient::query()->where('student_id', $otherStudent->id)->sole()->read_at);
    }
}
