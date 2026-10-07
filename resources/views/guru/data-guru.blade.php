@extends('layouts.app')

@section('title', 'Data Saya')

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Data Saya</h1>
            <a href="{{ route('account.password.edit') }}" class="btn btn-outline-primary shadow-sm">
                <i class="bi bi-key me-1"></i> Ganti Password
            </a>
        </div>

        <x-page-guide>Halaman ini menampilkan data Anda sendiri dan hanya untuk dilihat. Perubahan data guru dilakukan oleh admin. Yang bisa Anda ubah sendiri adalah password, lewat tombol <strong>Ganti Password</strong>.</x-page-guide>

        @if ($teacher)
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center p-4">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($teacher->name) }}&background=e7f1ff&color=0d6efd&bold=true&size=120" class="rounded-circle mb-3" width="96" height="96" alt="Foto {{ $teacher->name }}">
                            <h2 class="h5 fw-bold mb-1">{{ $teacher->name }}</h2>
                            <p class="text-muted small mb-3">NIP/NUPTK: {{ $teacher->nip ?: '-' }}</p>

                            @if ($teacher->homeroomClassrooms->isNotEmpty())
                                <span class="badge bg-success-subtle text-success-emphasis">Wali Kelas {{ $teacher->homeroomClassrooms->pluck('name')->join(', ') }}</span>
                            @else
                                <span class="badge bg-primary-subtle text-primary-emphasis">Guru Mapel</span>
                            @endif

                            @unless ($teacher->is_active)
                                <div class="mt-3"><span class="badge bg-secondary">Tidak aktif</span></div>
                            @endunless
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Biodata</h6></div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-sm-4 text-muted fw-normal">Jenis Kelamin</dt>
                                <dd class="col-sm-8">{{ $teacher->gender?->label() ?? '-' }}</dd>

                                <dt class="col-sm-4 text-muted fw-normal">Tempat, Tanggal Lahir</dt>
                                <dd class="col-sm-8">{{ $teacher->birth_place ?: '-' }}{{ $teacher->birth_date ? ', '.$teacher->birth_date->translatedFormat('d F Y') : '' }}</dd>

                                <dt class="col-sm-4 text-muted fw-normal">Pendidikan Terakhir</dt>
                                <dd class="col-sm-8">{{ $teacher->last_education?->label() ?? '-' }}</dd>

                                <dt class="col-sm-4 text-muted fw-normal">No. WhatsApp</dt>
                                <dd class="col-sm-8">{{ $teacher->phone ?: '-' }}</dd>

                                <dt class="col-sm-4 text-muted fw-normal">Email</dt>
                                <dd class="col-sm-8">{{ $teacher->email ?: '-' }}</dd>

                                <dt class="col-sm-4 text-muted fw-normal">Alamat</dt>
                                <dd class="col-sm-8 mb-0">{{ $teacher->address ?: '-' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Mengajar</h6></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($teacher->teachingAssignments as $assignment)
                                            <tr>
                                                <td>{{ $assignment->subject?->name ?? '-' }}</td>
                                                <td>{{ $assignment->classroom?->name ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted py-4">Belum ada mata pelajaran yang ditugaskan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <p class="fs-5 fw-semibold mt-3 mb-1">Data guru belum terhubung ke akun ini</p>
                    <p class="text-muted mb-0">Minta admin menautkan akun Anda ke data guru lewat menu Data Guru.</p>
                </div>
            </div>
        @endif
    </div>
@endsection
