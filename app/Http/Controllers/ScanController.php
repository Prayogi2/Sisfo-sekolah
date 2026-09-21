<?php

namespace App\Http\Controllers;

use App\Exceptions\AttendanceScanException;
use App\Models\Setting;
use App\Services\AttendanceScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(): View
    {
        $lateScanBlockingEnabled = (bool) Setting::get('late_scan_blocking_enabled', false);

        return view('sistem.scan-qr', compact('lateScanBlockingEnabled'));
    }

    public function store(Request $request, AttendanceScanner $scanner): JsonResponse
    {
        $request->validate(['qr_token' => ['required', 'string']]);

        try {
            $result = $scanner->scan($request->string('qr_token'));
        } catch (AttendanceScanException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'event' => $result->event,
            'status' => $result->attendance->status->value,
            'student' => [
                'name' => $result->student->name,
                'classroom' => $result->student->classroom->name,
            ],
            'time' => now()->format('H:i:s'),
        ]);
    }

    public function toggleLateBlocking(Request $request): RedirectResponse
    {
        $request->validate(['enabled' => ['required', 'boolean']]);

        Setting::set('late_scan_blocking_enabled', $request->boolean('enabled'));

        return back()->with('success', 'Pengaturan mode blokir scan telat berhasil diperbarui.');
    }
}
