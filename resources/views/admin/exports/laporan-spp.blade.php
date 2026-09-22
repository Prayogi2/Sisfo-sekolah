<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan SPP {{ $period }}</title>
    <style>
        body { font: 12px Arial, sans-serif; color: #111; margin: 24px; }
        h1 { margin: 0 0 6px; } p { margin: 0 0 18px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; }
        th { background: #e9ecef; } .number { text-align: right; }
        .toolbar { margin-bottom: 18px; } @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @unless($pdf ?? false)<div class="toolbar"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>@endunless
    <h1>Laporan Pembayaran SPP</h1>
    <p>Periode {{ \Illuminate\Support\Carbon::parse($period)->translatedFormat('F Y') }}</p>
    <table>
        <thead><tr><th>NISN</th><th>Nama Siswa</th><th>Kelas</th><th>Tagihan</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($bills as $bill)
                <tr>
                    <td>{{ $bill->student->nisn }}</td>
                    <td>{{ $bill->student->name }}</td>
                    <td>{{ $bill->student->classroom?->name ?? '-' }}</td>
                    <td class="number">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($bill->paidAmount(), 0, ',', '.') }}</td>
                    <td class="number">Rp {{ number_format($bill->remainingAmount(), 0, ',', '.') }}</td>
                    <td>{{ $bill->status()->value }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada tagihan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
