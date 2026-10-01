@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div class="container-fluid">
    <x-page-guide>Halaman ini otomatis memperbarui diri. Jangan tutup/refresh — tunggu soal muncul, lalu jawab (pilih, tulis, atau jodohkan) sebelum waktunya habis. Makin cepat jawaban benar, makin besar poin permainanmu!</x-page-guide>
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h1 class="h3 fw-bold">{{ $quiz->title }}</h1>
            <p class="text-muted">Tunggu guru menampilkan soal berikutnya.</p>
            <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                <div class="display-6 fw-bold" id="phase">Menunggu...</div>
                <div class="live-timer d-none" id="timerWrap"><span id="timer">0</span></div>
            </div>
            <div class="h4 mb-4" id="question"></div>
            <div class="row g-2" id="options"></div>
            <div class="alert d-none mt-3" id="answerStatus"></div>
            <div class="d-none mt-3" id="roundResult">
                <div class="round-points" id="roundPoints"></div>
                <div class="fw-bold mb-2" id="roundRank"></div>
                <ol class="live-top5 text-start mx-auto" id="roundTop5"></ol>
            </div>
            <div class="alert alert-info mt-4" id="score">Skor sementara: 0</div>
        </div>
    </div>
</div>

{{-- Popup animasi nilai akhir, muncul otomatis saat guru mengakhiri sesi kuis --}}
<div id="resultPopup" class="result-popup-overlay" style="display:none;">
    <div class="confetti-container" id="confettiContainer"></div>
    <div class="result-popup-card">
        <div class="result-emoji" id="resultEmoji">🎉</div>
        <h2 class="result-title">Kuis Selesai!</h2>
        <div class="result-score" id="resultScore">0</div>
        <p class="result-subtitle" id="resultSubtitle"></p>
        <p class="result-detail" id="resultDetail"></p>
        <a href="{{ route('siswa.kuis') }}" class="btn btn-lg btn-warning rounded-pill fw-bold px-4 result-btn">Lihat Peringkat Kelas 🏅</a>
    </div>
</div>
@endsection

@push('styles')
<style>
.live-timer { width: 64px; height: 64px; border-radius: 50%; background: #46178f; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; font-weight: 900; }
.live-timer.urgent { background: #e21b3c; animation: pulse .5s infinite alternate; }
@keyframes pulse { to { transform: scale(1.1); } }
.round-points { font-size: 2.4rem; font-weight: 900; color: #26890c; animation: popupPopIn .5s; }
.round-points.zero { color: #6b7280; }
.live-top5 { max-width: 360px; padding-left: 0; list-style: none; }
.live-top5 li { display: flex; justify-content: space-between; background: #f3f0ff; border-radius: 8px; padding: 6px 12px; margin-bottom: 4px; font-weight: 700; }
.live-top5 li.me { background: #ffe9a8; }
.option.opt-A { --kahoot: #e21b3c; } .option.opt-B { --kahoot: #1368ce; } .option.opt-C { --kahoot: #d89e00; } .option.opt-D { --kahoot: #26890c; }
.option.btn-outline-primary { border-color: var(--kahoot); color: var(--kahoot); border-width: 3px; }
.option.btn-primary { background: var(--kahoot); border-color: var(--kahoot); }
.result-popup-overlay {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(20, 20, 45, .78);
    backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center;
    animation: popupFadeIn .3s ease;
}
@keyframes popupFadeIn { from { opacity: 0; } to { opacity: 1; } }

.result-popup-card {
    position: relative; z-index: 2;
    background: #fff; border-radius: 28px;
    padding: 2.5rem 2rem; max-width: 380px; width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    animation: popupPopIn .55s cubic-bezier(.34,1.56,.64,1);
}
@keyframes popupPopIn {
    0%   { transform: scale(.3) rotate(-8deg); opacity: 0; }
    70%  { transform: scale(1.08) rotate(2deg); }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

.result-emoji {
    font-size: 4.5rem; line-height: 1;
    animation: resultBounce 1s ease-in-out infinite;
}
@keyframes resultBounce {
    0%, 100% { transform: translateY(0); }
    50%      { transform: translateY(-14px); }
}

.result-title { font-weight: 800; color: #2d2d5f; margin: .5rem 0 0; }
.result-score {
    font-size: 3.75rem; font-weight: 900; margin: .1rem 0;
    background: linear-gradient(90deg, #ff9800, #ff5252);
    -webkit-background-clip: text; background-clip: text; color: transparent;
}
.result-subtitle { font-weight: 700; font-size: 1.1rem; color: #444; margin-bottom: .25rem; }
.result-detail { color: #777; margin-bottom: 1.4rem; }
.result-btn { box-shadow: 0 8px 20px rgba(255,152,0,.35); }

.confetti-container { position: absolute; inset: 0; overflow: hidden; pointer-events: none; }
.confetti-piece {
    position: absolute; top: -12px; width: 10px; height: 16px; opacity: .9;
    animation-name: confettiFall; animation-timing-function: linear; animation-fill-mode: forwards;
}
@keyframes confettiFall {
    to { transform: translateY(110vh) rotate(540deg); opacity: .5; }
}
</style>
@endpush

@push('scripts')
<script>
const stateUrl = @json(route('siswa.kuis.live.state', $quiz));
const answerUrl = @json(route('siswa.kuis.live.answer', $attempt));
let current = null;
let popupShown = false;
let pollTimer = null;
let selectedKeys = [];
let deadline = null;
let myStudentId = @json($student->id);
const shapes = { A: '▲', B: '◆', C: '●', D: '■' };

function showAnswerStatus(message, type) {
    const box = document.getElementById('answerStatus');
    box.className = 'alert mt-3 alert-' + type;
    box.textContent = message;
}

function lockAnswers() {
    document.querySelectorAll('#options button, #options select, #options textarea').forEach(item => item.disabled = true);
}

async function sendAnswer(keys) {
    const response = await fetch(answerUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        body: JSON.stringify({ answer: keys })
    });
    if (response.ok) {
        showAnswerStatus('Jawaban terkirim! Tunggu guru menampilkan jawaban yang benar…', 'success');
    } else {
        const data = await response.json().catch(() => ({}));
        showAnswerStatus(data.message || 'Jawaban gagal dikirim, coba lagi.', 'danger');
        if (response.status === 422 && /habis|sudah menjawab/i.test(data.message || '')) {
            lockAnswers();
            return { ok: true };
        }
    }
    return response;
}

function launchConfetti() {
    const colors = ['#ff5252', '#ffca28', '#66bb6a', '#42a5f5', '#ab47bc', '#ff7043'];
    const container = document.getElementById('confettiContainer');
    container.innerHTML = '';
    for (let i = 0; i < 60; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        piece.style.left = Math.random() * 100 + '%';
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.animationDuration = (Math.random() * 1.5 + 1.8) + 's';
        piece.style.animationDelay = (Math.random() * 0.6) + 's';
        container.appendChild(piece);
    }
}

function animateScoreNumber(target) {
    const el = document.getElementById('resultScore');
    let current = 0;
    const step = Math.max(1, target / 40);
    const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = Math.round(current);
    }, 25);
}

function showResultPopup(data) {
    popupShown = true;
    const score = data.score;
    const correct = data.correct_count;
    const total = data.total;

    let emoji = '🎉', subtitle = 'Kuis Selesai!';
    if (score >= 90)      { emoji = '🏆'; subtitle = 'Luar Biasa! Kamu Hebat!'; }
    else if (score >= 75) { emoji = '🌟'; subtitle = 'Kerja Bagus, Pertahankan!'; }
    else if (score >= 60) { emoji = '👍'; subtitle = 'Lumayan, Terus Belajar Ya!'; }
    else                  { emoji = '💪'; subtitle = 'Semangat, Coba Lagi Lain Kali!'; }

    document.getElementById('resultEmoji').textContent = emoji;
    document.getElementById('resultSubtitle').textContent = subtitle;
    document.getElementById('resultDetail').textContent = `Kamu menjawab benar ${correct} dari ${total} soal.`
        + (data.rank ? ` Peringkat #${data.rank} dengan ${data.game_points.toLocaleString('id-ID')} poin permainan.` : '')
        + ' Nilai essay (jika ada) menyusul setelah dikoreksi guru.';
    document.getElementById('resultScore').textContent = '0';
    document.getElementById('resultPopup').style.display = 'flex';

    launchConfetti();
    animateScoreNumber(score);
}

async function refresh() {
    const data = await fetch(stateUrl).then(response => response.json());

    document.getElementById('phase').textContent = data.phase === 'finished'
        ? 'Selesai'
        : 'Soal ' + (data.index === null ? '-' : data.index + 1) + '/' + data.total;
    document.getElementById('score').textContent = data.show_score
        ? 'Skor sementara: ' + data.score
        : 'Skor akan ditampilkan sesuai pengaturan guru.';

    if (data.phase === 'finished') {
        if (!popupShown) showResultPopup(data);
        clearInterval(pollTimer);
        return;
    }

    if (!data.question) return;

    if (data.phase === 'question' && data.seconds_left !== null) {
        deadline = Date.now() + data.seconds_left * 1000;
        document.getElementById('timerWrap').classList.remove('d-none');
    } else {
        deadline = null;
        document.getElementById('timerWrap').classList.add('d-none');
    }

    // Gambar ulang hanya saat soal atau fasenya berubah (question -> reveal).
    const renderKey = data.question.id + ':' + data.phase;
    if (current === renderKey) return;
    current = renderKey;
    selectedKeys = [];
    renderRoundResult(data);
    if (data.phase === 'question') {
        document.getElementById('answerStatus').className = 'alert d-none mt-3';
        if (data.answer) showAnswerStatus('Jawaban terkirim! Tunggu guru menampilkan jawaban yang benar…', 'success');
    }

    const media = data.question.media_url
        ? (data.question.media_type === 'video'
            ? '<video src="' + escapeHtml(data.question.media_url) + '" class="img-fluid rounded mb-3" controls></video>'
            : '<img src="' + escapeHtml(data.question.media_url) + '" class="img-fluid rounded mb-3" alt="Media soal">')
        : '';
    document.getElementById('question').innerHTML = media + typeBadge(data.question.type) + '<div>' + escapeHtml(data.question.text) + '</div>';

    const isAnswering = data.phase === 'question' && !data.answer;
    if (data.question.type === 'essay') return renderEssay(data, isAnswering);
    if (data.question.type === 'matching') return renderMatching(data, isAnswering);
    renderChoice(data, isAnswering);
}

function renderRoundResult(data) {
    const box = document.getElementById('roundResult');
    if (data.phase !== 'reveal') {
        box.classList.add('d-none');
        return;
    }
    document.getElementById('answerStatus').className = 'alert d-none mt-3';
    box.classList.remove('d-none');
    const gained = data.gained_points ?? 0;
    const points = document.getElementById('roundPoints');
    points.textContent = data.question.type === 'essay' ? 'Menunggu koreksi guru ✍️' : (gained > 0 ? '+' + gained.toLocaleString('id-ID') + ' poin' : (data.answer ? 'Belum tepat 😅' : 'Tidak menjawab ⏰'));
    points.classList.toggle('zero', gained === 0);
    document.getElementById('roundRank').textContent = data.rank ? 'Peringkatmu: #' + data.rank + ' · Total ' + data.game_points.toLocaleString('id-ID') + ' poin' : '';
    document.getElementById('roundTop5').innerHTML = (data.leaderboard || []).map(row =>
        '<li class="' + (row.student_id === myStudentId ? 'me' : '') + '"><span>' + row.rank + '. ' + escapeHtml(row.name) + '</span><span>' + row.points.toLocaleString('id-ID') + '</span></li>'
    ).join('');
}

setInterval(() => {
    if (!deadline) return;
    const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
    document.getElementById('timer').textContent = left;
    document.getElementById('timerWrap').classList.toggle('urgent', left <= 5);
    if (left === 0) {
        lockAnswers();
        if (!document.getElementById('answerStatus').classList.contains('alert-success')) showAnswerStatus('Waktu habis! ⏰', 'warning');
        deadline = null;
    }
}, 250);

function typeBadge(type) {
    const badges = {
        multiple: 'Pilih semua jawaban yang benar',
        essay: 'Tulis jawabanmu',
        matching: 'Jodohkan pasangan yang tepat',
    };
    return badges[type] ? '<div class="badge bg-info text-dark mb-2">' + badges[type] + '</div><br>' : '';
}

async function submitWith(button, payload, lockSelector) {
    document.querySelectorAll(lockSelector).forEach(item => item.disabled = true);
    button.disabled = true;
    const response = await sendAnswer(payload);
    if (response.ok) {
        button.textContent = 'Jawaban terkirim ✓';
        button.classList.replace('btn-warning', 'btn-success');
    } else {
        document.querySelectorAll(lockSelector).forEach(item => item.disabled = false);
        button.disabled = false;
    }
}

function renderEssay(data, isAnswering) {
    const text = data.answer?.text ?? '';
    let html = '<div class="col-12"><textarea id="essayAnswer" class="form-control" rows="5" maxlength="5000" placeholder="Tulis jawabanmu di sini..."' + (isAnswering ? '' : ' disabled') + '>' + escapeHtml(text) + '</textarea></div>';
    if (isAnswering) {
        html += '<div class="col-12 mt-2"><button type="button" id="submitEssay" class="btn btn-warning w-100">Kirim Jawaban</button></div>';
    } else if (data.phase === 'reveal') {
        const key = Array.isArray(data.question.correct) && data.question.correct[0] ? '<div class="mt-1">Pedoman jawaban: <strong>' + escapeHtml(data.question.correct[0]) + '</strong></div>' : '';
        html += '<div class="col-12 mt-2"><div class="alert alert-secondary mb-0 text-start">Jawaban essay akan dinilai oleh guru.' + key + '</div></div>';
    } else if (text) {
        html += '<div class="col-12 mt-2"><div class="alert alert-success mb-0">Jawaban terkirim ✓</div></div>';
    }
    document.getElementById('options').innerHTML = html;

    if (!isAnswering) return;
    const button = document.getElementById('submitEssay');
    button.onclick = () => {
        const answer = document.getElementById('essayAnswer').value.trim();
        if (answer === '') return;
        submitWith(button, answer, '#essayAnswer');
    };
}

function renderMatching(data, isAnswering) {
    const answer = data.answer ?? {};
    const correct = data.question.correct;
    const rows = data.question.matching_left.map((left, index) => {
        const chosen = answer[index] ?? '';
        let status = '';
        if (data.phase === 'reveal' && Array.isArray(correct)) {
            const isRight = chosen === correct[index];
            status = '<div class="small mt-1 ' + (isRight ? 'text-success' : 'text-danger') + '">' + (isRight ? '✓ Benar' : '✗ Jawaban benar: ' + escapeHtml(correct[index])) + '</div>';
        }
        const options = ['<option value="">— Pilih pasangan —</option>'].concat(
            data.question.matching_choices.map(choice => '<option value="' + escapeHtml(choice) + '"' + (choice === chosen ? ' selected' : '') + '>' + escapeHtml(choice) + '</option>')
        ).join('');
        return '<div class="col-12"><div class="row g-2 align-items-center text-start border rounded p-2 mx-0">'
            + '<div class="col-md-5 fw-semibold">' + escapeHtml(left) + '</div>'
            + '<div class="col-md-7"><select class="form-select matching-choice" data-index="' + index + '"' + (isAnswering ? '' : ' disabled') + '>' + options + '</select>' + status + '</div>'
            + '</div></div>';
    }).join('');
    const submit = isAnswering ? '<div class="col-12 mt-2"><button type="button" id="submitMatching" class="btn btn-warning w-100" disabled>Kirim Jawaban</button></div>' : '';
    document.getElementById('options').innerHTML = rows + submit;

    if (!isAnswering) return;
    const button = document.getElementById('submitMatching');
    const selects = [...document.querySelectorAll('.matching-choice')];
    selects.forEach(select => select.onchange = () => {
        button.disabled = !selects.every(item => item.value !== '');
    });
    button.onclick = () => {
        const payload = {};
        selects.forEach(select => payload[select.dataset.index] = select.value);
        submitWith(button, payload, '.matching-choice');
    };
}

function renderChoice(data, isAnswering) {
    const isMultiple = data.question.type === 'multiple';
    const submitButtonHtml = (isAnswering && isMultiple)
        ? '<div class="col-12 mt-2"><button type="button" id="submitMultiAnswer" class="btn btn-warning w-100" disabled>Kirim Jawaban</button></div>'
        : '';
    document.getElementById('options').innerHTML = Object.entries(data.question.options).map(([key, value]) =>
        '<div class="col-md-6"><button class="btn ' + optionClass(key, data) + ' w-100 py-3 option opt-' + escapeHtml(key) + '" data-key="' + escapeHtml(key) + '"' + (isAnswering ? '' : ' disabled') + '><strong>' + (shapes[key] || escapeHtml(key)) + '</strong> ' + escapeHtml(value) + '</button></div>'
    ).join('') + submitButtonHtml;

    if (!isAnswering) return;

    if (isMultiple) {
        const submitButton = document.getElementById('submitMultiAnswer');
        document.querySelectorAll('.option').forEach(button => button.onclick = () => {
            const key = button.dataset.key;
            if (selectedKeys.includes(key)) {
                selectedKeys = selectedKeys.filter(item => item !== key);
                button.classList.replace('btn-primary', 'btn-outline-primary');
            } else {
                selectedKeys.push(key);
                button.classList.replace('btn-outline-primary', 'btn-primary');
            }
            submitButton.disabled = selectedKeys.length === 0;
        });
        submitButton.onclick = async () => {
            document.querySelectorAll('.option').forEach(item => item.disabled = true);
            submitButton.disabled = true;
            const response = await sendAnswer(selectedKeys);
            if (!response.ok) {
                document.querySelectorAll('.option').forEach(item => item.disabled = false);
                submitButton.disabled = false;
            }
        };
        return;
    }

    document.querySelectorAll('.option').forEach(button => button.onclick = async () => {
        document.querySelectorAll('.option').forEach(item => item.disabled = true);
        const response = await sendAnswer([button.dataset.key]);
        if (response.ok) {
            button.classList.replace('btn-outline-primary', 'btn-primary');
        } else {
            document.querySelectorAll('.option').forEach(item => item.disabled = false);
        }
    });
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML.replace(/"/g, '&quot;');
}

function optionClass(key, data) {
    const isSelected = Array.isArray(data.answer) && data.answer.includes(key);
    if (data.phase === 'reveal') {
        const isCorrectOption = Array.isArray(data.question.correct) && data.question.correct.includes(key);
        if (isCorrectOption) return 'btn-success';
        if (isSelected) return 'btn-danger';
        return 'btn-outline-secondary';
    }
    return isSelected ? 'btn-primary' : 'btn-outline-primary';
}

refresh();
pollTimer = setInterval(refresh, 2500);
</script>
@endpush