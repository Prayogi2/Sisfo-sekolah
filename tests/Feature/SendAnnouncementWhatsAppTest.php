<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementWhatsApp;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Student;
use App\Services\WhatsAppGateway;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendAnnouncementWhatsAppTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function recipientFor(?string $parentPhone): AnnouncementRecipient
    {
        $student = Student::factory()->create(['parent_phone' => $parentPhone]);
        $announcement = Announcement::factory()->create(['title' => 'Libur Sekolah', 'message' => 'Sekolah libur besok.']);

        return AnnouncementRecipient::create(['announcement_id' => $announcement->id, 'student_id' => $student->id]);
    }

    public function test_it_sends_a_whatsapp_message_and_marks_the_recipient_sent(): void
    {
        Http::fake([
            '*/send-message' => Http::response(['ok' => true]),
        ]);
        $recipient = $this->recipientFor('081234567890');

        (new SendAnnouncementWhatsApp($recipient->id))->handle(app(WhatsAppGateway::class));

        Http::assertSent(fn ($request) => $request->url() === config('services.whatsapp.url').'/send-message'
            && $request['to'] === '6281234567890'
            && str_contains($request['text'], 'Libur Sekolah'));
        $recipient->refresh();
        $this->assertSame(AnnouncementRecipient::WHATSAPP_SENT, $recipient->whatsapp_status);
        $this->assertNotNull($recipient->whatsapp_sent_at);
    }

    public function test_it_marks_the_recipient_failed_when_the_whatsapp_server_rejects_the_message(): void
    {
        Http::fake([
            '*/send-message' => Http::response(['ok' => false, 'message' => 'not connected'], 500),
        ]);
        $recipient = $this->recipientFor('081234567890');

        (new SendAnnouncementWhatsApp($recipient->id))->handle(app(WhatsAppGateway::class));

        $recipient->refresh();
        $this->assertSame(AnnouncementRecipient::WHATSAPP_FAILED, $recipient->whatsapp_status);
        $this->assertNull($recipient->whatsapp_sent_at);
    }

    public function test_it_skips_recipients_without_a_parent_phone_number(): void
    {
        Http::fake();
        $recipient = $this->recipientFor(null);

        (new SendAnnouncementWhatsApp($recipient->id))->handle(app(WhatsAppGateway::class));

        Http::assertNothingSent();
        $recipient->refresh();
        $this->assertSame(AnnouncementRecipient::WHATSAPP_SKIPPED, $recipient->whatsapp_status);
    }
}
