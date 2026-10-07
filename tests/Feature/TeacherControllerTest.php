<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_sees_the_admin_teacher_view(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.data-guru'))
            ->assertOk()
            ->assertViewIs('admin.data-guru');
    }

    public function test_guru_sees_the_read_only_guru_teacher_view(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('guru.data-guru'))
            ->assertOk()
            ->assertViewIs('guru.data-guru');
    }

    /**
     * Halaman Data Guru milik guru hanya berisi datanya sendiri — daftar
     * seluruh rekan guru adalah wewenang admin.
     */
    public function test_guru_only_sees_their_own_data_not_other_teachers(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $teacher->update(['name' => 'Ustadz Ali', 'nip' => '1987001']);
        $otherTeacher = Teacher::factory()->create(['name' => 'Ustadzah Rahma', 'nip' => '1987002']);

        $response = $this->actingAs($user)->get(route('guru.data-guru'));

        $response->assertOk()
            ->assertSee('Ustadz Ali')
            ->assertSee('1987001')
            ->assertDontSee('Ustadzah Rahma')
            ->assertDontSee('1987002');
        $this->assertSame($teacher->id, $response->viewData('teacher')->id);
    }

    /**
     * Guru hanya bisa mengganti passwordnya sendiri lewat halaman akun;
     * tombol reset password guru lain tidak boleh ada di halamannya.
     */
    public function test_the_guru_page_offers_only_their_own_password_change(): void
    {
        [$user] = $this->teacherWithAccount();

        $response = $this->actingAs($user)->get(route('guru.data-guru'));

        $response->assertOk()
            ->assertSee(route('account.password.edit'), false)
            ->assertDontSee('data-guru/', false);
    }

    public function test_a_guru_account_without_teacher_data_still_opens_the_page(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('guru.data-guru'))
            ->assertOk()
            ->assertSee('belum terhubung ke akun ini');
    }

    /**
     * Lapisan kedua di bawah middleware: izin "lihat semua guru" memang
     * hanya milik admin, jadi data rekan guru tetap tertutup walau nanti
     * ada halaman baru yang lupa dipasangi middleware admin.
     */
    public function test_only_admin_is_allowed_to_list_all_teachers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$guru, $teacher] = $this->teacherWithAccount();
        $otherTeacher = Teacher::factory()->create();

        $this->assertTrue($admin->can('viewAny', Teacher::class));
        $this->assertFalse($guru->can('viewAny', Teacher::class));

        // Guru boleh melihat datanya sendiri, tapi bukan data rekannya.
        $this->assertTrue($guru->can('view', $teacher));
        $this->assertFalse($guru->can('view', $otherTeacher));
        $this->assertTrue($admin->can('view', $otherTeacher));
    }

    public function test_guru_cannot_download_the_teacher_list(): void
    {
        [$guru] = $this->teacherWithAccount();

        $this->actingAs($guru)->get(route('admin.data-guru.unduh', 'xlsx'))->assertForbidden();
        $this->actingAs($guru)->get(route('admin.data-guru.unduh', 'pdf'))->assertForbidden();
    }

    public function test_guru_cannot_reset_another_teachers_password(): void
    {
        [$user] = $this->teacherWithAccount();
        $otherUser = User::factory()->create(['role' => 'guru']);
        $otherTeacher = Teacher::factory()->create(['user_id' => $otherUser->id]);
        $passwordBefore = $otherUser->password;

        $this->actingAs($user)->post(route('admin.data-guru.reset-password', $otherTeacher))
            ->assertForbidden();

        $this->assertSame($passwordBefore, $otherUser->fresh()->password);
    }

    public function test_guru_cannot_reset_their_own_password_from_the_teacher_module(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $passwordBefore = $user->password;

        $this->actingAs($user)->post(route('admin.data-guru.reset-password', $teacher))
            ->assertForbidden();

        $this->assertSame($passwordBefore, $user->fresh()->password);
    }

    public function test_guru_cannot_change_or_delete_another_teacher(): void
    {
        [$user] = $this->teacherWithAccount();
        $otherTeacher = Teacher::factory()->create(['name' => 'Ustadzah Rahma']);

        $this->actingAs($user)->put(route('admin.data-guru.update', $otherTeacher), [
            'nip' => '9999999999999999',
            'name' => 'Nama Diubah Guru Lain',
            'gender' => 'L',
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('admin.data-guru.destroy', $otherTeacher))->assertForbidden();

        $this->assertDatabaseHas('teachers', ['id' => $otherTeacher->id, 'name' => 'Ustadzah Rahma']);
    }

    /**
     * Guru tetap bisa mengganti passwordnya sendiri — itu satu-satunya
     * perubahan akun yang boleh ia lakukan.
     */
    public function test_guru_can_still_change_their_own_password(): void
    {
        $user = User::factory()->create(['role' => 'guru', 'password' => 'password-lama']);
        Teacher::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'password-lama',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    public function test_admin_can_create_a_teacher_with_a_login_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $classroomA = Classroom::factory()->create();
        $classroomB = Classroom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
            'email' => 'fulan@guru.local',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroomA->id, $classroomB->id]],
            ],
        ]);

        $response->assertRedirect();
        $teacher = Teacher::where('nip', '1234567890123456')->firstOrFail();

        $this->assertNotNull($teacher->user_id);
        $this->assertSame('fulan@guru.local', $teacher->user->email);
        $this->assertTrue($teacher->user->hasRole('guru'));
        $this->assertTrue($teacher->subjects->contains($subject));
        $this->assertTrue($teacher->teaches($subject->id, $classroomA->id));
        $this->assertTrue($teacher->teaches($subject->id, $classroomB->id));
        $this->assertFalse($teacher->user->hasRole('admin'));
    }

    public function test_admin_can_create_a_teacher_who_is_also_an_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '9876543210',
            'name' => 'Operator Sekolah',
            'gender' => 'P',
            'email' => 'operator@guru.local',
            'is_admin' => '1',
        ])->assertSessionHas('success', fn (string $message) => str_starts_with($message, 'Guru berhasil ditambahkan sebagai guru sekaligus administrator. Login: operator@guru.local'));

        $user = Teacher::where('nip', '9876543210')->sole()->user;
        $this->assertTrue($user->hasRole('guru'));
        $this->assertTrue($user->hasRole('admin'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_save_the_teacher_biodata(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '198001012005011001',
            'name' => 'Ustadzah Aminah, S.Pd.',
            'gender' => 'P',
            'email' => 'aminah@gmail.com',
            'phone' => '081234567890',
            'nik' => '3203014501800001',
            'birth_place' => 'Cianjur',
            'birth_date' => '1980-01-05',
            'address' => 'Kp. Sukamaju RT 01/02',
            'village' => 'Sukamaju',
            'district' => 'Cibeber',
            'province' => 'Jawa Barat',
            'last_education' => 's1',
            'blood_type' => 'O',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('teachers', [
            'nik' => '3203014501800001',
            'birth_place' => 'Cianjur',
            'village' => 'Sukamaju',
            'district' => 'Cibeber',
            'province' => 'Jawa Barat',
            'last_education' => 's1',
            'blood_type' => 'O',
            'email' => 'aminah@gmail.com',
            'phone' => '081234567890',
        ]);
    }

    public function test_teacher_biodata_is_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();

        $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nip' => $teacher->nip,
            'name' => $teacher->name,
            'gender' => 'L',
            'nik' => '12345',
            'birth_date' => now()->addDay()->toDateString(),
            'last_education' => 'profesor',
            'blood_type' => 'Z',
        ])->assertSessionHasErrors(['nik', 'birth_date', 'last_education', 'blood_type']);
    }

    public function test_creating_a_teacher_rejects_the_same_subject_chosen_twice(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();
        $classroom = Classroom::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
            'assignments' => [
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroom->id]],
                ['subject_id' => $subject->id, 'classroom_ids' => [$classroom->id]],
            ],
        ]);

        $response->assertSessionHasErrors('assignments');
        $this->assertDatabaseMissing('teachers', ['nip' => '1234567890123456']);
    }

    public function test_guru_cannot_create_a_teacher(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Fulan',
            'gender' => 'L',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('teachers', ['nip' => '1234567890123456']);
    }

    public function test_admin_can_update_a_teacher_and_resync_assignments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();
        $oldSubject = Subject::factory()->create();
        $newSubject = Subject::factory()->create();
        $oldClassroom = Classroom::factory()->create();
        $newClassroom = Classroom::factory()->create();
        $teacher->teachingAssignments()->create(['subject_id' => $oldSubject->id, 'classroom_id' => $oldClassroom->id]);

        $response = $this->actingAs($admin)->put(route('admin.data-guru.update', $teacher), [
            'nip' => $teacher->nip,
            'name' => 'Nama Baru',
            'gender' => $teacher->gender->value,
            'assignments' => [
                ['subject_id' => $newSubject->id, 'classroom_ids' => [$newClassroom->id]],
            ],
        ]);

        $response->assertRedirect();
        $teacher->refresh();
        $this->assertSame('Nama Baru', $teacher->name);
        $this->assertTrue($teacher->subjects->contains($newSubject));
        $this->assertFalse($teacher->subjects->contains($oldSubject));
        $this->assertTrue($teacher->teaches($newSubject->id, $newClassroom->id));
        $this->assertFalse($teacher->teaches($oldSubject->id, $oldClassroom->id));
    }

    public function test_admin_can_reset_a_teachers_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->for(User::factory()->state(['role' => 'guru']))->create();
        $oldHash = $teacher->user->password;

        $response = $this->actingAs($admin)->post(route('admin.data-guru.reset-password', $teacher));

        $response->assertRedirect();
        $this->assertNotSame($oldHash, $teacher->user->fresh()->password);
    }

    public function test_admin_can_delete_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.data-guru.destroy', $teacher));

        $response->assertRedirect();
        $this->assertModelMissing($teacher);
    }

    /**
     * @return array{0: User, 1: Teacher}
     */
    private function teacherWithAccount(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, $teacher];
    }

    public function test_admin_can_make_a_teacher_an_admin_who_keeps_the_guru_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $teacher] = $this->teacherWithAccount();

        $this->actingAs($admin)->post(route('admin.data-guru.admin-access.grant', $teacher))
            ->assertSessionHas('success', "{$teacher->name} sekarang juga memiliki akses admin. Menu admin bisa dibuka lewat Akses Role di pojok kanan atas.");

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('guru'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_a_teacher_without_a_login_account_cannot_be_made_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create(['user_id' => null]);

        $this->actingAs($admin)->post(route('admin.data-guru.admin-access.grant', $teacher))
            ->assertSessionHas('error', "{$teacher->name} belum memiliki akun login, jadi belum bisa dijadikan admin.");
    }

    public function test_guru_cannot_grant_admin_access(): void
    {
        [$guru] = $this->teacherWithAccount();
        [$otherUser, $otherTeacher] = $this->teacherWithAccount();

        $this->actingAs($guru)->post(route('admin.data-guru.admin-access.grant', $otherTeacher))->assertForbidden();

        $this->assertFalse($otherUser->fresh()->hasRole('admin'));
    }

    public function test_admin_can_revoke_admin_access_from_a_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');

        $this->actingAs($admin)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('success', "Akses admin {$teacher->name} dicabut. Akunnya kembali hanya sebagai guru.");

        $user->refresh();
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('guru'));
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_a_teacher_admin_cannot_revoke_their_own_admin_access(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');

        $this->actingAs($user)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('error', 'Anda tidak bisa mencabut akses admin akun Anda sendiri.');

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_the_primary_admin_account_cannot_be_revoked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $primaryAdmin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create(['user_id' => $primaryAdmin->id]);

        $this->actingAs($admin)->delete(route('admin.data-guru.admin-access.revoke', $teacher))
            ->assertSessionHas('error', "Akun {$teacher->name} adalah akun admin utama, aksesnya tidak bisa dicabut dari sini.");

        $this->assertTrue($primaryAdmin->fresh()->hasRole('admin'));
    }

    /**
     * Guru yang diangkat jadi admin memegang dua peran sekaligus: seluruh
     * menu admin harus terbuka untuknya, sementara halaman di URL /guru/...
     * tetap berperilaku sebagai portal guru.
     */
    public function test_a_teacher_promoted_to_admin_can_open_every_admin_page(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');
        Teacher::factory()->create(['name' => 'Ustadzah Rahma']);
        Http::fake();

        $adminPages = [
            'admin.dashboard', 'admin.akun', 'admin.approval-izin', 'admin.bank-soal',
            'admin.buku-induk', 'admin.data-guru', 'admin.data-mapel', 'admin.data-siswa',
            'admin.data-siswa.import', 'admin.data-siswa.create', 'admin.inventaris',
            'admin.kritik-saran', 'admin.laporan', 'admin.laporan-absensi', 'admin.laporan-nilai',
            'admin.laporan-spp', 'admin.notifikasi', 'admin.pembagian-kelas',
            'admin.peserta-presensi', 'admin.prestasi-pelanggaran', 'admin.verifikasi-spp',
            'admin.whatsapp', 'admin.log-aktivitas',
        ];

        foreach ($adminPages as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }

        // Di menu admin ia melihat seluruh guru, bukan hanya dirinya.
        $this->actingAs($user)->get(route('admin.data-guru'))
            ->assertViewIs('admin.data-guru')
            ->assertSee('Ustadzah Rahma');

        // Di URL guru, halamannya tetap portal guru berisi datanya sendiri.
        $this->actingAs($user)->get(route('guru.data-guru'))
            ->assertViewIs('guru.data-guru')
            ->assertDontSee('Ustadzah Rahma');
        $this->assertTrue($user->fresh()->can('viewAny', Teacher::class));
    }

    /**
     * Tindakan pengelolaan — bukan cuma membuka halaman — juga harus bisa
     * dilakukan guru yang sudah diangkat jadi admin.
     */
    public function test_a_teacher_promoted_to_admin_can_manage_other_teachers(): void
    {
        [$user] = $this->teacherWithAccount();
        $user->assignRole('admin');
        $otherUser = User::factory()->create(['role' => 'guru']);
        $otherTeacher = Teacher::factory()->create(['user_id' => $otherUser->id, 'name' => 'Ustadzah Rahma']);
        $passwordBefore = $otherUser->password;

        $this->actingAs($user)->post(route('admin.data-guru.reset-password', $otherTeacher))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNotSame($passwordBefore, $otherUser->fresh()->password);

        $this->actingAs($user)->post(route('admin.data-guru.store'), [
            'nip' => '1234567890123456',
            'name' => 'Ustadz Baru',
            'gender' => 'L',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('teachers', ['nip' => '1234567890123456']);

        // Ekspor daftar guru dijaga policy yang sama; unduhan filenya sendiri
        // sudah diuji di DataExportControllerTest.
        $this->assertTrue($user->fresh()->can('viewAny', Teacher::class));
    }

    public function test_a_teacher_with_admin_access_still_gets_the_guru_pages_under_guru_urls(): void
    {
        [$user, $teacher] = $this->teacherWithAccount();
        $user->assignRole('admin');
        $homeroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);
        $ownStudent = Student::factory()->create(['classroom_id' => $homeroom->id]);
        $otherStudent = Student::factory()->create();
        LeaveRequest::factory()->create(['student_id' => $ownStudent->id]);
        LeaveRequest::factory()->create(['student_id' => $otherStudent->id]);

        $this->actingAs($user)->get(route('guru.data-guru'))->assertViewIs('guru.data-guru');
        $this->actingAs($user)->get(route('guru.bank-soal'))->assertViewIs('guru.bank-soal');
        $this->actingAs($user)->get(route('guru.approval-izin'))
            ->assertViewIs('guru.approval-izin')
            ->assertSee($ownStudent->name)
            ->assertDontSee($otherStudent->name);

        // Di halaman admin, dia melihat semua data seperti admin.
        $this->actingAs($user)->get(route('admin.approval-izin'))
            ->assertViewIs('admin.approval-izin')
            ->assertSee($otherStudent->name);
    }
}
