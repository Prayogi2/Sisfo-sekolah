<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentConductControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_and_record_student_conduct(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['name' => 'Ahmad Fauzi']);

        $this->actingAs($admin)->get(route('admin.prestasi-pelanggaran'))
            ->assertOk()
            ->assertSee('Ahmad Fauzi');

        $this->actingAs($admin)->post(route('admin.prestasi-pelanggaran.prestasi.store'), [
            'student_id' => $student->id,
            'category' => 'academic',
            'title' => 'Juara Olimpiade',
            'achievement' => 'Juara 1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.prestasi-pelanggaran.pelanggaran.store'), [
            'student_id' => $student->id,
            'severity' => 'light',
            'title' => 'Terlambat',
            'description' => 'Datang setelah bel masuk.',
            'occurred_at' => '2026-09-21',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_achievements', ['student_id' => $student->id, 'title' => 'Juara Olimpiade']);
        $this->assertDatabaseHas('student_violations', ['student_id' => $student->id, 'title' => 'Terlambat']);
    }

    public function test_guru_can_record_conduct_but_siswa_cannot(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create();

        $this->actingAs($guru)->post(route('guru.prestasi-pelanggaran.pelanggaran.store'), [
            'student_id' => $student->id, 'severity' => 'medium', 'title' => 'Gadget', 'description' => 'Menggunakan gadget saat pelajaran.', 'occurred_at' => '2026-09-21',
        ])->assertRedirect();

        $this->actingAs($siswa)->get(route('admin.prestasi-pelanggaran'))->assertForbidden();
        $this->actingAs($siswa)->post(route('admin.prestasi-pelanggaran.prestasi.store'), [
            'student_id' => $student->id, 'category' => 'academic', 'title' => 'Tidak sah',
        ])->assertForbidden();
    }

    public function test_siswa_only_sees_their_own_conduct(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa']);
        $student = Student::factory()->create(['user_id' => $siswa->id]);
        $other = Student::factory()->create(['name' => 'Siswa Lain']);
        StudentAchievement::create(['student_id' => $student->id, 'category' => 'academic', 'title' => 'Prestasi Sendiri']);
        StudentAchievement::create(['student_id' => $other->id, 'category' => 'academic', 'title' => 'Rahasia Siswa Lain']);

        $this->actingAs($siswa)->get(route('siswa.prestasi-pelanggaran'))
            ->assertOk()
            ->assertSee('Prestasi Sendiri')
            ->assertDontSee('Rahasia Siswa Lain');
    }
}
