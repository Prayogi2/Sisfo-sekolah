<?php

use Illuminate\Support\Facades\Route;

// ==========================================
// 1. UMUM & SISTEM
// ==========================================
Route::get('/', function () {
    return redirect()->route('login');
});

// Login Multi-Role
Route::get('/login', fn() => view('auth.login'))->name('login');

// Interface Scan QR Absensi
Route::get('/presensi/scan-qr', fn() => view('sistem.scan-qr'))->name('presensi.scan');


// ==========================================
// 2. MODUL ADMIN & GURU
// ==========================================
Route::prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', fn() => view('admin.dashboard'))->name('dashboard');
    
    // Buku Induk (Baru)
    Route::get('/buku-induk', fn() => view('admin.buku-induk'))->name('buku-induk');
    
    // Data Siswa
    Route::get('/data-siswa', fn() => view('admin.data-siswa'))->name('data-siswa');
    
    // Data Kelas
    Route::get('/data-kelas', fn() => view('admin.data-kelas'))->name('data-kelas');
    
    // Absensi & Laporan
    Route::get('/laporan-absensi', fn() => view('admin.laporan-absensi'))->name('laporan-absensi');
    Route::get('/approval-izin', fn() => view('admin.approval-izin'))->name('approval-izin');
    
    // Prestasi & Pelanggaran (Baru)
    Route::get('/prestasi-pelanggaran', fn() => view('admin.prestasi-pelanggaran'))->name('prestasi-pelanggaran');
    
    // Keuangan (SPP)
    Route::get('/verifikasi-spp', fn() => view('admin.verifikasi-spp'))->name('verifikasi-spp');
    Route::get('/laporan-spp', fn() => view('admin.laporan-spp'))->name('laporan-spp');
    
    // Kuis Guru / Bank Soal
    Route::get('/bank-soal', fn() => view('admin.bank-soal'))->name('bank-soal');
});


// ==========================================
// 3. MODUL SISWA
// ==========================================
Route::prefix('siswa')->name('siswa.')->group(function () {
    
    Route::get('/kartu-digital', fn() => view('siswa.kartu-digital'))->name('kartu-digital');
    Route::get('/kuis-ranking', fn() => view('siswa.kuis-ranking'))->name('kuis-ranking');
});


// ==========================================
// 4. PORTAL WALI MURID
// ==========================================
Route::prefix('wali-murid')->name('wali-murid.')->group(function () {
    
    Route::get('/dashboard', fn() => view('wali-murid.dashboard'))->name('dashboard');
    Route::get('/absensi-izin', fn() => view('wali-murid.absensi-izin'))->name('absensi-izin');
});