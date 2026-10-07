<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Feedback;
use App\Models\LeaveRequest;
use App\Models\SppBill;
use App\Models\SppPayment;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * Paginator bawaan Laravel memakai markup Tailwind. Antarmuka ini memakai
 * Bootstrap, jadi tanpa penyesuaian ikon panahnya tampil sebagai SVG
 * raksasa setinggi layar. Test ini menjaga agar itu tidak terulang.
 */
class PaginationStyleTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_every_paginated_page_uses_bootstrap_pagination(): void
    {
        $admin = $this->seedEnoughRowsToPaginate();

        $pages = [
            'admin.data-siswa',
            'admin.peserta-presensi',
            'admin.kritik-saran',
            'admin.log-aktivitas',
            'admin.approval-izin',
            'admin.verifikasi-spp',
            'admin.notifikasi',
            'admin.laporan-absensi',
        ];

        foreach ($pages as $name) {
            $response = $this->actingAs($admin)->get(route($name));
            $response->assertOk();
            $body = $response->getContent();

            $this->assertStringContainsString('pagination', $body, "Paginasi Bootstrap tidak ditemukan di {$name}");
            $this->assertStringContainsString('page-item', $body, "Paginasi Bootstrap tidak ditemukan di {$name}");

            // Markup Tailwind bawaan: SVG panah tanpa pembatas ukuran, yang
            // membuat ikonnya melar setinggi layar di halaman Bootstrap ini.
            $this->assertStringNotContainsString('w-5 h-5', $body, "SVG paginasi Tailwind masih ada di {$name}");
            $this->assertStringNotContainsString('relative inline-flex items-center', $body, "Paginasi Tailwind masih dipakai di {$name}");

            // Teksnya ikut berbahasa Indonesia seperti sisa antarmuka.
            $this->assertStringContainsString('Menampilkan', $body, "Teks paginasi belum berbahasa Indonesia di {$name}");
            $this->assertStringNotContainsString('Showing', $body, "Teks paginasi masih berbahasa Inggris di {$name}");
            $this->assertStringNotContainsString('Previous', $body, "Teks paginasi masih berbahasa Inggris di {$name}");
        }
    }

    /**
     * Data dibuat lebih banyak dari satu halaman supaya tautan paginasinya
     * benar-benar ter-render.
     */
    private function seedEnoughRowsToPaginate(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();

        $students = Student::factory()->count(25)->create(['classroom_id' => $classroom->id]);

        foreach ($students as $student) {
            Attendance::factory()->create(['student_id' => $student->id, 'date' => now()->toDateString()]);
            LeaveRequest::factory()->create(['student_id' => $student->id]);
            $bill = SppBill::factory()->create(['student_id' => $student->id]);
            SppPayment::factory()->create(['spp_bill_id' => $bill->id]);
        }

        Feedback::factory()->count(25)->create();
        Announcement::factory()->count(25)->create();
        ActivityLog::factory()->count(40)->create();

        return $admin;
    }
}
