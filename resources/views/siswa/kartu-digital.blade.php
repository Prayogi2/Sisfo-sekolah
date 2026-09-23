@extends('layouts.app')

@section('title', 'Kartu Digital Siswa')

@push('styles')
    <style>
        .student-card {
            max-width: 620px;
            margin: 0 auto;
            overflow: hidden;
            border: 0;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 16px 36px rgba(24, 42, 76, .14);
        }

        .student-card-header {
            position: relative;
            overflow: hidden;
            padding: 1.35rem 1.5rem;
            color: #fff;
            background: #0d6efd;
        }

        .student-card-header::after {
            content: '';
            position: absolute;
            width: 190px;
            height: 190px;
            right: -70px;
            top: -105px;
            border: 24px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
        }

        .student-card-brand,
        .student-card-header small {
            position: relative;
            z-index: 1;
        }

        .student-card-brand {
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
        }

        .student-card-header small {
            display: block;
            margin-top: .2rem;
            opacity: .82;
        }

        .student-card-body {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr) 116px;
            gap: 1.25rem;
            align-items: center;
            padding: 1.5rem;
        }

        .student-photo {
            width: 112px;
            height: 136px;
            object-fit: cover;
            border: 4px solid #e7f1ff;
            border-radius: 12px;
            background: #e7f1ff;
        }

        .student-photo-placeholder {
            display: grid;
            place-items: center;
            color: #0d6efd;
            font-size: 2rem;
            font-weight: 700;
        }

        .student-card-name {
            margin-bottom: .7rem;
            color: #172033;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .student-card-data {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: .28rem .7rem;
            margin: 0;
            font-size: .88rem;
        }

        .student-card-data dt { color: #6c757d; font-weight: 600; }
        .student-card-data dd { min-width: 0; margin: 0; color: #172033; font-weight: 600; overflow-wrap: anywhere; }

        .student-qr {
            width: 116px;
            height: 116px;
            padding: .45rem;
            border: 1px solid #e4eaf2;
            border-radius: 10px;
            background: #fff;
        }

        .student-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .75rem 1.5rem;
            color: #6c757d;
            border-top: 1px solid #edf0f4;
            font-size: .76rem;
        }

        @media (max-width: 575.98px) {
            .student-card-body { grid-template-columns: 88px minmax(0, 1fr); gap: 1rem; padding: 1.1rem; }
            .student-photo { width: 88px; height: 108px; }
            .student-qr { width: 92px; height: 92px; grid-column: 1 / -1; justify-self: end; }
            .student-card-footer { padding: .7rem 1.1rem; }
        }

        @media print {
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sidebar, .topbar, .footer, .btn, .badge, .alert, .card, .bottom-nav, .offcanvas { display: none !important; }
            /* Layout aplikasi (margin sidebar, grid Bootstrap) tetap aktif saat
               print karena breakpoint-nya tidak dibatasi ke media screen, jadi
               harus direset manual supaya kartu tidak kegeser/kepotong. */
            .main-content, .main-content.active { margin-left: 0 !important; padding-bottom: 0 !important; }
            .container-fluid { padding-left: 0 !important; padding-right: 0 !important; }
            .row.justify-content-center { margin-left: 0 !important; margin-right: 0 !important; }
            .col-lg-5 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; padding: 0 !important; }
            .student-card, .student-card-header { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .student-card { display: block !important; max-width: 85.6mm; margin: 0 auto; border-radius: 0; box-shadow: none; }
            .student-card-body { grid-template-columns: 22mm minmax(0, 1fr) 22mm; gap: 3mm; padding: 4mm; }
            .student-card-header { padding: 3mm 4mm; }
            .student-card-brand { font-size: 7pt; }
            .student-card-header small, .student-card-footer { font-size: 5.5pt; }
            .student-photo { width: 22mm; height: 28mm; border-width: 1mm; }
            .student-card-name { font-size: 8.5pt; margin-bottom: 2mm; }
            .student-card-data { gap: 1mm 2mm; font-size: 6.5pt; }
            .student-qr { width: 22mm; height: 22mm; padding: 1mm; }
            .student-card-footer { padding: 2mm 4mm; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4 d-print-none">
            <h1 class="h3 mb-0 text-gray-800">Absensi & Kartu Digital</h1>
            <span class="badge bg-success p-2"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i> Sistem Presensi Online</span>
        </div>

        <x-page-guide>Tunjukkan kode QR ini ke petugas untuk scan absensi pagi & pulang di sekolah.</x-page-guide>

        <div class="row justify-content-center">
            <!-- Kolom Kartu Digital -->
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="student-card" id="studentCard">
                    <div class="student-card-header">
                        <div class="student-card-brand">KARTU PELAJAR</div>
                        <small>MIS Nurul Falaq · NURFA.ID</small>
                    </div>
                    <div class="student-card-body">
                        @if ($student->profile?->photo_path)
                            <img src="{{ Storage::url($student->profile->photo_path) }}" class="student-photo" alt="Foto {{ $student->name }}" crossorigin="anonymous">
                        @else
                            <div class="student-photo student-photo-placeholder" aria-label="Foto {{ $student->name }}">{{ strtoupper(substr($student->name, 0, 1)) }}</div>
                        @endif

                        <div>
                            <div class="student-card-name">{{ $student->name }}</div>
                            <dl class="student-card-data">
                                <dt>NIS</dt><dd>{{ $student->nis ?: '-' }}</dd>
                                <dt>NISN</dt><dd>{{ $student->nisn ?: '-' }}</dd>
                                <dt>Kelas</dt><dd>{{ $student->classroom?->name ?: '-' }}</dd>
                            </dl>
                        </div>

                        <img class="student-qr" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($student->qr_token) }}" alt="QR Code {{ $student->name }}" crossorigin="anonymous">
                    </div>
                    <div class="student-card-footer" data-html2canvas-ignore="true">
                        <span>Gunakan QR untuk presensi</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="downloadCardBtn"><i class="bi bi-download me-1"></i> Unduh Kartu</button>
                    </div>
                </div>
            </div>

            <!-- Kolom Status & Riwayat Absensi -->
            <div class="col-lg-7 col-md-12 d-print-none">
                <!-- Status Presensi Hari Ini -->
                <div class="card shadow mb-4 border-start-success">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Presensi Hari Ini</h5>
                        <p class="card-text text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <h6 class="text-muted">Check-In (Pagi)</h6>
                                @if ($today?->check_in_at)
                                    <h3 class="text-success"><i class="bi bi-box-arrow-in-right me-1"></i> {{ $today->check_in_at->format('H:i') }} WIB</h3>
                                    <span class="badge {{ $today->status->value === 'telat' ? 'bg-warning-soft text-warning' : 'bg-success-soft text-success' }}">{{ $today->status->value === 'telat' ? 'Terlambat' : 'Tepat Waktu' }}</span>
                                @else
                                    <h3 class="text-muted"><i class="bi bi-box-arrow-in-right me-1"></i> --:-- WIB</h3>
                                    <span class="badge bg-secondary text-white">Belum Scan</span>
                                @endif
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted">Check-Out (Pulang)</h6>
                                @if ($today?->check_out_at)
                                    <h3 class="text-success"><i class="bi bi-box-arrow-right me-1"></i> {{ $today->check_out_at->format('H:i') }} WIB</h3>
                                    <span class="badge bg-success-soft text-success">Selesai</span>
                                @else
                                    <h3 class="text-muted"><i class="bi bi-box-arrow-right me-1"></i> --:-- WIB</h3>
                                    <span class="badge bg-secondary text-white">Menunggu</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Absensi Mingguan -->
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Riwayat Absensi 7 Hari Terakhir</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history as $row)
                                        <tr>
                                            <td>{{ $row['date']->translatedFormat('d F Y') }}</td>
                                            <td>{{ $row['date']->translatedFormat('l') }}</td>
                                            @if (! $row['is_school_day'])
                                                <td colspan="3" class="text-center text-muted">Libur Sekolah</td>
                                            @elseif ($row['attendance'])
                                                <td>{{ $row['attendance']->check_in_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                                <td>{{ $row['attendance']->check_out_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                                <td>
                                                    @php
                                                        $badge = match ($row['attendance']->status->value) {
                                                            'hadir' => 'bg-success',
                                                            'telat' => 'bg-warning text-dark',
                                                            'izin' => 'bg-info',
                                                            default => 'bg-danger',
                                                        };
                                                        $label = match ($row['attendance']->status->value) {
                                                            'hadir' => 'Hadir',
                                                            'telat' => 'Telat',
                                                            'izin' => 'Izin',
                                                            default => 'Alpa',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                                </td>
                                            @else
                                                <td colspan="2" class="text-muted">--:--</td>
                                                <td><span class="badge bg-danger">Alpa</span></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        document.getElementById('downloadCardBtn').addEventListener('click', async function () {
            const button = this;
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan...';

            try {
                const canvas = await html2canvas(document.getElementById('studentCard'), {
                    scale: 3,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                });
                const link = document.createElement('a');
                link.download = 'kartu-digital-{{ \Illuminate\Support\Str::slug($student->name) ?: 'siswa' }}.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            } catch (error) {
                alert('Gagal membuat gambar kartu. Silakan coba lagi.');
            } finally {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        });
    </script>
@endpush
