@extends('layouts.app')

@section('title', 'Pusat Laporan & Rekapitulasi')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Pusat Laporan Sistem</h1>
        </div>

        <!-- Grid Menu Laporan -->
        <div class="row">
            <!-- Laporan Absensi -->
            <div class="col-xl-4 col-md-6 mb-4">
                <a href="{{ route('admin.laporan-absensi') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 border-start border-primary border-4 hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary-soft p-3 rounded-3 d-inline-block mb-3" style="background-color: #e7f1ff;">
                                <i class="bi bi-clipboard2-data-fill fs-2 text-primary"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Laporan Absensi</h5>
                            <p class="text-muted small mb-0">Rekap harian/bulanan, status hadir, & kontrol mode keterlambatan.</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Laporan Nilai -->
            <div class="col-xl-4 col-md-6 mb-4">
                <a href="{{ route('admin.laporan-nilai') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 border-start border-success border-4 hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-success-soft p-3 rounded-3 d-inline-block mb-3" style="background-color: #e6f9ee;">
                                <i class="bi bi-journal-text fs-2 text-success"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Laporan Nilai</h5>
                            <p class="text-muted small mb-0">Rekap nilai mata pelajaran, perkembangan akademik, & draf raport.</p>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Laporan SPP -->
            <div class="col-xl-4 col-md-6 mb-4">
                <a href="{{ route('admin.laporan-spp') }}" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 border-start border-warning border-4 hover-shadow">
                        <div class="card-body text-center p-4">
                            <div class="bg-warning-soft p-3 rounded-3 d-inline-block mb-3" style="background-color: #fff8e6;">
                                <i class="bi bi-cash-stack fs-2 text-warning"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Laporan SPP</h5>
                            <p class="text-muted small mb-0">Rekap tagihan, status lunas/cicil, tunggakan, & transaksi VA.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<style>
    .hover-shadow {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.075) !important;
    }
</style>
@endpush