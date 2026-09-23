<?php

namespace App\Jobs;

use App\Models\AnnouncementRecipient;
use App\Services\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Mengirim satu notifikasi lewat WhatsApp ke orang tua satu siswa.
 *
 * Tidak diberi retry otomatis: mengirim ulang pesan WhatsApp bukan operasi
 * idempoten (bisa dobel terkirim), jadi kalau gagal cukup dicatat sebagai
 * gagal, bukan dicoba lagi begitu saja.
 */
class SendAnnouncementWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $recipientId) {}

    public function handle(WhatsAppGateway $gateway): void
    {
        $recipient = AnnouncementRecipient::with(['announcement', 'student'])->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $phone = $recipient->student->parent_phone;

        if (! $phone) {
            $recipient->update(['whatsapp_status' => AnnouncementRecipient::WHATSAPP_SKIPPED]);

            return;
        }

        $text = "*{$recipient->announcement->title}*\n\n{$recipient->announcement->message}\n\n_Pesan otomatis dari NURFA.ID, mohon tidak dibalas._";

        $sent = $gateway->send($phone, $text);

        $recipient->update([
            'whatsapp_status' => $sent ? AnnouncementRecipient::WHATSAPP_SENT : AnnouncementRecipient::WHATSAPP_FAILED,
            'whatsapp_sent_at' => $sent ? now() : null,
        ]);
    }
}
