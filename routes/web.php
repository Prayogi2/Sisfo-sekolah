<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChildSelectionController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradeWeightController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SppBillController;
use App\Http\Controllers\SppPaymentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentRecordController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\QuizController;
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
        Route::get('/buku-induk', [StudentRecordController::class, 'index'])->name('buku-induk');
        Route::get('/data-siswa', [StudentController::class, 'index'])->name('data-siswa');
        Route::post('/data-siswa', [StudentController::class, 'store'])->name('data-siswa.store');
        Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('data-siswa.update');
        Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('data-siswa.destroy');
        Route::get('/pembagian-kelas', [ClassroomController::class, 'index'])->name('pembagian-kelas');
        Route::post('/pembagian-kelas', [ClassroomController::class, 'store'])->name('pembagian-kelas.store');
        Route::put('/pembagian-kelas/{classroom}', [ClassroomController::class, 'update'])->name('pembagian-kelas.update');
        Route::delete('/pembagian-kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('pembagian-kelas.destroy');
        Route::post('/pembagian-kelas/{classroom}/siswa', [ClassroomController::class, 'assignStudents'])->name('pembagian-kelas.assign-students');

        Route::get('/data-mapel', [SubjectController::class, 'index'])->name('data-mapel');
        Route::post('/data-mapel', [SubjectController::class, 'store'])->name('data-mapel.store');
        Route::put('/data-mapel/{subject}', [SubjectController::class, 'update'])->name('data-mapel.update');
        Route::delete('/data-mapel/{subject}', [SubjectController::class, 'destroy'])->name('data-mapel.destroy');

        Route::get('/data-guru', [TeacherController::class, 'index'])->name('data-guru');
        Route::post('/data-guru', [TeacherController::class, 'store'])->name('data-guru.store');
        Route::put('/data-guru/{teacher}', [TeacherController::class, 'update'])->name('data-guru.update');
        Route::delete('/data-guru/{teacher}', [TeacherController::class, 'destroy'])->name('data-guru.destroy');
        Route::post('/data-guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('data-guru.reset-password');
        Route::get('/verifikasi-spp', [SppPaymentController::class, 'index'])->name('verifikasi-spp');
        Route::post('/verifikasi-spp/{sppPayment}/approve', [SppPaymentController::class, 'approve'])->name('verifikasi-spp.approve');
        Route::post('/verifikasi-spp/{sppPayment}/reject', [SppPaymentController::class, 'reject'])->name('verifikasi-spp.reject');
        Route::get('/approval-izin', [LeaveRequestController::class, 'index'])->name('approval-izin');
        Route::post('/approval-izin/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approval-izin.approve');
        Route::post('/approval-izin/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('approval-izin.reject');
        Route::get('/kritik-saran', [FeedbackController::class, 'index'])->name('kritik-saran');
        Route::get('/bank-soal', [QuizController::class, 'questionBank'])->name('bank-soal');
        Route::delete('/bank-soal/{question}', [QuizController::class, 'destroyQuestion'])->name('bank-soal.destroy');
        Route::get('/prestasi-pelanggaran', function () {
            return view('admin.prestasi-pelanggaran');
        })->name('prestasi-pelanggaran');

        // Laporan Admin
        Route::get('/laporan-absensi', [AttendanceController::class, 'report'])->name('laporan-absensi');
        Route::post('/laporan-absensi/toggle-late-blocking', [ScanController::class, 'toggleLateBlocking'])->name('laporan-absensi.toggle-late-blocking');
        Route::get('/laporan-spp', [SppBillController::class, 'report'])->name('laporan-spp');
        Route::post('/laporan-spp/generate', [SppBillController::class, 'generate'])->name('laporan-spp.generate');
        Route::get('/laporan-nilai', [GradeController::class, 'report'])->name('laporan-nilai');
        Route::put('/data-mapel/{subject}/bobot-nilai', [GradeWeightController::class, 'update'])->name('data-mapel.bobot-nilai');
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
        Route::get('/approval-izin', [LeaveRequestController::class, 'index'])->name('approval-izin');
        Route::post('/approval-izin/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approval-izin.approve');
        Route::post('/approval-izin/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('approval-izin.reject');

        // Manajemen Bank Soal & Kuis
        Route::get('/bank-soal', [QuizController::class, 'questionBank'])->name('bank-soal');
        Route::post('/bank-soal/simpan', [QuizController::class, 'storeQuestion'])->name('bank-soal.simpan');
        Route::put('/bank-soal/{question}', [QuizController::class, 'updateQuestion'])->name('bank-soal.update');
        Route::delete('/bank-soal/{question}', [QuizController::class, 'destroyQuestion'])->name('bank-soal.destroy');
        Route::post('/kuis', [QuizController::class, 'storeQuiz'])->name('kuis.store');

        // Laporan & Input Nilai
        Route::get('/laporan-nilai', [GradeController::class, 'index'])->name('laporan-nilai');
        Route::post('/laporan-nilai/simpan', [GradeController::class, 'store'])->name('laporan-nilai.simpan');
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
        Route::get('/kartu-digital', [StudentPortalController::class, 'digitalCard'])->name('kartu-digital');
        Route::get('/pembayaran-spp', [SppPaymentController::class, 'create'])->name('spp');
        Route::post('/pembayaran-spp', [SppPaymentController::class, 'store'])->name('spp.store');
        Route::get('/kuis-cbt', [QuizController::class, 'available'])->name('kuis');
        Route::get('/kuis-cbt/{quiz}', [QuizController::class, 'start'])->name('kuis.start');
        Route::post('/kuis-cbt/attempt/{attempt}/jawaban', [QuizController::class, 'answer'])->name('kuis.answer');
        Route::post('/kuis-cbt/attempt/{attempt}/kumpulkan', [QuizController::class, 'submit'])->name('kuis.submit');
        Route::get('/riwayat-absensi', [StudentPortalController::class, 'attendanceHistory'])->name('absensi');
    });

    /*
    |--------------------------------------------------------------------------
    | Pilih Anak (wali dengan >1 anak, dipakai sebelum akses halaman siswa.*)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:wali')->prefix('pilih-anak')->name('pilih-anak.')->group(function () {
        Route::get('/', [ChildSelectionController::class, 'index'])->name('index');
        Route::get('/{student}', [ChildSelectionController::class, 'select'])->name('select');
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
        Route::get('/absensi-izin', [StudentPortalController::class, 'guardianAttendance'])->name('izin');
        Route::post('/absensi-izin', [LeaveRequestController::class, 'store'])->name('izin.store');
        Route::get('/kritik-saran', [FeedbackController::class, 'create'])->name('kritik-saran');
        Route::post('/kritik-saran', [FeedbackController::class, 'store'])->name('kritik-saran.store');
        Route::get('/status-spp', [SppPaymentController::class, 'guardianStatus'])->name('spp');
        Route::get('/hasil-kuis', [QuizController::class, 'results'])->name('kuis');
    });

    /*
    |--------------------------------------------------------------------------
    | 5. UMUM / SISTEM (pos presensi, dioperasikan admin/guru piket)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin|guru')->group(function () {
        Route::get('/scan-qr', [ScanController::class, 'index'])->name('scan-qr');
        Route::post('/scan-qr', [ScanController::class, 'store'])->name('scan-qr.store');
    });
});
