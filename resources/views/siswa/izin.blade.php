@extends('layouts.app')

@section('title', 'Ajukan Izin / Sakit')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 mb-1 fw-bold">Ajukan Izin / Sakit</h1>
        <p class="text-muted mb-0">Pengajuan dikirim ke wali kelas {{ $student->classroom?->name ? 'kelas '.$student->classroom->name : '' }}{{ $student->classroom?->homeroomTeacher ? ' ('.$student->classroom->homeroomTeacher->name.')' : '' }} untuk disetujui.</p>
    </div>

    <x-page-guide>Isi jenis & tanggal izin, tulis alasannya, lalu lampirkan foto surat dokter atau surat dari orang tua. Bila disetujui wali kelas, absensi pada tanggal tersebut otomatis tercatat <strong>Izin</strong>.</x-page-guide>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <form action="{{ route('siswa.izin.store') }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
                @csrf
                <div class="card-header bg-white fw-bold"><i class="bi bi-envelope-paper-heart me-2 text-primary"></i>Form Pengajuan</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <div class="d-flex gap-3">
                            @foreach($types as $type)
                                <label class="form-check"><input type="radio" name="type" value="{{ $type->value }}" class="form-check-input" @checked(old('type', 'sakit') === $type->value) required> <span class="form-check-label">{{ ucfirst($type->value) }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Dari Tanggal</label><input type="date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}" min="{{ today()->subDays(\App\Http\Requests\LeaveRequest\StoreStudentLeaveRequest::MAX_DAYS_BACK)->toDateString() }}" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Sampai Tanggal</label><input type="date" name="end_date" value="{{ old('end_date', today()->toDateString()) }}" class="form-control" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan / Keterangan</label>
                        <textarea name="reason" rows="3" maxlength="1000" class="form-control" placeholder="mis. Demam sejak semalam, disarankan dokter istirahat" required>{{ old('reason') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diajukan oleh</label>
                        @if($student->guardians->isNotEmpty())
                            <select name="guardian_id" class="form-select" id="guardianSelect">
                                @foreach($student->guardians as $guardian)
                                    <option value="{{ $guardian->id }}" @selected((string) old('guardian_id') === (string) $guardian->id)>{{ $guardian->name }} ({{ $guardian->relationship?->label() ?? 'orang tua/wali' }})</option>
                                @endforeach
                                <option value="" @selected(old('guardian_id') === '')>Lainnya (tulis nama)</option>
                            </select>
                        @endif
                        <input type="text" name="applicant_name" id="applicantName" value="{{ old('applicant_name') }}" maxlength="100" class="form-control {{ $student->guardians->isNotEmpty() ? 'mt-2' : '' }}" placeholder="Nama orang tua / wali yang mengajukan">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Bukti (foto surat / PDF)</label>
                        <input type="file" name="attachment" accept="image/*,application/pdf" capture="environment" class="form-control" required>
                        <div class="form-text">Maksimal 5 MB.</div>
                    </div>
                </div>
                <div class="card-footer bg-white text-end"><button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Kirim Pengajuan</button></div>
            </form>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Pengajuan</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Jenis</th><th>Alasan</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse($leaveRequests as $leaveRequest)
                                    @php
                                        [$badge, $label] = match ($leaveRequest->status) {
                                            \App\Enums\LeaveRequestStatus::Approved => ['success', 'Disetujui'],
                                            \App\Enums\LeaveRequestStatus::Rejected => ['danger', 'Ditolak'],
                                            default => ['warning', 'Menunggu wali kelas'],
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $leaveRequest->start_date->translatedFormat('d M Y') }}@unless($leaveRequest->start_date->equalTo($leaveRequest->end_date)) – {{ $leaveRequest->end_date->translatedFormat('d M Y') }}@endunless</td>
                                        <td>{{ ucfirst($leaveRequest->type->value) }}</td>
                                        <td class="small">{{ $leaveRequest->reason }}</td>
                                        <td>
                                            <span class="badge bg-{{ $badge }}{{ $badge === 'warning' ? ' text-dark' : '' }}">{{ $label }}</span>
                                            @if($leaveRequest->reviewer)<div class="small text-muted">oleh {{ $leaveRequest->reviewer->name }}</div>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada pengajuan izin.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const select = document.getElementById('guardianSelect');
        const name = document.getElementById('applicantName');
        if (!select) return;
        const apply = () => {
            const other = select.value === '';
            name.classList.toggle('d-none', !other);
            name.required = other;
            name.disabled = !other;
        };
        select.addEventListener('change', apply);
        apply();
    })();
</script>
@endpush
