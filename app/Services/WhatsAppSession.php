<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Status & kendali sesi login WhatsApp di server Baileys (lihat wa-server.mjs).
 * Dipakai halaman "Koneksi WhatsApp" supaya admin bisa memindai QR dan
 * berganti nomor dari browser, tanpa membuka terminal.
 *
 * Pengiriman pesannya sendiri tetap lewat WhatsAppGateway.
 */
class WhatsAppSession
{
    /**
     * Status sesi dalam bentuk siap tampil. Tidak pernah melempar exception:
     * server WA yang mati adalah kondisi normal yang harus terbaca di halaman,
     * bukan error 500.
     *
     * @return array{state: string, label: string, description: string, tone: string, reachable: bool, connected: bool, user: ?string, qr: ?string, logged_out_at: ?string}
     */
    public function status(): array
    {
        try {
            $response = Http::baseUrl(config('services.whatsapp.url'))
                ->connectTimeout(3)
                ->timeout(8)
                ->get('/status');

            if (! $response->successful()) {
                return $this->unreachable('Server WhatsApp menjawab dengan status '.$response->status().'.');
            }
        } catch (Throwable $e) {
            Log::debug('Status WhatsApp tidak bisa dibaca.', ['error' => $e->getMessage()]);

            return $this->unreachable('Server WhatsApp tidak berjalan atau tidak bisa dihubungi.');
        }

        $state = (string) ($response->json('state') ?? 'unknown');

        return [
            'state' => $state,
            ...$this->presentation($state, (string) ($response->json('last_error') ?? '')),
            'reachable' => true,
            'connected' => (bool) $response->json('connected'),
            'user' => $this->formatNumber($response->json('user')),
            'qr' => $response->json('qr'),
            'logged_out_at' => $response->json('logged_out_at'),
        ];
    }

    /**
     * Keluar dari akun WhatsApp yang sedang tertaut. Server langsung
     * menyiapkan QR baru, jadi nomor lain bisa ditautkan setelah ini.
     *
     * @return ?string Alasan gagal untuk ditampilkan ke admin, atau null bila berhasil.
     */
    public function logout(): ?string
    {
        try {
            $response = Http::baseUrl(config('services.whatsapp.url'))
                ->connectTimeout(3)
                ->timeout(15)
                ->post('/logout');

            if ($response->successful() && $response->json('ok') === true) {
                return null;
            }

            Log::warning('Logout WhatsApp ditolak server.', ['status' => $response->status(), 'body' => $response->json()]);

            return 'Server WhatsApp menolak permintaan logout.';
        } catch (Throwable $e) {
            Log::error('Tidak bisa menghubungi server WhatsApp untuk logout.', ['error' => $e->getMessage()]);

            return 'Server WhatsApp tidak bisa dihubungi, jadi logout belum diproses.';
        }
    }

    /**
     * @return array{state: string, label: string, description: string, tone: string, reachable: bool, connected: bool, user: ?string, qr: ?string, logged_out_at: ?string}
     */
    private function unreachable(string $reason): array
    {
        return [
            'state' => 'unreachable',
            'label' => 'Server WhatsApp mati',
            'description' => $reason.' Jalankan wa-server terlebih dahulu (npm run wa:start), lalu muat ulang halaman ini.',
            'tone' => 'danger',
            'reachable' => false,
            'connected' => false,
            'user' => null,
            'qr' => null,
            'logged_out_at' => null,
        ];
    }

    /**
     * @return array{label: string, description: string, tone: string}
     */
    private function presentation(string $state, string $lastError): array
    {
        return match ($state) {
            'connected' => [
                'label' => 'Tersambung',
                'description' => 'WhatsApp siap mengirim notifikasi ke orang tua.',
                'tone' => 'success',
            ],
            'waiting_qr' => [
                'label' => 'Menunggu scan QR',
                'description' => 'Buka WhatsApp di HP: Pengaturan › Perangkat Tertaut › Tautkan Perangkat, lalu pindai QR di bawah.',
                'tone' => 'warning',
            ],
            'logged_out' => [
                'label' => 'Sudah logout',
                'description' => 'Akun WhatsApp sudah dilepas dari sistem. Notifikasi WhatsApp tidak akan terkirim sampai ada nomor baru yang ditautkan.',
                'tone' => 'secondary',
            ],
            'reconnecting' => [
                'label' => 'Menyambung ulang',
                'description' => 'Koneksi terputus dan sedang disambungkan kembali.'.($lastError !== '' ? ' '.$lastError : ''),
                'tone' => 'warning',
            ],
            'starting' => [
                'label' => 'Sedang menyiapkan',
                'description' => 'Server WhatsApp baru dijalankan, tunggu beberapa saat.',
                'tone' => 'secondary',
            ],
            default => [
                'label' => 'Status tidak dikenali',
                'description' => 'Server WhatsApp menjawab dengan status yang tidak dikenali ("'.$state.'").',
                'tone' => 'secondary',
            ],
        };
    }

    /**
     * JID Baileys ("6281234567890:12@s.whatsapp.net") jadi nomor yang enak dibaca.
     */
    private function formatNumber(?string $jid): ?string
    {
        if (! $jid) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', explode(':', explode('@', $jid)[0])[0]);

        return $digits === '' ? null : '+'.$digits;
    }
}
