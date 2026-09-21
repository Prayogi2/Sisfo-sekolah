@extends('layouts.app')

@section('title', 'Approval Izin & Sakit')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Approval Pengajuan Izin</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Daftar Pengajuan</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th><th>Nama Anak</th><th>Alasan</th><th>Lampiran</th><th>Status</th><th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>20 Mei 2024</td>
                            <td>Ahmad Fauzi (VI)</td>
                            <td>Sakit Demam</td>
                            <td><button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalSurat"><i class="bi bi-file-earmark-medical"></i> Lihat Surat</button></td>
                            <td><span class="badge bg-warning-soft text-warning">Pending</span></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalSurat"><i class="bi bi-check-lg"></i></button>
                                <button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Surat Keterangan Dokter Realistis -->
<div class="modal fade" id="modalSurat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-medical me-2"></i>Verifikasi Surat Keterangan Dokter</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Template Surat -->
                <div class="bg-white p-4 shadow-sm border" style="font-family: 'Times New Roman', serif;">
                    <!-- Kop Surat -->
                    <div class="d-flex align-items-center border-bottom border-2 border-dark pb-3 mb-4">
                        <div class="logo-fallback logo-md bg-danger me-3">RS</div>
                        <div class="flex-grow-1 text-center">
                            <h4 class="fw-bold mb-0 text-uppercase">RSUD Nurul Falaq</h4>
                            <p class="small mb-0">Jl. Kesehatan No. 1, Cianjur | Telp: (0263) 123456</p>
                            <p class="small text-muted">Email: rsud.nurulfalaq@dummy.sch.id</p>
                        </div>
                    </div>
                    
                    <h5 class="text-center fw-bold text-decoration-underline mb-4 text-uppercase">Surat Keterangan Sakit</h5>
                    
                    <!-- Data Pasien -->
                    <div class="mb-4" style="line-height: 1.8;">
                        <p>Yang bertanda tangan di bawah ini, dokter RSUD Nurul Falaq, menerangkan bahwa:</p>
                        <table class="table table-borderless table-sm">
                            <tr><td width="150">Nama Pasien</td><td>: <strong>Ahmad Fauzi</strong> (Siswa MIS Nurul Falaq)</td></tr>
                            <tr><td>Kelas</td><td>: <strong>VI (Enam)</strong></td></tr>
                            <tr><td>Diagnosa Singkat</td><td>: <strong>Demam Tifoid (Tipes)</strong></td></tr>
                            <tr><td>Tanggal Periksa</td><td>: 19 Mei 2024</td></tr>
                        </table>
                        <p>Setelah dilakukan pemeriksaan, pasien dinyatakan perlu istirahat total selama <strong>2 Hari</strong> (20 Mei 2024 s/d 21 Mei 2024).</p>
                    </div>

                    <!-- TTD & Stempel -->
                    <div class="d-flex justify-content-between align-items-end mt-5">
                        <div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=VerifikasiSuratNURFA-ID-001" alt="QR Verifikasi" class="mb-1">
                            <p class="small text-center m-0">Scan untuk verifikasi</p>
                        </div>
                        <div class="text-center me-5">
                            <p class="m-0">Cianjur, 19 Mei 2024</p>
                            <p class="m-0">Dokter Pemeriksa,</p>
                            <br>
                            <h5 class="fw-bold text-primary" style="font-family: 'Brush Script MT', cursive; font-size: 2rem;">dr. Andi, Sp.PD</h5>
                            <p class="small m-0">STRA: 11/123/456</p>
                        </div>
                        <div style="width: 100px; height: 100px; border: 3px solid rgba(220,53,69,0.3); border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: rotate(-15deg); color: rgba(220,53,69,0.5); font-weight: bold; text-align: center; font-size: 0.8rem;">
                            RSUD NURUL FALAQ<br>CIANJUR
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i> Tolak (Alpa)</button>
                <button type="button" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Setujui Izin</button>
            </div>
        </div>
    </div>
</div>
@endsection