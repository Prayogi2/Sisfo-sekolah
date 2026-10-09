<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceParticipantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassAttendanceController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomImportController;
use App\Http\Controllers\DataExportController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradeWeightController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuizEssayGradingController;
use App\Http\Controllers\QuizQuestionController;
use App\Http\Controllers\ReportBookController;
use App\Http\Controllers\ReportHubController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SppBillController;
use App\Http\Controllers\SppPaymentController;
use App\Http\Controllers\StudentConductController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\StudentLeaveRequestController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentRecitationNoteController;
use App\Http\Controllers\StudentRecordController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherDashboardController;
use App\Http\Controllers\WhatsAppSessionController;
use Illuminate\Support\Facades\Artisan;
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
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');
Route::get('/presensi/scan', [ScanController::class, 'index'])->name('presensi.scan');
Route::post('/presensi/scan', [ScanController::class, 'store'])->name('presensi.scan.store');

// Authenticated Routes (Hanya bisa diakses jika SUDAH login)
Route::middleware('auth')->group(function () {

    // Proses Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/akun/password', [AuthController::class, 'editPassword'])->name('account.password.edit');
    Route::put('/akun/password', [AuthController::class, 'updatePassword'])->name('account.password.update');
    Route::put('/akun/nama', [AuthController::class, 'updateName'])->name('account.name.update');
    Route::post('/akun/kembali-ke-admin', [AccountController::class, 'stopImpersonating'])->name('account.impersonate.stop');

    /*
    |--------------------------------------------------------------------------
    | 1. MODUL ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/akun', [AccountController::class, 'index'])->name('akun');
        Route::post('/akun/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('akun.reset-password');
        Route::post('/akun/{user}/masuk', [AccountController::class, 'impersonate'])->name('akun.impersonate');
        Route::get('/buku-induk', [StudentRecordController::class, 'index'])->name('buku-induk');
        Route::get('/buku-induk/download', [StudentRecordController::class, 'download'])->name('buku-induk.download');
        Route::get('/buku-induk/{student}/download', [StudentRecordController::class, 'download'])->name('buku-induk.student.download');
        Route::get('/buku-induk/export/xlsx', [StudentRecordController::class, 'exportXlsx'])->name('buku-induk.export.xlsx');
        Route::get('/buku-induk/{student}/export/xlsx', [StudentRecordController::class, 'exportXlsx'])->name('buku-induk.student.export.xlsx');
        Route::get('/buku-induk/{student}/edit', [StudentRecordController::class, 'edit'])->name('buku-induk.edit');
        Route::put('/buku-induk/{student}', [StudentRecordController::class, 'update'])->name('buku-induk.update');
        Route::get('/buku-induk/{student}/nilai', [ReportBookController::class, 'edit'])->name('buku-induk.nilai.edit');
        Route::put('/buku-induk/{student}/nilai', [ReportBookController::class, 'update'])->name('buku-induk.nilai.update');
        Route::get('/buku-induk/{student}/nilai/export/xlsx', [ReportBookController::class, 'exportXlsx'])->name('buku-induk.nilai.export.xlsx');
        Route::post('/buku-induk/{student}/nilai/import/xlsx', [ReportBookController::class, 'importXlsx'])->name('buku-induk.nilai.import.xlsx');
        Route::get('/buku-induk/{student}/nilai/pdf', [ReportBookController::class, 'pdf'])->name('buku-induk.nilai.pdf');
        Route::get('/data-siswa', [StudentController::class, 'index'])->name('data-siswa');
        Route::get('/data-siswa/unduh/{format}', [DataExportController::class, 'students'])->name('data-siswa.unduh');
        Route::get('/data-siswa/tambah', [StudentRecordController::class, 'create'])->name('data-siswa.create');
        Route::get('/data-siswa/import', [StudentImportController::class, 'create'])->name('data-siswa.import');
        Route::get('/data-siswa/import/template', [StudentImportController::class, 'template'])->name('data-siswa.import.template');
        Route::post('/data-siswa/import', [StudentImportController::class, 'store'])->name('data-siswa.import.store');
        Route::get('/data-siswa/{student}/kartu-qr', [StudentController::class, 'qrCard'])->name('data-siswa.kartu-qr');
        Route::get('/peserta-presensi', [AttendanceParticipantController::class, 'index'])->name('peserta-presensi');
        Route::post('/peserta-presensi', [AttendanceParticipantController::class, 'store'])->name('peserta-presensi.store');
        Route::post('/data-siswa', [StudentController::class, 'store'])->name('data-siswa.store');
        Route::put('/data-siswa/{student}', [StudentController::class, 'update'])->name('data-siswa.update');
        Route::delete('/data-siswa/{student}', [StudentController::class, 'destroy'])->name('data-siswa.destroy');
        Route::get('/pembagian-kelas', [ClassroomController::class, 'index'])->name('pembagian-kelas');
        Route::post('/pembagian-kelas', [ClassroomController::class, 'store'])->name('pembagian-kelas.store');
        Route::post('/pembagian-kelas/cari-siswa', [ClassroomController::class, 'lookupStudents'])->name('pembagian-kelas.cari-siswa');
        Route::get('/pembagian-kelas/{classroom}/import/template', [ClassroomImportController::class, 'template'])->name('pembagian-kelas.import.template');
        Route::post('/pembagian-kelas/{classroom}/import', [ClassroomImportController::class, 'store'])->name('pembagian-kelas.import');
        Route::put('/pembagian-kelas/{classroom}', [ClassroomController::class, 'update'])->name('pembagian-kelas.update');
        Route::delete('/pembagian-kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('pembagian-kelas.destroy');
        Route::post('/pembagian-kelas/{classroom}/siswa', [ClassroomController::class, 'assignStudents'])->name('pembagian-kelas.assign-students');

        Route::get('/data-mapel', [SubjectController::class, 'index'])->name('data-mapel');
        Route::post('/data-mapel', [SubjectController::class, 'store'])->name('data-mapel.store');
        Route::put('/data-mapel/{subject}', [SubjectController::class, 'update'])->name('data-mapel.update');
        Route::delete('/data-mapel/{subject}', [SubjectController::class, 'destroy'])->name('data-mapel.destroy');
        Route::put('/data-mapel/{subject}/guru-pengampu', [SubjectController::class, 'updateTeachers'])->name('data-mapel.guru-pengampu');

        Route::get('/inventaris', [InventoryController::class, 'index'])->name('inventaris');
        Route::get('/inventaris/import/template', [InventoryController::class, 'importTemplate'])->name('inventaris.import.template');
        Route::post('/inventaris/{classroom}/import', [InventoryController::class, 'import'])->name('inventaris.import');
        Route::post('/inventaris/{classroom}/barang', [InventoryController::class, 'storeItem'])->name('inventaris.items.store');
        Route::post('/inventaris/{classroom}/barang-standar', [InventoryController::class, 'storeDefaultItems'])->name('inventaris.items.defaults');
        Route::delete('/inventaris/barang/{inventoryItem}', [InventoryController::class, 'destroyItem'])->name('inventaris.items.destroy');
        Route::get('/inventaris/laporan/{inventoryReport}', [InventoryController::class, 'showReport'])->name('inventaris.laporan.show');
        Route::get('/inventaris/{classroom}/unduh/{format}', [DataExportController::class, 'inventory'])->name('inventaris.unduh');

        Route::get('/data-guru', [TeacherController::class, 'index'])->name('data-guru');
        Route::get('/data-guru/unduh/{format}', [DataExportController::class, 'teachers'])->name('data-guru.unduh');
        Route::post('/data-guru', [TeacherController::class, 'store'])->name('data-guru.store');
        Route::put('/data-guru/{teacher}', [TeacherController::class, 'update'])->name('data-guru.update');
        Route::delete('/data-guru/{teacher}', [TeacherController::class, 'destroy'])->name('data-guru.destroy');
        Route::post('/data-guru/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('data-guru.reset-password');
        Route::post('/data-guru/{teacher}/akses-admin', [TeacherController::class, 'grantAdminAccess'])->name('data-guru.admin-access.grant');
        Route::delete('/data-guru/{teacher}/akses-admin', [TeacherController::class, 'revokeAdminAccess'])->name('data-guru.admin-access.revoke');
        Route::get('/verifikasi-spp', [SppPaymentController::class, 'index'])->name('verifikasi-spp');
        Route::post('/verifikasi-spp/{sppPayment}/approve', [SppPaymentController::class, 'approve'])->name('verifikasi-spp.approve');
        Route::post('/verifikasi-spp/{sppPayment}/reject', [SppPaymentController::class, 'reject'])->name('verifikasi-spp.reject');
        Route::get('/approval-izin', [LeaveRequestController::class, 'index'])->name('approval-izin');
        Route::get('/kritik-saran', [FeedbackController::class, 'index'])->name('kritik-saran');
        Route::get('/notifikasi', [AnnouncementController::class, 'index'])->name('notifikasi');
        Route::post('/notifikasi', [AnnouncementController::class, 'store'])->name('notifikasi.store');
        Route::get('/notifikasi/{announcement}', [AnnouncementController::class, 'show'])->name('notifikasi.show');
        Route::delete('/notifikasi/{announcement}', [AnnouncementController::class, 'destroy'])->name('notifikasi.destroy');
        Route::get('/bank-soal', [QuizController::class, 'questionBank'])->name('bank-soal');
        Route::delete('/bank-soal/{question}', [QuizController::class, 'destroyQuestion'])->name('bank-soal.destroy');
        Route::get('/prestasi-pelanggaran', [StudentConductController::class, 'index'])->name('prestasi-pelanggaran');
        Route::post('/prestasi-pelanggaran/prestasi', [StudentConductController::class, 'storeAchievement'])->name('prestasi-pelanggaran.prestasi.store');
        Route::delete('/prestasi-pelanggaran/prestasi/{achievement}', [StudentConductController::class, 'destroyAchievement'])->name('prestasi-pelanggaran.prestasi.destroy');
        Route::post('/prestasi-pelanggaran/pelanggaran', [StudentConductController::class, 'storeViolation'])->name('prestasi-pelanggaran.pelanggaran.store');
        Route::delete('/prestasi-pelanggaran/pelanggaran/{violation}', [StudentConductController::class, 'destroyViolation'])->name('prestasi-pelanggaran.pelanggaran.destroy');
        Route::get('/prestasi-pelanggaran/export/xlsx', [StudentConductController::class, 'exportXlsx'])->name('prestasi-pelanggaran.export.xlsx');
        Route::get('/prestasi-pelanggaran/export/pdf', [StudentConductController::class, 'exportPdf'])->name('prestasi-pelanggaran.export.pdf');

        // Laporan Admin
        Route::get('/laporan', ReportHubController::class)->name('laporan');
        Route::get('/laporan-absensi', [AttendanceController::class, 'report'])->name('laporan-absensi');
        Route::get('/laporan-absensi/export/csv', [AttendanceController::class, 'exportCsv'])->name('laporan-absensi.export.csv');
        Route::get('/laporan-absensi/export/pdf', [AttendanceController::class, 'exportPdf'])->name('laporan-absensi.export.pdf');
        Route::post('/laporan-absensi/toggle-late-blocking', [ScanController::class, 'toggleLateBlocking'])->name('laporan-absensi.toggle-late-blocking');
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas');
        Route::get('/whatsapp', [WhatsAppSessionController::class, 'index'])->name('whatsapp');
        Route::get('/whatsapp/status', [WhatsAppSessionController::class, 'status'])->name('whatsapp.status');
        Route::post('/whatsapp/aktifkan', [WhatsAppSessionController::class, 'startServer'])->name('whatsapp.start');
        Route::post('/whatsapp/logout', [WhatsAppSessionController::class, 'logout'])->name('whatsapp.logout');
        Route::get('/laporan-spp', [SppBillController::class, 'report'])->name('laporan-spp');
        Route::get('/laporan-spp/export/csv', [SppBillController::class, 'exportCsv'])->name('laporan-spp.export.csv');
        Route::get('/laporan-spp/export/pdf', [SppBillController::class, 'exportPdf'])->name('laporan-spp.export.pdf');
        Route::post('/laporan-spp/generate', [SppBillController::class, 'generate'])->name('laporan-spp.generate');
        Route::get('/laporan-nilai', [GradeController::class, 'report'])->name('laporan-nilai');
        Route::get('/laporan-nilai/export/csv', [GradeController::class, 'exportCsv'])->name('laporan-nilai.export.csv');
        Route::get('/laporan-nilai/export/pdf', [GradeController::class, 'exportPdf'])->name('laporan-nilai.export.pdf');
        Route::put('/data-mapel/{subject}/bobot-nilai', [GradeWeightController::class, 'update'])->name('data-mapel.bobot-nilai');
    });

    /*
    |--------------------------------------------------------------------------
    | 2. MODUL GURU
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin|guru')->prefix('guru')->name('guru.')->group(function () {
        Route::get('/dashboard', TeacherDashboardController::class)->name('dashboard');
        Route::get('/data-guru', [TeacherController::class, 'index'])->name('data-guru');
        Route::get('/approval-izin', [LeaveRequestController::class, 'index'])->name('approval-izin');
        Route::post('/approval-izin/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('approval-izin.approve');
        Route::post('/approval-izin/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('approval-izin.reject');

        // Manajemen Bank Soal & Kuis
        Route::get('/bank-soal', [QuizController::class, 'questionBank'])->name('bank-soal');
        Route::get('/bank-soal/tambah', [QuizQuestionController::class, 'create'])->name('bank-soal.tambah');
        Route::post('/bank-soal/simpan', [QuizQuestionController::class, 'store'])->name('bank-soal.simpan');
        Route::get('/bank-soal/import/template', [QuizQuestionController::class, 'importTemplate'])->name('bank-soal.import.template');
        Route::post('/bank-soal/import', [QuizQuestionController::class, 'import'])->name('bank-soal.import');
        Route::get('/bank-soal/{question}/edit', [QuizQuestionController::class, 'edit'])->name('bank-soal.edit');
        Route::put('/bank-soal/{question}', [QuizQuestionController::class, 'update'])->name('bank-soal.update');
        Route::delete('/bank-soal/{question}', [QuizController::class, 'destroyQuestion'])->name('bank-soal.destroy');
        Route::post('/kuis', [QuizController::class, 'storeQuiz'])->name('kuis.store');
        Route::post('/kuis/{quiz}/publikasi', [QuizController::class, 'togglePublish'])->name('kuis.toggle-publish');
        Route::post('/kuis/{quiz}/buka-tutup', [QuizController::class, 'toggleOpen'])->name('kuis.toggle-open');
        Route::get('/kuis/{quiz}/live', [QuizController::class, 'liveHost'])->name('kuis.live');
        Route::get('/kuis/{quiz}/live/state', [QuizController::class, 'liveHostState'])->name('kuis.live.state');
        Route::get('/kuis/{quiz}/cetak', [QuizController::class, 'printSheet'])->name('kuis.cetak');
        Route::post('/kuis/{quiz}/live/mulai', [QuizController::class, 'startLive'])->name('kuis.live.start');
        Route::post('/kuis/{quiz}/live/berikutnya', [QuizController::class, 'advanceLive'])->name('kuis.live.next');
        Route::post('/kuis/{quiz}/live/selesai', [QuizController::class, 'finishLive'])->name('kuis.live.finish');
        Route::get('/kuis/{quiz}/koreksi', [QuizEssayGradingController::class, 'index'])->name('kuis.koreksi');
        Route::put('/kuis/{quiz}/koreksi', [QuizEssayGradingController::class, 'update'])->name('kuis.koreksi.simpan');
        Route::get('/prestasi-pelanggaran', [StudentConductController::class, 'index'])->name('prestasi-pelanggaran');
        Route::post('/prestasi-pelanggaran/prestasi', [StudentConductController::class, 'storeAchievement'])->name('prestasi-pelanggaran.prestasi.store');
        Route::delete('/prestasi-pelanggaran/prestasi/{achievement}', [StudentConductController::class, 'destroyAchievement'])->name('prestasi-pelanggaran.prestasi.destroy');
        Route::post('/prestasi-pelanggaran/pelanggaran', [StudentConductController::class, 'storeViolation'])->name('prestasi-pelanggaran.pelanggaran.store');
        Route::delete('/prestasi-pelanggaran/pelanggaran/{violation}', [StudentConductController::class, 'destroyViolation'])->name('prestasi-pelanggaran.pelanggaran.destroy');

        Route::get('/catatan-hafalan-bacaan', [StudentRecitationNoteController::class, 'index'])->name('catatan-hafalan-bacaan');
        Route::post('/catatan-hafalan-bacaan/{classroom}', [StudentRecitationNoteController::class, 'store'])->name('catatan-hafalan-bacaan.store');

        // Absensi Kelas (wali kelas)
        Route::get('/absensi-kelas', [ClassAttendanceController::class, 'index'])->name('absensi-kelas');
        Route::put('/absensi-kelas/{classroom}', [ClassAttendanceController::class, 'update'])->name('absensi-kelas.simpan');
        Route::get('/absensi-kelas/{classroom}/unduh/{format}', [ClassAttendanceController::class, 'export'])->name('absensi-kelas.unduh');

        // Inventaris Kelas (wali kelas)
        Route::get('/inventaris', [InventoryController::class, 'index'])->name('inventaris');
        Route::get('/inventaris/import/template', [InventoryController::class, 'importTemplate'])->name('inventaris.import.template');
        Route::post('/inventaris/{classroom}/import', [InventoryController::class, 'import'])->name('inventaris.import');
        Route::post('/inventaris/{classroom}/barang', [InventoryController::class, 'storeItem'])->name('inventaris.items.store');
        Route::post('/inventaris/{classroom}/barang-standar', [InventoryController::class, 'storeDefaultItems'])->name('inventaris.items.defaults');
        Route::post('/inventaris/{classroom}/laporan', [InventoryController::class, 'storeReport'])->name('inventaris.laporan.store');
        Route::get('/inventaris/laporan/{inventoryReport}', [InventoryController::class, 'showReport'])->name('inventaris.laporan.show');
        Route::get('/inventaris/{classroom}/unduh/{format}', [DataExportController::class, 'inventory'])->name('inventaris.unduh');

        // Laporan & Input Nilai
        Route::get('/laporan-nilai', [GradeController::class, 'index'])->name('laporan-nilai');
        Route::post('/laporan-nilai/simpan', [GradeController::class, 'store'])->name('laporan-nilai.simpan');
    });

    /*
    |--------------------------------------------------------------------------
    | 3. MODUL SISWA
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:siswa')->prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/status-spp', [SppPaymentController::class, 'status'])->name('status-spp');
        Route::get('/pembayaran-spp', [SppPaymentController::class, 'create'])->name('spp');
        Route::post('/pembayaran-spp', [SppPaymentController::class, 'store'])->name('spp.store');
        Route::get('/kritik-saran', [FeedbackController::class, 'studentCreate'])->name('kritik-saran');
        Route::post('/kritik-saran', [FeedbackController::class, 'studentStore'])->name('kritik-saran.store');
        Route::get('/hasil-kuis', [QuizController::class, 'results'])->name('hasil-kuis');
        Route::get('/prestasi-pelanggaran', [StudentConductController::class, 'studentIndex'])->name('prestasi-pelanggaran');
        Route::get('/kuis-cbt', [QuizController::class, 'available'])->name('kuis');
        Route::get('/kuis-cbt/{quiz}', [QuizController::class, 'start'])->name('kuis.start');
        Route::get('/kuis-cbt/{quiz}/live', [QuizController::class, 'liveStart'])->name('kuis.live');
        Route::get('/kuis-cbt/{quiz}/live/state', [QuizController::class, 'liveState'])->name('kuis.live.state');
        Route::post('/kuis-cbt/attempt/{attempt}/live-jawaban', [QuizController::class, 'liveAnswer'])->name('kuis.live.answer');
        Route::post('/kuis-cbt/attempt/{attempt}/jawaban', [QuizController::class, 'answer'])->name('kuis.answer');
        Route::post('/kuis-cbt/attempt/{attempt}/kumpulkan', [QuizController::class, 'submit'])->name('kuis.submit');
        Route::get('/riwayat-absensi', [StudentPortalController::class, 'attendanceHistory'])->name('absensi');
        Route::get('/izin', [StudentLeaveRequestController::class, 'index'])->name('izin');
        Route::post('/izin', [StudentLeaveRequestController::class, 'store'])->name('izin.store');
        Route::get('/notifikasi', [AnnouncementController::class, 'studentIndex'])->name('notifikasi');
        Route::post('/notifikasi/tandai-semua', [AnnouncementController::class, 'markAllAsRead'])->name('notifikasi.read-all');
        Route::get('/notifikasi/{announcement}', [AnnouncementController::class, 'studentShow'])->name('notifikasi.show');
    });

    /*
    |--------------------------------------------------------------------------
    | 4. UMUM / SISTEM (pos presensi, dioperasikan admin/guru piket)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin|guru')->group(function () {
        Route::get('/scan-qr', [ScanController::class, 'index'])->name('scan-qr');
        Route::post('/scan-qr', [ScanController::class, 'store'])->name('scan-qr.store');
    });
});

/*
|--------------------------------------------------------------------------
| 5. MAINTENANCE & SYSTEM UTILITIES (Single Setup Route)
|--------------------------------------------------------------------------
*/
Route::get('/system-setup', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');

    Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = Artisan::output();

    return response()->json([
        'status' => 'success',
        'message' => 'Cache berhasil dibersihkan dan migration berhasil dijalankan!',
        'migration_output' => $migrateOutput,
    ]);
});
