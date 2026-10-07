<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\AttendanceScheduleSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'name' => 'Admin Sekolah']);
    }

    public function test_opening_a_page_is_not_logged(): void
    {
        $this->actingAs($this->admin())->get(route('admin.data-siswa'))->assertOk();

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_adding_a_subject_is_logged_with_the_actor_and_a_readable_description(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.data-mapel.store'), ['code' => 'MTK', 'name' => 'Matematika'])
            ->assertSessionHasNoErrors();

        $log = ActivityLog::sole();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Admin Sekolah', $log->user_name);
        $this->assertSame('admin', $log->user_role);
        $this->assertSame('admin.data-mapel.store', $log->action);
        $this->assertSame('Menambah mata pelajaran', $log->description);
        $this->assertSame('POST', $log->method);
        $this->assertNotNull($log->ip_address);
        $this->assertFalse($log->isRejected());
    }

    /**
     * Objek yang dikenai aksi diambil dari parameter route, dan namanya
     * disalin supaya jejaknya tetap terbaca kalau datanya dihapus nanti.
     */
    public function test_the_subject_of_the_action_is_recorded(): void
    {
        $student = Student::factory()->create(['name' => 'Budi Santoso']);

        $this->actingAs($this->admin())->delete(route('admin.data-siswa.destroy', $student));

        $log = ActivityLog::sole();
        $this->assertSame('Menghapus Data Siswa', $log->description);
        $this->assertSame(Student::class, $log->subject_type);
        $this->assertSame($student->id, $log->subject_id);
        $this->assertSame('Budi Santoso', $log->subject_label);
    }

    public function test_a_special_action_gets_its_own_sentence(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);

        $this->actingAs($this->admin())->post(route('admin.akun.reset-password', $siswa));

        $this->assertSame('Mereset password akun', ActivityLog::sole()->description);
    }

    public function test_a_successful_login_is_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email' => 'kepala@sekolah.test', 'password' => 'password123']);

        $this->post(route('login'), ['identifier' => $admin->email, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $log = ActivityLog::sole();
        $this->assertSame('Login berhasil', $log->description);
        $this->assertSame($admin->id, $log->user_id);
    }

    /**
     * Login gagal justru yang paling perlu terlihat. Identitas yang dicoba
     * dicatat, passwordnya tidak.
     */
    public function test_a_failed_login_is_logged_without_the_password(): void
    {
        User::factory()->create(['role' => 'admin', 'email' => 'kepala@sekolah.test', 'password' => 'password123']);

        $this->post(route('login'), ['identifier' => 'kepala@sekolah.test', 'password' => 'salah-sekali']);

        $log = ActivityLog::sole();
        $this->assertSame('Login gagal', $log->description);
        $this->assertNull($log->user_id);
        $this->assertSame('kepala@sekolah.test', $log->user_name);
        $this->assertStringNotContainsString('salah-sekali', $log->toJson());
    }

    public function test_logout_is_logged_as_the_user_who_left(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('logout'));

        $log = ActivityLog::sole();
        $this->assertSame('Logout dari sistem', $log->description);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('Admin Sekolah', $log->user_name);
    }

    public function test_a_rejected_action_is_logged_and_flagged(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->post(route('admin.data-mapel.store'), ['code' => 'MTK', 'name' => 'Matematika'])->assertForbidden();

        $log = ActivityLog::sole();
        $this->assertSame(403, $log->status_code);
        $this->assertTrue($log->isRejected());
        $this->assertSame($guru->id, $log->user_id);
    }

    public function test_an_action_taken_while_impersonating_records_the_admin_behind_it(): void
    {
        $admin = $this->admin();
        $guruUser = User::factory()->create(['role' => 'guru', 'name' => 'Ustadz Ali']);

        $this->actingAs($admin)->post(route('admin.akun.impersonate', $guruUser))->assertRedirect();
        $this->put(route('account.name.update'), ['name' => 'Ustadz Ali Baru']);

        $log = ActivityLog::query()->where('action', 'account.name.update')->sole();
        $this->assertSame($guruUser->id, $log->user_id);
        $this->assertSame($admin->id, $log->impersonator_id);
    }

    /**
     * Jawaban kuis per soal terjadi puluhan kali per siswa; mencatatnya
     * hanya akan mengubur aktivitas yang perlu dilihat admin.
     */
    public function test_per_question_quiz_answers_are_not_logged(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        Student::factory()->create(['user_id' => $siswa->id]);

        $this->actingAs($siswa)->post(route('siswa.kuis.answer', ['attempt' => 1]), []);

        $this->assertDatabaseCount('activity_logs', 0);
    }

    /**
     * Mencatat log tidak boleh pernah menggagalkan aksi penggunanya.
     */
    public function test_an_action_still_succeeds_when_logging_itself_fails(): void
    {
        $admin = $this->admin();
        $logger = $this->mock(ActivityLogger::class);
        $logger->shouldReceive('record')->once()->andThrow(new \RuntimeException('tabel log hilang'));

        $this->actingAs($admin)->post(route('admin.data-mapel.store'), ['code' => 'MTK', 'name' => 'Matematika'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('subjects', ['name' => 'Matematika']);
    }

    /**
     * Aksi yang ditolak validasi tidak boleh terbaca "berhasil" di log,
     * karena datanya sama sekali tidak tersimpan.
     */
    public function test_an_action_rejected_by_validation_is_not_logged_as_successful(): void
    {
        $this->actingAs($this->admin())->post(route('admin.data-mapel.store'), ['name' => ''])
            ->assertSessionHasErrors();

        $log = ActivityLog::sole();
        $this->assertSame(422, $log->status_code);
        $this->assertTrue($log->isInvalid());
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_a_successful_action_is_logged_as_successful(): void
    {
        $this->actingAs($this->admin())->post(route('admin.data-mapel.store'), ['code' => 'MTK', 'name' => 'Matematika'])
            ->assertSessionHasNoErrors();

        $log = ActivityLog::sole();
        $this->assertFalse($log->isInvalid());
        $this->assertFalse($log->isRejected());
        $this->assertFalse($log->isFailed());
    }

    public function test_the_log_page_lists_activities_newest_first(): void
    {
        ActivityLog::factory()->create(['description' => 'Aktivitas lama', 'created_at' => now()->subDay()]);
        ActivityLog::factory()->create(['description' => 'Aktivitas baru', 'created_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.log-aktivitas'));

        $response->assertOk()->assertViewIs('admin.log-aktivitas');
        $this->assertSame(
            ['Aktivitas baru', 'Aktivitas lama'],
            $response->viewData('logs')->pluck('description')->all(),
        );
    }

    public function test_the_log_page_filters_by_search_role_user_and_date(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'name' => 'Ustadz Ali']);
        ActivityLog::factory()->create(['user_id' => $guru->id, 'user_role' => 'guru', 'user_name' => 'Ustadz Ali', 'description' => 'Menyimpan absensi kelas']);
        ActivityLog::factory()->create(['user_role' => 'siswa', 'user_name' => 'Budi', 'description' => 'Mengumpulkan jawaban kuis', 'created_at' => now()->subMonth()]);

        $admin = $this->admin();

        $byRole = $this->actingAs($admin)->get(route('admin.log-aktivitas', ['role' => 'guru']));
        $this->assertSame(['Menyimpan absensi kelas'], $byRole->viewData('logs')->pluck('description')->all());

        $bySearch = $this->actingAs($admin)->get(route('admin.log-aktivitas', ['search' => 'Budi']));
        $this->assertSame(['Mengumpulkan jawaban kuis'], $bySearch->viewData('logs')->pluck('description')->all());

        $byUser = $this->actingAs($admin)->get(route('admin.log-aktivitas', ['user_id' => $guru->id]));
        $this->assertSame(['Menyimpan absensi kelas'], $byUser->viewData('logs')->pluck('description')->all());

        $byDate = $this->actingAs($admin)->get(route('admin.log-aktivitas', ['date_from' => now()->subWeek()->toDateString()]));
        $this->assertSame(['Menyimpan absensi kelas'], $byDate->viewData('logs')->pluck('description')->all());
    }

    public function test_the_log_page_can_show_only_rejected_and_failed_actions(): void
    {
        ActivityLog::factory()->create(['description' => 'Aksi biasa']);
        ActivityLog::factory()->rejected()->create(['description' => 'Aksi ditolak']);
        ActivityLog::factory()->create(['description' => 'Aksi gagal validasi', 'status_code' => 422]);

        $response = $this->actingAs($this->admin())->get(route('admin.log-aktivitas', ['only_rejected' => 1]));

        $this->assertSame(
            ['Aksi gagal validasi', 'Aksi ditolak'],
            $response->viewData('logs')->pluck('description')->sort()->values()->reverse()->values()->all(),
        );
    }

    public function test_an_invalid_date_filter_is_ignored_instead_of_breaking_the_page(): void
    {
        ActivityLog::factory()->create(['description' => 'Aksi biasa']);

        $this->actingAs($this->admin())->get(route('admin.log-aktivitas', ['date_from' => 'bukan-tanggal']))
            ->assertOk()
            ->assertSee('Aksi biasa');
    }

    /**
     * Jaring pengaman untuk klaim "semua aktivitas tercatat": tiap route yang
     * mengubah data harus menghasilkan kalimat berbahasa Indonesia, bukan
     * nama teknis route-nya. Route baru yang belum terbaca akan menggagalkan
     * test ini, jadi tidak ada aktivitas yang lolos tanpa keterangan.
     */
    public function test_every_action_that_changes_data_has_a_readable_description(): void
    {
        $verbs = ['Me', 'Login', 'Logout', 'Scan', 'Kembali', 'Masuk'];
        $unreadable = [];

        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null
                || ! array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods())
                || in_array($name, ActivityLogger::IGNORED_ROUTES, true)
                // Route bawaan framework (PUT /storage/{path}) bukan aktivitas aplikasi.
                || str_starts_with($name, 'storage.public')) {
                continue;
            }

            $label = ActivityLogger::labelFor($name);

            // Kalimat harus dimulai kata kerja Indonesia, dan tidak boleh
            // sekadar memantulkan nama route-nya.
            $readable = $label !== $name
                && collect($verbs)->contains(fn (string $verb) => str_starts_with($label, $verb));

            if (! $readable) {
                $unreadable[$name] = $label;
            }
        }

        $this->assertSame([], $unreadable, 'Route berikut belum punya kalimat aktivitas yang layak: '.json_encode($unreadable, JSON_PRETTY_PRINT));
    }

    public function test_guru_and_siswa_cannot_read_the_activity_log(): void
    {
        foreach (['guru', 'siswa'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.log-aktivitas'))
                ->assertForbidden();
        }
    }

    public function test_pruning_removes_only_logs_older_than_the_retention_window(): void
    {
        ActivityLog::factory()->create(['description' => 'Masih disimpan', 'created_at' => now()->subDays(10)]);
        ActivityLog::factory()->create(['description' => 'Sudah lama', 'created_at' => now()->subDays(400)]);

        $this->artisan('activity:prune', ['--days' => 365])->assertSuccessful();

        $this->assertSame(['Masih disimpan'], ActivityLog::pluck('description')->all());
    }

    public function test_the_scan_at_the_gate_is_logged_even_without_a_login(): void
    {
        $this->seed(AttendanceScheduleSeeder::class);
        $classroom = Classroom::factory()->create(['grade_level' => 3]);
        $student = Student::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Siti Aminah']);

        $this->postJson(route('presensi.scan.store'), ['qr_token' => $student->qr_token])->assertOk();

        $log = ActivityLog::sole();
        $this->assertSame('Scan QR presensi di pos gerbang', $log->description);
        $this->assertNull($log->user_id);
    }

    public function test_an_import_is_logged(): void
    {
        $classroom = Classroom::factory()->create();
        Subject::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.pembagian-kelas.import', $classroom), []);

        $log = ActivityLog::sole();
        $this->assertSame('Mengimpor siswa ke kelas dari Excel', $log->description);
        $this->assertSame(Classroom::class, $log->subject_type);
    }
}
