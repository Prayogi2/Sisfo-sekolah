{{--
    Satu kartu soal di form bank soal. Nama field: questions[{index}][...].
    Bagian isian berganti sesuai jenis soal (lihat script di bank-soal-form).
    $index boleh berupa placeholder "__INDEX__" untuk template JavaScript.
--}}
@props(['index', 'item' => [], 'types', 'removable' => true, 'question' => null])

@php
    $name = fn (string $field) => "questions[{$index}]{$field}";
    $type = $item['type'] ?? \App\Models\QuizQuestion::TYPE_SINGLE;
    $pairs = $item['pairs'] ?? [];
    if (count($pairs) < 2) {
        $pairs = array_pad($pairs, 2, ['left' => '', 'right' => '']);
    }
@endphp

<div class="card border shadow-sm mb-3 question-card" data-index="{{ $index }}">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold question-number">Soal</span>
        @if($removable)
            <button type="button" class="btn btn-sm btn-outline-danger remove-question" title="Hapus soal ini"><i class="bi bi-trash"></i></button>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-8">
                <label class="form-label">Jenis Soal</label>
                <select name="{{ $name('[type]') }}" class="form-select question-type">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Poin</label>
                <input type="number" name="{{ $name('[points]') }}" value="{{ $item['points'] ?? 1 }}" min="1" max="100" class="form-control">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Ketik Soal / Pertanyaan</label>
            <textarea name="{{ $name('[question]') }}" rows="3" class="form-control" placeholder="Tulis soal yang akan dijawab siswa..." required>{{ $item['question'] ?? '' }}</textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Gambar / Video (opsional)</label>
            @if($question?->media_path)
                <div class="mb-2">
                    @if($question->media_type === 'video')
                        <video src="{{ Storage::url($question->media_path) }}" class="rounded border" style="max-height: 140px;" controls></video>
                    @else
                        <img src="{{ Storage::url($question->media_path) }}" class="rounded border" style="max-height: 140px;" alt="Media soal">
                    @endif
                    <label class="form-check small mt-1"><input type="checkbox" name="{{ $name('[remove_media]') }}" value="1" class="form-check-input"> Hapus media ini</label>
                </div>
            @endif
            <input type="file" name="{{ $name('[media]') }}" class="form-control" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime">
        </div>

        {{-- Pilihan ganda (satu / banyak jawaban) --}}
        <div class="type-section" data-types="single,multiple">
            <p class="text-muted small mb-2 key-hint">Centang kunci jawaban.</p>
            <div class="row g-2">
                @foreach(\App\Models\QuizQuestion::OPTION_KEYS as $letter)
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">{{ $letter }}</span>
                            <input name="{{ $name("[options][{$letter}]") }}" value="{{ $item['options'][$letter] ?? '' }}" class="form-control" data-required>
                            <span class="input-group-text"><input type="checkbox" class="answer-key me-1" name="{{ $name('[correct_answer][]') }}" value="{{ $letter }}" @checked(in_array($letter, $item['correct_answer'] ?? [], true))> Kunci</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Essay --}}
        <div class="type-section" data-types="essay">
            <label class="form-label">Kunci / Pedoman Jawaban (opsional)</label>
            <textarea name="{{ $name('[answer_key]') }}" rows="2" class="form-control" placeholder="Hanya terlihat oleh guru saat mengoreksi">{{ $item['answer_key'] ?? '' }}</textarea>
            <div class="form-text">Jawaban essay dinilai guru lewat menu <strong>Koreksi Essay</strong> setelah kuis berjalan.</div>
        </div>

        {{-- Menjodohkan --}}
        <div class="type-section" data-types="matching">
            <p class="text-muted small mb-2">Tulis pasangan yang <strong>benar</strong>. Saat kuis, pilihan di sisi kanan diacak untuk siswa.</p>
            <div class="pair-rows">
                @foreach($pairs as $pairIndex => $pair)
                    <div class="row g-2 mb-2 pair-row">
                        <div class="col"><input name="{{ $name("[pairs][{$pairIndex}][left]") }}" value="{{ $pair['left'] ?? '' }}" class="form-control" placeholder="Kiri, mis. Kucing" data-required></div>
                        <div class="col-auto d-flex align-items-center"><i class="bi bi-arrow-left-right text-muted"></i></div>
                        <div class="col"><input name="{{ $name("[pairs][{$pairIndex}][right]") }}" value="{{ $pair['right'] ?? '' }}" class="form-control" placeholder="Kanan, mis. Mengeong" data-required></div>
                        <div class="col-auto"><button type="button" class="btn btn-outline-danger remove-pair" title="Hapus pasangan"><i class="bi bi-x-lg"></i></button></div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary add-pair"><i class="bi bi-plus-lg me-1"></i>Tambah Pasangan</button>
        </div>

        <div class="mt-3">
            <label class="form-label">Pembahasan (opsional)</label>
            <textarea name="{{ $name('[explanation]') }}" rows="2" class="form-control">{{ $item['explanation'] ?? '' }}</textarea>
        </div>
    </div>
</div>
