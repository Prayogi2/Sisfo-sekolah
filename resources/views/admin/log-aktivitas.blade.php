@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Log Aktivitas</h1>
        <span class="badge bg-light text-dark border">{{ number_format($logs->total(), 0, ',', '.') }} aktivitas tercatat</span>
    </div>

    <x-page-guide>Setiap aksi yang mengubah data tercatat otomatis di sini — siapa pelakunya, apa yang diubah, kapan, dan dari alamat IP mana. Membuka halaman tidak dicatat, begitu juga jawaban kuis per soal. Log hanya bisa dibaca, tidak bisa diubah atau dihapus dari halaman ini.</x-page-guide>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.log-aktivitas') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted" for="search">Cari</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-primary"></i></span>
                        <input type="text" id="search" name="search" value="{{ $filters['search'] }}" class="form-control border-start-0 ps-0" placeholder="Nama, aktivitas, objek, atau IP...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted" for="role">Peran</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted" for="user_id">Pengguna</label>
                    <select id="user_id" name="user_id" class="form-select">
                        <option value="">Semua pengguna</option>
                        @foreach ($actors as $actor)
                            <option value="{{ $actor->id }}" @selected($filters['user_id'] === $actor->id)>{{ $actor->name }} ({{ $actor->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small text-muted" for="date_from">Dari tanggal</label>
                    <input type="date" id="date_from" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
                </div>
                <div class="col-md-3 col-6">
                    <label class="form-label small text-muted" for="date_to">Sampai tanggal</label>
                    <input type="date" id="date_to" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="only_rejected" name="only_rejected" value="1" @checked($filters['only_rejected'])>
                        <label class="form-check-label small" for="only_rejected">Hanya yang ditolak/gagal</label>
                    </div>
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Terapkan</button>
                    <a href="{{ route('admin.log-aktivitas') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Riwayat Aktivitas</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Pelaku</th>
                            <th>Aktivitas</th>
                            <th>Objek</th>
                            <th>Hasil</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="text-nowrap small">
                                    {{ $log->created_at->translatedFormat('d M Y') }}<br>
                                    <span class="text-muted">{{ $log->created_at->format('H:i:s') }} WIB</span>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $log->user_name ?? 'Tanpa akun' }}</span>
                                    @if ($log->user_role)
                                        <br><span class="badge bg-light text-dark border">{{ $roles[$log->user_role] ?? $log->user_role }}</span>
                                    @endif
                                    @if ($log->impersonator)
                                        <br><span class="small text-warning-emphasis"><i class="bi bi-person-badge me-1"></i>via {{ $log->impersonator->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $log->description }}
                                    <br><code class="small text-muted">{{ $log->action }}</code>
                                </td>
                                <td class="small">{{ $log->subject_label ?? '—' }}</td>
                                <td>
                                    @if ($log->isInvalid())
                                        <span class="badge bg-secondary" title="Isian belum benar, data tidak tersimpan">Gagal validasi</span>
                                    @elseif ($log->isRejected())
                                        <span class="badge bg-warning text-dark" title="Aksi ditolak">Ditolak ({{ $log->status_code }})</span>
                                    @elseif ($log->isFailed())
                                        <span class="badge bg-danger" title="Gagal di server">Gagal ({{ $log->status_code }})</span>
                                    @else
                                        <span class="badge bg-success-subtle text-success-emphasis">Berhasil</span>
                                    @endif
                                </td>
                                <td class="small text-muted text-nowrap">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas yang cocok dengan filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer bg-white">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
