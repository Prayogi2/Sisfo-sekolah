@extends('layouts.app')

@section('title', 'Status Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Status Pembayaran SPP</h1>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-soft text-primary p-2 shadow-sm">{{ $student->name }}</span>
                <a href="{{ route('siswa.spp') }}" class="btn btn-primary btn-sm shadow-sm"><i class="bi bi-upload me-1"></i>Upload Bukti Transfer</a>
            </div>
        </div>

        <x-page-guide>Rekap tagihan & riwayat pembayaran anak Anda. Klik <strong>Upload Bukti Transfer</strong> untuk mengirim bukti bayar — status berubah menjadi Lunas setelah admin memverifikasi.</x-page-guide>

        @php
            $totalTagihan = $bills->sum('amount');
            $totalDibayar = $bills->sum(fn ($bill) => $bill->paidAmount());
        @endphp

        <div class="row mb-4">
            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Tagihan</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">Sudah Dibayar (Terverifikasi)</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-3">
                <div class="card shadow-sm h-100 border-start border-danger border-4">
                    <div class="card-body">
                        <div class="text-xs fw-bold text-danger text-uppercase mb-1">Sisa Tunggakan</div>
                        <div class="h5 mb-0 fw-bold text-gray-800">Rp {{ number_format($totalTagihan - $totalDibayar, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">Rincian per Bulan</h6>
                <a href="{{ route('siswa.spp') }}" class="btn btn-sm btn-primary"><i class="bi bi-cloud-upload me-1"></i> Upload Bukti Bayar</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Bulan / Tahun</th>
                                <th>Jatuh Tempo</th>
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
                                    <td>{{ $bill->period->translatedFormat('F Y') }}</td>
                                    <td>{{ $bill->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td>Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($bill->paidAmount(), 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</td>
                                    <td><span class="badge {{ $badgeClass }}" style="background-color: {{ $badgeColor }};">{{ $badgeLabel }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada tagihan SPP.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
