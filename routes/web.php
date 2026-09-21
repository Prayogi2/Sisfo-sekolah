<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// 1. UMUM & SISTEM (Public Routes)
// ==========================================
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', fn() => view('auth.login'))->name('login');
Route::get('/scan-qr', fn() => view('sistem.scan-qr'))->name('presensi.scan');

// ==========================================
// 2. MODUL ADMIN
// ==========================================
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
    Route::get('/buku-induk', fn() => view('admin.buku-induk'))->name('buku-induk');
    Route::get('/data-guru', fn() => view('admin.data-guru'))->name('data-guru');
    Route::get('/data-kelas', fn() => view('admin.data-kelas'))->name('data-kelas');
    Route::get('/verifikasi-spp', fn() => view('admin.verifikasi-spp'))->name('verifikasi-spp');
    Route::get('/approval-izin', fn() => view('admin.approval-izin'))->name('approval-izin');
    
    // Menu Laporan
    Route::get('/laporan', fn() => view('admin.laporan'))->name('laporan');
    Route::get('/laporan-absensi', fn() => view('admin.laporan-absensi'))->name('laporan-absensi');
    Route::get('/laporan-nilai', fn() => view('admin.laporan-nilai'))->name('laporan-nilai');
    Route::get('/laporan-spp', fn() => view('admin.laporan-spp'))->name('laporan-spp');
    
    // Prestasi & Pelanggaran
    Route::get('/prestasi-pelanggaran', fn() => view('admin.prestasi-pelanggaran'))->name('prestasi-pelanggaran');
    
    // Manajemen Akun
    Route::get('/manajemen-akun', fn() => view('admin.manajemen-akun'))->name('manajemen-akun');
});

// ==========================================
// 3. MODUL GURU
// ==========================================
Route::prefix('guru')->name('guru.')->group(function () {
    // Rute dashboard tetap ada agar tombol login berfungsi, tapi disembunyikan dari menu
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
    Route::get('/data-guru', fn() => view('admin.data-guru'))->name('data-guru'); // Menu Baru
    Route::get('/bank-soal', fn() => view('admin.bank-soal'))->name('bank-soal');
    Route::get('/input-nilai', fn() => view('admin.laporan-nilai'))->name('input-nilai');
    Route::get('/approval-izin', fn() => view('admin.approval-izin'))->name('approval-izin');
});

// ==========================================
// 4. MODUL SISWA
// ==========================================
Route::prefix('siswa')->name('siswa.')->group(function () {
    Route::get('/jadwal', fn() => view('siswa.dashboard'))->name('jadwal');
    Route::get('/nilai-kuis', fn() => view('siswa.kuis-ranking'))->name('nilai-kuis');
    Route::get('/presensi', fn() => view('siswa.kartu-digital'))->name('presensi');
    Route::get('/pembayaran-spp', fn() => view('siswa.pembayaran-spp'))->name('spp'); // Menu Baru
});

// ==========================================
// 5. PORTAL WALI MURID
// ==========================================
Route::prefix('wali-murid')->name('wali.')->group(function () {
    Route::get('/dashboard', fn() => view('wali-murid.dashboard'))->name('dashboard');
    // Rute data-anak dan kritik-saran dihapus
    Route::get('/absensi-nilai', fn() => view('wali-murid.absensi-izin'))->name('absensi-nilai');
    Route::get('/hasil-kuis', fn() => view('wali-murid.hasil-kuis'))->name('hasil-kuis'); // Menu Baru
    Route::get('/prestasi-pelanggaran', fn() => view('admin.prestasi-pelanggaran'))->name('prestasi-pelanggaran'); // Menu Baru
    Route::get('/tagihan-spp', fn() => view('siswa.pembayaran-spp'))->name('tagihan-spp');
    Route::get('/ajukan-izin', fn() => view('wali-murid.absensi-izin'))->name('ajukan-izin');
});