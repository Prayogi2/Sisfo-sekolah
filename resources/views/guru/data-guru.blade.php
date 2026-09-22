@extends('layouts.app')

@section('title', 'Data Guru & Wali Kelas')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800 fw-bold">Daftar Guru & Wali Kelas</h1>
        <p class="text-muted">Informasi data pengajar dan wali kelas MIS Nurul Falaq.</p>

        <x-page-guide>Halaman ini hanya untuk melihat. Perubahan data guru dilakukan oleh admin lewat menu Data Guru miliknya.</x-page-guide>

        <div class="card border-0 shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Daftar Tenaga Pendidik</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>NIP / NUPTK</th>
                                <th>Nama Lengkap</th>
                                <th>Mata Pelajaran</th>
                                <th>Status</th>
                                <th>Kontak</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->nip }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($teacher->name) }}&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="35" height="35">
                                            <span class="fw-semibold text-dark">{{ $teacher->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $teacher->subjects->pluck('name')->join(', ') ?: '-' }}</td>
                                    <td>
                                        @if ($teacher->homeroomClassrooms->isNotEmpty())
                                            <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Wali Kelas {{ $teacher->homeroomClassrooms->pluck('name')->join(', ') }}</span>
                                        @else
                                            <span class="badge bg-primary-soft text-primary">Guru Mapel</span>
                                        @endif
                                    </td>
                                    <td>{{ $teacher->phone ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada data guru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
