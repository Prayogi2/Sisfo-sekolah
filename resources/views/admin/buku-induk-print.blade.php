<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Induk Siswa - NURFA.ID</title>
    <x-favicon />
    <style>
        body { margin: 0; color: #1f2937; font: 14px/1.5 Arial, sans-serif; background: #e5e7eb; }
        .toolbar { position: sticky; top: 0; z-index: 2; padding: 12px 20px; background: #0d6efd; text-align: right; }
        .toolbar button { border: 0; border-radius: 6px; padding: 9px 16px; color: #0d6efd; background: #fff; font-weight: 700; cursor: pointer; }
        .document { max-width: 980px; margin: 24px auto; padding: 36px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: start; border-bottom: 2px solid #111827; padding-bottom: 14px; margin-bottom: 24px; }
        h1 { margin: 0 0 4px; font-size: 24px; } h2 { margin: 0 0 14px; font-size: 18px; }
        .muted { color: #6b7280; } .section { margin-top: 24px; page-break-inside: avoid; }
        .section-title { padding: 8px 10px; color: #fff; background: #1d4ed8; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 8px 10px; border: 1px solid #d1d5db; vertical-align: top; text-align: left; } th { width: 24%; background: #f3f4f6; }
        .student { page-break-before: always; } .student:first-of-type { page-break-before: auto; }
        .student-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .student-photo { width: 90px; height: 112px; object-fit: cover; border: 1px solid #d1d5db; }
        .student-photo-placeholder { width: 90px; height: 112px; border: 1px solid #d1d5db; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; text-align: center; }
        h3 { margin: 16px 0 8px; font-size: 14px; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #d1d5db; color: #6b7280; font-size: 12px; }
        @media print { body { background: #fff; } .toolbar { display: none; } .document { max-width: none; margin: 0; padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button></div>
    <main class="document">
        <div class="header">
            <div><h1>BUKU INDUK SISWA</h1><div>MIS Nurul Falaq · NURFA.ID</div></div>
            <div class="muted">Dicetak: {{ now()->translatedFormat('d F Y H:i') }}</div>
        </div>

        @forelse($students as $student)
            @php($profile = $student->profile)
            @php($academic = $student->academicRecord)
            <section class="student">
                <div class="student-heading">
                    <h2>{{ $student->name }}</h2>
                    @if($profile?->photo_path)
                        <img class="student-photo" src="{{ Storage::url($profile->photo_path) }}" alt="Foto {{ $student->name }}">
                    @else
                        <div class="student-photo-placeholder">Belum ada foto</div>
                    @endif
                </div>
                <div class="section"><div class="section-title">Identitas Peserta Didik</div><table>
                    <tr><th>NISN</th><td>{{ $student->nisn ?: '-' }}</td><th>NIS</th><td>{{ $student->nis ?: '-' }}</td></tr>
                    <tr><th>Jenis Kelamin</th><td>{{ $student->gender?->label() ?: '-' }}</td><th>Status</th><td>{{ $student->status?->label() ?: '-' }}</td></tr>
                    <tr><th>Tempat, Tanggal Lahir</th><td>{{ $student->birth_place ?: '-' }}, {{ $student->birth_date?->translatedFormat('d F Y') ?: '-' }}</td><th>Kelas</th><td>{{ $student->classroom?->name ?: '-' }}</td></tr>
                    <tr><th>Alamat</th><td colspan="3">{{ $student->address ?: '-' }}</td></tr>
                    <tr><th>Nama Orang Tua</th><td>{{ $student->parent_name ?: '-' }}</td><th>No. Telepon</th><td>{{ $student->parent_phone ?: '-' }}</td></tr>
                    <tr><th>Bertempat Tinggal di</th><td>{{ $profile?->residence_type?->label() ?: '-' }}</td><th>Transportasi ke Sekolah</th><td>{{ $profile?->transportation?->label() ?: '-' }}</td></tr>
                    <tr><th>Jarak Tempuh</th><td>{{ $profile?->distance_km !== null ? $profile->distance_km.' km' : '-' }}</td><th>Durasi Tempuh</th><td>{{ $profile?->travel_duration_minutes !== null ? $profile->travel_duration_minutes.' menit' : '-' }}</td></tr>
                </table></div>
                <div class="section"><div class="section-title">Profil Lengkap</div><table>
                    <tr><th>Nama Panggilan</th><td>{{ $profile?->nickname ?: '-' }}</td><th>NIK</th><td>{{ $profile?->nik ?: '-' }}</td></tr>
                    <tr><th>Agama</th><td>{{ $profile?->religion?->label() ?: '-' }}</td><th>Status Keluarga</th><td>{{ $profile?->family_status?->label() ?: '-' }}</td></tr>
                    <tr><th>Golongan Darah</th><td>{{ $profile?->blood_type?->value ?: '-' }}</td><th>Data Fisik</th><td>{{ $profile?->height_cm ?: '-' }} cm / {{ $profile?->weight_kg ?: '-' }} kg · Anak ke-{{ $profile?->birth_order ?: '-' }} dari {{ $profile?->siblings_count ?: '-' }} saudara</td></tr>
                    <tr><th>Alamat Detail</th><td colspan="3">{{ collect([$profile?->street_address, $profile?->hamlet, $profile?->village, $profile?->district, $profile?->regency, $profile?->province, $profile?->postal_code])->filter()->join(', ') ?: '-' }}</td></tr>
                </table></div>
                <div class="section"><div class="section-title">Riwayat Pendidikan</div>
                    <h3>A. Pendidikan Sebelumnya</h3>
                    <table>
                        <tr><th>Nama TK / PAUD</th><td>{{ $academic?->kindergarten_origin ?: '-' }}</td><th>Alamat</th><td>{{ $academic?->kindergarten_address ?: '-' }}</td></tr>
                        <tr><th>NPSN / NSM</th><td colspan="3">{{ $academic?->kindergarten_npsn ?: '-' }}</td></tr>
                    </table>
                    <h3>B. Status Peserta Didik</h3>
                    <table>
                        <tr><th>Status Peserta Didik</th><td>{{ $academic?->entry_status ?: '-' }}</td><th>Tahun Masuk</th><td>{{ $academic?->entry_year ?: '-' }}</td></tr>
                        <tr><th>Tanggal Masuk</th><td>{{ $academic?->entry_date?->translatedFormat('d F Y') ?: '-' }}</td><th>Masuk ke Kelas</th><td>{{ $academic?->entry_classroom ?: '-' }}</td></tr>
                    </table>
                    <h3>C. Lulus</h3>
                    <table>
                        <tr><th>Tahun Lulus</th><td>{{ $academic?->graduation_year ?: '-' }}</td><th>Tanggal Lulus</th><td>{{ $academic?->graduation_certificate_date?->translatedFormat('d F Y') ?: '-' }}</td></tr>
                        <tr><th>No. Seri Ijazah</th><td>{{ $academic?->graduation_certificate_number ?: '-' }}</td><th>No. Seri SKL</th><td>{{ $academic?->graduation_skl_number ?: '-' }}</td></tr>
                        <tr><th>Melanjutkan ke Sekolah</th><td colspan="3">{{ $academic?->continued_to ?: '-' }}</td></tr>
                        <tr><th>Alamat Sekolah</th><td>Kec. {{ $academic?->continued_to_district ?: '-' }}</td><th>Provinsi</th><td>{{ $academic?->continued_to_province ?: '-' }}</td></tr>
                        <tr><th>Catatan</th><td colspan="3">{{ $academic?->graduation_notes ?: '-' }}</td></tr>
                    </table>
                    <h3>D. Meninggalkan Sekolah</h3>
                    <table>
                        <tr><th>No. Surat</th><td>{{ $academic?->transfer_out_letter_number ?: '-' }}</td><th>Tanggal</th><td>{{ $academic?->transfer_out_date?->translatedFormat('d F Y') ?: '-' }}</td></tr>
                        <tr><th>Kelas yang Ditinggalkan</th><td>{{ $academic?->transfer_out_classroom ?: '-' }}</td><th>NSM</th><td>{{ $academic?->transfer_out_nsm ?: '-' }}</td></tr>
                        <tr><th>Alasan Pindah</th><td colspan="3">{{ $academic?->transfer_out_reason ?: '-' }}</td></tr>
                        <tr><th>NPSN Sekolah Tujuan</th><td colspan="3">{{ $academic?->transfer_out_npsn ?: '-' }}</td></tr>
                        <tr><th>Alamat Sekolah Tujuan</th><td>Desa {{ $academic?->transfer_out_village ?: '-' }}</td><th>Kec. / Prov.</th><td>{{ $academic?->transfer_out_district ?: '-' }} / {{ $academic?->transfer_out_province ?: '-' }}</td></tr>
                    </table>
                    <h3>E. Putus Sekolah / Dropout</h3>
                    <table>
                        <tr><th>Hari, Tanggal</th><td>{{ $academic?->exit_date?->translatedFormat('l, d F Y') ?: '-' }}</td><th>Kelas</th><td>{{ $academic?->exit_classroom ?: '-' }}</td></tr>
                        <tr><th>Alasan</th><td colspan="3">{{ $academic?->exit_reason ?: '-' }}</td></tr>
                    </table>
                </div>
                <div class="section"><div class="section-title">Perkembangan Akademik & Rapor</div>
                    @forelse(($academicReports[$student->id] ?? collect()) as $report)
                        <h3>{{ $report['academic_year'] }} · Semester {{ $report['semester']->label() }} · Rata-rata {{ $report['average'] ?? '-' }} · Peringkat {{ $report['rank'] ? $report['rank'].' / '.$report['rank_total'] : '-' }}</h3>
                        <table><tr><th>Mapel</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th><th>Nilai Akhir</th><th>Grade</th></tr>
                        @foreach($report['grades'] as $grade)<tr><td>{{ $grade['subject'] }}</td><td>{{ $grade['assignment'] ?? '-' }}</td><td>{{ $grade['quiz'] ?? '-' }}</td><td>{{ $grade['midterm'] ?? '-' }}</td><td>{{ $grade['final'] ?? '-' }}</td><td>{{ $grade['final_score'] ?? '-' }}</td><td>{{ $grade['letter'] }}</td></tr>@endforeach
                        </table>
                    @empty
                        <p class="muted">Belum ada nilai.</p>
                    @endforelse
                    @forelse($student->progressNotes->sortByDesc('academic_year') as $note)<p><strong>{{ $note->academic_year }} · {{ $note->semester->label() }} · {{ $note->promotion_status->label() }}</strong><br>{{ $note->notes ?: '-' }}</p>@empty<p class="muted">Belum ada catatan perkembangan.</p>@endforelse
                </div>
                <div class="section"><div class="section-title">Orang Tua / Wali Terhubung</div><table>
                    <tr><th>Nama</th><th>Hubungan</th><th>Tempat, Tanggal Lahir</th><th>Agama</th><th>No. Telepon</th><th>Pekerjaan</th></tr>
                    @forelse($student->guardians as $guardian)<tr><td>{{ $guardian->name }}</td><td>{{ $guardian->relationship?->label() ?: '-' }}</td><td>{{ $guardian->birth_place ?: '-' }}, {{ $guardian->birth_date?->translatedFormat('d F Y') ?: '-' }}</td><td>{{ $guardian->religion?->label() ?: '-' }}</td><td>{{ $guardian->phone ?: '-' }}</td><td>{{ $guardian->occupation ?: '-' }}</td></tr>@empty<tr><td colspan="6">Belum ada wali terhubung.</td></tr>@endforelse
                </table></div>
                <div class="footer">Dokumen Buku Induk Siswa · {{ $student->name }}</div>
            </section>
        @empty
            <p class="muted">Belum ada data siswa.</p>
        @endforelse
    </main>
</body>
</html>
