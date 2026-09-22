@extends('layouts.app')

@section('title', 'Kartu Digital Siswa')

@push('styles')
    <style>
        @media print {
            body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sidebar, .topbar, .footer, .btn, .badge, .card:not(.digital-card) { display: none !important; }
            .digital-card, .digital-card .card-header, .digital-card .card-footer { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .digital-card { display: block !important; max-width: 90mm; margin: 0 auto; }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Absensi & Kartu Digital</h1>
            <span class="badge bg-success p-2"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i> Sistem Presensi Online</span>
        </div>

        <div class="row justify-content-center">
            <!-- Kolom Kartu Digital -->
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="card digital-card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h5 class="mb-0">KARTU PELAJAR DIGITAL</h5>
                        <small>MIS Nurul Falaq</small>
                    </div>
                    <div class="card-body text-center">
                        <div class="d-flex flex-column align-items-center">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&size=150&background=4e73df&color=fff&bold=true"
                                 class="rounded-circle mb-3 shadow" width="120" height="120" alt="Foto Siswa">

                            <h4 class="mb-0">{{ $student->name }}</h4>
                            <p class="text-muted mb-2">NIS: {{ $student->nis }} / NISN: {{ $student->nisn }}</p>
                            <span class="badge bg-info mb-3">Kelas {{ $student->classroom?->name ?? '-' }}</span>

                            <hr class="w-100">

                            <p class="mb-2"><small class="text-muted">Tunjukkan QR Code ini ke alat scanner presensi:</small></p>

                            <div class="bg-white p-3 rounded shadow-sm mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($student->qr_token) }}" alt="QR Code" width="200" height="200">
                            </div>

                            <button type="button" class="btn btn-outline-primary w-100" onclick="window.print()"><i class="bi bi-download me-2"></i> Unduh Kartu (PDF)</button>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center" style="font-size: 0.8rem;">
                        NURFA.ID - Digital Platform MIS Nurul Falaq
                    </div>
                </div>
            </div>

            <!-- Kolom Status & Riwayat Absensi -->
            <div class="col-lg-7 col-md-12">
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
