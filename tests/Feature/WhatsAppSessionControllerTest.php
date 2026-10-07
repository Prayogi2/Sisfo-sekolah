<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsAppServerProcess;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeStatus(array $overrides = []): void
    {
        Http::fake([
            '*/status' => Http::response([
                'ok' => true,
                'state' => 'connected',
                'connected' => true,
                'user' => '6281234567890:12@s.whatsapp.net',
                'name' => 'MI NURFA',
                'qr' => null,
                'logged_out_at' => null,
                'last_error' => null,
                ...$overrides,
            ]),
        ]);
    }

    public function test_the_page_shows_the_linked_number_when_whatsapp_is_connected(): void
    {
        $this->fakeStatus();

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertViewIs('admin.whatsapp')
            ->assertSee('Tersambung')
            ->assertSee('+6281234567890')
            ->assertSee('WhatsApp sudah tersambung');
    }

    public function test_the_page_shows_the_qr_as_an_image_when_whatsapp_is_waiting_to_be_linked(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2 2"></svg>';
        $this->fakeStatus(['state' => 'waiting_qr', 'connected' => false, 'user' => null, 'qr' => $svg]);

        $response = $this->actingAs($this->admin())->get(route('admin.whatsapp'));

        $response->assertOk()
            ->assertSee('Menunggu scan QR')
            ->assertSee('Perangkat Tertaut')
            // QR dipasang sebagai gambar data-URL, bukan SVG inline.
            ->assertSee('data:image/svg+xml;base64,'.base64_encode($svg))
            ->assertDontSee($svg, false);
    }

    public function test_the_page_states_plainly_that_whatsapp_has_been_logged_out(): void
    {
        $this->fakeStatus([
            'state' => 'logged_out',
            'connected' => false,
            'user' => null,
            'logged_out_at' => '2026-10-07T07:19:33.000Z',
        ]);

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Sudah logout')
            ->assertSee('tidak akan terkirim sampai ada nomor baru yang ditautkan', false);
    }

    /**
     * Server WA yang belum dijalankan adalah kondisi sehari-hari, jadi
     * halamannya harus tetap terbuka dan memberi tahu apa yang harus dilakukan.
     */
    public function test_the_page_still_opens_when_the_whatsapp_server_is_not_running(): void
    {
        Http::fake(['*/status' => fn () => throw new ConnectionException('Connection refused')]);

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Server WhatsApp mati')
            ->assertSee('wa-server');
    }

    public function test_the_page_still_opens_when_the_whatsapp_server_answers_with_an_error(): void
    {
        Http::fake(['*/status' => Http::response('boom', 500)]);

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Server WhatsApp mati');
    }

    public function test_the_status_endpoint_returns_the_session_state_without_caching_it(): void
    {
        $this->fakeStatus();

        $response = $this->actingAs($this->admin())->getJson(route('admin.whatsapp.status'));

        $response->assertOk()
            ->assertJsonPath('state', 'connected')
            ->assertJsonPath('connected', true)
            ->assertJsonPath('user', '+6281234567890');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_admin_logs_out_the_whatsapp_account(): void
    {
        Http::fake(['*/logout' => Http::response(['ok' => true, 'state' => 'logged_out'])]);

        $this->actingAs($this->admin())->post(route('admin.whatsapp.logout'))
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->url() === config('services.whatsapp.url').'/logout'
            && $request->method() === 'POST');
    }

    public function test_a_failed_logout_is_reported_instead_of_crashing(): void
    {
        Http::fake(['*/logout' => fn () => throw new ConnectionException('Connection refused')]);

        $this->actingAs($this->admin())->post(route('admin.whatsapp.logout'))
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('error');
    }

    public function test_a_logout_the_server_refuses_is_reported(): void
    {
        Http::fake(['*/logout' => Http::response(['ok' => false, 'message' => 'tidak bisa'], 500)]);

        $this->actingAs($this->admin())->post(route('admin.whatsapp.logout'))
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('error');
    }

    public function test_the_page_offers_the_activate_button_while_the_server_is_down(): void
    {
        Http::fake(['*/status' => fn () => throw new ConnectionException('Connection refused')]);

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Aktifkan Server')
            ->assertSee(route('admin.whatsapp.start'), false);
    }

    public function test_admin_starts_the_whatsapp_server_from_the_page(): void
    {
        $this->fakeStatus();
        $process = $this->mock(WhatsAppServerProcess::class);
        $process->shouldReceive('isSupported')->andReturn(true);
        $process->shouldReceive('unsupportedReason')->andReturn(null);
        $process->shouldReceive('start')->once()->andReturn(null);

        $this->actingAs($this->admin())->post(route('admin.whatsapp.start'))
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('success');
    }

    public function test_a_server_that_fails_to_start_is_reported_instead_of_crashing(): void
    {
        $this->fakeStatus();
        $process = $this->mock(WhatsAppServerProcess::class);
        $process->shouldReceive('isSupported')->andReturn(true);
        $process->shouldReceive('unsupportedReason')->andReturn(null);
        $process->shouldReceive('start')->once()->andReturn('Server WhatsApp sudah dijalankan tapi belum menjawab.');

        $this->actingAs($this->admin())->post(route('admin.whatsapp.start'))
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('error', 'Server WhatsApp sudah dijalankan tapi belum menjawab.');
    }

    /**
     * Di hosting yang melarang PHP menjalankan proses, tombolnya harus mati
     * dan alasannya dijelaskan — bukan ditekan lalu gagal diam-diam.
     */
    public function test_the_activate_button_is_disabled_with_a_reason_when_hosting_forbids_it(): void
    {
        Http::fake(['*/status' => fn () => throw new ConnectionException('Connection refused')]);
        $process = $this->mock(WhatsAppServerProcess::class);
        $process->shouldReceive('isSupported')->andReturn(false);
        $process->shouldReceive('unsupportedReason')->andReturn('Hosting ini tidak mengizinkan PHP menjalankan proses (proc_open dinonaktifkan), jadi server WhatsApp harus dinyalakan lewat SSH: npm run wa:start');

        $this->actingAs($this->admin())->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('proc_open dinonaktifkan')
            ->assertSee('npm run wa:start');
    }

    public function test_the_server_is_not_started_when_whatsapp_runs_on_another_machine(): void
    {
        config(['services.whatsapp.url' => 'http://wa.sekolah-lain.test:3001']);
        Http::fake(['*' => fn () => throw new ConnectionException('Connection refused')]);

        $reason = app(WhatsAppServerProcess::class)->unsupportedReason();

        $this->assertNotNull($reason);
        $this->assertStringContainsString('server lain', $reason);
    }

    public function test_starting_is_skipped_when_the_server_already_answers(): void
    {
        $this->fakeStatus();

        $this->assertNull(app(WhatsAppServerProcess::class)->start());
        // Tidak ada proses baru yang dijalankan: cukup status yang dibaca.
        Http::assertSentCount(1);
    }

    public function test_guru_and_siswa_cannot_reach_the_whatsapp_pages(): void
    {
        $this->fakeStatus();

        foreach (['guru', 'siswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('admin.whatsapp'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.whatsapp.status'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.whatsapp.logout'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.whatsapp.start'))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.whatsapp'))->assertRedirect(route('login'));
    }
}
