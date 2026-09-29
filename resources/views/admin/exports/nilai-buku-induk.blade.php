<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nilai Buku Induk - {{ $student->name }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font: 9.5px Arial, sans-serif; color: #111; margin: 0; }
        h1 { font-size: 14px; text-align: center; margin: 0 0 2px; }
        .subtitle { text-align: center; color: #555; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        .report-book-identity { margin-bottom: 8px; }
        .report-book-identity td { border: none; padding: 1px 4px; text-align: left; }
        .report-book-table { margin-bottom: 0; }
        .report-book-table th, .report-book-table td { border: 1px solid #555; padding: 2px 3px; text-align: center; }
        .report-book-table th { background: #e9ecef; }
        .report-book-title { font-size: 11px; font-weight: bold; text-align: center; margin-bottom: 6px; }
        .text-center { text-align: center; }
        .report-book-table tfoot th, .report-book-table tfoot td { font-weight: bold; background: #f6f7f9; }
        .text-start { text-align: left !important; }
        .text-muted { color: #666; }
        .fw-semibold { font-weight: bold; }
    </style>
</head>
<body>
    <h1>PRESTASI SISWA / PERKEMBANGAN AKADEMIK</h1>
    <p class="subtitle">MIS Nurul Falaq</p>

    <x-nilai-buku-induk :student="$student" :summary="$summary" />
</body>
</html>
