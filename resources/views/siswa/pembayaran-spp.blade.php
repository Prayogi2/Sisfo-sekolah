@extends('layouts.app')

@section('title', 'Pembayaran SPP')

@php
    $statusBadge = fn ($status) => match ($status) {
        \App\Enums\SppBillStatus::Paid => ['bg-success-soft text-success', '#e6f9ee', 'Lunas'],
        \App\Enums\SppBillStatus::Partial => ['bg-warning-soft text-warning', '#fff8e6', 'Cicil'],
        default => ['bg-danger-soft text-danger', '#ffeaea', 'Belum Dibayar'],
    };
@endphp

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Informasi & Pembayaran SPP</h1>
            <span class="badge bg-primary-soft text-primary p-2 shadow-sm">{{ $student->name }}</span>
        </div>

        <x-page-guide>Transfer sesuai tagihan, lalu unggah bukti transfer di form <strong>Upload Bukti Transfer</strong>. Status berubah menjadi Lunas setelah admin memverifikasi.</x-page-guide>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">
            <!-- Kolom Kiri: Riwayat Tagihan & Pembayaran -->
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary">Tagihan & Riwayat Pembayaran SPP</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bulan / Tahun</th>
                                        <th>Tagihan</th>
                                        <th>Sudah Dibayar</th>
                                        <th>Sisa</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($bills as $bill)
                                        @php
                                            [$badgeClass, $badgeColor, $badgeLabel] = $statusBadge($bill->status());
                                        @endphp
                                        <tr>
                                            <td>{{ $bill->period->translatedFormat('F Y') }}</td>
                                            <td>Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($bill->paidAmount(), 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</td>
                                            <td><span class="badge {{ $badgeClass }}" style="background-color: {{ $badgeColor }};">{{ $badgeLabel }}</span></td>
                                        </tr>
                                        @foreach ($bill->payments as $payment)
                                            <tr class="small text-muted">
                                                <td class="ps-4" colspan="2">
                                                    <i class="bi bi-arrow-return-right me-1"></i>
                                                    Setoran {{ $payment->created_at->translatedFormat('d M Y') }}
                                                </td>
                                                <td colspan="2">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                                <td>
                                                    @php
                                                        $paymentLabel = match ($payment->status) {
                                                            \App\Enums\SppPaymentStatus::Approved => ['bg-success-soft text-success', '#e6f9ee', 'Disetujui'],
                                                            \App\Enums\SppPaymentStatus::Rejected => ['bg-danger-soft text-danger', '#ffeaea', 'Ditolak'],
                                                            default => ['bg-warning-soft text-warning', '#fff8e6', 'Menunggu Verifikasi'],
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $paymentLabel[0] }}" style="background-color: {{ $paymentLabel[1] }};">{{ $paymentLabel[2] }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">Belum ada tagihan SPP.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Info Rek & Form Upload -->
            <div class="col-lg-4 col-md-12">
                <!-- Info Rekening -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                        <h6 class="m-0 fw-bold"><i class="bi bi-bank me-2"></i>Rekening Tujuan Sekolah</h6>
                    </div>
                    <div class="card-body">
                        @php
                            $bankName = \App\Models\Setting::get('spp_bank_name');
                            $bankAccount = \App\Models\Setting::get('spp_bank_account');
                            $bankHolder = \App\Models\Setting::get('spp_bank_holder');
                        @endphp
                        @if ($bankAccount)
                            <p class="mb-1">Bank : <strong>{{ $bankName }}</strong></p>
                            <p class="mb-1">No. Rekening : <strong>{{ $bankAccount }}</strong></p>
                            <p class="mb-2">Atas Nama : <strong>{{ $bankHolder }}</strong></p>
                            <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i> Pastikan nominal transfer sesuai dengan tagihan.</p>
                        @else
                            <p class="mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i> Rekening tujuan belum diatur. Silakan hubungi admin madrasah.</p>
                        @endif
                    </div>
                </div>

                <!-- Form Upload Bukti -->
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-cloud-upload me-2"></i>Upload Bukti Transfer</h6>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @php
                            $unpaidBills = $bills->filter(fn ($bill) => $bill->status() !== \App\Enums\SppBillStatus::Paid);
                        @endphp

                        @if ($unpaidBills->isEmpty())
                            <p class="text-muted mb-0">Tidak ada tagihan yang perlu dibayar saat ini.</p>
                        @else
                            <form action="{{ route('siswa.spp.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih Bulan Tagihan <span class="text-danger">*</span></label>
                                    <select name="spp_bill_id" class="form-select" required>
                                        <option value="">-- Pilih Tagihan --</option>
                                        @foreach ($unpaidBills as $bill)
                                            <option value="{{ $bill->id }}" @selected(old('spp_bill_id') == $bill->id)>
                                                {{ $bill->period->translatedFormat('F Y') }} - sisa Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nominal Dibayar <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" min="1000" step="1000" required>
                                    <small class="text-muted">Boleh dicicil, tidak harus lunas sekaligus.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Bukti Pembayaran (Foto Struk) <span class="text-danger">*</span></label>
                                    <input type="file" name="proof" class="form-control" accept="image/png, image/jpeg, application/pdf" required>
                                    <small class="text-muted">Format: PNG, JPG, PDF (Max 2MB)</small>
                                </div>
                                <div class="alert alert-info d-flex p-2" role="alert">
                                    <i class="bi bi-info-circle me-2 mt-1"></i>
                                    <small>Pembayaran akan diverifikasi oleh Admin.</small>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="bi bi-send me-1"></i> Kirim Bukti Bayar</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
