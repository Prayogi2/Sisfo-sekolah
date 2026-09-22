@extends('layouts.app')

@section('title', 'Panel Kahoot - '.$quiz->title)
@section('content')
<div class="container-fluid"><div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">{{ $quiz->title }}</h1><p class="text-muted mb-0">{{ $quiz->classroom->name }} · {{ $quiz->questions->count() }} soal</p></div><a href="{{ route('guru.bank-soal') }}" class="btn btn-outline-secondary">Kembali</a></div>
<x-page-guide>Tekan <strong>Mulai</strong> agar siswa bisa bergabung dan soal pertama tampil. Tekan <strong>Soal Berikutnya</strong> untuk menampilkan jawaban, lalu tekan lagi untuk lanjut ke soal berikutnya — ulangi sampai soal terakhir untuk otomatis mengakhiri sesi & menghitung nilai. Siswa menjawab di device masing-masing.</x-page-guide>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card shadow-sm text-center"><div class="card-body p-5"><div class="display-6 fw-bold" id="phase">{{ ucfirst($quiz->live_phase) }}</div><div class="text-muted mb-4" id="question">Tekan mulai untuk memulai sesi bersama.</div>
<form method="POST" action="{{ route('guru.kuis.live.start', $quiz) }}" class="d-inline">@csrf<button class="btn btn-success btn-lg">Mulai / Ulangi Sesi</button></form>
<form method="POST" action="{{ route('guru.kuis.live.next', $quiz) }}" class="d-inline ms-2">@csrf<button class="btn btn-primary btn-lg">Soal Berikutnya / Tampilkan Jawaban</button></form>
<form method="POST" action="{{ route('guru.kuis.live.finish', $quiz) }}" class="d-inline ms-2">@csrf<button class="btn btn-outline-danger btn-lg">Selesaikan</button></form></div></div></div>
@endsection
