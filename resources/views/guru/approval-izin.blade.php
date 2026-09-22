@extends('layouts.app')

@section('title', 'Approval Izin & Sakit')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Approval Izin & Sakit Siswa</h4>
            <p class="text-muted mb-0">Verifikasi pengajuan surat izin dan sakit siswa kelas Anda.</p>
        </div>
    </div>

    <x-page-guide>Hanya izin dari kelas yang Anda menjadi wali kelasnya yang tampil di sini. Periksa bukti (surat dokter/foto) sebelum menyetujui.</x-page-guide>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Tabel Daftar Pengajuan Izin -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Kategori</th>
                            <th>Tanggal Izin</th>
                            <th>Alasan / Keterangan</th>
                            <th>Bukti File</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveRequests as $leaveRequest)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $leaveRequest->student->name }}</strong></td>
                                <td>{{ $leaveRequest->student->classroom?->name ?? '-' }}</td>
                                <td>
                                    @if ($leaveRequest->type === \App\Enums\LeaveType::Sick)
                                        <span class="badge bg-info text-dark">Sakit</span>
                                    @else
                                        <span class="badge bg-primary">Izin</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $leaveRequest->start_date->translatedFormat('d M Y') }}
                                    @if (! $leaveRequest->start_date->equalTo($leaveRequest->end_date))
                                        - {{ $leaveRequest->end_date->translatedFormat('d M Y') }}
                                    @endif
                                </td>
                                <td>{{ $leaveRequest->reason }}</td>
                                <td>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($leaveRequest->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-file-earmark-pdf"></i> Lihat
                                    </a>
                                </td>
                                <td>
                                    @php
                                        $badge = match ($leaveRequest->status) {
                                            \App\Enums\LeaveRequestStatus::Approved => 'bg-success text-white',
                                            \App\Enums\LeaveRequestStatus::Rejected => 'bg-danger text-white',
                                            default => 'bg-warning text-dark',
                                        };
                                        $label = match ($leaveRequest->status) {
                                            \App\Enums\LeaveRequestStatus::Approved => 'Disetujui',
                                            \App\Enums\LeaveRequestStatus::Rejected => 'Ditolak',
                                            default => 'Pending',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($leaveRequest->status === \App\Enums\LeaveRequestStatus::Pending)
                                        <form action="{{ route('guru.approval-izin.approve', $leaveRequest) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success me-1"><i class="bi bi-check-lg"></i> Setujui</button>
                                        </form>
                                        <form action="{{ route('guru.approval-izin.reject', $leaveRequest) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> Tolak</button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Belum ada pengajuan izin untuk kelas Anda.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $leaveRequests->links() }}
        </div>
    </div>
</div>
@endsection
