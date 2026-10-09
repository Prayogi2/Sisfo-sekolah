@extends('layouts.app')

@section('title', $question ? 'Edit Soal' : 'Tambah Soal')

@php
    $isEdit = $question !== null;
    // Setelah validasi gagal tampilkan kembali isian guru; kalau edit, isi dari soal tersimpan.
    $items = old('questions') ?: ($isEdit ? [\App\Services\QuizQuestionForm::itemFromQuestion($question)] : [['type' => \App\Models\QuizQuestion::TYPE_SINGLE]]);
    $selectedSubject = old('subject_id', $question?->subject_id ?? request('subject_id'));
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold">{{ $isEdit ? 'Edit Soal' : 'Tambah Soal' }}</h1>
            <p class="text-muted mb-0">{{ $isEdit ? 'Ubah isi, jenis, atau kunci jawaban soal.' : 'Ketik soal pada kolom Pertanyaan, isi opsi atau pasangan sesuai jenisnya, lalu simpan.' }}</p>
        </div>
        <a href="{{ route('guru.bank-soal') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ $isEdit ? route('guru.bank-soal.update', $question) : route('guru.bank-soal.simpan') }}" method="POST" enctype="multipart/form-data" id="questionForm">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label">Mata Pelajaran</label>
                <select name="subject_id" class="form-select" required>
                    <option value="">Pilih mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((string) $selectedSubject === (string) $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
                @unless($isEdit)<div class="form-text">Semua soal di halaman ini masuk ke mata pelajaran yang dipilih.</div>@endunless
            </div>
        </div>

        <div id="questionCards">
            @foreach($items as $index => $item)
                <x-kuis.question-card :index="$index" :item="$item" :types="$types" :removable="! $isEdit" :question="$question" />
            @endforeach
        </div>

        <div class="d-flex flex-wrap justify-content-between gap-2 mb-5">
            @unless($isEdit)
                <button type="button" class="btn btn-outline-primary" id="addQuestion"><i class="bi bi-plus-lg me-1"></i>Tambah Soal Lagi</button>
            @endunless
            <button type="submit" class="btn btn-primary ms-auto"><i class="bi bi-save me-1"></i>{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Semua Soal' }}</button>
        </div>
    </form>

    @unless($isEdit)
        <template id="questionCardTemplate">
            <x-kuis.question-card index="__INDEX__" :types="$types" />
        </template>
    @endunless
</div>
@endsection

@push('scripts')
<script>
(function () {
    const container = document.getElementById('questionCards');
    const template = document.getElementById('questionCardTemplate');
    let nextIndex = Math.max(-1, ...[...container.querySelectorAll('.question-card')].map(card => parseInt(card.dataset.index, 10) || 0)) + 1;

    const keyHints = {
        single: 'Centang satu opsi sebagai kunci jawaban.',
        multiple: 'Centang lebih dari satu opsi sebagai kunci jawaban.',
    };

    // Tampilkan bagian isian sesuai jenis soal; bagian yang disembunyikan
    // dinonaktifkan supaya tidak ikut terkirim & tidak mengganggu validasi.
    function applyType(card) {
        const type = card.querySelector('.question-type').value;
        card.querySelectorAll('.type-section').forEach(section => {
            const active = section.dataset.types.split(',').includes(type);
            section.classList.toggle('d-none', !active);
            section.querySelectorAll('input, textarea, select').forEach(field => {
                field.disabled = !active;
                if (field.hasAttribute('data-required')) field.required = active;
            });
        });
        const hint = card.querySelector('.key-hint');
        if (hint && keyHints[type]) hint.textContent = keyHints[type];
        if (type === 'single') keepSingleKey(card);
    }

    function keepSingleKey(card, changed = null) {
        const keys = [...card.querySelectorAll('.answer-key')];
        const checked = keys.filter(key => key.checked);
        const keep = changed && changed.checked ? changed : checked[0];
        keys.forEach(key => { if (key !== keep) key.checked = false; });
    }

    function renumber() {
        container.querySelectorAll('.question-card').forEach((card, position) => {
            card.querySelector('.question-number').textContent = 'Soal #' + (position + 1);
        });
    }

    function initCard(card) {
        card.querySelector('.question-type').addEventListener('change', () => applyType(card));
        card.querySelectorAll('.answer-key').forEach(key => key.addEventListener('change', () => {
            if (card.querySelector('.question-type').value === 'single') keepSingleKey(card, key);
        }));

        const pairRows = card.querySelector('.pair-rows');
        let nextPair = pairRows.querySelectorAll('.pair-row').length;
        card.querySelector('.add-pair').addEventListener('click', () => {
            if (pairRows.querySelectorAll('.pair-row').length >= {{ \App\Services\QuizQuestionForm::MAX_PAIRS }}) return;
            const row = pairRows.querySelector('.pair-row').cloneNode(true);
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace(/\[pairs\]\[\d+\]/, '[pairs][' + nextPair + ']');
                input.value = '';
            });
            nextPair++;
            pairRows.appendChild(row);
        });
        pairRows.addEventListener('click', event => {
            const button = event.target.closest('.remove-pair');
            if (!button) return;
            const rows = pairRows.querySelectorAll('.pair-row');
            if (rows.length > 2) {
                button.closest('.pair-row').remove();
            } else {
                button.closest('.pair-row').querySelectorAll('input').forEach(input => input.value = '');
            }
        });

        card.querySelector('.remove-question')?.addEventListener('click', () => {
            if (container.querySelectorAll('.question-card').length > 1) {
                card.remove();
                renumber();
            }
        });

        applyType(card);
    }

    container.querySelectorAll('.question-card').forEach(initCard);
    renumber();

    document.getElementById('addQuestion')?.addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', nextIndex++);
        container.insertAdjacentHTML('beforeend', html);
        const card = container.lastElementChild;
        // Jenis soal baru mengikuti soal sebelumnya supaya cepat mengisi banyak soal sejenis.
        const previous = card.previousElementSibling?.querySelector('.question-type');
        if (previous) card.querySelector('.question-type').value = previous.value;
        initCard(card);
        renumber();
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();
</script>
@endpush
