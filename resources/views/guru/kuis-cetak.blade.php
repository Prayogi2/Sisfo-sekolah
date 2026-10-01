<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $withKey ? 'Kunci Jawaban' : 'Lembar Soal' }} - {{ $quiz->title }}</title>
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font: 12pt "Times New Roman", serif; color: #111; margin: 0; background: #e5e7eb; }
        .toolbar { position: sticky; top: 0; background: #1e3a8a; padding: 10px 16px; display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; font-family: Arial, sans-serif; }
        .toolbar button, .toolbar a { background: #fff; color: #1e3a8a; border: 0; border-radius: 6px; padding: 8px 14px; font-weight: bold; font-size: 14px; text-decoration: none; cursor: pointer; }
        .toolbar a.secondary { background: transparent; color: #fff; border: 1px solid #fff; }
        .page { background: #fff; max-width: 210mm; margin: 16px auto; padding: 16mm 14mm; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
        .header { text-align: center; border-bottom: 3px double #111; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 15pt; margin: 0; text-transform: uppercase; }
        .header .sub { font-size: 11pt; }
        .identity { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; margin-bottom: 14px; font-size: 11pt; }
        .identity div { border-bottom: 1px dotted #555; padding: 4px 0; }
        .key-banner { background: #dcfce7; border: 1px solid #16a34a; color: #14532d; padding: 6px 10px; margin-bottom: 12px; font-weight: bold; text-align: center; }
        .section-title { font-weight: bold; margin: 16px 0 6px; padding: 4px 8px; background: #f3f4f6; border-left: 4px solid #1e3a8a; }
        .instruction { font-style: italic; font-size: 11pt; margin: 0 0 8px; }
        .question { margin: 0 0 12px; page-break-inside: avoid; }
        .question .text { display: flex; gap: 6px; }
        .question .points { font-size: 9pt; color: #555; white-space: nowrap; margin-left: auto; }
        .media { max-width: 60%; max-height: 55mm; margin: 6px 0 4px 22px; display: block; }
        .options { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 16px; margin: 4px 0 0 22px; }
        .options .correct, .answer-key { color: #15803d; font-weight: bold; }
        .lines { margin: 6px 0 0 22px; }
        .lines div { border-bottom: 1px solid #9ca3af; height: 9mm; }
        .matching { display: grid; grid-template-columns: 1fr 1fr; gap: 0 24px; margin: 6px 0 0 22px; }
        .matching div { padding: 2px 0; }
        .answer-key { margin: 4px 0 0 22px; font-size: 11pt; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { box-shadow: none; margin: 0; padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
@php
    $sections = [
        \App\Models\QuizQuestion::TYPE_SINGLE => ['Pilihan Ganda', 'Berilah tanda silang (X) pada huruf A, B, C, atau D di depan jawaban yang paling benar!'],
        \App\Models\QuizQuestion::TYPE_MULTIPLE => ['Pilihan Ganda Kompleks', 'Berilah tanda silang (X) pada SEMUA jawaban yang benar (jawaban benar lebih dari satu)!'],
        \App\Models\QuizQuestion::TYPE_MATCHING => ['Menjodohkan', 'Jodohkan pernyataan di kiri dengan jawaban di kanan. Tulis huruf jawaban di dalam kurung!'],
        \App\Models\QuizQuestion::TYPE_ESSAY => ['Uraian / Essay', 'Jawablah pertanyaan berikut dengan jelas dan lengkap!'],
    ];
    $grouped = $quiz->questions->groupBy('type');
    $number = 0;
    $letters = range('a', 'z');
@endphp

<div class="toolbar">
    <button type="button" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
    @if($withKey)
        <a href="{{ route('guru.kuis.cetak', $quiz) }}" class="secondary">Lihat Lembar Soal Siswa</a>
    @else
        <a href="{{ route('guru.kuis.cetak', ['quiz' => $quiz, 'kunci' => 1]) }}" class="secondary">Lihat Kunci Jawaban</a>
    @endif
    <a href="{{ route('guru.bank-soal') }}" class="secondary">Kembali</a>
</div>

<div class="page">
    <div class="header">
        <h1>{{ $quiz->title }}</h1>
        <div class="sub">MIS Nurul Falaq · {{ $quiz->subject->name }} · Kelas {{ $quiz->classroom->name }}</div>
    </div>

    @if($withKey)
        <div class="key-banner">KUNCI JAWABAN — UNTUK GURU</div>
    @else
        <div class="identity">
            <div>Nama&nbsp;:</div>
            <div>No. Absen&nbsp;:</div>
            <div>Kelas&nbsp;: {{ $quiz->classroom->name }}</div>
            <div>Tanggal&nbsp;:</div>
        </div>
    @endif

    @foreach($sections as $type => [$title, $instruction])
        @continue(! $grouped->has($type))
        <div class="section-title">{{ $loop->iteration }}. {{ $title }}</div>
        <p class="instruction">{{ $instruction }}</p>

        @foreach($grouped[$type] as $question)
            @php
                $number++;
            @endphp
            <div class="question">
                <div class="text"><strong>{{ $number }}.</strong><span>{{ $question->question }}</span><span class="points">({{ $question->pivot->points }} poin)</span></div>
                @if($question->media_path && $question->media_type !== 'video')
                    <img src="{{ Storage::url($question->media_path) }}" class="media" alt="Gambar soal {{ $number }}">
                @endif

                @if($question->isChoice())
                    <div class="options">
                        @foreach($question->options as $key => $option)
                            <div class="{{ $withKey && in_array($key, $question->correct_answer ?? [], true) ? 'correct' : '' }}">{{ $key }}. {{ $option }}</div>
                        @endforeach
                    </div>
                @elseif($question->type === \App\Models\QuizQuestion::TYPE_MATCHING)
                    @php
                        $choices = $question->shuffledMatchingChoices();
                    @endphp
                    <div class="matching">
                        <div>
                            @foreach($question->matchingPairs() as $pairIndex => $pair)
                                @php
                                    $correctLetter = $letters[array_search($pair['right'], $choices, true)];
                                @endphp
                                <div>{{ $pairIndex + 1 }}. {{ $pair['left'] }} &nbsp;( @if($withKey)<span class="answer-key">{{ $correctLetter }}</span>@else&nbsp;&nbsp;&nbsp;&nbsp;@endif )</div>
                            @endforeach
                        </div>
                        <div>
                            @foreach($choices as $choiceIndex => $choice)
                                <div>{{ $letters[$choiceIndex] }}. {{ $choice }}</div>
                            @endforeach
                        </div>
                    </div>
                @else
                    @if($withKey)
                        <div class="answer-key">Pedoman: {{ $question->correct_answer[0] ?? '(dinilai sesuai kebijakan guru)' }}</div>
                    @else
                        <div class="lines">@for($line = 0; $line < 4; $line++)<div></div>@endfor</div>
                    @endif
                @endif
            </div>
        @endforeach
    @endforeach
</div>
</body>
</html>
