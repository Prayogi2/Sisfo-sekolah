<?php

namespace App\Services;

use App\Http\Controllers\AccountController;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyusun satu baris log aktivitas dari request yang sudah dijalankan.
 *
 * Deskripsinya dibuat dari nama route (mis. "admin.data-siswa.store" ->
 * "Menambah Data Siswa") supaya route baru ikut tercatat tanpa perlu
 * didaftarkan di sini. Yang perlu kalimat khusus ditaruh di DESCRIPTIONS.
 */
class ActivityLogger
{
    /**
     * Aksi yang tidak dicatat karena terjadi berulang-ulang sebagai mekanika
     * aplikasi, bukan sebagai keputusan pengguna — mencatatnya hanya akan
     * mengubur aktivitas yang benar-benar perlu dilihat admin.
     *
     * @var list<string>
     */
    public const IGNORED_ROUTES = [
        'siswa.kuis.answer',
        'siswa.kuis.live.answer',
        'guru.kuis.live.next',
        'admin.pembagian-kelas.cari-siswa',
        'boost.browser-logs',
    ];

    /**
     * Kalimat khusus untuk route yang namanya tidak cukup menjelaskan.
     *
     * @var array<string, string>
     */
    private const DESCRIPTIONS = [
        'login.attempt' => 'Login ke sistem',
        'logout' => 'Logout dari sistem',
        'presensi.scan.store' => 'Scan QR presensi di pos gerbang',
        'scan-qr.store' => 'Scan QR presensi',
        'account.password.update' => 'Mengganti password sendiri',
        'account.name.update' => 'Mengganti nama sendiri',
        'account.impersonate.stop' => 'Kembali ke akun admin',
        'admin.akun.impersonate' => 'Masuk sebagai pengguna lain',
        'admin.akun.reset-password' => 'Mereset password akun',
        'admin.data-guru.reset-password' => 'Mereset password akun guru',
        'admin.data-guru.admin-access.grant' => 'Memberi akses admin ke guru',
        'admin.data-guru.admin-access.revoke' => 'Mencabut akses admin dari guru',
        'admin.data-mapel.bobot-nilai' => 'Mengubah bobot nilai mata pelajaran',
        'admin.data-mapel.guru-pengampu' => 'Mengubah guru pengampu mata pelajaran',
        'admin.laporan-absensi.toggle-late-blocking' => 'Mengubah pengaturan blokir scan telat',
        'admin.laporan-spp.generate' => 'Membuat tagihan SPP',
        'admin.pembagian-kelas.assign-students' => 'Mengatur anggota kelas',
        'admin.inventaris.items.defaults' => 'Menambah barang standar inventaris',
        'guru.inventaris.items.defaults' => 'Menambah barang standar inventaris',
        'admin.whatsapp.start' => 'Menyalakan server WhatsApp',
        'admin.whatsapp.logout' => 'Logout akun WhatsApp sekolah',
        'admin.buku-induk.nilai.import.xlsx' => 'Mengimpor nilai buku induk dari Excel',
        'guru.absensi-kelas.simpan' => 'Menyimpan absensi kelas',
        'guru.kuis.koreksi.simpan' => 'Menyimpan koreksi jawaban essay',
        'guru.kuis.toggle-open' => 'Membuka/menutup kuis',
        'guru.kuis.toggle-publish' => 'Mempublikasikan/menarik kuis',
        'guru.kuis.live.start' => 'Memulai sesi kuis Kahoot',
        'guru.kuis.live.finish' => 'Menyelesaikan sesi kuis Kahoot',
        'siswa.kuis.submit' => 'Mengumpulkan jawaban kuis',
        'siswa.notifikasi.read-all' => 'Menandai semua notifikasi sudah dibaca',
        'siswa.izin.store' => 'Mengajukan izin/sakit',
        'siswa.spp.store' => 'Mengunggah bukti pembayaran SPP',

        'admin.data-siswa.import.store' => 'Mengimpor data siswa dari Excel',
        'admin.notifikasi.store' => 'Mengirim notifikasi ke siswa',
        'admin.peserta-presensi.store' => 'Menambah peserta presensi',
        'admin.buku-induk.nilai.update' => 'Mengubah nilai buku induk',

        'admin.pembagian-kelas.store' => 'Menambah kelas',
        'admin.pembagian-kelas.update' => 'Mengubah data kelas',
        'admin.pembagian-kelas.destroy' => 'Menghapus kelas',
        'admin.pembagian-kelas.import' => 'Mengimpor siswa ke kelas dari Excel',

        'admin.data-mapel.store' => 'Menambah mata pelajaran',
        'admin.data-mapel.update' => 'Mengubah mata pelajaran',
        'admin.data-mapel.destroy' => 'Menghapus mata pelajaran',

        'admin.verifikasi-spp.approve' => 'Menyetujui pembayaran SPP',
        'admin.verifikasi-spp.reject' => 'Menolak pembayaran SPP',
        'guru.approval-izin.approve' => 'Menyetujui izin siswa',
        'guru.approval-izin.reject' => 'Menolak izin siswa',

        'admin.inventaris.items.store' => 'Menambah barang inventaris',
        'admin.inventaris.items.destroy' => 'Menghapus barang inventaris',
        'guru.inventaris.items.store' => 'Menambah barang inventaris',
        'guru.inventaris.laporan.store' => 'Melaporkan kondisi barang inventaris',

        'admin.bank-soal.destroy' => 'Menghapus soal kuis',
        'guru.bank-soal.destroy' => 'Menghapus soal kuis',
        'guru.bank-soal.simpan' => 'Menyimpan soal kuis',
        'guru.bank-soal.update' => 'Mengubah soal kuis',
        'guru.bank-soal.import' => 'Mengimpor soal kuis dari Excel',
        'guru.kuis.store' => 'Membuat kuis',
        'guru.laporan-nilai.simpan' => 'Menyimpan nilai siswa',

        'admin.prestasi-pelanggaran.prestasi.store' => 'Menambah catatan prestasi',
        'admin.prestasi-pelanggaran.prestasi.destroy' => 'Menghapus catatan prestasi',
        'admin.prestasi-pelanggaran.pelanggaran.store' => 'Menambah catatan pelanggaran',
        'admin.prestasi-pelanggaran.pelanggaran.destroy' => 'Menghapus catatan pelanggaran',
        'guru.prestasi-pelanggaran.prestasi.store' => 'Menambah catatan prestasi',
        'guru.prestasi-pelanggaran.prestasi.destroy' => 'Menghapus catatan prestasi',
        'guru.prestasi-pelanggaran.pelanggaran.store' => 'Menambah catatan pelanggaran',
        'guru.prestasi-pelanggaran.pelanggaran.destroy' => 'Menghapus catatan pelanggaran',
    ];

    /**
     * Kata kerja per akhiran nama route, dipakai kalau route tidak ada di
     * DESCRIPTIONS.
     *
     * @var array<string, string>
     */
    private const VERBS = [
        'store' => 'Menambah',
        'update' => 'Mengubah',
        'destroy' => 'Menghapus',
        'simpan' => 'Menyimpan',
        'import' => 'Mengimpor',
        'approve' => 'Menyetujui',
        'reject' => 'Menolak',
        'generate' => 'Membuat',
        'start' => 'Memulai',
        'finish' => 'Menyelesaikan',
        'defaults' => 'Menambah data standar',
    ];

    /**
     * Catat satu aktivitas. Mengembalikan null bila route-nya memang tidak
     * perlu dicatat.
     */
    public function record(Request $request, Response $response, ?User $actor): ?ActivityLog
    {
        $routeName = $request->route()?->getName();

        if ($routeName !== null && in_array($routeName, self::IGNORED_ROUTES, true)) {
            return null;
        }

        // Pelaku biasanya sudah dikenal sebelum request jalan. Pengecualiannya
        // login: identitasnya baru terbentuk setelah request selesai.
        $actor ??= $request->user();

        $action = $routeName ?? mb_strtolower($request->method()).' '.$request->path();
        $subject = $this->subject($request);
        $impersonatorId = $request->hasSession()
            ? $request->session()->get(AccountController::IMPERSONATOR_KEY)
            : null;

        return ActivityLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? $this->guestName($request, $routeName),
            'user_role' => $actor?->role,
            'impersonator_id' => is_numeric($impersonatorId) ? (int) $impersonatorId : null,
            'action' => Str::limit($action, 255, ''),
            'description' => Str::limit($this->describe($request, $response, $routeName), 255, ''),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? Str::limit($this->subjectLabel($subject), 255, '') : null,
            'method' => $request->method(),
            'url' => Str::limit($request->fullUrl(), 2048, ''),
            'status_code' => $this->statusFor($request, $response),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
        ]);
    }

    /**
     * Aksi yang ditolak validasi dikembalikan sebagai redirect 302, sama
     * seperti aksi yang berhasil. Dicatat sebagai 422 supaya di log tidak
     * terbaca "berhasil" padahal datanya tidak tersimpan.
     */
    private function statusFor(Request $request, Response $response): int
    {
        $status = $response->getStatusCode();

        if ($response->isRedirect() && $request->hasSession()) {
            $errors = $request->session()->get('errors');

            if ($errors !== null && method_exists($errors, 'any') && $errors->any()) {
                return 422;
            }
        }

        return $status;
    }

    /**
     * Login adalah satu-satunya aksi penting yang pelakunya belum dikenal
     * saat request masuk, jadi identitas yang dicoba dicatat apa adanya.
     * Password tidak pernah ikut dicatat.
     */
    private function guestName(Request $request, ?string $routeName): ?string
    {
        if ($routeName !== 'login.attempt') {
            return null;
        }

        $identifier = trim((string) $request->input('identifier'));

        return $identifier === '' ? null : Str::limit($identifier, 255, '');
    }

    private function describe(Request $request, Response $response, ?string $routeName): string
    {
        if ($routeName === 'login.attempt') {
            return $response->isRedirect() && auth()->check()
                ? 'Login berhasil'
                : 'Login gagal';
        }

        if ($routeName === null) {
            return $request->method().' '.$request->path();
        }

        return self::labelFor($routeName);
    }

    /**
     * Kalimat aktivitas untuk satu nama route. Dipisah supaya bisa diperiksa
     * sendiri: setiap route yang mengubah data harus menghasilkan kalimat
     * yang layak dibaca admin, bukan nama teknis.
     */
    public static function labelFor(string $routeName): string
    {
        if (isset(self::DESCRIPTIONS[$routeName])) {
            return self::DESCRIPTIONS[$routeName];
        }

        $segments = explode('.', $routeName);
        $last = array_pop($segments) ?? '';
        $verb = self::VERBS[$last] ?? null;

        // "admin.data-siswa.store" -> modul "data-siswa", peran "admin".
        if (count($segments) > 1 && in_array($segments[0], ['admin', 'guru', 'siswa'], true)) {
            array_shift($segments);
        }

        $module = Str::headline(implode(' ', $segments === [] ? [$last] : $segments));

        return $verb === null
            ? Str::headline($last).($module !== '' ? ' — '.$module : '')
            : $verb.' '.$module;
    }

    /**
     * Objek yang dikenai aksi, diambil dari parameter route yang sudah
     * di-resolve jadi model (mis. {student} di admin.data-siswa.update).
     */
    private function subject(Request $request): ?Model
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return $parameter;
            }
        }

        return null;
    }

    private function subjectLabel(Model $subject): string
    {
        foreach (['name', 'title', 'nama', 'username', 'email'] as $attribute) {
            if (filled($subject->getAttribute($attribute))) {
                return (string) $subject->getAttribute($attribute);
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
