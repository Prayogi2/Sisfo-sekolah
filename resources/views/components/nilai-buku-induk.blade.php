{{--
    Format form fisik buku induk: identitas siswa, lalu tabel "Nilai Laporan
    Hasil Belajar Peserta Didik" (Kelas 1–3 dan Kelas 4–6). Dipakai halaman
    Buku Induk admin & PDF, jadi hanya markup tabel biasa + class Bootstrap
    yang aman diabaikan oleh dompdf.
--}}
@props(['student', 'summary'])

@php
    $academic = $student->academicRecord;
    $identity = [
        'Nama Siswa' => $student->name,
        'Tempat, Tanggal Lahir' => collect([$student->birth_place, $student->birth_date?->translatedFormat('d F Y')])->filter()->join(', ') ?: '-',
        'Jenis Kelamin' => $student->gender?->label() ?? '-',
        'NIS / NISN' => ($student->nis ?: '-').' / '.($student->nisn ?: '-'),
        'No. Seri Rapor' => $academic?->report_book_serial_number ?: '-',
        'No. Seri Ijazah' => $academic?->graduation_certificate_number ?: '-',
        'No. Ujian' => $academic?->exam_number ?: '-',
    ];
    $score = fn (?float $value) => \App\Services\ReportBook::formatScore($value);
    $column = fn (int $gradeLevel, int $semester) => \App\Models\ReportBookGrade::scoreColumn($gradeLevel, $semester);
@endphp

<table class="table table-sm table-borderless report-book-identity mb-4">
    @foreach($identity as $label => $value)
        <tr><td class="text-muted" style="width: 190px;">{{ $label }}</td><td style="width: 10px;">:</td><td class="fw-semibold">{{ $value }}</td></tr>
    @endforeach
</table>

@foreach(\App\Services\ReportBook::GRADE_GROUPS as $gradeLevels)
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle text-center report-book-table mb-0">
            <thead>
                <tr><th colspan="{{ 2 + count($gradeLevels) * 2 }}" class="report-book-title">NILAI LAPORAN HASIL BELAJAR PESERTA DIDIK</th></tr>
                <tr>
                    <th colspan="2" class="text-start">Tahun Ajaran</th>
                    @foreach($gradeLevels as $gradeLevel)
                        <td colspan="2">{{ $summary['years']->get($gradeLevel)?->academic_year ?? '' }}</td>
                    @endforeach
                </tr>
                <tr>
                    <th rowspan="2" style="width: 40px;">No.</th>
                    <th rowspan="2">Mata Pelajaran</th>
                    @foreach($gradeLevels as $gradeLevel)
                        <th colspan="2">Kelas {{ $gradeLevel }}</th>
                    @endforeach
                </tr>
                <tr>
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
@endforeach
