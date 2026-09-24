<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu QR - {{ $student->name }}</title>
    <x-favicon />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #eef2f7; font-family: Arial, sans-serif; color: #172033; }
        .actions { position: fixed; top: 20px; right: 20px; display: flex; gap: 8px; }
        .actions button, .actions a { border: 0; border-radius: 8px; padding: 10px 14px; font-size: 14px; text-decoration: none; cursor: pointer; }
        .print-button { color: #fff; background: #0d6efd; }
        .download-button { color: #fff; background: #198754; }
        .back-button { color: #172033; background: #fff; }
        .student-card { width: 85.6mm; height: 54mm; padding: 5mm; border-radius: 4mm; color: #fff; background-color: #0d6efd; background-image: linear-gradient(135deg, #0d6efd, #073b9a); -webkit-print-color-adjust: exact; print-color-adjust: exact; box-shadow: 0 10px 30px rgba(20, 40, 80, .25); position: relative; overflow: hidden; }
        .student-card::after { content: ''; position: absolute; width: 45mm; height: 45mm; right: -18mm; bottom: -22mm; border: 8mm solid rgba(255,255,255,.1); border-radius: 50%; }
        .school { font-size: 9pt; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; }
        .school-subtitle { font-size: 6.5pt; opacity: .8; margin-top: 1mm; }
        .card-content { display: flex; align-items: center; gap: 4mm; margin-top: 5mm; position: relative; z-index: 1; }
        .qr { width: 28mm; height: 28mm; padding: 1.5mm; background: #fff; border-radius: 2mm; flex: 0 0 auto; }
        .student-name { font-size: 11pt; font-weight: 700; line-height: 1.15; margin-bottom: 3mm; word-break: break-word; }
        .student-data { font-size: 7.5pt; line-height: 1.65; }
        .student-data strong { font-weight: 700; }
        .footer { position: absolute; left: 5mm; bottom: 3mm; font-size: 5.5pt; opacity: .75; z-index: 1; }
        @media print {
            @page { size: 85.6mm 54mm; margin: 0; }
            body { min-height: auto; background: #fff; }
            .actions { display: none; }
            .student-card { box-shadow: none; border-radius: 0; background-color: #0d6efd !important; background-image: linear-gradient(135deg, #0d6efd, #073b9a) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .student-card::after { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a class="back-button" href="{{ route('admin.data-siswa') }}">Kembali</a>
        <button class="print-button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        <button class="download-button" type="button" id="downloadQrBtn">Unduh PNG QR</button>
    </div>

    <article class="student-card" aria-label="Kartu pelajar digital {{ $student->name }}">
        <div class="school">NURFA.ID</div>
        <div class="school-subtitle">Digital Platform MIS Nurul Falaq</div>
        <div class="card-content">
            <img class="qr" id="qrImage" src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data={{ urlencode($student->qr_token) }}" alt="QR Code {{ $student->name }}">
            <div>
                <div class="student-name">{{ $student->name }}</div>
                <div class="student-data"><strong>NIS</strong> {{ $student->nis ?: '-' }}<br><strong>NISN</strong> {{ $student->nisn ?: '-' }}<br><strong>Kelas</strong> {{ $student->classroom?->name ?: '-' }}</div>
            </div>
        </div>
        <div class="footer">Gunakan QR ini untuk presensi siswa</div>
    </article>

    <script>
        document.getElementById('downloadQrBtn').addEventListener('click', async function () {
            const button = this;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Menyiapkan...';

            try {
                const response = await fetch(document.getElementById('qrImage').src);
                const blob = await response.blob();
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'qr-{{ \Illuminate\Support\Str::slug($student->name) ?: 'siswa' }}.png';
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (error) {
                alert('Gagal mengunduh QR. Silakan coba lagi.');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    </script>
</body>
</html>
