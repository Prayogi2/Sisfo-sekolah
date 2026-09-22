<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Absensi {{ $dateFrom }} - {{ $dateTo }}</title>
    <style>
        body { font: 12px Arial, sans-serif; color: #111; margin: 24px; }
        h1 { margin: 0 0 6px; } p { margin: 0 0 18px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; }
        th { background: #e9ecef; } .toolbar { margin-bottom: 18px; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @unless($pdf ?? false)<div class="toolbar"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>@endunless
    <h1>Laporan Kehadiran Siswa</h1>
    <p>Periode {{ \Illuminate\Support\Carbon::parse($dateFrom)->translatedFormat('d F Y') }} sampai {{ \Illuminate\Support\Carbon::parse($dateTo)->translatedFormat('d F Y') }}</p>
    <table>
        <thead><tr><th>Tanggal</th><th>NISN</th><th>Nama Siswa</th><th>Kelas</th><th>Jam Masuk</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ $attendance->date->format('d/m/Y') }}</td>
                    <td>{{ $attendance->student->nisn }}</td>
                    <td>{{ $attendance->student->name }}</td>
                    <td>{{ $attendance->student->classroom?->name ?? '-' }}</td>
                    <td>{{ $attendance->check_in_at?->format('H:i:s') ?? '-' }}</td>
                    <td>{{ $attendance->status->value }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada data absensi.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
