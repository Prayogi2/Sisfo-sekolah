<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes - System SISFO Siswa (NURFA.ID)
|--------------------------------------------------------------------------
*/

// Redirect halaman utama ke halaman login
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes (Hanya bisa diakses jika BELUM login)
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes (Hanya bisa diakses jika SUDAH login)
Route::middleware('auth')->group(function () {
    
    // Proses Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | 1. MODUL ADMIN
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');
        Route::get('/buku-induk', function () { return view('admin.buku-induk'); })->name('buku-induk');
        Route::get('/pembagian-kelas', function () { return view('admin.data-kelas'); })->name('pembagian-kelas');
        Route::get('/data-guru', function () { return view('admin.data-guru'); })->name('data-guru');
        Route::get('/verifikasi-spp', function () { return view('admin.verifikasi-spp'); })->name('verifikasi-spp');
        Route::get('/approval-izin', function () { return view('admin.approval-izin'); })->name('approval-izin');
        Route::get('/bank-soal', function () { return view('admin.bank-soal'); })->name('bank-soal');
        Route::get('/prestasi-pelanggaran', function () { return view('admin.prestasi-pelanggaran'); })->name('prestasi-pelanggaran');

        // Laporan Admin
        Route::get('/laporan-absensi', function () { return view('admin.laporan-absensi'); })->name('laporan-absensi');
        Route::get('/laporan-spp', function () { return view('admin.laporan-spp'); })->name('laporan-spp');
        Route::get('/laporan-nilai', function () { return view('admin.laporan-nilai'); })->name('laporan-nilai');
    });

    /*
    |--------------------------------------------------------------------------
    | 2. MODUL GURU
    |--------------------------------------------------------------------------
    */
    Route::prefix('guru')->name('guru.')->group(function () {
        Route::get('/dashboard', function () { return view('guru.dashboard'); })->name('dashboard');
        Route::get('/data-guru', function () { return view('guru.data-guru'); })->name('data-guru');
        Route::get('/approval-izin', function () { return view('guru.approval-izin'); })->name('approval-izin');

        // Manajemen Bank Soal & Kuis
        Route::get('/bank-soal', function () { return view('guru.bank-soal'); })->name('bank-soal');
        Route::post('/bank-soal/simpan', function () {
            return back()->with('success', 'Soal/Kuis baru berhasil disimpan!');
        })->name('bank-soal.simpan');

        // Laporan & Input Nilai
        Route::get('/laporan-nilai', function () { return view('guru.laporan-nilai'); })->name('laporan-nilai');
        Route::post('/laporan-nilai/simpan', function () {
            return back()->with('success', 'Nilai siswa berhasil diperbarui!');
        })->name('laporan-nilai.simpan');
    });

    /*
    |--------------------------------------------------------------------------
    | 3. MODUL SISWA
    |--------------------------------------------------------------------------
    */
    Route::prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/dashboard', function () { return view('siswa.dashboard'); })->name('dashboard');
        Route::get('/kartu-digital', function () { return view('siswa.kartu-digital'); })->name('kartu-digital');
        Route::get('/pembayaran-spp', function () { return view('siswa.pembayaran-spp'); })->name('spp');
        Route::get('/kuis-cbt', function () { return view('siswa.kuis-ranking'); })->name('kuis');
        Route::get('/riwayat-absensi', function () { return view('siswa.absensi'); })->name('absensi');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. PORTAL WALI MURID
    |--------------------------------------------------------------------------
    */
    Route::prefix('wali-murid')->name('wali.')->group(function () {
        Route::get('/dashboard', function () { return view('wali-murid.dashboard'); })->name('dashboard');
        Route::get('/absensi-izin', function () { return view('wali-murid.absensi-izin'); })->name('izin');
        Route::get('/status-spp', function () { return view('wali-murid.status-spp'); })->name('spp');
        Route::get('/hasil-kuis', function () { return view('wali-murid.hasil-kuis'); })->name('kuis');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. UMUM / SISTEM
    |--------------------------------------------------------------------------
    */
    Route::get('/scan-qr', function () { return view('sistem.scan-qr'); })->name('scan-qr');
});