{{--
    Format form fisik buku induk: identitas siswa, lalu tabel "Nilai Laporan
    Hasil Belajar Peserta Didik" Kelas 1–6 dalam satu tabel. Dipakai halaman
    Buku Induk admin & PDF, jadi hanya markup tabel biasa + class Bootstrap
    yang aman diabaikan oleh dompdf.
--}}
@props(['student', 'summary'])

@php
    $academic = $student->academicRecord;
    // Susunan form fisik: identitas diri di kiri, nomor dokumen di kanan.
    $identityLeft = [
        'Nama Siswa' => $student->name,
        'Tempat, Tanggal Lahir' => collect([$student->birth_place, $student->birth_date?->translatedFormat('d F Y')])->filter()->join(', ') ?: '-',
        'Jenis Kelamin' => $student->gender?->label() ?? '-',
        'NIS / NISN' => ($student->nis ?: '-').' / '.($student->nisn ?: '-'),
    ];
    $identityRight = [
        'No. Seri Rapor' => $academic?->report_book_serial_number ?: '-',
        'No. Seri Ijazah' => $academic?->graduation_certificate_number ?: '-',
        'No. Ujian' => $academic?->exam_number ?: '-',
    ];
    $score = fn (?float $value) => \App\Services\ReportBook::formatScore($value);
    $column = fn (int $gradeLevel, int $semester) => \App\Models\ReportBookGrade::scoreColumn($gradeLevel, $semester);
@endphp

<table class="table table-sm table-borderless report-book-identity mb-3">
    @foreach(range(0, count($identityLeft) - 1) as $index)
        @php
            $leftLabel = array_keys($identityLeft)[$index];
            $rightLabel = array_keys($identityRight)[$index] ?? null;
        @endphp
        <tr>
            <td class="text-muted" style="width: 22%;">{{ $leftLabel }}</td><td style="width: 10px;">:</td><td class="fw-semibold" style="width: 33%;">{{ $identityLeft[$leftLabel] }}</td>
            <td class="text-muted" style="width: 18%;">{{ $rightLabel }}</td><td style="width: 10px;">{{ $rightLabel ? ':' : '' }}</td><td class="fw-semibold">{{ $rightLabel ? $identityRight[$rightLabel] : '' }}</td>
        </tr>
    @endforeach
</table>

<div class="report-book-title text-center fw-bold mb-3">NILAI LAPORAN HASIL BELAJAR<br>PESERTA DIDIK</div>

@php
    $gradeLevels = \App\Models\ReportBookGrade::GRADE_LEVELS;
@endphp

<div class="table-responsive mb-4">
    <table class="table table-bordered table-sm align-middle text-center report-book-table mb-0">
        <thead>
            <tr>
                <th rowspan="3" style="width: 40px;">No.</th>
                <th class="text-start">Tahun Ajaran</th>
                @foreach($gradeLevels as $gradeLevel)
                    <td colspan="2">{{ $summary['years']->get($gradeLevel)?->academic_year ?? '' }}</td>
                @endforeach
            </tr>
            <tr>
                <th class="text-start">Kelas</th>
                @foreach($gradeLevels as $gradeLevel)
                    <th colspan="2">{{ $gradeLevel }}</th>
                @endforeach
            </tr>
            <tr>
                <th class="text-start">Mata Pelajaran</th>
                @foreach($gradeLevels as $gradeLevel)
                    <th style="width: 60px;">Sem 1</th><th style="width: 60px;">Sem 2</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($summary['subjects'] as $subject)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-start">{{ $subject->subject_name }}</td>
                    @foreach($gradeLevels as $gradeLevel)
                        <td>{{ $score($subject->{$column($gradeLevel, 1)}) }}</td>
                        <td>{{ $score($subject->{$column($gradeLevel, 2)}) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 2 + count($gradeLevels) * 2 }}" class="text-muted py-3">Belum ada nilai mata pelajaran.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="fw-bold">
            <tr>
                <th colspan="2" class="text-start">Jumlah Nilai</th>
                @foreach($gradeLevels as $gradeLevel)
                    <td>{{ $score($summary['totals'][$column($gradeLevel, 1)]) }}</td>
                    <td>{{ $score($summary['totals'][$column($gradeLevel, 2)]) }}</td>
                @endforeach
            </tr>
            <tr>
                <th colspan="2" class="text-start">Nilai Rata-rata</th>
                @foreach($gradeLevels as $gradeLevel)
                    <td>{{ $score($summary['averages'][$column($gradeLevel, 1)]) }}</td>
                    <td>{{ $score($summary['averages'][$column($gradeLevel, 2)]) }}</td>
                @endforeach
            </tr>
            <tr>
                <th colspan="2" class="text-start">Naik ke Kelas</th>
                @foreach($gradeLevels as $gradeLevel)
                    <td colspan="2">{{ $summary['years']->get($gradeLevel)?->promoted_to ?? '' }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>
</div>
