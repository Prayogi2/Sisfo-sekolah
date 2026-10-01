@extends('layouts.app')

@section('title', 'Panel Kahoot - '.$quiz->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 fw-bold mb-1">{{ $quiz->title }}</h1>
            <p class="text-muted mb-0">{{ $quiz->classroom->name }} · {{ $quiz->questions->count() }} soal · {{ rtrim(rtrim(number_format($quiz->question_seconds / 60, 2, ',', ''), '0'), ',') }} menit/soal</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-dark" id="soundToggle">🔇 Musik Mati</button>
            <button type="button" class="btn btn-outline-secondary" id="fullscreenToggle"><i class="bi bi-arrows-fullscreen me-1"></i>Layar Penuh</button>
            <a href="{{ route('guru.bank-soal') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <x-page-guide>Tampilkan halaman ini di proyektor. Tekan <strong>Mulai</strong>, siswa menjawab di HP masing-masing. Saat waktu habis atau semua siswa sudah menjawab, jawaban benar & grafik otomatis tampil — tekan <strong>Lanjut</strong> untuk soal berikutnya. Poin kecepatan hanya untuk papan peringkat; nilai rapor tetap dari benar/salahnya jawaban.</x-page-guide>

    <div id="stage" class="kahoot-stage">
        <div class="kahoot-topbar">
            <div><span class="badge bg-light text-dark fs-6" id="progress">Lobi</span></div>
            <div class="kahoot-timer d-none" id="timerWrap"><span id="timer">0</span></div>
            <div class="text-end"><div class="fs-4 fw-bold" id="answered">0</div><div class="small">sudah menjawab</div></div>
        </div>

        <div id="lobby" class="text-center py-5">
            <div class="display-5 fw-bold mb-2">Bersiap!</div>
            <p class="fs-5 mb-4">Minta siswa membuka menu <strong>Kuis & Ranking</strong> lalu masuk ke kuis <strong>{{ $quiz->title }}</strong>.</p>
            <div class="fs-4 mb-3"><span id="participants">0</span> siswa bergabung</div>
            <div id="lobbyNames" class="d-flex flex-wrap justify-content-center gap-2"></div>
        </div>

        <div id="questionView" class="d-none">
            <div class="kahoot-question">
                <div class="small text-uppercase opacity-75 mb-1" id="questionType"></div>
                <div id="questionMedia"></div>
                <div class="fs-2 fw-bold" id="questionText"></div>
            </div>
            <div class="row g-3 mt-1" id="questionBody"></div>
            <div class="row g-3 mt-2 d-none" id="revealView">
                <div class="col-lg-7"><div class="kahoot-panel"><h5 class="fw-bold mb-3">Sebaran Jawaban</h5><div id="distribution"></div></div></div>
                <div class="col-lg-5"><div class="kahoot-panel"><h5 class="fw-bold mb-3">🏆 Top 5</h5><ol id="leaderboard" class="kahoot-leaderboard mb-0"></ol></div></div>
            </div>
        </div>

        <div id="finishedView" class="d-none text-center py-4">
            <div class="display-5 fw-bold mb-4">🎉 Kuis Selesai!</div>
            <div class="kahoot-podium" id="podium"></div>
            <ol id="finalBoard" class="kahoot-leaderboard text-start mx-auto mt-4" style="max-width: 480px;"></ol>
        </div>

        <div class="kahoot-controls">
            <form method="POST" action="{{ route('guru.kuis.live.start', $quiz) }}" class="host-action" data-confirm-restart="Mengulang sesi akan MENGHAPUS semua jawaban siswa pada sesi sebelumnya. Lanjutkan?">@csrf<button class="btn btn-success btn-lg" id="startButton">▶ Mulai / Ulangi Sesi</button></form>
            <form method="POST" action="{{ route('guru.kuis.live.next', $quiz) }}" class="host-action">@csrf<button class="btn btn-primary btn-lg" id="nextButton">Lanjut ⏭</button></form>
            <form method="POST" action="{{ route('guru.kuis.live.finish', $quiz) }}" class="host-action" data-confirm="Akhiri sesi kuis sekarang? Nilai semua siswa akan dihitung.">@csrf<button class="btn btn-outline-light btn-lg">⏹ Selesaikan</button></form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .kahoot-stage { background: linear-gradient(135deg, #46178f, #7b2ff7); color: #fff; border-radius: 18px; padding: 20px; min-height: 70vh; display: flex; flex-direction: column; }
    .kahoot-stage:fullscreen { border-radius: 0; padding: 32px; overflow: auto; }
    .kahoot-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .kahoot-timer { width: 84px; height: 84px; border-radius: 50%; background: #fff; color: #46178f; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 900; box-shadow: 0 6px 18px rgba(0,0,0,.25); transition: transform .2s; }
    .kahoot-timer.urgent { color: #e21b3c; transform: scale(1.12); }
    .kahoot-question { background: #fff; color: #222; border-radius: 14px; padding: 18px 22px; text-align: center; box-shadow: 0 6px 18px rgba(0,0,0,.2); }
    .kahoot-question img, .kahoot-question video { max-height: 30vh; border-radius: 10px; margin-bottom: 10px; }
    .kahoot-option { border-radius: 12px; padding: 18px 20px; font-size: 1.4rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 14px; min-height: 84px; transition: opacity .3s, transform .3s; }
    .kahoot-option .shape { font-size: 1.8rem; }
    .kahoot-option.dim { opacity: .35; }
    .kahoot-option.correct { outline: 5px solid #fff; transform: scale(1.02); }
    .opt-A { background: #e21b3c; } .opt-B { background: #1368ce; } .opt-C { background: #d89e00; } .opt-D { background: #26890c; }
    .kahoot-panel { background: rgba(255,255,255,.96); color: #222; border-radius: 14px; padding: 16px 20px; height: 100%; }
    .dist-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .dist-label { width: 140px; font-weight: 700; }
    .dist-bar { flex: 1; background: #eee; border-radius: 6px; height: 28px; overflow: hidden; }
    .dist-bar span { display: block; height: 100%; border-radius: 6px; background: #9ca3af; transition: width .8s; }
    .dist-bar span.ok { background: #26890c; }
    .dist-count { width: 36px; text-align: right; font-weight: 800; }
    .kahoot-leaderboard li { display: flex; justify-content: space-between; padding: 8px 12px; margin-bottom: 6px; background: #f3f0ff; border-radius: 8px; font-weight: 700; color: #222; }
    .kahoot-controls { margin-top: auto; padding-top: 18px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
    .lobby-name { background: rgba(255,255,255,.2); border-radius: 999px; padding: 6px 14px; font-weight: 700; animation: popIn .4s; }
    @keyframes popIn { from { transform: scale(.3); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .kahoot-podium { display: flex; justify-content: center; align-items: flex-end; gap: 14px; }
    .podium-step { width: 150px; border-radius: 12px 12px 0 0; background: rgba(255,255,255,.95); color: #46178f; padding: 12px; font-weight: 800; animation: rise .8s ease-out; }
    @keyframes rise { from { transform: translateY(60px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const stateUrl = @json(route('guru.kuis.live.state', $quiz));
    const nextUrl = @json(route('guru.kuis.live.next', $quiz));
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const shapes = { A: '▲', B: '◆', C: '●', D: '■' };
    const el = id => document.getElementById(id);
    let state = null;
    let deadline = null;
    let renderedKey = null;
    let autoRevealedFor = null;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    // ---------- Musik & efek suara (dibuat langsung oleh browser, tanpa file) ----------
    const sound = { on: false, ctx: null, loop: null, step: 0 };
    function tone(freq, duration = 0.15, type = 'triangle', volume = 0.08, delay = 0) {
        if (!sound.on || !sound.ctx) return;
        const osc = sound.ctx.createOscillator();
        const gain = sound.ctx.createGain();
        const start = sound.ctx.currentTime + delay;
        osc.type = type;
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(volume, start);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration);
        osc.connect(gain).connect(sound.ctx.destination);
        osc.start(start);
        osc.stop(start + duration + 0.05);
    }
    function startMusic() {
        stopMusic();
        const melody = [262, 330, 392, 330, 294, 349, 440, 349];
        sound.loop = setInterval(() => {
            const note = melody[sound.step++ % melody.length];
            tone(note, 0.22, 'triangle', 0.05);
            if (sound.step % 2 === 0) tone(note / 2, 0.3, 'sine', 0.04);
        }, 260);
    }
    function stopMusic() { clearInterval(sound.loop); sound.loop = null; }
    function chime() { [523, 659, 784, 1047].forEach((freq, i) => tone(freq, 0.25, 'sine', 0.08, i * 0.12)); }
    function fanfare() { [392, 523, 659, 784, 659, 784, 1047].forEach((freq, i) => tone(freq, 0.3, 'square', 0.05, i * 0.16)); }
    el('soundToggle').addEventListener('click', () => {
        sound.on = !sound.on;
        sound.ctx ??= new (window.AudioContext || window.webkitAudioContext)();
        sound.ctx.resume();
        el('soundToggle').textContent = sound.on ? '🔊 Musik Nyala' : '🔇 Musik Mati';
        if (sound.on && state && ['lobby', 'question'].includes(state.phase)) startMusic(); else stopMusic();
    });
    el('fullscreenToggle').addEventListener('click', () => {
        document.fullscreenElement ? document.exitFullscreen() : el('stage').requestFullscreen?.();
    });

    // ---------- Tombol kendali dikirim tanpa memuat ulang halaman ----------
    document.querySelectorAll('.host-action').forEach(form => form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.confirm && !confirm(form.dataset.confirm)) return;
        if (form.dataset.confirmRestart && state && state.phase !== 'lobby' && !confirm(form.dataset.confirmRestart)) return;
        await fetch(form.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' } });
        refresh();
    }));

    async function autoReveal(questionId) {
        if (autoRevealedFor === questionId) return;
        autoRevealedFor = questionId;
        await fetch(nextUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'text/html' } });
        refresh();
    }

    // ---------- Tampilan ----------
    function renderLeaderboard(target, rows) {
        el(target).innerHTML = rows.length
            ? rows.map(row => '<li><span>' + row.rank + '. ' + escapeHtml(row.name) + '</span><span>' + row.points.toLocaleString('id-ID') + '</span></li>').join('')
            : '<li><span>Belum ada poin</span><span>-</span></li>';
    }

    function renderQuestion(data) {
        const q = data.question;
        el('questionType').textContent = q.type_label;
        el('questionText').textContent = q.text;
        el('questionMedia').innerHTML = q.media_url
            ? (q.media_type === 'video' ? '<video src="' + escapeHtml(q.media_url) + '" controls></video>' : '<img src="' + escapeHtml(q.media_url) + '" alt="Media soal">')
            : '';
        const reveal = data.phase !== 'question';
        const correct = q.correct || [];
        let body = '';
        if (q.options) {
            body = Object.entries(q.options).map(([key, text]) => {
                const cls = reveal ? (correct.includes(key) ? 'correct' : 'dim') : '';
                return '<div class="col-md-6"><div class="kahoot-option opt-' + key + ' ' + cls + '"><span class="shape">' + shapes[key] + '</span><span>' + escapeHtml(text) + '</span></div></div>';
            }).join('');
        } else if (q.matching_left) {
            const items = reveal ? correct : q.matching_left.map(left => left + ' → ?');
            body = '<div class="col-12"><div class="kahoot-panel">' + items.map(item => '<div class="fs-4 fw-bold py-1">' + escapeHtml(item) + '</div>').join('') + '</div></div>';
        } else {
            body = '<div class="col-12"><div class="kahoot-panel text-center fs-4">✍️ Siswa sedang menulis jawaban essay di perangkatnya.'
                + (reveal && correct[0] ? '<div class="fs-5 mt-2 text-success">Pedoman: ' + escapeHtml(correct[0]) + '</div>' : '') + '</div></div>';
        }
        el('questionBody').innerHTML = body;
    }

    function renderReveal(data) {
        const rows = data.distribution || [];
        const max = Math.max(1, ...rows.map(row => row.count));
        el('distribution').innerHTML = rows.map(row =>
            '<div class="dist-row"><div class="dist-label">' + (shapes[row.label] ? shapes[row.label] + ' ' : '') + escapeHtml(row.label) + (row.correct ? ' ✓' : '') + '</div>'
            + '<div class="dist-bar"><span class="' + (row.correct ? 'ok' : '') + '" style="width:' + (row.count / max * 100) + '%"></span></div>'
            + '<div class="dist-count">' + row.count + '</div></div>'
        ).join('');
        renderLeaderboard('leaderboard', data.leaderboard || []);
    }

    function renderFinished(data) {
        const board = data.leaderboard || [];
        const order = [board[1], board[0], board[2]];
        const heights = [130, 180, 100];
        const medals = ['🥈', '🥇', '🥉'];
        el('podium').innerHTML = order.map((row, i) => row
            ? '<div class="podium-step" style="height:' + heights[i] + 'px"><div class="fs-2">' + medals[i] + '</div>' + escapeHtml(row.name) + '<div>' + row.points.toLocaleString('id-ID') + '</div></div>'
            : '').join('');
        renderLeaderboard('finalBoard', board);
    }

    async function refresh() {
        let data;
        try {
            data = await fetch(stateUrl, { headers: { 'Accept': 'application/json' } }).then(response => response.json());
        } catch (error) {
            return;
        }
        const previousPhase = state?.phase;
        state = data;

        el('progress').textContent = data.phase === 'lobby' || data.index === null ? 'Lobi' : (data.phase === 'finished' ? 'Selesai' : 'Soal ' + (data.index + 1) + ' / ' + data.total);
        el('answered').textContent = data.answered + ' / ' + data.participants;
        el('participants').textContent = data.participants;
        el('lobby').classList.toggle('d-none', !(data.phase === 'lobby' || !data.question) || data.phase === 'finished');
        el('questionView').classList.toggle('d-none', !data.question || data.phase === 'finished' || data.phase === 'lobby');
        el('finishedView').classList.toggle('d-none', data.phase !== 'finished');
        el('revealView').classList.toggle('d-none', data.phase !== 'reveal');
        el('nextButton').textContent = data.phase === 'question' ? 'Tampilkan Jawaban 👀' : (data.index !== null && data.index + 1 >= data.total ? 'Akhiri & Hitung Nilai 🏁' : 'Soal Berikutnya ⏭');
        el('lobbyNames').innerHTML = (data.leaderboard || []).map(row => '<span class="lobby-name">' + escapeHtml(row.name) + '</span>').join('');

        if (data.phase === 'question' && data.seconds_left !== null) {
            deadline = Date.now() + data.seconds_left * 1000;
            el('timerWrap').classList.remove('d-none');
        } else {
            deadline = null;
            el('timerWrap').classList.add('d-none');
        }

        const key = (data.question?.id ?? 'none') + ':' + data.phase;
        if (key !== renderedKey) {
            renderedKey = key;
            if (data.question && data.phase !== 'finished') renderQuestion(data);
            if (data.phase === 'reveal') { renderReveal(data); stopMusic(); chime(); }
            if (data.phase === 'finished') { renderFinished(data); stopMusic(); fanfare(); }
            if (data.phase === 'question' && sound.on) startMusic();
        } else if (data.phase === 'reveal') {
            renderReveal(data);
        }

        // Seperti Kahoot: jawaban otomatis dibuka saat waktu habis atau semua siswa sudah menjawab.
        if (data.phase === 'question' && data.question && (data.seconds_left === 0 || (data.participants > 0 && data.answered >= data.participants))) {
            autoReveal(data.question.id);
        }
        if (previousPhase !== data.phase && data.phase === 'lobby' && sound.on) startMusic();
    }

    setInterval(() => {
        if (!deadline) return;
        const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        el('timer').textContent = left;
        el('timerWrap').classList.toggle('urgent', left <= 5);
        if (left <= 5 && left > 0 && sound.on) tone(880, 0.08, 'square', 0.05);
    }, 1000);

    refresh();
    setInterval(refresh, 1500);
})();
</script>
@endpush
