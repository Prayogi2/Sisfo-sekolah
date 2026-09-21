@extends('layouts.app')

@section('title', 'Buku Induk Siswa')

@section('content')
    @php
        $profile = $student?->profile;
        $academicRecord = $student?->academicRecord;
        $guardianBlocks = [
            ['title' => 'Data Ayah', 'icon' => 'bi-person-fill', 'color' => 'primary', 'guardian' => $father],
            ['title' => 'Data Ibu', 'icon' => 'bi-person-fill', 'color' => 'primary', 'guardian' => $mother],
            ['title' => 'Data Wali', 'icon' => 'bi-person-badge', 'color' => 'warning', 'guardian' => $legalGuardian],
        ];
    @endphp

    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 fw-bold">Buku Induk Siswa</h1>
                <p class="text-muted mb-0">Rekam jejak lengkap data peserta didik MIS Nurul Falaq.</p>
            </div>
        </div>

        <!-- Pilih Siswa -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.buku-induk') }}" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Cari Siswa</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-primary"></i></span>
                            <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0 ps-0" placeholder="Nama, NISN, atau NIS...">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Siswa yang Dipilih</label>
                        <select name="student" class="form-select fw-semibold" onchange="this.form.submit()">
                            @forelse ($students as $option)
                                <option value="{{ $option->id }}" @selected($student?->id === $option->id)>
                                    {{ $option->name }} ({{ $option->classroom?->name ?? 'Tanpa Kelas' }})
                                </option>
                            @empty
                                <option value="">Tidak ada siswa yang cocok</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel me-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        @if (! $student)
            <div class="alert alert-light border text-center text-muted">
                Tidak ada data siswa yang cocok dengan pencarian. Kosongkan kolom pencarian atau tambahkan siswa lebih dulu pada menu Data Siswa.
            </div>
        @else
            <!-- Identitas Ringkas Siswa Terpilih -->
            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex align-items-center">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-3" width="50" height="50" alt="Foto {{ $student->name }}">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">{{ $student->name }}</h5>
                        <span class="text-muted small">
                            NISN {{ $student->nisn }} &middot; {{ $student->classroom?->name ?? 'Belum masuk kelas' }} &middot; {{ $student->status->label() }}
                        </span>
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
                    @unless ($profile)
                        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Data identitas buku induk siswa ini belum dilengkapi.</div>
                    @endunless

                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Identitas Dasar</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label small text-muted">NISN</label><input type="text" class="form-control bg-light" value="{{ $student->nisn }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">NIS</label><input type="text" class="form-control bg-light" value="{{ $student->nis }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Nama Panggilan</label><input type="text" class="form-control bg-light" value="{{ $profile?->nickname ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">NIK</label><input type="text" class="form-control bg-light" value="{{ $profile?->nik ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">No. Kartu Keluarga</label><input type="text" class="form-control bg-light" value="{{ $profile?->family_card_number ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Jenis Kelamin</label><input type="text" class="form-control bg-light" value="{{ $student->gender->label() }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tempat Lahir</label><input type="text" class="form-control bg-light" value="{{ $student->birth_place ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tanggal Lahir</label><input type="text" class="form-control bg-light" value="{{ $student->birth_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Agama</label><input type="text" class="form-control bg-light" value="{{ $profile?->religion?->label() ?? '-' }}" readonly></div>
                        <div class="col-md-3"><label class="form-label small text-muted">Status dalam Keluarga</label><input type="text" class="form-control bg-light" value="{{ $profile?->family_status?->label() ?? '-' }}" readonly></div>
                        <div class="col-md-3"><label class="form-label small text-muted">Golongan Darah</label><input type="text" class="form-control bg-light" value="{{ $profile?->blood_type?->value ?? '-' }}" readonly></div>
                        <div class="col-md-3"><label class="form-label small text-muted">Tinggi / Berat (cm/kg)</label>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control bg-light" value="{{ $profile?->height_cm ?: '-' }}" readonly>
                                <input type="text" class="form-control bg-light" value="{{ $profile?->weight_kg ?: '-' }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-3"><label class="form-label small text-muted">Anak ke- / Jumlah Saudara</label>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control bg-light" value="{{ $profile?->birth_order ?: '-' }}" readonly>
                                <input type="text" class="form-control bg-light" value="{{ $profile?->siblings_count ?? '-' }}" readonly>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-geo-alt-fill me-2"></i>Alamat Detail</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small text-muted">Alamat (Jalan)</label><input type="text" class="form-control bg-light" value="{{ $profile?->street_address ?: '-' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-muted">Gang / Dusun</label><input type="text" class="form-control bg-light" value="{{ $profile?->hamlet ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Desa / Kelurahan</label><input type="text" class="form-control bg-light" value="{{ $profile?->village ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Kecamatan</label><input type="text" class="form-control bg-light" value="{{ $profile?->district ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Kabupaten / Kota</label><input type="text" class="form-control bg-light" value="{{ $profile?->regency ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Provinsi</label><input type="text" class="form-control bg-light" value="{{ $profile?->province ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Kode Pos</label><input type="text" class="form-control bg-light" value="{{ $profile?->postal_code ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Alamat Ringkas (Data Siswa)</label><input type="text" class="form-control bg-light" value="{{ $student->address ?: '-' }}" readonly></div>
                    </div>
                </div>

                <!-- Tab 2: Riwayat Pendidikan -->
                <div class="tab-pane fade" id="riwayat" role="tabpanel">
                    @unless ($academicRecord)
                        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Riwayat pendidikan siswa ini belum dilengkapi.</div>
                    @endunless

                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-school me-2"></i>Riwayat Pendidikan Formal</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label small text-muted">Asal TK / PAUD</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->kindergarten_origin ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">No. Ijazah / SKL TK</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->kindergarten_certificate_number ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tanggal Ijazah TK</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->kindergarten_certificate_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                    </div>

                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-info-circle-fill me-2"></i>Status di Sekolah</h6>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label small text-muted">Status Siswa</label><input type="text" class="form-control bg-light" value="{{ $student->status->label() }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Status Masuk</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->entry_status ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tanggal Masuk</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->entry_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tanggal Pindah Keluar</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->transfer_out_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                        <div class="col-md-8"><label class="form-label small text-muted">Alasan Pindah Keluar</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->transfer_out_reason ?: '-' }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small text-muted">Tanggal Keluar</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->exit_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                        <div class="col-md-8"><label class="form-label small text-muted">Alasan Keluar</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->exit_reason ?: '-' }}" readonly></div>
                    </div>
                </div>

                <!-- Tab 3: Perkembangan Akademik -->
                <div class="tab-pane fade" id="akademik" role="tabpanel">
                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-graph-up me-2"></i>Rekap Nilai &amp; Rapor</h6>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Rekap nilai dan rapor belum tersedia. Modul penilaian belum memiliki data, sehingga tab ini akan terisi otomatis setelah modul nilai aktif.
                    </div>
                </div>

                <!-- Tab 4: Data Orang Tua / Wali -->
                <div class="tab-pane fade" id="ortu" role="tabpanel">
                    @if ($student->guardians->isEmpty())
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Siswa ini belum memiliki data wali murid terhubung.
                            @if ($student->parent_name)
                                Data ringkas dari berkas siswa: <strong>{{ $student->parent_name }}</strong> ({{ $student->parent_phone ?: 'tanpa nomor telepon' }}).
                            @endif
                        </div>
                    @endif

                    <div class="row g-4">
                        @foreach ($guardianBlocks as $block)
                            @php($guardian = $block['guardian'])
                            <div class="col-lg-4">
                                <h6 class="text-{{ $block['color'] }} fw-bold mb-3"><i class="bi {{ $block['icon'] }} me-2"></i>{{ $block['title'] }}</h6>
                                @if ($guardian)
                                    <div class="mb-3"><label class="form-label small text-muted">Nama Lengkap</label><input type="text" class="form-control bg-light" value="{{ $guardian->name }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">NIK</label><input type="text" class="form-control bg-light" value="{{ $guardian->nik ?: '-' }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">Pekerjaan</label><input type="text" class="form-control bg-light" value="{{ $guardian->occupation ?: '-' }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">Penghasilan per Bulan</label><input type="text" class="form-control bg-light" value="{{ $guardian->monthly_income ?: '-' }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">Pendidikan Terakhir</label><input type="text" class="form-control bg-light" value="{{ $guardian->last_education?->label() ?? '-' }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">No. WhatsApp Aktif</label><input type="text" class="form-control bg-light" value="{{ $guardian->phone ?: '-' }}" readonly></div>
                                    <div class="mb-3"><label class="form-label small text-muted">Alamat</label><textarea class="form-control bg-light" rows="2" readonly>{{ $guardian->address ?: '-' }}</textarea></div>
                                @else
                                    <div class="alert alert-light border text-muted mb-0">Belum ada data {{ strtolower(str_replace('Data ', '', $block['title'])) }}.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Tab 5: Data Kelulusan -->
                <div class="tab-pane fade" id="lulus" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small text-muted">Status Kelulusan</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->graduation_status?->label() ?? 'Belum Lulus' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-muted">Tahun Kelulusan</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->graduation_year ?: '-' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-muted">No. Ijazah</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->graduation_certificate_number ?: '-' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-muted">Tanggal Ijazah</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->graduation_certificate_date?->translatedFormat('d F Y') ?? '-' }}" readonly></div>
                        <div class="col-md-6"><label class="form-label small text-muted">Melanjutkan Ke</label><input type="text" class="form-control bg-light" value="{{ $academicRecord?->continued_to ?: '-' }}" readonly></div>
                        <div class="col-md-12"><label class="form-label small text-muted">Catatan Kelulusan</label><textarea class="form-control bg-light" rows="3" readonly>{{ $academicRecord?->graduation_notes ?: '-' }}</textarea></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
