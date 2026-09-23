<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Klien untuk server WhatsApp lokal (Baileys) yang dijalankan terpisah
 * (lihat WA_SERVER_URL). Server itu yang memegang sesi WhatsApp; kelas ini
 * cuma memanggil endpoint POST /send-message miliknya.
 */
class WhatsAppGateway
{
    /**
     * Kirim satu pesan WhatsApp. Selalu mengembalikan status sukses/gagal,
     * tidak pernah melempar exception, supaya pemanggil (job antrian) tidak
     * ikut gagal hanya karena server WA sedang tidak terhubung.
     */
    public function send(string $phone, string $message): bool
    {
        $number = $this->normalizePhone($phone);

        if ($number === null) {
            return false;
        }

        try {
            $response = Http::baseUrl(config('services.whatsapp.url'))
                ->connectTimeout(3)
                ->timeout(10)
                ->post('/send-message', ['to' => $number, 'text' => $message]);

            if ($response->successful() && $response->json('ok') === true) {
                return true;
            }

            Log::warning('Pengiriman WhatsApp ditolak server.', [
                'to' => $number,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('Tidak bisa menghubungi server WhatsApp.', [
                'to' => $number,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Ubah nomor telepon lokal (mis. "0812-3456-7890" atau "+62 812...")
     * jadi format yang dipahami server WA: kode negara 62 tanpa simbol.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
