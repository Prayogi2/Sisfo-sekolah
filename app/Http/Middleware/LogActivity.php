<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat setiap aksi yang mengubah data ke Log Aktivitas.
 *
 * Hanya metode yang mengubah (POST/PUT/PATCH/DELETE) yang dicatat; membuka
 * halaman tidak. Pencatatan dilakukan setelah response terbentuk supaya
 * status hasilnya ikut tersimpan, dan kegagalan mencatat tidak pernah
 * boleh menggagalkan aksi penggunanya.
 */
class LogActivity
{
    private const LOGGED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private ActivityLogger $logger) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pelaku diambil sebelum request dijalankan, supaya aksi logout
        // tetap tercatat atas nama pengguna yang baru saja keluar.
        $actor = $request->user();

        $response = $next($request);

        if (! in_array($request->method(), self::LOGGED_METHODS, true)) {
            return $response;
        }

        try {
            $this->logger->record($request, $response, $actor);
        } catch (Throwable $e) {
            Log::warning('Gagal mencatat log aktivitas.', [
                'route' => $request->route()?->getName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
