@extends('layouts.app')

@section('title', 'Detail Notifikasi')

@php
    $readCount = $recipients->filter(fn ($recipient) => $recipient->isRead())->count();
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <a href="{{ route('admin.notifikasi') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
        <form action="{{ route('admin.notifikasi.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini? Siswa tidak akan melihatnya lagi.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Hapus Notifikasi</button>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <span class="badge bg-{{ $announcement->category->color() }} mb-2"><i class="bi {{ $announcement->category->icon() }} me-1"></i>{{ $announcement->category->label() }}</span>
                    @if ($announcement->isScheduled())
                        <span class="badge bg-warning text-dark mb-2"><i class="bi bi-clock me-1"></i>Terjadwal</span>
                    @endif
                    <h4 class="fw-bold">{{ $announcement->title }}</h4>
                    <p class="mb-3" style="white-space: pre-line;">{{ $announcement->message }}</p>
                    <hr>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted fw-normal">Penerima</dt>
                        <dd class="col-7">{{ $announcement->target === \App\Enums\AnnouncementTarget::Classroom ? 'Kelas '.($announcement->classroom?->name ?? '-') : $announcement->target->label() }}</dd>
                        <dt class="col-5 text-muted fw-normal">{{ $announcement->isScheduled() ? 'Dijadwalkan' : 'Dikirim' }}</dt>
                        <dd class="col-7">{{ $announcement->published_at->translatedFormat('d F Y, H:i') }} WIB</dd>
                        <dt class="col-5 text-muted fw-normal">Pengirim</dt>
                        <dd class="col-7 mb-0">{{ $announcement->creator?->name ?? '-' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">Status Baca</h6>
                    <span class="small"><span class="fw-bold text-success">{{ $readCount }}</span> sudah · <span class="fw-bold text-danger">{{ $recipients->count() - $readCount }}</span> belum</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Siswa</th><th>Kelas</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recipients as $recipient)
                                    <tr>
                                        <td class="fw-semibold">{{ $recipient->student->name }}</td>
                                        <td>{{ $recipient->student->classroom?->name ?? '-' }}</td>
                                        <td>
                                            @if ($recipient->isRead())
                                                <span class="badge bg-success">Dibaca</span>
                                                <small class="text-muted d-block">{{ $recipient->read_at->translatedFormat('d M Y, H:i') }}</small>
                                            @else
                                                <span class="badge bg-secondary">Belum dibaca</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada penerima.</td></tr>
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
