@extends('layouts.app')

@section('title', 'Edit Nilai Buku Induk')

@use('App\Models\ReportBookGrade')

@php
    $gradeLevels = ReportBookGrade::GRADE_LEVELS;
    $semesters = ReportBookGrade::SEMESTERS;
    // Setelah validasi gagal, tampilkan kembali isian admin (bukan data tersimpan).
    $rows = old('subjects', $summary['subjects']->map(fn (ReportBookGrade $subject) => [
        'name' => $subject->subject_name,
        'scores' => collect(ReportBookGrade::scoreColumns())->mapWithKeys(fn (string $column) => [$column => $subject->{$column}])->all(),
    ])->all());
    $yearValue = fn (int $gradeLevel, string $field) => old("years.{$gradeLevel}.{$field}", $summary['years']->get($gradeLevel)?->{$field});
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="h3 mb-1 fw-bold">Nilai Laporan Hasil Belajar</h1>
            <p class="text-muted mb-0">{{ $student->name }} · NISN {{ $student->nisn }}</p>
        </div>
        <a href="{{ route('admin.buku-induk', ['student' => $student->id, 'tab' => 'akademik']) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    <x-page-guide>Isi <strong>Tahun Ajaran</strong> tiap kelas, lalu nilai rapor tiap mata pelajaran per semester (0–100, boleh desimal). Klik <strong>Tambah Mata Pelajaran</strong> untuk menambah baris, atau ikon tempat sampah untuk menghapus. Jumlah Nilai & Nilai Rata-rata dihitung otomatis. Kolom yang belum ada nilainya boleh dikosongkan.</x-page-guide>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ route('admin.buku-induk.nilai.update', $student) }}" method="POST" id="reportBookForm">
        @csrf
        @method('PUT')

        <div class="card shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center mb-0 report-book-edit">
                        <thead class="table-light">
                            <tr>
                                <th colspan="3" class="text-start">Tahun Ajaran</th>
                                @foreach($gradeLevels as $gradeLevel)
                                    <td colspan="2"><input name="years[{{ $gradeLevel }}][academic_year]" class="form-control form-control-sm text-center" value="{{ $yearValue($gradeLevel, 'academic_year') }}" placeholder="2024/2025" pattern="\d{4}/\d{4}" title="Format YYYY/YYYY, mis. 2024/2025" aria-label="Tahun ajaran kelas {{ $gradeLevel }}"></td>
                                @endforeach
                            </tr>
                            <tr>
                                <th rowspan="2" style="width: 44px;">No.</th>
                                <th rowspan="2" style="min-width: 220px;">Mata Pelajaran</th>
                                <th rowspan="2" style="width: 44px;"><span class="visually-hidden">Hapus</span></th>
                                @foreach($gradeLevels as $gradeLevel)
                                    <th colspan="2">Kelas {{ $gradeLevel }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($gradeLevels as $gradeLevel)
                                    @foreach($semesters as $semester)<th>Sem {{ $semester }}</th>@endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="subjectRows">
                            @foreach($rows as $row)
                                <tr class="subject-row">
                                    <td class="row-number">{{ $loop->iteration }}</td>
                                    <td><input data-field="name" class="form-control form-control-sm" value="{{ $row['name'] ?? '' }}" list="subjectSuggestions" maxlength="100" required aria-label="Nama mata pelajaran"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Hapus mata pelajaran"><i class="bi bi-trash"></i></button></td>
                                    @foreach(ReportBookGrade::scoreColumns() as $column)
                                        <td><input data-field="{{ $column }}" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-center score-input" value="{{ $row['scores'][$column] ?? '' }}" aria-label="Nilai {{ str_replace('_', ' ', $column) }}"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="fw-bold table-light">
                            <tr>
                                <th colspan="3" class="text-start">Jumlah Nilai</th>
                                @foreach(ReportBookGrade::scoreColumns() as $column)<td data-total="{{ $column }}"></td>@endforeach
                            </tr>
                            <tr>
                                <th colspan="3" class="text-start">Nilai Rata-rata</th>
                                @foreach(ReportBookGrade::scoreColumns() as $column)<td data-average="{{ $column }}"></td>@endforeach
                            </tr>
                            <tr>
                                <th colspan="3" class="text-start">Naik ke Kelas</th>
                                @foreach($gradeLevels as $gradeLevel)
                                    <td colspan="2"><input name="years[{{ $gradeLevel }}][promoted_to]" class="form-control form-control-sm text-center" value="{{ $yearValue($gradeLevel, 'promoted_to') }}" maxlength="50" placeholder="{{ $gradeLevel < 6 ? 'Kelas '.($gradeLevel + 1) : 'Lulus' }}" aria-label="Naik ke kelas, kelas {{ $gradeLevel }}"></td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex flex-wrap justify-content-between gap-2">
                <button type="button" class="btn btn-outline-primary" id="addSubjectRow"><i class="bi bi-plus-lg me-1"></i>Tambah Mata Pelajaran</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan Nilai</button>
            </div>
        </div>
    </form>

    <datalist id="subjectSuggestions">
        @foreach($subjectSuggestions as $suggestion)<option value="{{ $suggestion }}">@endforeach
    </datalist>

    <template id="subjectRowTemplate">
        <tr class="subject-row">
            <td class="row-number"></td>
            <td><input data-field="name" class="form-control form-control-sm" list="subjectSuggestions" maxlength="100" required aria-label="Nama mata pelajaran"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row" title="Hapus mata pelajaran"><i class="bi bi-trash"></i></button></td>
            @foreach(ReportBookGrade::scoreColumns() as $column)
                <td><input data-field="{{ $column }}" type="number" step="0.01" min="0" max="100" class="form-control form-control-sm text-center score-input" aria-label="Nilai {{ str_replace('_', ' ', $column) }}"></td>
            @endforeach
        </tr>
    </template>
</div>
@endsection

@push('styles')
<style>
    .report-book-edit .score-input { min-width: 64px; }
    .report-book-edit input[name$="[academic_year]"], .report-book-edit input[name$="[promoted_to]"] { min-width: 110px; }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const rowsBody = document.getElementById('subjectRows');
        const columns = @json(ReportBookGrade::scoreColumns());
        const format = value => Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/0+$/, '').replace('.', ',');

        function renumberRows() {
            rowsBody.querySelectorAll('.subject-row').forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
                // Nama input selalu berurutan 0,1,2… supaya pesan error "baris ke-N" sesuai tampilan.
                row.querySelectorAll('[data-field]').forEach(input => {
                    const field = input.dataset.field;
                    input.name = field === 'name' ? `subjects[${index}][name]` : `subjects[${index}][scores][${field}]`;
                });
            });
        }

        function recalculate() {
            columns.forEach(column => {
                const scores = [...rowsBody.querySelectorAll(`[data-field="${column}"]`)]
                    .map(input => input.value.trim())
                    .filter(value => value !== '' && !Number.isNaN(Number(value)))
                    .map(Number);
                const total = scores.reduce((sum, score) => sum + score, 0);
                document.querySelector(`[data-total="${column}"]`).textContent = scores.length ? format(Math.round(total * 100) / 100) : '';
                document.querySelector(`[data-average="${column}"]`).textContent = scores.length ? format(Math.round(total / scores.length * 100) / 100) : '';
            });
        }

        document.getElementById('addSubjectRow').addEventListener('click', () => {
            rowsBody.appendChild(document.getElementById('subjectRowTemplate').content.cloneNode(true));
            renumberRows();
            rowsBody.lastElementChild.querySelector('[data-field="name"]').focus();
        });

        rowsBody.addEventListener('click', event => {
            const button = event.target.closest('.remove-row');
            if (!button) return;
            button.closest('.subject-row').remove();
            renumberRows();
            recalculate();
        });

        rowsBody.addEventListener('input', event => {
            if (event.target.classList.contains('score-input')) recalculate();
        });

        if (!rowsBody.querySelector('.subject-row')) {
            document.getElementById('addSubjectRow').click();
        }
        renumberRows();
        recalculate();
    })();
</script>
@endpush
