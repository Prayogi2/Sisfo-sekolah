<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Nilai Buku Induk - {{ $student->name }}</title>
    <style>
        body { font: 10.5px Arial, sans-serif; color: #111; margin: 20px; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; }
        .subtitle { text-align: center; color: #555; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        .report-book-identity { margin-bottom: 14px; }
        .report-book-identity td { border: none; padding: 2px 4px; text-align: left; }
        .report-book-table { margin-bottom: 16px; page-break-inside: avoid; }
        .report-book-table th, .report-book-table td { border: 1px solid #555; padding: 4px; text-align: center; }
        .report-book-table th { background: #e9ecef; }
        .report-book-table .report-book-title { background: #d7e3fc; font-size: 11px; }
        .report-book-table tfoot th, .report-book-table tfoot td { font-weight: bold; background: #f6f7f9; }
        .text-start { text-align: left !important; }
        .text-muted { color: #666; }
        .fw-semibold { font-weight: bold; }
    </style>
</head>
<body>
    <h1>BUKU INDUK PESERTA DIDIK</h1>
    <p class="subtitle">MIS Nurul Falaq</p>

    <x-nilai-buku-induk :student="$student" :summary="$summary" />
</body>
</html>
