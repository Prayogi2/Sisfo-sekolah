@extends('layouts.app')

@section('title', 'Laporan Absensi & Tata Tertib')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.laporan') }}" class="btn btn-light me-3 shadow-sm border" title="Kembali ke Pusat Laporan">
                    <i class="bi bi-arrow-left fs-5 text-primary"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Kehadiran Siswa</h1>
            </div>
            <div class="btn-group">
                <a href="{{ route('admin.laporan-absensi.export.csv', request()->query()) }}" class="btn btn-outline-success shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="{{ route('admin.laporan-absensi.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
            </div>
            </div>

        <x-page-guide>Pilih kelas/periode dengan filter di bawah, lalu unduh Excel atau PDF untuk arsip atau laporan ke wali kelas. Sakelar <strong>Blokir Scan Telat</strong> menghentikan siswa terlambat untuk bisa absen sama sekali (langsung tercatat alpa).</x-page-guide>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Control Bar Admin -->
        <div class="card shadow-sm mb-4 border-start border-danger border-4">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-3 mb-md-0">
                    <i class="bi bi-shield-fill-exclamation fs-3 text-danger me-3"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Pengaturan Sistem Presensi</h6>
                        <small class="text-muted">Jika diaktifkan, siswa yang datang terlambat tidak bisa scan & langsung dicatat sebagai <strong>Alpa</strong>.</small>
                    </div>
                </div>
                <form action="{{ route('admin.laporan-absensi.toggle-late-blocking') }}" method="POST" class="form-check form-switch form-switch-lg d-flex align-items-center" style="transform: scale(1.2);" onchange="this.submit()">
                    @csrf
                    <input type="hidden" name="enabled" value="0">
                    <input class="form-check-input me-2" type="checkbox" role="switch" name="enabled" value="1" id="sakelarTelat" @checked($lateScanBlockingEnabled)>
                    <label class="form-check-label fw-bold text-danger" for="sakelarTelat">Blokir Scan Telat</label>
                </form>
            </div>
        </div>

        <!-- Form Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.laporan-absensi') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Mulai</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tanggal Selesai</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected($classroomId == $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-success border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Hadir Tepat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['hadir'] }} Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-warning border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Telat</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['telat'] }} Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-info border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">Izin / Sakit</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['izin'] }} Siswa</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm border-start border-danger border-4 h-100 py-2">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Total Alpa</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">{{ $stats['alpa'] }} Siswa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Rekapitulasi Kehadiran</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Hari, Tanggal</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jam Scan Masuk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($attendances as $attendance)
                                <tr>
                                    <td>{{ $attendance->date->translatedFormat('l, d F Y') }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($attendance->student->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                            <span class="fw-semibold text-dark">{{ $attendance->student->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $attendance->student->classroom?->name ?? '-' }}</td>
                                    <td>
                                        @if ($attendance->check_in_at)
                                            {{ $attendance->check_in_at->format('H:i:s') }} WIB
                                        @else
                                            <span class="text-muted">Tidak Scan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badge = match ($attendance->status->value) {
                                                'hadir' => 'bg-success-soft text-success',
                                                'telat' => 'bg-warning-soft text-warning',
                                                'izin' => 'bg-primary-soft text-primary',
                                                default => 'bg-danger-soft text-danger',
                                            };
                                            $label = match ($attendance->status->value) {
                                                'hadir' => 'Hadir Tepat',
                                                'telat' => 'Telat',
                                                'izin' => 'Izin',
                                                default => 'Alpa',
                                            };
                                        @endphp
                                        <span class="badge {{ $badge }}" style="background-color: #f5f5f5;">{{ $label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data absensi pada rentang ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($attendances->hasPages())
                    <div class="card-footer bg-white">
                        {{ $attendances->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
