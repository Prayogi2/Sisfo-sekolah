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
            <div class="d-flex gap-2">
                @if($student)
                    <a href="{{ route('admin.buku-induk.student.download', $student) }}" target="_blank" class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Siswa Terpilih</a>
                    <a href="{{ route('admin.buku-induk.student.export.xlsx', $student) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel Terpilih</a>
                @endif
                <a href="{{ route('admin.buku-induk.download') }}" target="_blank" class="btn btn-primary"><i class="bi bi-download me-1"></i>Semua Siswa</a>
                <a href="{{ route('admin.buku-induk.export.xlsx') }}" class="btn btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel Semua</a>
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Daftar Nama Siswa</h6>
                <span class="badge bg-primary-subtle text-primary">{{ $students->count() }} siswa</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">No</th>
                                <th>Nama Siswa</th>
                                <th>NISN</th>
                                <th>Kelas</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $option)
                                <tr>
                                    <td class="ps-3">{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $option->name }}</td>
                                    <td>{{ $option->nisn }}</td>
                                    <td>{{ $option->classroom?->name ?? 'Belum masuk kelas' }}</td>
                                    <td><span class="badge {{ $option->status->value === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $option->status->label() }}</span></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-gear me-1"></i>Aksi
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.buku-induk', ['student' => $option->id, 'search' => $search]) }}"><i class="bi bi-eye me-2 text-primary"></i>Lihat Buku Induk</a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.buku-induk.edit', $option) }}"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Buku Induk Lengkap</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data siswa.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                    @forelse($academicReports as $report)
                        <div class="card border mb-4"><div class="card-header bg-light d-flex justify-content-between"><strong>Tahun Ajaran {{ $report['academic_year'] }} · Semester {{ $report['semester']->label() }}</strong><span class="text-primary fw-bold">Rata-rata: {{ $report['average'] ?? '-' }} · Peringkat: {{ $report['rank'] ? $report['rank'].' / '.$report['rank_total'] : '-' }}</span></div><div class="table-responsive"><table class="table table-sm table-bordered mb-0 align-middle"><thead><tr><th>Mapel</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th><th>Nilai Akhir</th><th>Grade</th></tr></thead><tbody>@foreach($report['grades'] as $grade)<tr><td>{{ $grade['subject'] }}</td><td>{{ $grade['assignment'] ?? '-' }}</td><td>{{ $grade['quiz'] ?? '-' }}</td><td>{{ $grade['midterm'] ?? '-' }}</td><td>{{ $grade['final'] ?? '-' }}</td><td class="fw-bold">{{ $grade['final_score'] ?? '-' }}</td><td>{{ $grade['letter'] }}</td></tr>@endforeach</tbody></table></div></div>
                    @empty
                        <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Belum ada nilai yang tersimpan untuk siswa ini.</div>
                    @endforelse
                    <h6 class="text-primary fw-bold mb-3"><i class="bi bi-chat-left-text me-2"></i>Kenaikan Kelas & Catatan Perkembangan</h6>
                    @forelse($student->progressNotes->sortByDesc('academic_year') as $note)
                        <div class="border rounded p-3 mb-2"><div class="d-flex justify-content-between"><strong>{{ $note->academic_year }} · Semester {{ $note->semester->label() }}</strong><span class="badge {{ $note->promotion_status->badgeClass() }}">{{ $note->promotion_status->label() }}</span></div><div class="mt-2 text-muted">{{ $note->notes ?: 'Tidak ada catatan.' }}</div></div>
                    @empty
                        <div class="alert alert-light border">Belum ada catatan perkembangan. Tambahkan melalui menu Edit Buku Induk Lengkap.</div>
                    @endforelse
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

    <div class="modal fade" id="modalEditBukuInduk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formEditBukuInduk" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">NISN</label><input type="text" name="nisn" id="bukuEditNisn" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">NIS</label><input type="text" name="nis" id="bukuEditNis" class="form-control" required></div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Nama Lengkap</label><input type="text" name="name" id="bukuEditName" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Jenis Kelamin</label><select name="gender" id="bukuEditGender" class="form-select" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Kelas</label><select name="classroom_id" id="bukuEditClassroomId" class="form-select"><option value="">-- Belum Ada Kelas --</option>@foreach ($students->pluck('classroom')->filter()->unique('id') as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                            <div class="col-md-12"><label class="form-label fw-semibold">Alamat</label><textarea name="address" id="bukuEditAddress" class="form-control" rows="2"></textarea></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-warning">Simpan Perubahan</button></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('modalEditBukuInduk')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('formEditBukuInduk').action = button.dataset.action;
            document.getElementById('bukuEditNisn').value = button.dataset.nisn;
            document.getElementById('bukuEditNis').value = button.dataset.nis;
            document.getElementById('bukuEditName').value = button.dataset.name;
            document.getElementById('bukuEditGender').value = button.dataset.gender;
            document.getElementById('bukuEditClassroomId').value = button.dataset.classroomId || '';
            document.getElementById('bukuEditAddress').value = button.dataset.address || '';
        });
    </script>
@endsection
