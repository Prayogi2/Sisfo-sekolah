@extends('layouts.app')

@section('title', 'Buku Induk Siswa')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Buku Induk Siswa</h1>
                <p class="text-muted mb-0">Rekam jejak lengkap data peserta didik MIS Nurul Falaq.</p>
            </div>
            <div class="btn-group">
                <button class="btn btn-outline-success shadow-sm btn-sm"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
                <button class="btn btn-outline-danger shadow-sm btn-sm"><i class="bi bi-file-earmark-pdf"></i> Export PDF</button>
            </div>
        </div>

        <!-- Pilih Siswa -->
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex align-items-center">
                <img src="https://ui-avatars.com/api/?name=Ahmad+Fauzi&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-3" width="50" height="50" alt="Siswa">
                <div class="flex-grow-1">
                    <label class="form-label small text-muted mb-0">Siswa yang Dipilih:</label>
                    <select class="form-select form-select-sm border-0 bg-light fw-semibold" style="width: auto; display: inline-block;">
                        <option selected>Ahmad Fauzi (Kelas VI)</option>
                        <option>Siti Aminah (Kelas V)</option>
                        <option>Budi Santoso (Kelas IV)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tab Navigasi -->
        <ul class="nav nav-pills nav-fill bg-primary-soft p-2 rounded mb-4" id="bukuIndukTab" role="tablist" style="background-color: #e7f1ff;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="data-tab" data-bs-toggle="pill" data-bs-target="#data" type="button"><i class="bi bi-person-vcard me-1"></i> Data Peserta Didik</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="riwayat-tab" data-bs-toggle="pill" data-bs-target="#riwayat" type="button"><i class="bi bi-clock-history me-1"></i> Riwayat Pendidikan</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="akademik-tab" data-bs-toggle="pill" data-bs-target="#akademik" type="button"><i class="bi bi-journal-text me-1"></i> Perkembangan Akademik</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="ortu-tab" data-bs-toggle="pill" data-bs-target="#ortu" type="button"><i class="bi bi-people me-1"></i> Data Orang Tua</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="lulus-tab" data-bs-toggle="pill" data-bs-target="#lulus" type="button"><i class="bi bi-award me-1"></i> Data Kelulusan</button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content bg-white p-4 rounded shadow-sm border-0">
            
            <!-- Tab 1: Data Peserta Didik -->
            <div class="tab-pane fade show active" id="data" role="tabpanel">
                <h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Identitas Dasar</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4"><label class="form-label small text-muted">NISN</label><input type="text" class="form-control" value="0098761234"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">NIK</label><input type="text" class="form-control" value="3201234567890001"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">No. Kartu Keluarga</label><input type="text" class="form-control" value="3201234567890123"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Tempat Lahir</label><input type="text" class="form-control" value="Cianjur"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Tanggal Lahir</label><input type="date" class="form-control" value="2012-05-15"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Jenis Kelamin</label><select class="form-select"><option>Laki-laki</option><option>Perempuan</option></select></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Golongan Darah</label><select class="form-select"><option>O</option><option>A</option><option>B</option><option>AB</option></select></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Tinggi/Berat (cm/kg)</label><div class="d-flex gap-2"><input type="text" class="form-control" value="140"><input type="text" class="form-control" value="35"></div></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Anak ke-</label><input type="number" class="form-control" value="2"></div>
                    <div class="col-md-3"><label class="form-label small text-muted">Jumlah Saudara</label><input type="number" class="form-control" value="1"></div>
                </div>
                <h6 class="text-primary fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Alamat Detail</h6>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small text-muted">Alamat (Jalan)</label><input type="text" class="form-control" value="Jl. Sirnarasa No. 12"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Gang / Dusun</label><input type="text" class="form-control" value="Dusun Cibogo"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">RT / RW</label><div class="d-flex gap-2"><input type="text" class="form-control" value="003"><input type="text" class="form-control" value="005"></div></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Desa / Kelurahan</label><input type="text" class="form-control" value="Sukamaju"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Kecamatan</label><input type="text" class="form-control" value="Cibeber"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Kabupaten/Kota</label><input type="text" class="form-control" value="Cianjur"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Kode Pos</label><input type="text" class="form-control" value="43262"></div>
                </div>
                <div class="text-end mt-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button></div>
            </div>

            <!-- Tab 2: Riwayat Pendidikan -->
            <div class="tab-pane fade" id="riwayat" role="tabpanel">
                <h6 class="text-primary fw-bold mb-3"><i class="bi bi-school me-2"></i>Riwayat Pendidikan Formal</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label small text-muted">Asal TK / PAUD</label><input type="text" class="form-control" value="TK Nurul Huda"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Tahun Lulus TK</label><input type="text" class="form-control" value="2018"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">No. Ijazah / SKL</label><input type="text" class="form-control" value="SKL-2023-001"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Tanggal Ijazah</label><input type="date" class="form-control" value="2023-06-20"></div>
                </div>
                <h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Status di Sekolah</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Status Siswa</label>
                        <select class="form-select"><option>Aktif</option><option>Pindah Masuk</option><option>Pindah Keluar</option><option>Lulus</option><option>Keluar</option></select>
                    </div>
                    <div class="col-md-4"><label class="form-label small text-muted">Tanggal Masuk</label><input type="date" class="form-control" value="2018-07-15"></div>
                    <div class="col-md-4"><label class="form-label small text-muted">Keterangan</label><input type="text" class="form-control" placeholder="Contoh: Pindahan dari SD X" value="-"></div>
                </div>
                <div class="text-end mt-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button></div>
            </div>

            <!-- Tab 3: Perkembangan Akademik -->
            <div class="tab-pane fade" id="akademik" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-primary fw-bold mb-0"><i class="bi bi-graph-up me-2"></i>Rekap Nilai & Rapor</h6>
                    <select class="form-select form-select-sm w-auto">
                        <option>Semester 1 (Ganjil) 2023/2024</option>
                        <option>Semester 2 (Genap) 2023/2024</option>
                    </select>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light text-center">
                            <tr>
                                <th>No</th>
                                <th class="text-start">Mata Pelajaran</th>
                                <th>Nilai Pengetahuan</th>
                                <th>Nilai Keterampilan</th>
                                <th>Nilai Akhir</th>
                                <th>Predikat</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <tr><td>1</td><td class="text-start">Al-Qur'an Hadits</td><td>85</td><td>90</td><td class="fw-bold">87.5</td><td><span class="badge bg-success">A</span></td></tr>
                            <tr><td>2</td><td class="text-start">Akidah Akhlak</td><td>88</td><td>85</td><td class="fw-bold">86.5</td><td><span class="badge bg-success">A</span></td></tr>
                            <tr><td>3</td><td class="text-start">Matematika</td><td>75</td><td>80</td><td class="fw-bold">77.5</td><td><span class="badge bg-primary">B</span></td></tr>
                            <tr><td>4</td><td class="text-start">Bahasa Indonesia</td><td>90</td><td>95</td><td class="fw-bold">92.5</td><td><span class="badge bg-success">A</span></td></tr>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">Rata-rata & Peringkat</td>
                                <td colspan="2" class="fw-bold text-primary">86.00</td>
                                <td class="fw-bold text-warning">Rank 3 / 30</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <label class="form-label small text-muted">Catatan Wali Kelas:</label>
                    <textarea class="form-control" rows="2" readonly>Anak sangat rajin dan mandiri, perlu sedikit peningkatan pada mata pelajaran Matematika.</textarea>
                </div>
            </div>

            <!-- Tab 4: Data Orang Tua / Wali -->
            <div class="tab-pane fade" id="ortu" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 border-end pe-4">
                        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-person-fill me-2"></i>Data Ayah</h6>
                        <div class="mb-3"><label class="form-label small text-muted">Nama Lengkap Ayah</label><input type="text" class="form-control" value="Bapak Udin"></div>
                        <div class="row mb-3">
                            <div class="col-8"><label class="form-label small text-muted">Pekerjaan</label><input type="text" class="form-control" value="Petani"></div>
                            <div class="col-4"><label class="form-label small text-muted">Penghasilan</label><input type="text" class="form-control" value="2.500.000"></div>
                        </div>
                        <div class="mb-3"><label class="form-label small text-muted">No. WhatsApp Aktif</label><input type="text" class="form-control" value="081234567890"></div>
                    </div>
                    <div class="col-md-6 ps-4">
                        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-person-fill me-2"></i>Data Ibu</h6>
                        <div class="mb-3"><label class="form-label small text-muted">Nama Lengkap Ibu</label><input type="text" class="form-control" value="Ibu Aminah"></div>
                        <div class="row mb-3">
                            <div class="col-8"><label class="form-label small text-muted">Pekerjaan</label><input type="text" class="form-control" value="Ibu Rumah Tangga"></div>
                            <div class="col-4"><label class="form-label small text-muted">Penghasilan</label><input type="text" class="form-control" value="-"></div>
                        </div>
                        <div class="mb-3"><label class="form-label small text-muted">No. WhatsApp Aktif</label><input type="text" class="form-control" value="081234567891"></div>
                    </div>
                </div>
                <hr class="my-4">
                <h6 class="text-warning fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>Data Wali (Jika ada)</h6>
                <div class="mb-3"><label class="form-label small text-muted">Nama Lengkap Wali</label><input type="text" class="form-control" placeholder="Kosongkan jika tidak ada"></div>
                <div class="text-end mt-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button></div>
            </div>

            <!-- Tab 5: Data Kelulusan -->
            <div class="tab-pane fade" id="lulus" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small text-muted">Status Kelulusan</label><select class="form-select"><option>Belum Lulus</option><option>Lulus</option><option>Tidak Lulus</option></select></div>
                    <div class="col-md-6"><label class="form-label small text-muted">Tanggal Kelulusan</label><input type="date" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small text-muted">No. Ijazah</label><input type="text" class="form-control" placeholder="Otomatis terisi saat lulus" disabled></div>
                    <div class="col-md-6"><label class="form-label small text-muted">No. SKHUN</label><input type="text" class="form-control" placeholder="Otomatis terisi saat lulus" disabled></div>
                    <div class="col-md-12"><label class="form-label small text-muted">Catatan Kelulusan</label><textarea class="form-control" rows="3" placeholder="Contoh: Lulus dengan predikat Mumtaz"></textarea></div>
                </div>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle me-2"></i>Fitur cetak Ijazah dan SKL akan aktif otomatis setelah status diubah menjadi "Lulus".</div>
                <div class="text-end mt-4"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Perubahan</button></div>
            </div>
        </div>
    </div>
@endsection