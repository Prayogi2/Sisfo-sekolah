@extends('layouts.app')

@section('title', 'Data Izin & Sakit Siswa')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Data Izin & Sakit Siswa</h1>
    </div>

    <x-page-guide>Halaman ini untuk memantau pengajuan izin/sakit seluruh siswa. Persetujuan dilakukan oleh <strong>wali kelas</strong> masing-masing (admin bisa membantu lewat Kelola Akun → Masuk ke akun wali kelas).</x-page-guide>

    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Daftar Pengajuan</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Izin</th><th>Nama Anak</th><th>Kategori</th><th>Alasan</th><th>Lampiran</th><th>Status</th><th>Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leaveRequests as $leaveRequest)
                            <tr>
                                <td>
                                    {{ $leaveRequest->start_date->translatedFormat('d M Y') }}
                                    @if (! $leaveRequest->start_date->equalTo($leaveRequest->end_date))
                                        - {{ $leaveRequest->end_date->translatedFormat('d M Y') }}
                                    @endif
                                </td>
                                <td>{{ $leaveRequest->student->name }} ({{ $leaveRequest->student->classroom?->name ?? '-' }})<div class="small text-muted">Diajukan: {{ $leaveRequest->applicantLabel() }}</div></td>
                                <td>{{ $leaveRequest->type === \App\Enums\LeaveType::Sick ? 'Sakit' : 'Izin' }}</td>
                                <td>{{ $leaveRequest->reason }}</td>
                                <td>
                                    <a href="{{ \Illuminate\Support\Facades\Storage::url($leaveRequest->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-file-earmark-medical"></i> Lihat Lampiran
                                    </a>
                                </td>
                                <td>
                                    @php
                                        $badge = match ($leaveRequest->status) {
                                            \App\Enums\LeaveRequestStatus::Approved => 'bg-success-soft text-success',
                                            \App\Enums\LeaveRequestStatus::Rejected => 'bg-danger-soft text-danger',
                                            default => 'bg-warning-soft text-warning',
                                        };
                                        $label = match ($leaveRequest->status) {
                                            \App\Enums\LeaveRequestStatus::Approved => 'Disetujui',
                                            \App\Enums\LeaveRequestStatus::Rejected => 'Ditolak',
                                            default => 'Pending',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $label }}</span>
                                </td>
                                <td class="small">
                                    @if ($leaveRequest->status === \App\Enums\LeaveRequestStatus::Pending)
                                        <span class="text-muted">Menunggu wali kelas{{ $leaveRequest->student->classroom?->homeroomTeacher ? ' ('.$leaveRequest->student->classroom->homeroomTeacher->name.')' : '' }}</span>
                                    @else
                                        <div class="fw-semibold">{{ $leaveRequest->reviewer?->name ?? '-' }}</div>
                                        <div class="text-muted">{{ $leaveRequest->reviewed_at?->translatedFormat('d M Y, H:i') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada pengajuan izin.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($leaveRequests->hasPages())
            <div class="card-footer bg-white">
                {{ $leaveRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
