{{--
    Template PDF umum untuk unduhan tabel (data guru, data siswa, absensi,
    inventaris, dll.). Data: $title, $subtitle (opsional), $headers, $rows.
--}}
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font: 10px Arial, sans-serif; color: #111; margin: 0; }
        @page { margin: 22px 24px; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
        .header h1 { font-size: 14px; margin: 0; text-transform: uppercase; }
        .header p { margin: 2px 0 0; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #e5e7eb; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { margin-top: 8px; font-size: 9px; color: #666; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>MIS Nurul Falaq{{ ! empty($subtitle) ? ' · '.$subtitle : '' }}</p>
    </div>
    <table>
        <thead><tr><th style="width: 24px;">No</th>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr><td>{{ $loop->iteration }}</td>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) + 1 }}" style="text-align: center; color: #666;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Dicetak {{ now()->translatedFormat('d F Y H:i') }}</div>
</body>
</html>
