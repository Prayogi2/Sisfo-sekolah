@extends('layouts.app')
@section('title', $quiz->title)
@section('content')
<div class="container-fluid">
    <x-page-guide>Halaman ini otomatis memperbarui diri. Jangan tutup/refresh — tunggu soal muncul, lalu ketuk pilihan jawaban secepatnya sebelum guru lanjut ke soal berikutnya.</x-page-guide>
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h1 class="h3 fw-bold">{{ $quiz->title }}</h1>
            <p class="text-muted">Tunggu guru menampilkan soal berikutnya.</p>
            <div class="display-6 fw-bold mb-3" id="phase">Menunggu...</div>
            <div class="h4 mb-4" id="question"></div>
            <div class="row g-2" id="options"></div>
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
    document.getElementById('resultDetail').textContent = `Kamu menjawab benar ${correct} dari ${total} soal.`;
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

    // Gambar ulang hanya saat soal atau fasenya berubah (question -> reveal).
    const renderKey = data.question.id + ':' + data.phase;
    if (current === renderKey) return;
    current = renderKey;

    const media = data.question.media_url
        ? (data.question.media_type === 'video'
            ? '<video src="' + escapeHtml(data.question.media_url) + '" class="img-fluid rounded mb-3" controls></video>'
            : '<img src="' + escapeHtml(data.question.media_url) + '" class="img-fluid rounded mb-3" alt="Media soal">')
        : '';
    document.getElementById('question').innerHTML = media + '<div>' + escapeHtml(data.question.text) + '</div>';

    const isAnswering = data.phase === 'question' && !data.answer;
    document.getElementById('options').innerHTML = Object.entries(data.question.options).map(([key, value]) =>
        '<div class="col-md-6"><button class="btn ' + optionClass(key, data) + ' w-100 py-3 option" data-key="' + escapeHtml(key) + '"' + (isAnswering ? '' : ' disabled') + '><strong>' + escapeHtml(key) + '</strong> ' + escapeHtml(value) + '</button></div>'
    ).join('');

    if (!isAnswering) return;
    document.querySelectorAll('.option').forEach(button => button.onclick = async () => {
        document.querySelectorAll('.option').forEach(item => item.disabled = true);
        const response = await fetch(answerUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
            body: JSON.stringify({ answer: button.dataset.key })
        });
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
    if (data.phase === 'reveal') {
        if (key === data.question.correct) return 'btn-success';
        if (key === data.answer) return 'btn-danger';
        return 'btn-outline-secondary';
    }
    return key === data.answer ? 'btn-primary' : 'btn-outline-primary';
}

refresh();
pollTimer = setInterval(refresh, 2500);
</script>
@endpush