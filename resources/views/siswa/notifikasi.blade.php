@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <h1 class="h3 mb-0 fw-bold">Notifikasi</h1>
        @if (($studentUnreadNotificationCount ?? 0) > 0)
            <form action="{{ route('siswa.notifikasi.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all me-1"></i> Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <x-page-guide>Pengumuman dan informasi dari sekolah. Pesan yang belum dibaca ditandai warna biru — klik untuk membaca isi lengkapnya.</x-page-guide>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm overflow-hidden">
        @forelse ($notifications as $notification)
            @php($announcement = $notification->announcement)
            <a href="{{ route('siswa.notifikasi.show', $announcement) }}" class="notif-item {{ $notification->isRead() ? '' : 'is-unread' }} py-3">
                <i class="bi {{ $announcement->category->icon() }} text-{{ $announcement->category->color() }} notif-item-icon"></i>
                <div class="notif-item-body">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="notif-item-title">{{ $announcement->title }}</span>
                        <small class="text-muted flex-shrink-0">{{ $announcement->published_at->locale('id')->diffForHumans() }}</small>
                    </div>
                    <div class="notif-item-snippet">{{ $announcement->message }}</div>
                    <span class="badge bg-{{ $announcement->category->color() }}-subtle text-{{ $announcement->category->color() }} mt-1">{{ $announcement->category->label() }}</span>
                </div>
                @unless ($notification->isRead())<span class="notif-item-dot" aria-label="Belum dibaca"></span>@endunless
            </a>
        @empty
            <div class="text-center text-muted py-5"><i class="bi bi-bell-slash d-block fs-1 mb-2"></i>Belum ada notifikasi.</div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-3">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
