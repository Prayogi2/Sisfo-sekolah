@extends('layouts.app')

@section('title', 'Pemantauan Absensi & Izin')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Pemantauan Absensi & Pengajuan Izin</h1>
            <span class="badge bg-primary-soft text-primary p-2 shadow-sm">Tahun Ajaran {{ \App\Models\Classroom::currentAcademicYear() }}</span>
        </div>

        <x-page-guide>Ajukan izin/sakit lewat form di bawah dan lampirkan bukti (surat dokter/foto) — tanpa bukti, anak tercatat alpa. Wali kelas atau admin akan memverifikasi pengajuan Anda.</x-page-guide>

        <div class="row">
            <!-- Kolom Kiri: Riwayat Absensi & Status Pengajuan -->
            <div class="col-lg-8 col-md-12 mb-4">
                <!-- Riwayat Absensi -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-clock-history me-2"></i>Riwayat Kehadiran 7 Hari Terakhir - {{ $student->name }}</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Check-In</th>
                                        <th>Check-Out</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history as $row)
                                        <tr>
                                            <td>{{ $row['date']->translatedFormat('d F Y') }}</td>
                                            @if (! $row['is_school_day'])
                                                <td colspan="3" class="text-center text-muted">Libur Sekolah</td>
                                            @elseif ($row['attendance'])
                                                <td>{{ $row['attendance']->check_in_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                                <td>{{ $row['attendance']->check_out_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                                <td>
                                                    @php
                                                        $badge = match ($row['attendance']->status->value) {
                                                            'hadir' => 'bg-success-soft text-success',
                                                            'telat' => 'bg-warning-soft text-warning',
                                                            'izin' => 'bg-primary-soft text-primary',
                                                            default => 'bg-danger-soft text-danger',
                                                        };
                                                        $label = match ($row['attendance']->status->value) {
                                                            'hadir' => 'Hadir',
                                                            'telat' => 'Telat',
                                                            'izin' => 'Izin',
                                                            default => 'Alpa',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $badge }}" style="background-color: #e6f9ee;">{{ $label }}</span>
                                                </td>
                                            @else
                                                <td colspan="2" class="text-muted">--</td>
                                                <td><span class="badge bg-danger-soft text-danger">Alpa</span></td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Status Pengajuan Izin -->
                <div class="card shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-file-earmark-text me-2"></i>Riwayat Pengajuan Izin/Sakit</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse ($leaveRequests as $leaveRequest)
                                @php
                                    $badge = match ($leaveRequest->status) {
                                        \App\Enums\LeaveRequestStatus::Approved => 'bg-success',
                                        \App\Enums\LeaveRequestStatus::Rejected => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                    $label = match ($leaveRequest->status) {
                                        \App\Enums\LeaveRequestStatus::Approved => 'Disetujui',
                                        \App\Enums\LeaveRequestStatus::Rejected => 'Ditolak',
                                        default => 'Pending',
                                    };
                                @endphp
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-medical fs-3 text-danger me-3"></i>
                                        <div>
                                            <span class="fw-bold text-dark">{{ $leaveRequest->type === \App\Enums\LeaveType::Sick ? 'Izin Sakit' : 'Izin' }}</span><br>
                                            <small class="text-muted">
                                                {{ $leaveRequest->start_date->translatedFormat('d M Y') }}
                                                @if (! $leaveRequest->start_date->equalTo($leaveRequest->end_date))
                                                    - {{ $leaveRequest->end_date->translatedFormat('d M Y') }}
                                                @endif
                                                | {{ $leaveRequest->reason }}
                                            </small>
                                        </div>
                                    </div>
                                    <span class="badge {{ $badge }} rounded-pill">{{ $label }}</span>
                                </div>
                            @empty
                                <div class="list-group-item text-center text-muted py-4">Belum ada pengajuan izin.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Form Pengajuan Izin Baru -->
            <div class="col-lg-4 col-md-12">
                <div class="card shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                        <h6 class="m-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Form Pengajuan Izin/Sakit Baru</h6>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <form action="{{ route('wali.izin.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p class="text-muted small mb-3">Pengajuan untuk: <strong>{{ $student->name }}</strong></p>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Jenis Izin <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="sakit" @selected(old('type') === 'sakit')>Sakit</option>
                                    <option value="izin" @selected(old('type') === 'izin')>Izin</option>
                                </select>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Tgl Mulai <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Tgl Selesai <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Alasan / Keterangan <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="Jelaskan alasan pengajuan izin..." required>{{ old('reason') }}</textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Upload Lampiran (Surat Dokter/Dll) <span class="text-danger">*</span></label>
                                <input type="file" name="attachment" class="form-control" required>
                                <small class="text-muted">Format: PDF, JPG, PNG (Max 2MB)</small>
                            </div>

                            <div class="alert alert-info d-flex align-items-center p-2" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>Pengajuan akan dikirim ke Wali Kelas untuk disetujui.</small>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 shadow-sm">Kirim Pengajuan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection