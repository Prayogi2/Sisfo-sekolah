<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Authenticated Routes (Hanya bisa diakses jika SUDAH login)
Route::middleware('auth')->group(function () {

    // Proses Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | 1. MODUL ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
        Route::get('/buku-induk', function () {
            return view('admin.buku-induk');
        })->name('buku-induk');
        Route::get('/data-siswa', [StudentController::class, 'index'])->name('data-siswa');
        Route::post('/data-siswa', [StudentController::class, 'store'])->name('data-siswa.store');
        Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('data-siswa.update');
        Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('data-siswa.destroy');
        Route::get('/pembagian-kelas', [ClassroomController::class, 'index'])->name('pembagian-kelas');
        Route::post('/pembagian-kelas', [ClassroomController::class, 'store'])->name('pembagian-kelas.store');
        Route::put('/pembagian-kelas/{classroom}', [ClassroomController::class, 'update'])->name('pembagian-kelas.update');
        Route::delete('/pembagian-kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('pembagian-kelas.destroy');
        Route::post('/pembagian-kelas/{classroom}/siswa', [ClassroomController::class, 'assignStudents'])->name('pembagian-kelas.assign-students');

        Route::get('/data-guru', [TeacherController::class, 'index'])->name('data-guru');
        Route::post('/data-guru', [TeacherController::class, 'store'])->name('data-guru.store');
        Route::put('/data-guru/{teacher}', [TeacherController::class, 'update'])->name('data-guru.update');
        Route::delete('/data-guru/{teacher}', [TeacherController::class, 'destroy'])->name('data-guru.destroy');
        Route::post('/data-guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('data-guru.reset-password');
        Route::get('/verifikasi-spp', function () {
            return view('admin.verifikasi-spp');
        })->name('verifikasi-spp');
        Route::get('/approval-izin', function () {
            return view('admin.approval-izin');
        })->name('approval-izin');
        Route::get('/bank-soal', function () {
            return view('admin.bank-soal');
        })->name('bank-soal');
        Route::get('/prestasi-pelanggaran', function () {
            return view('admin.prestasi-pelanggaran');
        })->name('prestasi-pelanggaran');

        // Laporan Admin
        Route::get('/laporan-absensi', function () {
            return view('admin.laporan-absensi');
        })->name('laporan-absensi');
        Route::get('/laporan-spp', function () {
            return view('admin.laporan-spp');
        })->name('laporan-spp');
        Route::get('/laporan-nilai', function () {
            return view('admin.laporan-nilai');
        })->name('laporan-nilai');
    });

    /*
    |--------------------------------------------------------------------------
    | 2. MODUL GURU
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:guru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('/dashboard', function () {
            return view('guru.dashboard');
        })->name('dashboard');
        Route::get('/data-guru', [TeacherController::class, 'index'])->name('data-guru');
        Route::get('/approval-izin', function () {
            return view('guru.approval-izin');
        })->name('approval-izin');

        // Manajemen Bank Soal & Kuis
        Route::get('/bank-soal', function () {
            return view('guru.bank-soal');
        })->name('bank-soal');
        Route::post('/bank-soal/simpan', function () {
            return back()->with('success', 'Soal/Kuis baru berhasil disimpan!');
        })->name('bank-soal.simpan');

        // Laporan & Input Nilai
        Route::get('/laporan-nilai', function () {
            return view('guru.laporan-nilai');
        })->name('laporan-nilai');
        Route::post('/laporan-nilai/simpan', function () {
            return back()->with('success', 'Nilai siswa berhasil diperbarui!');
        })->name('laporan-nilai.simpan');
    });

    /*
    |--------------------------------------------------------------------------
    | 3. MODUL SISWA (diakses lewat akun Wali Murid, siswa tidak login sendiri)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:wali')->prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/dashboard', function () {
            return view('siswa.dashboard');
        })->name('dashboard');
        Route::get('/kartu-digital', function () {
            return view('siswa.kartu-digital');
        })->name('kartu-digital');
        Route::get('/pembayaran-spp', function () {
            return view('siswa.pembayaran-spp');
        })->name('spp');
        Route::get('/kuis-cbt', function () {
            return view('siswa.kuis-ranking');
        })->name('kuis');
        Route::get('/riwayat-absensi', function () {
            return view('siswa.absensi');
        })->name('absensi');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. PORTAL WALI MURID
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:wali')->prefix('wali-murid')->name('wali.')->group(function () {
        Route::get('/dashboard', function () {
            return view('wali-murid.dashboard');
        })->name('dashboard');
        Route::get('/absensi-izin', function () {
            return view('wali-murid.absensi-izin');
        })->name('izin');
        Route::get('/status-spp', function () {
            return view('wali-murid.status-spp');
        })->name('spp');
        Route::get('/hasil-kuis', function () {
            return view('wali-murid.hasil-kuis');
        })->name('kuis');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. UMUM / SISTEM (pos presensi, dioperasikan admin/guru piket)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin|guru')->get('/scan-qr', function () {
        return view('sistem.scan-qr');
    })->name('scan-qr');
});
