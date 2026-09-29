<?php

namespace App\Jobs;

use App\Models\AnnouncementRecipient;
use App\Services\WhatsAppGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Mengirim satu notifikasi lewat WhatsApp ke orang tua satu siswa. Untuk
 * notifikasi terjadwal, job ini di-dispatch dengan delay sampai waktu kirim.
 *
 * Tidak diberi retry otomatis: mengirim ulang pesan WhatsApp bukan operasi
 * idempoten (bisa dobel terkirim), jadi kalau gagal cukup dicatat sebagai
 * gagal beserta alasannya, bukan dicoba lagi begitu saja.
 */
class SendAnnouncementWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $recipientId) {}

    public function handle(WhatsAppGateway $gateway): void
    {
        $recipient = AnnouncementRecipient::with(['announcement', 'student.guardians'])->find($this->recipientId);

        // Notifikasi sudah dihapus admin, atau pesan ini sudah pernah terkirim.
        if (! $recipient || $recipient->whatsapp_status === AnnouncementRecipient::WHATSAPP_SENT) {
            return;
        }

        $phone = $recipient->student->parentWhatsAppNumber();

        if (! $phone) {
            $recipient->update([
                'whatsapp_status' => AnnouncementRecipient::WHATSAPP_SKIPPED,
                'whatsapp_error' => 'Nomor WhatsApp orang tua belum terisi.',
            ]);

            return;
        }

        $text = "*{$recipient->announcement->title}*\n\n{$recipient->announcement->message}\n\n_Pesan otomatis dari NURFA.ID, mohon tidak dibalas._";

        $error = $gateway->deliver($phone, $text);

        $recipient->update([
            'whatsapp_status' => $error === null ? AnnouncementRecipient::WHATSAPP_SENT : AnnouncementRecipient::WHATSAPP_FAILED,
            'whatsapp_sent_at' => $error === null ? now() : null,
            'whatsapp_error' => $error,
        ]);
    }
}
