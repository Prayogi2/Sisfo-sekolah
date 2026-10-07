<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppServerProcess;
use App\Services\WhatsAppSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Halaman "Koneksi WhatsApp": admin memindai QR, melihat nomor yang sedang
 * tertaut, dan logout untuk berganti nomor — tanpa membuka terminal.
 */
class WhatsAppSessionController extends Controller
{
    public function __construct(
        private WhatsAppSession $session,
        private WhatsAppServerProcess $server,
    ) {}

    public function index(): View
    {
        return view('admin.whatsapp', [
            'status' => $this->session->status(),
            'canStartServer' => $this->server->isSupported(),
            'startServerBlockedReason' => $this->server->unsupportedReason(),
        ]);
    }

    /**
     * Nyalakan server WhatsApp (wa-server.mjs) langsung dari halaman admin.
     */
    public function startServer(): RedirectResponse
    {
        $error = $this->server->start();

        return $error === null
            ? redirect()->route('admin.whatsapp')->with('success', 'Server WhatsApp berhasil dinyalakan. QR akan muncul di halaman ini sebentar lagi.')
            : redirect()->route('admin.whatsapp')->with('error', $error);
    }

    /**
     * Dipanggil berulang oleh halaman untuk memantau status & QR terbaru.
     * QR adalah kredensial penautan perangkat, jadi jawabannya tidak boleh
     * disimpan di cache browser maupun proxy.
     */
    public function status(): JsonResponse
    {
        return response()
            ->json($this->session->status())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    public function logout(): RedirectResponse
    {
        $error = $this->session->logout();

        return $error === null
            ? redirect()->route('admin.whatsapp')->with('success', 'Akun WhatsApp berhasil dilepas. Pindai QR baru untuk menautkan nomor lain.')
            : redirect()->route('admin.whatsapp')->with('error', $error);
    }
}
