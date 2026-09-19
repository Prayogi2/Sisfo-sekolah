@extends('layouts.app')

@section('title', 'Kartu Digital Siswa')

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
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h5 class="mb-0">KARTU PELAJAR DIGITAL</h5>
                        <small>SMAN 1 dummySejahtera</small>
                    </div>
                    <div class="card-body text-center">
                        <div class="d-flex flex-column align-items-center">
                            <!-- Foto Siswa (Dummy) -->
                            <img src="https://ui-avatars.com/api/?name=Budi+Santoso&size=150&background=4e73df&color=fff&bold=true" 
                                 class="rounded-circle mb-3 shadow" width="120" height="120" alt="Foto Siswa">
                            
                            <h4 class="mb-0">Budi Santoso</h4>
                            <p class="text-muted mb-2">NIS: 1002456 / NISN: 0098761234</p>
                            <span class="badge bg-info mb-3">Kelas X IPA 1</span>
                            
                            <hr class="w-100">
                            
                            <p class="mb-2"><small class="text-muted">Tunjukkan QR Code ini ke perangkat scanner presensi:</small></p>
                            
                            <!-- QR Code Dummy -->
                            <div class="bg-white p-3 rounded shadow-sm mb-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=SISFO-1002456-XIPA1" alt="QR Code" width="200" height="200">
                            </div>
                            
                            <button class="btn btn-outline-primary w-100"><i class="bi bi-download me-2"></i> Unduh Kartu (PDF)</button>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center" style="font-size: 0.8rem;">
                        Berlaku hingga 2025 | SISFO Sekolah
                    </div>
                </div>
            </div>

            <!-- Kolom Status & Riwayat Absensi -->
            <div class="col-lg-7 col-md-12">
                <!-- Status Presensi Hari Ini -->
                <div class="card shadow mb-4 border-start-success">
                    <div class="card-body">
                        <h5 class="card-title text-primary">Presensi Hari Ini</h5>
                        <p class="card-text text-muted">Senin, 15 Mei 2024</p>
                        <div class="row text-center">
                            <div class="col-6 border-end">
                                <h6 class="text-muted">Check-In (Pagi)</h6>
                                <h3 class="text-success"><i class="bi bi-box-arrow-in-right me-1"></i> 06:45 WIB</h3>
                                <span class="badge bg-success-soft text-success">Tepat Waktu</span>
                            </div>
                            <div class="col-6">
                                <h6 class="text-muted">Check-Out (Pulang)</h6>
                                <h3 class="text-muted"><i class="bi bi-box-arrow-right me-1"></i> --:-- WIB</h3>
                                <span class="badge bg-secondary text-white">Menunggu</span>
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
                                    <tr>
                                        <td>14 Mei 2024</td>
                                        <td>Minggu</td>
                                        <td colspan="3" class="text-center text-muted">Libur Sekolah</td>
                                    </tr>
                                    <tr>
                                        <td>13 Mei 2024</td>
                                        <td>Sabtu</td>
                                        <td>06:50 WIB</td>
                                        <td>12:00 WIB</td>
                                        <td><span class="badge bg-success">Hadir</span></td>
                                    </tr>
                                    <tr>
                                        <td>12 Mei 2024</td>
                                        <td>Jumat</td>
                                        <td>06:45 WIB</td>
                                        <td>13:00 WIB</td>
                                        <td><span class="badge bg-success">Hadir</span></td>
                                    </tr>
                                    <tr>
                                        <td>11 Mei 2024</td>
                                        <td>Kamis</td>
                                        <td>07:05 WIB</td>
                                        <td>13:00 WIB</td>
                                        <td><span class="badge bg-warning text-dark">Telat</span></td>
                                    </tr>
                                    <tr>
                                        <td>10 Mei 2024</td>
                                        <td>Rabu</td>
                                        <td colspan="3"><span class="badge bg-info">Izin (Sakit)</span> <small class="text-muted">Disetujui Pak Budi (Wali Kelas)</small></td>
                                    </tr>
                                    <tr>
                                        <td>09 Mei 2024</td>
                                        <td>Selasa</td>
                                        <td>06:40 WIB</td>
                                        <td>13:00 WIB</td>
                                        <td><span class="badge bg-success">Hadir</span></td>
                                    </tr>
                                    <tr>
                                        <td>08 Mei 2024</td>
                                        <td>Senin</td>
                                        <td>06:48 WIB</td>
                                        <td>13:00 WIB</td>
                                        <td><span class="badge bg-success">Hadir</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection