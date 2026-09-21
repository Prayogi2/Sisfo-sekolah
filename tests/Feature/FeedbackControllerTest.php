<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Guardian;
use App\Models\User;
use App\Notifications\NewFeedbackSubmitted;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_wali_can_submit_feedback(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        Guardian::factory()->create(['user_id' => $wali->id]);

        $response = $this->actingAs($wali)->post(route('wali.kritik-saran.store'), [
            'message' => 'Mohon jam masuk sedikit lebih fleksibel.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feedbacks', [
            'message' => 'Mohon jam masuk sedikit lebih fleksibel.',
        ]);
    }

    public function test_submitting_feedback_notifies_all_admins(): void
    {
        Notification::fake();

        $wali = User::factory()->create(['role' => 'wali']);
        Guardian::factory()->create(['user_id' => $wali->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($wali)->post(route('wali.kritik-saran.store'), [
            'message' => 'Terima kasih atas pelayanannya.',
        ]);

        Notification::assertSentTo($admin, NewFeedbackSubmitted::class);
    }

    public function test_admin_can_view_the_feedback_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Feedback::factory()->create();

        $this->actingAs($admin)->get(route('admin.kritik-saran'))->assertOk();
    }

    public function test_guru_is_forbidden_from_viewing_the_feedback_list(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);

        $this->actingAs($guru)->get(route('admin.kritik-saran'))->assertForbidden();
    }

    public function test_viewing_the_feedback_list_marks_notifications_as_read(): void
    {
        $wali = User::factory()->create(['role' => 'wali']);
        Guardian::factory()->create(['user_id' => $wali->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($wali)->post(route('wali.kritik-saran.store'), [
            'message' => 'Terima kasih atas pelayanannya.',
        ]);

        $this->assertSame(1, $admin->fresh()->unreadNotifications()->count());

        $this->actingAs($admin)->get(route('admin.kritik-saran'));

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }
}
