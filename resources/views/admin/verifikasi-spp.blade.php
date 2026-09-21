@extends('layouts.app')

@section('title', 'Verifikasi Pembayaran SPP')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Verifikasi Pembayaran SPP</h1>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- Tabel Verifikasi -->
        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white d-flex justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Daftar Bukti Transfer</h6>
                <span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">{{ $pendingCount }} Menunggu Verifikasi</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal Upload</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Periode</th>
                                <th>Nominal</th>
                                <th>Diunggah Oleh</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @php
                                    [$badgeClass, $badgeColor, $badgeLabel] = match ($payment->status) {
                                        \App\Enums\SppPaymentStatus::Approved => ['bg-success-soft text-success', '#e6f9ee', 'Disetujui'],
                                        \App\Enums\SppPaymentStatus::Rejected => ['bg-danger-soft text-danger', '#ffeaea', 'Ditolak'],
                                        default => ['bg-warning-soft text-warning', '#fff8e6', 'Pending'],
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $payment->created_at->translatedFormat('d M Y') }}</td>
                                    <td class="fw-semibold text-dark">{{ $payment->bill->student->name }}</td>
                                    <td>{{ $payment->bill->student->classroom?->name ?? '-' }}</td>
                                    <td>{{ $payment->bill->period->translatedFormat('F Y') }}</td>
                                    <td class="fw-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td>{{ $payment->guardian->name }}</td>
                                    <td><span class="badge {{ $badgeClass }}" style="background-color: {{ $badgeColor }};">{{ $badgeLabel }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($payment->proof_path) }}" target="_blank" class="btn btn-sm btn-info text-white" title="Lihat Bukti">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        @if ($payment->status === \App\Enums\SppPaymentStatus::Pending)
                                            <form action="{{ route('admin.verifikasi-spp.approve', $payment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-success" title="Setujui"><i class="bi bi-check-lg"></i></button>
                                            </form>
                                            <form action="{{ route('admin.verifikasi-spp.reject', $payment) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-danger" title="Tolak"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Belum ada bukti pembayaran yang masuk.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($payments->hasPages())
                <div class="card-footer bg-white">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
