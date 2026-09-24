@extends('layouts.app')

@section('title', 'Prestasi & Data Akademik')

@php
    $academic = $student->academicRecord;
    $academicIdentity = [
        'Nama Siswa' => $student->name,
        'Tempat, Tanggal Lahir' => collect([$student->birth_place, $student->birth_date?->translatedFormat('d F Y')])->filter()->join(', ') ?: '-',
        'Jenis Kelamin' => $student->gender?->label() ?? '-',
        'NIS / NISN' => ($student->nis ?: '-').' / '.($student->nisn ?: '-'),
        'No. Seri Rapor' => $academic?->report_book_serial_number ?: '-',
        'No. Ujian' => $academic?->exam_number ?: '-',
        'No. Seri Ijazah' => $academic?->graduation_certificate_number ?: '-',
    ];
@endphp

@section('content')
<div class="container-fluid"><h4 class="fw-bold mb-1">Prestasi & Perkembangan Akademik</h4><p class="text-muted">Riwayat {{ $student->name }}{{ $student->classroom ? ' · '.$student->classroom->name : '' }}</p><x-page-guide>Semua data di halaman ini diinput oleh admin/wali kelas — halaman ini hanya untuk melihat datamu. Jika ada data akademik yang salah, hubungi admin sekolah.</x-page-guide>
    <div class="card shadow-sm mb-4"><div class="card-header fw-bold text-primary"><i class="bi bi-person-vcard me-2"></i>Data Akademik</div><div class="card-body"><dl class="row mb-0">
        @foreach($academicIdentity as $label => $academicValue)
            <dt class="col-sm-4 col-lg-3 text-muted fw-normal">{{ $label }}</dt><dd class="col-sm-8 col-lg-9 fw-semibold">{{ $academicValue }}</dd>
        @endforeach
    </dl></div></div>
    <div class="row g-4">
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-bold text-primary">Prestasi</div><div class="card-body p-0"><div class="list-group list-group-flush">@forelse($achievements as $achievement)<div class="list-group-item"><div class="fw-semibold"><i class="bi bi-trophy-fill text-warning me-2"></i>{{ $achievement->title }}</div><small class="text-muted">{{ $achievement->category === 'academic' ? 'Akademik' : 'Non-akademik' }}{{ $achievement->event ? ' · '.$achievement->event : '' }}{{ $achievement->level ? ' · '.$achievement->level : '' }}</small>@if($achievement->benefit)<div class="small mt-1">Benefit: {{ $achievement->benefit }}</div>@endif</div>@empty<div class="p-4 text-center text-muted">Belum ada prestasi.</div>@endforelse</div></div></div></div>
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header fw-bold text-danger">Pelanggaran</div><div class="card-body p-0"><div class="list-group list-group-flush">@forelse($violations as $violation)<div class="list-group-item"><div class="d-flex justify-content-between"><span class="fw-semibold"><i class="bi bi-exclamation-octagon-fill text-danger me-2"></i>{{ $violation->title }}</span><span class="badge {{ $violation->severity === 'severe' ? 'bg-danger' : ($violation->severity === 'medium' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ['light' => 'Ringan', 'medium' => 'Sedang', 'severe' => 'Berat'][$violation->severity] }}</span></div><small class="text-muted">{{ $violation->occurred_at->format('d/m/Y') }}</small><div class="small mt-1">{{ $violation->description }}</div>@if($violation->action_taken)<div class="small text-muted mt-1">Pembinaan: {{ $violation->action_taken }}</div>@endif</div>@empty<div class="p-4 text-center text-muted">Belum ada pelanggaran.</div>@endforelse</div></div></div></div>
</div></div>
@endsection
