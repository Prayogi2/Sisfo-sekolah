@extends('layouts.app')

@section('title', 'Koneksi WhatsApp')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Koneksi WhatsApp</h1>
        <div class="d-none d-sm-flex gap-2">
            <form method="POST" action="{{ route('admin.whatsapp.start') }}" data-start-form>
                @csrf
                <button type="submit" class="btn btn-success shadow-sm" data-start-button
                    @disabled($status['reachable'] || ! $canStartServer)
                    @if (! $canStartServer) title="{{ $startServerBlockedReason }}" @endif>
                    <i class="bi bi-play-fill me-1"></i> Aktifkan Server
                </button>
            </form>
            <form method="POST" action="{{ route('admin.whatsapp.logout') }}" data-logout-form>
                @csrf
                <button type="submit" class="btn btn-outline-danger shadow-sm" @disabled(! $status['connected'])>
                    <i class="bi bi-box-arrow-right me-1"></i> Logout &amp; Ganti Nomor
                </button>
            </form>
        </div>
    </div>

    <x-page-guide>Nomor WhatsApp sekolah ditautkan di sini dengan memindai QR — tidak perlu lewat terminal. Satu nomor cukup ditautkan sekali; sesi tetap tersimpan meski halaman ditutup. Tekan <strong>Logout &amp; Ganti Nomor</strong> kalau mau pindah ke nomor lain.</x-page-guide>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Status Sambungan</h6></div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-{{ $status['tone'] }} fs-6" id="statusBadge">{{ $status['label'] }}</span>
                        <span class="spinner-border spinner-border-sm text-muted d-none" id="statusSpinner" role="status" aria-hidden="true"></span>
                    </div>

                    <p class="text-muted small" id="statusDescription">{{ $status['description'] }}</p>

                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal">Nomor tertaut</dt>
                        <dd class="col-7 fw-semibold" id="statusNumber">{{ $status['user'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Terakhir logout</dt>
                        <dd class="col-7" id="statusLoggedOut">{{ $status['logged_out_at'] ? \Illuminate\Support\Carbon::parse($status['logged_out_at'])->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i').' WIB' : '—' }}</dd>
                    </dl>

                    <hr>

                    <div class="d-sm-none d-grid gap-2">
                        <form method="POST" action="{{ route('admin.whatsapp.start') }}" data-start-form>
                            @csrf
                            <button type="submit" class="btn btn-success w-100" data-start-button @disabled($status['reachable'] || ! $canStartServer)>
                                <i class="bi bi-play-fill me-1"></i> Aktifkan Server
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.whatsapp.logout') }}" data-logout-form>
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100" @disabled(! $status['connected'])>
                                <i class="bi bi-box-arrow-right me-1"></i> Logout &amp; Ganti Nomor
                            </button>
                        </form>
                    </div>

                    @unless ($canStartServer)
                        <p class="small text-muted mb-0 mt-3"><i class="bi bi-exclamation-triangle me-1"></i>{{ $startServerBlockedReason }}</p>
                    @endunless

                    <p class="small text-muted mb-0 mt-3">
                        <i class="bi bi-shield-lock me-1"></i>QR ini setara kunci masuk akun WhatsApp sekolah. Jangan difoto atau dibagikan — hanya pindai langsung dari halaman ini.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Tautkan Nomor</h6></div>
                <div class="card-body text-center">
                    {{-- QR ditampilkan sebagai gambar data-URL, bukan SVG inline, supaya tidak ada skrip yang bisa ikut dijalankan dari isi QR. --}}
                    <div id="qrPanel" class="{{ $status['qr'] ? '' : 'd-none' }}">
                        <img id="qrImage" alt="QR untuk menautkan WhatsApp" class="img-fluid border rounded" style="max-width: 320px;"
                            src="{{ $status['qr'] ? 'data:image/svg+xml;base64,'.base64_encode($status['qr']) : '' }}">
                        <ol class="text-start small text-muted mt-4 mb-0 mx-auto" style="max-width: 420px;">
                            <li>Buka WhatsApp di HP nomor sekolah.</li>
                            <li>Masuk ke <strong>Pengaturan › Perangkat Tertaut</strong>.</li>
                            <li>Tekan <strong>Tautkan Perangkat</strong>, lalu pindai QR di atas.</li>
                            <li>QR berganti otomatis setiap beberapa detik — biarkan halaman ini terbuka.</li>
                        </ol>
                    </div>

                    <div id="connectedPanel" class="{{ $status['connected'] ? '' : 'd-none' }} py-5">
                        <i class="bi bi-whatsapp text-success" style="font-size: 4rem;"></i>
                        <p class="fs-5 fw-semibold mt-3 mb-1">WhatsApp sudah tersambung</p>
                        <p class="text-muted mb-0">Notifikasi ke orang tua bisa dikirim dari menu <strong>Kirim Notifikasi Siswa</strong>.</p>
                    </div>

                    <div id="waitingPanel" class="{{ $status['qr'] || $status['connected'] ? 'd-none' : '' }} py-5">
                        <i class="bi bi-hourglass-split text-muted" style="font-size: 4rem;"></i>
                        <p class="fs-5 fw-semibold mt-3 mb-1" id="waitingTitle">Belum ada QR</p>
                        <p class="text-muted mb-0" id="waitingText">{{ $status['reachable'] ? 'Tunggu beberapa saat, QR akan muncul sendiri di halaman ini.' : ($canStartServer ? 'Tekan Aktifkan Server di bawah, lalu QR-nya akan muncul sendiri di halaman ini.' : 'Jalankan server WhatsApp terlebih dahulu, lalu halaman ini akan memuat QR-nya sendiri.') }}</p>

                        @if ($canStartServer)
                            <form method="POST" action="{{ route('admin.whatsapp.start') }}" data-start-form class="mt-4" id="waitingStartForm">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg" data-start-button @disabled($status['reachable'])>
                                    <i class="bi bi-play-fill me-1"></i> Aktifkan Server
                                </button>
                                <p class="small text-muted mb-0 mt-2">Butuh beberapa detik — halaman akan memuat ulang sendiri setelah server siap.</p>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const statusUrl = @json(route('admin.whatsapp.status'));
    const canStartServer = @json($canStartServer);
    const el = id => document.getElementById(id);
    const tones = ['success', 'warning', 'danger', 'secondary'];
    let failures = 0;
    let startingServer = false;

    function setLoggedOutAt(value) {
        if (!value) {
            el('statusLoggedOut').textContent = '—';

            return;
        }

        const parsed = new Date(value);
        el('statusLoggedOut').textContent = Number.isNaN(parsed.getTime())
            ? '—'
            : parsed.toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
    }

    function render(status) {
        const badge = el('statusBadge');
        badge.textContent = status.label;
        tones.forEach(tone => badge.classList.remove('bg-' + tone));
        badge.classList.add('bg-' + status.tone);

        el('statusDescription').textContent = status.description;
        el('statusNumber').textContent = status.user || '—';
        setLoggedOutAt(status.logged_out_at);

        document.querySelectorAll('[data-logout-form] button[type="submit"]').forEach(button => {
            button.disabled = !status.connected;
        });

        // Tombol "Aktifkan Server" hanya berguna selagi servernya belum hidup.
        if (!startingServer) {
            document.querySelectorAll('[data-start-button]').forEach(button => {
                button.disabled = status.reachable || !canStartServer;
            });
        }

        const showQr = Boolean(status.qr) && !status.connected;
        el('qrPanel').classList.toggle('d-none', !showQr);
        el('connectedPanel').classList.toggle('d-none', !status.connected);
        el('waitingPanel').classList.toggle('d-none', showQr || status.connected);

        if (showQr) {
            el('qrImage').src = 'data:image/svg+xml;base64,' + btoa(status.qr);
        }

        if (!showQr && !status.connected) {
            el('waitingTitle').textContent = status.reachable ? 'Belum ada QR' : 'Server WhatsApp mati';
            el('waitingText').textContent = status.reachable
                ? 'Tunggu beberapa saat, QR akan muncul sendiri di halaman ini.'
                : 'Jalankan server WhatsApp terlebih dahulu, lalu halaman ini akan memuat QR-nya sendiri.';
        }
    }

    async function poll() {
        el('statusSpinner').classList.remove('d-none');

        try {
            const response = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            render(await response.json());
            failures = 0;
        } catch (error) {
            // Satu kegagalan jaringan tidak perlu mengubah tampilan; baru
            // setelah beberapa kali gagal statusnya ditandai tidak diketahui.
            failures++;
            if (failures >= 3) {
                el('statusDescription').textContent = 'Status tidak bisa dibaca dari server. Coba muat ulang halaman ini.';
            }
        } finally {
            el('statusSpinner').classList.add('d-none');
        }
    }

    // QR WhatsApp berganti tiap ~20 detik, jadi halaman ini menyegarkan
    // statusnya sendiri selama tab-nya terlihat.
    setInterval(() => {
        if (!document.hidden) {
            poll();
        }
    }, 3000);

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            poll();
        }
    });

    // Menyalakan server butuh beberapa detik; tombolnya dikunci selama itu
    // supaya tidak tertekan dua kali dan menjalankan dua proses node.
    document.querySelectorAll('[data-start-form]').forEach(form => {
        form.addEventListener('submit', () => {
            startingServer = true;
            form.querySelectorAll('[data-start-button]').forEach(button => {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Menyalakan server…';
            });
        });
    });

    document.querySelectorAll('[data-logout-form]').forEach(form => {
        form.addEventListener('submit', event => {
            if (!confirm('Lepas akun WhatsApp yang sekarang? Notifikasi WhatsApp tidak terkirim sampai ada nomor baru yang ditautkan.')) {
                event.preventDefault();
            }
        });
    });
})();
</script>
@endpush
