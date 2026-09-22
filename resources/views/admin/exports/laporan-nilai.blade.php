<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Nilai {{ $academicYear }}</title>
    <style>
        body { font: 11px Arial, sans-serif; color: #111; margin: 24px; }
        h1 { margin: 0 0 6px; } p { margin: 0 0 18px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 6px; text-align: center; }
        th { background: #e9ecef; } td.name { text-align: left; }
        .toolbar { margin-bottom: 18px; } @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @unless($pdf ?? false)<div class="toolbar"><button onclick="window.print()">Cetak / Simpan sebagai PDF</button></div>@endunless
    @php
        $activeSubject = $subjects->firstWhere('id', $subjectId);
        $activeClassroom = $classrooms->firstWhere('id', $classroomId);
    @endphp
    <h1>Laporan Rekapitulasi Nilai Siswa</h1>
    <p>{{ $activeSubject?->name ?? '-' }} | {{ $activeClassroom?->name ?? '-' }} | Semester {{ $semester->label() }} {{ $academicYear }}</p>
    <table>
        <thead><tr><th>NISN</th><th>Nama Siswa</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th><th>Nilai Akhir</th><th>Grade</th><th>Status</th></tr></thead>
        <tbody>
            @forelse($students as $student)
                @php
                    $grade = $grades->get($student->id);
                    $finalScore = $grade?->finalScore($weight);
                @endphp
                <tr>
                    <td>{{ $student->nisn }}</td><td class="name">{{ $student->name }}</td>
                    <td>{{ $grade?->assignment_score ?? '-' }}</td><td>{{ $grade?->quiz_score ?? '-' }}</td>
                    <td>{{ $grade?->midterm_score ?? '-' }}</td><td>{{ $grade?->final_score ?? '-' }}</td>
                    <td>{{ $finalScore ?? '-' }}</td>
                    <td>{{ $finalScore === null ? '-' : \App\Models\Grade::letterFor($finalScore) }}</td>
                    <td>{{ $finalScore === null ? 'Belum Dinilai' : ($finalScore >= $passingScore ? 'Lulus' : 'Remedial') }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Belum ada data nilai.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
