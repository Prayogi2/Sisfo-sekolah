@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1 fw-bold">Dashboard Utama</h1><p class="text-muted mb-0">Ringkasan data sekolah {{ now()->translatedFormat('F Y') }}.</p></div><a href="{{ route('admin.laporan') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan</a></div>

        <x-page-guide>Ringkasan cepat kondisi sekolah bulan ini. Gunakan menu di sisi kiri untuk mengelola data, atau klik <strong>Laporan</strong> untuk rekap lengkap.</x-page-guide>
        <div class="row g-3 mb-4">
            @foreach([['Total Siswa', $studentCount, 'primary'], ['Kehadiran Bulan Ini', $attendanceRate.'%', 'success'], ['Pembayaran Pending', $pendingPayments, 'warning'], ['Izin Pending', $pendingLeaves, 'danger']] as [$label, $value, $color])
                <div class="col-md-6 col-xl-3"><div class="card border-0 shadow-sm border-start border-{{ $color }} border-4 h-100"><div class="card-body"><small class="text-uppercase text-muted fw-bold">{{ $label }}</small><h3 class="mt-2 mb-0 text-{{ $color }}">{{ $value }}</h3></div></div></div>
            @endforeach
        </div>
        <div class="card border-0 shadow-sm"><div class="card-header bg-white"><h6 class="mb-0 fw-bold text-primary">Siswa Terbaru</h6></div><div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Nama</th><th>NIS</th><th>Kelas</th><th>Rekam Absensi</th><th>Prestasi</th></tr></thead><tbody>
            @forelse($students as $student)<tr><td>{{ $student->name }}</td><td>{{ $student->nis ?? '-' }}</td><td>{{ $student->classroom?->name ?? '-' }}</td><td>{{ $student->attendances_count }}</td><td>{{ $student->achievements_count }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data siswa.</td></tr>@endforelse
        </tbody></table></div></div>
    </div>
@endsection
