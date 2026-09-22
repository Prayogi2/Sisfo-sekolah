@extends('layouts.app')

@section('title', 'Laporan Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Pembayaran SPP</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.laporan-spp.export.csv', request()->query()) }}" class="btn btn-outline-success shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Excel
                </a>
                <a href="{{ route('admin.laporan-spp.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger shadow-sm btn-sm">
                    <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
                <form action="{{ route('admin.laporan-spp.generate') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary shadow-sm btn-sm">
                        <i class="bi bi-receipt-cutoff me-1"></i> Buat Tagihan Bulan Ini
                    </button>
                </form>
            </div>
        </div>

        <x-page-guide><strong>Buat Tagihan Bulan Ini</strong> membuat tagihan SPP untuk siswa yang belum ditagih bulan berjalan. Pembayaran wali murid diverifikasi lewat menu <strong>Verifikasi Pembayaran SPP</strong>.</x-page-guide>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Form Filter -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Periode</label>
                        <select name="period" class="form-select">
                            @forelse ($periods as $option)
                                <option value="{{ $option }}" @selected($period === $option)>
                                    {{ \Illuminate\Support\Carbon::parse($option)->translatedFormat('F Y') }}
                                </option>
                            @empty
                                <option value="{{ $period }}">{{ \Illuminate\Support\Carbon::parse($period)->translatedFormat('F Y') }}</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">Semua Kelas</option>
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected($classroomId === $classroom->id)>{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="lunas" @selected($status === 'lunas')>Lunas</option>
                            <option value="cicil" @selected($status === 'cicil')>Cicil</option>
                            <option value="pending" @selected($status === 'pending')>Belum Dibayar</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card Ringkasan Keuangan -->
        <div class="row mb-4">
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Tagihan</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($stats['total_tagihan'], 0, ',', '.') }}</div>
                        <small class="text-muted">{{ $bills->count() }} tagihan siswa</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Pemasukan Diterima</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($stats['diterima'], 0, ',', '.') }}</div>
                        <small class="text-muted">{{ $stats['lunas'] }} lunas, {{ $stats['cicil'] }} cicil</small>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Tunggakan</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($stats['tunggakan'], 0, ',', '.') }}</div>
                        <small class="text-muted">{{ $stats['pending'] }} siswa belum membayar sama sekali</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap SPP Siswa -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">
                    Rekap Status Pembayaran Siswa ({{ \Illuminate\Support\Carbon::parse($period)->translatedFormat('F Y') }})
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Tagihan</th>
                                <th>Dibayar</th>
                                <th>Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bills as $bill)
                                @php
                                    [$badgeClass, $badgeColor, $badgeLabel] = match ($bill->status()) {
                                        \App\Enums\SppBillStatus::Paid => ['bg-success-soft text-success', '#e6f9ee', 'Lunas'],
                                        \App\Enums\SppBillStatus::Partial => ['bg-warning-soft text-warning', '#fff8e6', 'Cicil'],
                                        default => ['bg-danger-soft text-danger', '#ffeaea', 'Belum Dibayar'],
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $bill->student->name }}</td>
                                    <td>{{ $bill->student->classroom?->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($bill->paidAmount(), 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $badgeClass }}" style="background-color: {{ $badgeColor }};">{{ $badgeLabel }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada tagihan untuk periode ini. Klik "Buat Tagihan Bulan Ini" untuk membuatnya.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
