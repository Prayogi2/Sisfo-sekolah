@extends('layouts.app')

@section('title', 'Riwayat Absensi')

@section('content')
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800 fw-bold">Riwayat Absensi - {{ $student->name }}</h1>

        <x-page-guide>Riwayat kehadiran 30 hari terakhir, tercatat otomatis dari scan QR di sekolah setiap pagi & pulang.</x-page-guide>

        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">30 Hari Terakhir</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $row)
                                <tr>
                                    <td>{{ $row['date']->translatedFormat('d F Y') }}</td>
                                    <td>{{ $row['date']->translatedFormat('l') }}</td>
                                    @if (! $row['is_school_day'])
                                        <td colspan="3" class="text-center text-muted">Libur Sekolah</td>
                                    @elseif ($row['attendance'])
                                        <td>{{ $row['attendance']->check_in_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                        <td>{{ $row['attendance']->check_out_at?->format('H:i').' WIB' ?? '--:--' }}</td>
                                        <td>
                                            @php
                                                $badge = match ($row['attendance']->status->value) {
                                                    'hadir' => 'bg-success',
                                                    'telat' => 'bg-warning text-dark',
                                                    'izin' => 'bg-info',
                                                    default => 'bg-danger',
                                                };
                                                $label = match ($row['attendance']->status->value) {
                                                    'hadir' => 'Hadir',
                                                    'telat' => 'Telat',
                                                    'izin' => 'Izin',
                                                    default => 'Alpa',
                                                };
                                            @endphp
                                            <span class="badge {{ $badge }}">{{ $label }}</span>
                                        </td>
                                    @else
                                        <td colspan="2" class="text-muted">--:--</td>
                                        <td><span class="badge bg-danger">Alpa</span></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
