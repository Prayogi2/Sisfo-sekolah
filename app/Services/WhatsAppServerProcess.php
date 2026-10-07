<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Menjalankan wa-server.mjs (server Baileys) dari halaman admin, supaya
 * admin tidak perlu membuka terminal hanya untuk menyalakannya.
 *
 * Prosesnya dilepas dari PHP (nohup + &) agar tetap hidup setelah request
 * selesai. Banyak shared hosting melarang PHP menjalankan proses; itu kondisi
 * yang wajar dan dilaporkan sebagai pesan, bukan error.
 */
class WhatsAppServerProcess
{
    /**
     * Berapa lama menunggu server menjawab setelah dinyalakan.
     */
    private const BOOT_TIMEOUT_SECONDS = 20;

    public function __construct(private WhatsAppSession $session) {}

    /**
     * Apakah hosting ini mengizinkan PHP menjalankan proses sama sekali.
     */
    public function isSupported(): bool
    {
        return $this->unsupportedReason() === null;
    }

    /**
     * Alasan tombol "Aktifkan Server" tidak bisa dipakai, atau null bila bisa.
     */
    public function unsupportedReason(): ?string
    {
        if (! function_exists('proc_open')) {
            return 'Hosting ini tidak mengizinkan PHP menjalankan proses (proc_open dinonaktifkan), jadi server WhatsApp harus dinyalakan lewat SSH: npm run wa:start';
        }

        if (! $this->isLocalServer()) {
            return 'WA_SERVER_URL menunjuk ke server lain ('.config('services.whatsapp.url').'), jadi server WhatsApp tidak bisa dinyalakan dari sini.';
        }

        if (! is_file($this->scriptPath())) {
            return 'Berkas wa-server.mjs tidak ditemukan di '.$this->scriptPath().'.';
        }

        return null;
    }

    /**
     * Nyalakan server WhatsApp lalu tunggu sampai ia menjawab.
     *
     * @return ?string Alasan gagal untuk ditampilkan ke admin, atau null bila berhasil.
     */
    public function start(): ?string
    {
        if ($reason = $this->unsupportedReason()) {
            return $reason;
        }

        if ($this->session->status()['reachable']) {
            return null;
        }

        if (! $this->nodeIsRunnable()) {
            return 'Perintah "'.$this->nodeBinary().'" tidak bisa dijalankan oleh PHP. Isi WA_NODE_BINARY di .env dengan path lengkap Node.js (mis. /usr/bin/node).';
        }

        $this->spawn();

        return $this->waitUntilReachable()
            ? null
            : 'Server WhatsApp sudah dijalankan tapi belum menjawab dalam '.self::BOOT_TIMEOUT_SECONDS.' detik. Cek '.$this->logPath().' untuk melihat penyebabnya.';
    }

    public function logPath(): string
    {
        return config('services.whatsapp.log') ?: storage_path('logs/wa-server.log');
    }

    /**
     * Jalankan node di latar belakang dan lepaskan dari proses PHP, supaya
     * server tetap hidup setelah request ini berakhir.
     */
    private function spawn(): void
    {
        $command = sprintf(
            'PORT=%s WA_SESSION_DIR=%s nohup %s %s >> %s 2>&1 &',
            escapeshellarg((string) $this->port()),
            escapeshellarg($this->sessionDir()),
            escapeshellarg($this->nodeBinary()),
            escapeshellarg($this->scriptPath()),
            escapeshellarg($this->logPath()),
        );

        Log::info('Menyalakan server WhatsApp dari halaman admin.', ['port' => $this->port()]);

        Process::fromShellCommandline($command, base_path())
            ->setTimeout(10)
            ->run();
    }

    private function waitUntilReachable(): bool
    {
        $deadline = microtime(true) + self::BOOT_TIMEOUT_SECONDS;

        do {
            usleep(500_000);

            if ($this->session->status()['reachable']) {
                return true;
            }
        } while (microtime(true) < $deadline);

        return false;
    }

    /**
     * Server hanya bisa dinyalakan dari sini kalau ia memang berjalan di
     * mesin yang sama dengan aplikasi.
     */
    private function isLocalServer(): bool
    {
        $host = parse_url((string) config('services.whatsapp.url'), PHP_URL_HOST);

        return in_array($host, ['127.0.0.1', 'localhost', '::1', '0.0.0.0'], true);
    }

    private function nodeIsRunnable(): bool
    {
        $process = Process::fromShellCommandline(
            'command -v '.escapeshellarg($this->nodeBinary()),
            base_path(),
        )->setTimeout(5);

        $process->run();

        return $process->isSuccessful();
    }

    private function nodeBinary(): string
    {
        return (string) (config('services.whatsapp.node_binary') ?: 'node');
    }

    private function scriptPath(): string
    {
        return config('services.whatsapp.script') ?: base_path('wa-server.mjs');
    }

    private function sessionDir(): string
    {
        return config('services.whatsapp.session_dir') ?: base_path('wa-session');
    }

    private function port(): int
    {
        return (int) (parse_url((string) config('services.whatsapp.url'), PHP_URL_PORT) ?: 3001);
    }
}
