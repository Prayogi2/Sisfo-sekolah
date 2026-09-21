<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedRoutes(): array
    {
        return [
            'admin dashboard' => ['/admin/dashboard'],
            'guru dashboard' => ['/guru/dashboard'],
            'siswa dashboard' => ['/siswa/dashboard'],
            'wali dashboard' => ['/wali-murid/dashboard'],
            'scan qr' => ['/scan-qr'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_guest_is_redirected_to_login(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'guru' => ['guru'],
            'wali' => ['wali'],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_role_is_forbidden_from_admin_dashboard(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_guru_can_access_guru_dashboard(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $response = $this->actingAs($guru)->get('/guru/dashboard');

        $response->assertOk();
    }

    public function test_wali_is_forbidden_from_guru_dashboard(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);

        $response = $this->actingAs($wali)->get('/guru/dashboard');

        $response->assertForbidden();
    }

    public function test_wali_can_access_siswa_and_wali_dashboards(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);

        $this->actingAs($wali)->get('/siswa/dashboard')->assertOk();
        $this->actingAs($wali)->get('/wali-murid/dashboard')->assertOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonWaliRoles(): array
    {
        return [
            'admin' => ['admin'],
            'guru' => ['guru'],
        ];
    }

    #[DataProvider('nonWaliRoles')]
    public function test_non_wali_role_is_forbidden_from_siswa_dashboard(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get('/siswa/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_and_guru_can_access_scan_qr(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($admin)->get('/scan-qr')->assertOk();
        $this->actingAs($guru)->get('/scan-qr')->assertOk();
    }

    public function test_wali_is_forbidden_from_scan_qr(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);

        $response = $this->actingAs($wali)->get('/scan-qr');

        $response->assertForbidden();
    }
}
