@extends('layouts.app')

@section('title', 'Laporan Nilai Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.laporan') }}" class="btn btn-light me-3 shadow-sm border" title="Kembali ke Pusat Laporan">
                    <i class="bi bi-arrow-left fs-5 text-primary"></i>
                </a>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Laporan Rekapitulasi Nilai</h1>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button class="btn btn-outline-danger shadow-sm btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
        </div>

        <!-- Filter Laporan -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Filter Kelas</label>
                        <select class="form-select">
                            <option>Semua Kelas</option>
                            <option selected>Kelas 6-A</option>
                            <option>Kelas 6-B</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Mata Pelajaran</label>
                        <select class="form-select">
                            <option>Semua Mapel</option>
                            <option selected>Matematika</option>
                            <option>Al-Qur'an Hadits</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Semester</label>
                        <select class="form-select">
                            <option selected>Ganjil 2023/2024</option>
                            <option>Genap 2023/2024</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekap Nilai -->
        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Rekap Nilai: Matematika (Kelas 6-A) - Semester Ganjil</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle text-center">
                        <thead class="table-light">
                            <tr>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th>Nilai Harian</th>
                                <th>Nilai Kuis CBT</th>
                                <th>Nilai UTS</th>
                                <th>Nilai UAS</th>
                                <th>Nilai Akhir</th>
                                <th>Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0098761234</td>
                                <td class="text-start fw-semibold text-dark">Ahmad Fauzi</td>
                                <td>85</td>
                                <td>90</td>
                                <td>88</td>
                                <td>87</td>
                                <td class="fw-bold text-primary">87.5</td>
                                <td><span class="badge bg-success">A</span></td>
                            </tr>
                            <tr>
                                <td>0098761235</td>
                                <td class="text-start fw-semibold text-dark">Siti Aminah</td>
                                <td>90</td>
                                <td>95</td>
                                <td>92</td>
                                <td>94</td>
                                <td class="fw-bold text-primary">92.5</td>
                                <td><span class="badge bg-success">A</span></td>
                            </tr>
                            <tr>
                                <td>0098761236</td>
                                <td class="text-start fw-semibold text-dark">Budi Santoso</td>
                                <td>75</td>
                                <td>80</td>
                                <td>78</td>
                                <td>76</td>
                                <td class="fw-bold text-primary">77.5</td>
                                <td><span class="badge bg-primary">B</span></td>
                            </tr>
                            <tr>
                                <td>0098761237</td>
                                <td class="text-start fw-semibold text-dark">Citra Lestari</td>
                                <td>65</td>
                                <td>70</td>
                                <td>68</td>
                                <td>66</td>
                                <td class="fw-bold text-primary">67.0</td>
                                <td><span class="badge bg-warning text-dark">C</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer py-3 bg-white text-end">
                <small class="text-muted">Rata-rata Kelas: <strong class="text-dark">81.12</strong> | Tertinggi: <strong class="text-success">92.5</strong> | Terendah: <strong class="text-danger">67.0</strong></small>
            </div>
        </div>
    </div>
@endsection