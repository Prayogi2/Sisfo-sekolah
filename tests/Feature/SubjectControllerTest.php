<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SubjectControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_subject_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create(['name' => 'Akidah Akhlak']);

        $response = $this->actingAs($admin)->get(route('admin.data-mapel'));

        $response->assertOk();
        $response->assertSee('Akidah Akhlak');
        $response->assertSee($subject->code);
    }

    public function test_guru_is_forbidden_from_the_subject_list(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.data-mapel'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_the_subject_list(): void
    {
        $this->get(route('admin.data-mapel'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_a_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.data-mapel.store'), [
            'code' => 'MTK',
            'name' => 'Matematika',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['code' => 'MTK', 'name' => 'Matematika']);
    }

    public function test_creating_a_subject_requires_a_code_and_a_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.data-mapel.store'), []);

        $response->assertSessionHasErrors([
            'code' => 'Kode mapel wajib diisi.',
            'name' => 'Nama mapel wajib diisi.',
        ]);
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_creating_a_subject_rejects_a_duplicate_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Subject::factory()->create(['code' => 'MTK']);

        $response = $this->actingAs($admin)->post(route('admin.data-mapel.store'), [
            'code' => 'MTK',
            'name' => 'Matematika Lanjut',
        ]);

        $response->assertSessionHasErrors(['code' => 'Kode mapel sudah dipakai.']);
        $this->assertDatabaseCount('subjects', 1);
    }

    public function test_admin_can_update_a_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create(['code' => 'MTK', 'name' => 'Matematika']);

        $response = $this->actingAs($admin)->put(route('admin.data-mapel.update', $subject), [
            'code' => 'MAT',
            'name' => 'Matematika Wajib',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'code' => 'MAT', 'name' => 'Matematika Wajib']);
    }

    public function test_updating_a_subject_allows_keeping_its_own_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create(['code' => 'MTK', 'name' => 'Matematika']);

        $response = $this->actingAs($admin)->put(route('admin.data-mapel.update', $subject), [
            'code' => 'MTK',
            'name' => 'Matematika Dasar',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Matematika Dasar']);
    }

    public function test_guru_is_forbidden_from_updating_a_subject(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $subject = Subject::factory()->create(['name' => 'Matematika']);

        $this->actingAs($guru)->put(route('admin.data-mapel.update', $subject), [
            'code' => 'XYZ',
            'name' => 'Diubah Guru',
        ])->assertForbidden();

        $this->assertDatabaseHas('subjects', ['id' => $subject->id, 'name' => 'Matematika']);
    }

    public function test_admin_can_delete_a_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.data-mapel.destroy', $subject));

        $response->assertRedirect();
        $this->assertModelMissing($subject);
    }

    public function test_the_subject_list_escapes_a_subject_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Subject::factory()->create(['name' => "<script>alert('xss')</script>"]);

        $response = $this->actingAs($admin)->get(route('admin.data-mapel'));

        $response->assertSee('&lt;script&gt;', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }
}
