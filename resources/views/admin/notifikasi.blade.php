@extends('layouts.app')

@section('title', 'Kirim Notifikasi Siswa')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">Kirim Notifikasi Siswa</h1>
    </div>

    <x-page-guide>Pilih penerima (semua siswa, satu kelas, atau siswa tertentu), tulis judul & isi pesan, lalu klik <strong>Kirim</strong>. Pesan muncul di ikon lonceng siswa. Isi <strong>Waktu Kirim</strong> untuk menjadwalkan — kosongkan untuk kirim sekarang.</x-page-guide>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card shadow-sm">
                <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary"><i class="bi bi-megaphone-fill me-1"></i> Notifikasi Baru</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.notifikasi.store') }}" method="POST" id="announcementForm">
                        @csrf

                        <label class="form-label fw-semibold">Kirim ke</label>
                        <div class="btn-group w-100 mb-3" role="group">
                            @foreach ($targets as $target)
                                <input type="radio" class="btn-check" name="target" id="target-{{ $target->value }}" value="{{ $target->value }}" @checked(old('target', 'semua') === $target->value)>
                                <label class="btn btn-outline-primary" for="target-{{ $target->value }}">{{ $target->label() }}</label>
                            @endforeach
                        </div>

                        <div class="mb-3" data-target-field="kelas">
                            <label for="classroom_id" class="form-label fw-semibold">Kelas</label>
                            <select name="classroom_id" id="classroom_id" class="form-select">
                                <option value="">— Pilih kelas —</option>
                                @foreach ($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((int) old('classroom_id') === $classroom->id)>{{ $classroom->name }} ({{ $classroom->students_count }} siswa)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" data-target-field="siswa">
                            <label for="studentSearch" class="form-label fw-semibold">Siswa <span class="text-muted fw-normal small" id="selectedStudentCount"></span></label>
                            <input type="search" id="studentSearch" class="form-control mb-2" placeholder="Cari nama atau NIS...">
                            <div class="border rounded student-picker">
                                @foreach ($students as $student)
                                    <label class="d-flex align-items-center gap-2 px-3 py-2 border-bottom student-option" data-search="{{ strtolower($student->name.' '.$student->nis) }}">
                                        <input type="checkbox" class="form-check-input mt-0" name="student_ids[]" value="{{ $student->id }}" @checked(in_array($student->id, old('student_ids', [])))>
                                        <span class="flex-grow-1">{{ $student->name }}</span>
                                        <small class="text-muted">{{ $student->nis }}{{ $student->classroom ? ' · '.$student->classroom->name : '' }}</small>
                                    </label>
                                @endforeach
                                <div class="text-center text-muted py-3" id="noStudentMatch" hidden>Tidak ada siswa yang cocok.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="category" class="form-label fw-semibold">Kategori</label>
                            <select name="category" id="category" class="form-select">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Judul</label>
                            <input type="text" name="title" id="title" class="form-control" maxlength="150" value="{{ old('title') }}" placeholder="Contoh: Libur Hari Raya" required>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label fw-semibold">Isi Pesan</label>
                            <textarea name="message" id="message" class="form-control" rows="5" maxlength="5000" placeholder="Tulis pengumuman untuk siswa..." required>{{ old('message') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="published_at" class="form-label fw-semibold">Waktu Kirim <span class="text-muted fw-normal small">(opsional)</span></label>
                            <input type="datetime-local" name="published_at" id="published_at" class="form-control" value="{{ old('published_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                            <div class="form-text">Kosongkan untuk mengirim sekarang.</div>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" name="send_whatsapp" value="1" class="form-check-input" id="send_whatsapp" @checked(old('send_whatsapp'))>
                            <label class="form-check-label" for="send_whatsapp"><i class="bi bi-whatsapp text-success me-1"></i>Kirim juga ke WhatsApp orang tua</label>
                            <div class="form-text">Butuh nomor HP orang tua terisi di data siswa. Tidak berlaku untuk notifikasi yang dijadwalkan.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send-fill me-1"></i> Kirim Notifikasi</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card shadow-sm">
                <div class="card-header py-3 bg-white"><h6 class="m-0 fw-bold text-primary">Riwayat Notifikasi</h6></div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @forelse ($announcements as $announcement)
                            @php($readPercentage = $announcement->recipients_count > 0 ? round($announcement->read_count / $announcement->recipients_count * 100) : 0)
                            <a href="{{ route('admin.notifikasi.show', $announcement) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div style="min-width: 0;">
                                        <span class="badge bg-{{ $announcement->category->color() }} mb-1">{{ $announcement->category->label() }}</span>
                                        @if ($announcement->isScheduled())
                                            <span class="badge bg-warning text-dark mb-1"><i class="bi bi-clock me-1"></i>Terjadwal</span>
                                        @endif
                                        <div class="fw-bold text-dark">{{ $announcement->title }}</div>
                                        <small class="text-muted">
                                            {{ $announcement->target === \App\Enums\AnnouncementTarget::Classroom ? 'Kelas '.($announcement->classroom?->name ?? '-') : $announcement->target->label() }}
                                            · {{ $announcement->published_at->translatedFormat('d M Y, H:i') }} WIB
                                        </small>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="fw-bold">{{ $announcement->read_count }}/{{ $announcement->recipients_count }}</div>
                                        <small class="text-muted">sudah baca</small>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 6px;" role="progressbar" aria-label="Persentase dibaca" aria-valuenow="{{ $readPercentage }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-success" style="width: {{ $readPercentage }}%"></div>
                                </div>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted py-5"><i class="bi bi-megaphone d-block fs-2 mb-2"></i>Belum ada notifikasi yang dikirim.</div>
                        @endforelse
                    </div>
                </div>
                @if ($announcements->hasPages())
                    <div class="card-footer bg-white">{{ $announcements->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .student-picker { max-height: 260px; overflow-y: auto; }
    .student-option { cursor: pointer; margin: 0; }
    .student-option:hover { background: var(--sidebar-hover); }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const targetInputs = document.querySelectorAll('input[name="target"]');
        const targetFields = document.querySelectorAll('[data-target-field]');
        const studentOptions = document.querySelectorAll('.student-option');
        const selectedCount = document.getElementById('selectedStudentCount');

        function showTargetFields() {
            const target = document.querySelector('input[name="target"]:checked')?.value;
            targetFields.forEach(field => field.hidden = field.dataset.targetField !== target);
        }

        function updateSelectedCount() {
            const count = document.querySelectorAll('input[name="student_ids[]"]:checked').length;
            selectedCount.textContent = count ? `(${count} dipilih)` : '';
        }

        document.getElementById('studentSearch').addEventListener('input', event => {
            const keyword = event.target.value.trim().toLowerCase();
            let visible = 0;
            studentOptions.forEach(option => {
                const matches = option.dataset.search.includes(keyword);
                option.classList.toggle('d-none', !matches);
                visible += matches ? 1 : 0;
            });
            document.getElementById('noStudentMatch').hidden = visible > 0;
        });

        targetInputs.forEach(input => input.addEventListener('change', showTargetFields));
        document.querySelectorAll('input[name="student_ids[]"]').forEach(input => input.addEventListener('change', updateSelectedCount));
        showTargetFields();
        updateSelectedCount();
    })();
</script>
@endpush
