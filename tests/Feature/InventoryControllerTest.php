<?php

namespace Tests\Feature;

use App\Enums\InventoryCategory;
use App\Models\Classroom;
use App\Models\InventoryItem;
use App\Models\InventoryReport;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: User, 1: Classroom}
     */
    private function homeroomTeacher(): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $classroom = Classroom::factory()->create(['homeroom_teacher_id' => $teacher->id]);

        return [$user, $classroom];
    }

    public function test_inventory_abilities_follow_the_admin_and_homeroom_rules(): void
    {
        [$homeroomUser, $classroom] = $this->homeroomTeacher();
        $admin = User::factory()->create(['role' => 'admin']);
        $otherGuru = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $otherGuru->id]);
        $siswa = User::factory()->create(['role' => 'siswa']);

        $expected = [
            'viewInventory' => ['admin' => true, 'homeroom' => true, 'otherGuru' => false, 'siswa' => false],
            'addInventoryItem' => ['admin' => true, 'homeroom' => true, 'otherGuru' => false, 'siswa' => false],
            'manageInventory' => ['admin' => true, 'homeroom' => false, 'otherGuru' => false, 'siswa' => false],
            'reportInventory' => ['admin' => false, 'homeroom' => true, 'otherGuru' => false, 'siswa' => false],
        ];
        $users = ['admin' => $admin, 'homeroom' => $homeroomUser, 'otherGuru' => $otherGuru, 'siswa' => $siswa];

        foreach ($expected as $ability => $allowedFor) {
            foreach ($allowedFor as $who => $allowed) {
                $this->assertSame($allowed, $users[$who]->can($ability, $classroom), "{$ability} untuk {$who}");
            }
        }
    }

    public function test_admin_sees_the_items_of_the_selected_classroom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Proyektor Kelas', 'good_quantity' => 1]);
        InventoryItem::factory()->create(['classroom_id' => $otherClassroom->id, 'name' => 'Televisi Kelas Lain']);

        $response = $this->actingAs($admin)->get(route('admin.inventaris', ['classroom' => $classroom->id]));

        $response->assertOk();
        $response->assertSee('Proyektor Kelas');
        $response->assertDontSee('Televisi Kelas Lain');
    }

    public function test_guru_only_sees_the_inventory_of_their_homeroom_class(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $otherClassroom = Classroom::factory()->create();
        InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Lemari Kelasku']);
        InventoryItem::factory()->create(['classroom_id' => $otherClassroom->id, 'name' => 'Lemari Kelas Lain']);

        $response = $this->actingAs($user)->get(route('guru.inventaris', ['classroom' => $otherClassroom->id]));

        $response->assertOk();
        $response->assertSee('Lemari Kelasku');
        $response->assertDontSee('Lemari Kelas Lain');
    }

    public function test_guru_without_a_homeroom_class_is_told_there_is_nothing_to_report(): void
    {
        $user = User::factory()->create(['role' => 'guru']);
        Teacher::factory()->create(['user_id' => $user->id]);
        Classroom::factory()->create();

        $this->actingAs($user)->get(route('guru.inventaris'))
            ->assertOk()
            ->assertSee('Anda belum ditugaskan sebagai wali kelas');
    }

    public function test_default_items_are_added_once_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $classroom = Classroom::factory()->create();
        InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'category' => InventoryCategory::Electronics, 'name' => 'Lampu']);
        $defaultCount = collect(InventoryCategory::cases())->sum(fn (InventoryCategory $category) => count($category->defaultItems()));

        $this->actingAs($admin)->post(route('admin.inventaris.items.defaults', $classroom))
            ->assertSessionHas('success', ($defaultCount - 1)." barang standar ditambahkan ke inventaris kelas {$classroom->name}.");
        $this->actingAs($admin)->post(route('admin.inventaris.items.defaults', $classroom))
            ->assertSessionHas('success', 'Semua barang standar sudah ada di kelas ini.');

        $this->assertSame($defaultCount, $classroom->inventoryItems()->count());
    }

    public function test_homeroom_teacher_can_add_an_item_to_their_class(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();

        $this->actingAs($user)->post(route('guru.inventaris.items.store', $classroom), [
            'category' => InventoryCategory::Electronics->value,
            'name' => 'Proyektor',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('inventory_items', [
            'classroom_id' => $classroom->id,
            'category' => InventoryCategory::Electronics->value,
            'name' => 'Proyektor',
            'created_by' => $user->id,
        ]);
    }

    public function test_adding_an_item_that_already_exists_in_the_category_is_rejected(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'category' => InventoryCategory::Furniture, 'name' => 'Lemari']);

        $this->actingAs($user)->post(route('guru.inventaris.items.store', $classroom), [
            'category' => InventoryCategory::Furniture->value,
            'name' => 'Lemari',
        ])->assertSessionHasErrors(['name' => 'Barang dengan nama ini sudah ada di kategori tersebut.']);
    }

    public function test_guru_cannot_add_items_to_a_class_they_do_not_teach(): void
    {
        [$user] = $this->homeroomTeacher();
        $otherClassroom = Classroom::factory()->create();

        $this->actingAs($user)->post(route('guru.inventaris.items.store', $otherClassroom), [
            'category' => InventoryCategory::Furniture->value,
            'name' => 'Lemari',
        ])->assertForbidden();

        $this->assertDatabaseCount('inventory_items', 0);
    }

    public function test_admin_can_delete_an_item_while_its_report_history_is_kept(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = InventoryItem::factory()->create(['name' => 'Kipas Angin']);
        $report = InventoryReport::factory()->create(['classroom_id' => $item->classroom_id]);
        $report->items()->create(['inventory_item_id' => $item->id, 'category' => $item->category, 'name' => 'Kipas Angin', 'good_quantity' => 2]);

        $this->actingAs($admin)->delete(route('admin.inventaris.items.destroy', $item))->assertRedirect();

        $this->assertModelMissing($item);
        $this->assertDatabaseHas('inventory_report_items', ['inventory_report_id' => $report->id, 'inventory_item_id' => null, 'name' => 'Kipas Angin']);
    }

    public function test_guru_cannot_delete_inventory_items(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $item = InventoryItem::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($user)->delete(route('admin.inventaris.items.destroy', $item))->assertForbidden();

        $this->assertModelExists($item);
    }

    public function test_homeroom_teacher_submits_a_condition_report_with_photos(): void
    {
        Storage::fake('public');
        [$user, $classroom] = $this->homeroomTeacher();
        $chair = InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Kursi Belajar Siswa']);
        $lamp = InventoryItem::factory()->create(['classroom_id' => $classroom->id, 'name' => 'Lampu', 'photo_path' => 'inventory-photos/lampu-lama.jpg']);

        $response = $this->actingAs($user)->post(route('guru.inventaris.laporan.store', $classroom), [
            'items' => [
                $chair->id => ['good_quantity' => 28, 'damaged_quantity' => 2, 'notes' => '2 kaki patah'],
                $lamp->id => ['good_quantity' => 4, 'damaged_quantity' => 0],
            ],
            'photos' => [$chair->id => UploadedFile::fake()->image('kursi.jpg')],
            'notes' => 'Mohon perbaikan kursi',
        ]);

        $response->assertRedirect(route('guru.inventaris', ['classroom' => $classroom->id]));
        $response->assertSessionHas('success', "Laporan kondisi inventaris kelas {$classroom->name} berhasil dikirim.");

        $chair->refresh();
        $this->assertSame([28, 2, '2 kaki patah'], [$chair->good_quantity, $chair->damaged_quantity, $chair->notes]);
        $this->assertNotNull($chair->last_reported_at);
        Storage::disk('public')->assertExists($chair->photo_path);
        // Tanpa foto baru, foto lama tetap dipakai.
        $this->assertSame('inventory-photos/lampu-lama.jpg', $lamp->fresh()->photo_path);

        $report = InventoryReport::sole();
        $this->assertSame([$classroom->id, $user->id, 'Mohon perbaikan kursi'], [$report->classroom_id, $report->reported_by, $report->notes]);
        $this->assertDatabaseHas('inventory_report_items', ['inventory_report_id' => $report->id, 'inventory_item_id' => $chair->id, 'name' => 'Kursi Belajar Siswa', 'good_quantity' => 28, 'damaged_quantity' => 2, 'photo_path' => $chair->photo_path]);
        $this->assertDatabaseHas('inventory_report_items', ['inventory_report_id' => $report->id, 'inventory_item_id' => $lamp->id, 'photo_path' => 'inventory-photos/lampu-lama.jpg']);
    }

    public function test_condition_report_ignores_items_from_another_class(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $ownItem = InventoryItem::factory()->create(['classroom_id' => $classroom->id]);
        $foreignItem = InventoryItem::factory()->create(['good_quantity' => 5]);

        $this->actingAs($user)->post(route('guru.inventaris.laporan.store', $classroom), [
            'items' => [
                $ownItem->id => ['good_quantity' => 1, 'damaged_quantity' => 0],
                $foreignItem->id => ['good_quantity' => 99, 'damaged_quantity' => 99],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, $foreignItem->fresh()->good_quantity);
        $this->assertDatabaseMissing('inventory_report_items', ['inventory_item_id' => $foreignItem->id]);
    }

    public function test_condition_report_rejects_negative_quantities(): void
    {
        [$user, $classroom] = $this->homeroomTeacher();
        $item = InventoryItem::factory()->create(['classroom_id' => $classroom->id]);

        $this->actingAs($user)->post(route('guru.inventaris.laporan.store', $classroom), [
            'items' => [$item->id => ['good_quantity' => -1, 'damaged_quantity' => 0]],
        ])->assertSessionHasErrors(["items.{$item->id}.good_quantity" => 'Jumlah barang tidak boleh negatif.']);

        $this->assertDatabaseCount('inventory_reports', 0);
    }

    public function test_guru_cannot_submit_a_report_for_a_class_they_do_not_teach(): void
    {
        [$user] = $this->homeroomTeacher();
        $otherItem = InventoryItem::factory()->create();

        $this->actingAs($user)->post(route('guru.inventaris.laporan.store', $otherItem->classroom), [
            'items' => [$otherItem->id => ['good_quantity' => 1, 'damaged_quantity' => 0]],
        ])->assertForbidden();

        $this->assertDatabaseCount('inventory_reports', 0);
    }

    public function test_report_detail_is_visible_to_admin_but_not_to_another_guru(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$otherGuru] = $this->homeroomTeacher();
        $report = InventoryReport::factory()->create();
        $report->items()->create(['category' => InventoryCategory::Cleaning, 'name' => 'Sapu Ijuk', 'good_quantity' => 3, 'damaged_quantity' => 1]);

        $this->actingAs($admin)->get(route('admin.inventaris.laporan.show', $report))
            ->assertOk()
            ->assertSee('Sapu Ijuk');
        $this->actingAs($otherGuru)->get(route('guru.inventaris.laporan.show', $report))->assertForbidden();
    }
}
