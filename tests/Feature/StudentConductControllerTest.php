<?php

namespace Tests\Feature;

use App\Models\Guardian;
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

    public function test_guru_can_record_conduct_but_wali_cannot(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $wali = User::factory()->create(['role' => 'wali']);
        $student = Student::factory()->create();

        $this->actingAs($guru)->post(route('guru.prestasi-pelanggaran.pelanggaran.store'), [
            'student_id' => $student->id, 'severity' => 'medium', 'title' => 'Gadget', 'description' => 'Menggunakan gadget saat pelajaran.', 'occurred_at' => '2026-09-21',
        ])->assertRedirect();

        $this->actingAs($wali)->get(route('admin.prestasi-pelanggaran'))->assertForbidden();
        $this->actingAs($wali)->post(route('admin.prestasi-pelanggaran.prestasi.store'), [
            'student_id' => $student->id, 'category' => 'academic', 'title' => 'Tidak sah',
        ])->assertForbidden();
    }

    public function test_guardian_can_only_see_the_selected_child_conduct(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        $guardian = Guardian::factory()->create(['user_id' => $wali->id]);
        $child = Student::factory()->create(['name' => 'Anak Wali']);
        $other = Student::factory()->create(['name' => 'Anak Lain']);
        $guardian->students()->attach($child);
        StudentAchievement::create(['student_id' => $child->id, 'category' => 'academic', 'title' => 'Prestasi Anak']);
        StudentAchievement::create(['student_id' => $other->id, 'category' => 'academic', 'title' => 'Rahasia Anak Lain']);

        $this->actingAs($wali)->get(route('wali.prestasi-pelanggaran'))
            ->assertOk()
            ->assertSee('Prestasi Anak')
            ->assertDontSee('Rahasia Anak Lain');
    }
}
