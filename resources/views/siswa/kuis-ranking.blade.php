@extends('layouts.app')

@section('title', 'Kuis & Ranking')

@section('content')
    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 fw-bold">Kuis & Leaderboard</h1>
        </div>

        <!-- Tabs / Pills Navigation -->
        <ul class="nav nav-pills nav-fill bg-primary-soft p-2 rounded mb-4" id="kuisTab" role="tablist" style="background-color: #e7f1ff;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="daftar-tab" data-bs-toggle="pill" data-bs-target="#daftar" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-list-task me-1"></i> Kuis Tersedia
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="kerjakan-tab" data-bs-toggle="pill" data-bs-target="#kerjakan" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-pencil-square me-1"></i> Sedang Dikerjakan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="leaderboard-tab" data-bs-toggle="pill" data-bs-target="#leaderboard" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-trophy me-1"></i> Ranking Kelas
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="pills-tabContent">
            
            <!-- Tab 1: Daftar Kuis Tersedia -->
            <div class="tab-pane fade show active" id="daftar" role="tabpanel">
                <div class="row">
                    <!-- Kuis 1 -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100 border-start border-primary border-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-primary-soft text-primary mb-2">Matematika</span>
                                    <span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Tersedia</span>
                                </div>
                                <h5 class="card-title fw-bold text-dark">Kuis Aljabar Bab 3</h5>
                                <p class="card-text text-muted small mb-3">Pak Budi Santoso - 10 Soal Pilihan Ganda</p>
                                <div class="d-flex justify-content-between text-muted small mb-3">
                                    <span><i class="bi bi-clock me-1"></i> 30 Menit</span>
                                    <span><i class="bi bi-calendar me-1"></i> Berakhir: 20 Mei 2024</span>
                                </div>
                                <button class="btn btn-primary w-100">Mulai Kuis</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kuis 2 -->
                    <div class="col-md-6 mb-4">
                        <div class="card shadow-sm h-100 border-start border-warning border-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span class="badge bg-primary-soft text-primary mb-2">Fisika</span>
                                    <span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Segera Hadir</span>
                                </div>
                                <h5 class="card-title fw-bold text-dark">Kuis Gerak Parabola</h5>
                                <p class="card-text text-muted small mb-3">Bu Sinta Dewi - 15 Soal Pilihan Ganda</p>
                                <div class="d-flex justify-content-between text-muted small mb-3">
                                    <span><i class="bi bi-clock me-1"></i> 45 Menit</span>
                                    <span><i class="bi bi-calendar me-1"></i> Mulai: 22 Mei 2024</span>
                                </div>
                                <button class="btn btn-outline-secondary w-100" disabled>Belum Dimulai</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Pengerjaan Kuis -->
            <div class="tab-pane fade" id="kerjakan" role="tabpanel">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h6 class="mb-0 fw-bold text-primary">Kuis Aljabar Bab 3</h6>
                                    <small class="text-muted">Soal 1 dari 10</small>
                                </div>
                                <!-- Countdown Timer -->
                                <div class="bg-danger text-white px-3 py-2 rounded-pill fw-bold">
                                    <i class="bi bi-clock-fill me-1"></i> <span id="countdown">29:45</span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold text-dark mb-4">1. Berapakah hasil dari pertidaksamaan 2x + 4 > 10 untuk nilai x?</h5>
                                
                                <div class="mb-3">
                                    <div class="form-check border p-3 rounded mb-2">
                                        <input class="form-check-input" type="radio" name="q1" id="q1a">
                                        <label class="form-check-label w-100" for="q1a">A. x > 2</label>
                                    </div>
                                    <div class="form-check border p-3 rounded mb-2 bg-primary-soft" style="background-color: #e7f1ff;">
                                        <input class="form-check-input" type="radio" name="q1" id="q1b" checked>
                                        <label class="form-check-label w-100 fw-bold" for="q1b">B. x > 3</label>
                                    </div>
                                    <div class="form-check border p-3 rounded mb-2">
                                        <input class="form-check-input" type="radio" name="q1" id="q1c">
                                        <label class="form-check-label w-100" for="q1c">C. x < 2</label>
                                    </div>
                                    <div class="form-check border p-3 rounded mb-2">
                                        <input class="form-check-input" type="radio" name="q1" id="q1d">
                                        <label class="form-check-label w-100" for="q1d">D. x < 3</label>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button class="btn btn-outline-secondary" disabled><i class="bi bi-arrow-left me-1"></i> Sebelumnya</button>
                                    <button class="btn btn-primary">Selanjutnya <i class="bi bi-arrow-right ms-1"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Leaderboard / Ranking -->
            <div class="tab-pane fade" id="leaderboard" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-header py-3 bg-white">
                        <h6 class="m-0 fw-bold text-primary"><i class="bi bi-trophy-fill me-2 text-warning"></i>Top 10 Ranking - Kuis Aljabar Bab 3</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80" class="text-center">Peringkat</th>
                                        <th>Nama Siswa</th>
                                        <th>Nilai</th>
                                        <th>Waktu Pengerjaan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Peringkat 1 -->
                                    <tr class="bg-light">
                                        <td class="text-center fs-4 fw-bold text-warning">1</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=Citra+L&background=ffc107&color=fff&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <span class="fw-bold text-dark">Citra Lestari</span><br>
                                                    <small class="text-muted">X IPA 1</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success fs-6">100</span></td>
                                        <td>12 Menit 45 Detik</td>
                                        <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lulus</span></td>
                                    </tr>
                                    <!-- Peringkat 2 -->
                                    <tr>
                                        <td class="text-center fs-4 fw-bold text-secondary">2</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=Andi+P&background=6c757d&color=fff&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <span class="fw-bold text-dark">Andi Pratama</span><br>
                                                    <small class="text-muted">X IPA 1</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success fs-6">95</span></td>
                                        <td>15 Menit 10 Detik</td>
                                        <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lulus</span></td>
                                    </tr>
                                    <!-- Peringkat 3 -->
                                    <tr>
                                        <td class="text-center fs-4 fw-bold" style="color: #cd7f32;">3</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=Dewi+A&background=cd7f32&color=fff&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <span class="fw-bold text-dark">Dewi Anggraini</span><br>
                                                    <small class="text-muted">X IPA 1</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success fs-6">90</span></td>
                                        <td>18 Menit 22 Detik</td>
                                        <td><span class="badge bg-success-soft text-success" style="background-color: #e6f9ee;">Lulus</span></td>
                                    </tr>
                                    <!-- Baris Anda -->
                                    <tr class="bg-primary-soft" style="background-color: #e7f1ff;">
                                        <td class="text-center fs-5 fw-bold text-primary">4</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=Budi+S&background=0d6efd&color=fff&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <span class="fw-bold text-primary">Budi Santoso (Anda)</span><br>
                                                    <small class="text-muted">X IPA 1</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary fs-6">85</span></td>
                                        <td>20 Menit 05 Detik</td>
                                        <td><span class="badge bg-primary-soft text-primary">Lulus</span></td>
                                    </tr>
                                    <!-- Peringkat 5 -->
                                    <tr>
                                        <td class="text-center fs-5 fw-bold text-muted">5</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=Eka+W&background=e7f1ff&color=0d6efd&bold=true" class="rounded-circle me-2" width="40" height="40" alt="">
                                                <div>
                                                    <span class="fw-bold text-dark">Eka Wahyuni</span><br>
                                                    <small class="text-muted">X IPA 1</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-warning fs-6">70</span></td>
                                        <td>25 Menit 30 Detik</td>
                                        <td><span class="badge bg-warning-soft text-warning" style="background-color: #fff8e6;">Remedial</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Dummy Countdown Timer untuk Tab Pengerjaan Kuis
        function startCountdown() {
            let timeLeft = 1795; // 29 menit 55 detik dalam detik
            const timerElement = document.getElementById('countdown');
            
            // Cek apakah elemen ada
            if(!timerElement) return;

            const timerInterval = setInterval(function() {
                let minutes = Math.floor(timeLeft / 60);
                let seconds = timeLeft % 60;
                
                // Format jadi 00:00
                seconds = seconds < 10 ? '0' + seconds : seconds;
                minutes = minutes < 10 ? '0' + minutes : minutes;
                
                timerElement.textContent = minutes + ':' + seconds;
                
                if(timeLeft <= 0) {
                    clearInterval(timerInterval);
                    alert('Waktu kuis telah habis!');
                    // Dummy logic auto-submit
                }
                timeLeft--;
            }, 1000);
        }

        // Jalankan timer saat tab pengerjaan diklik atau halaman dimuat
        document.querySelector('#kerjakan-tab').addEventListener('click', function() {
            // Reset timer jika perlu, di sini hanya dummy jadi tidak direset
            if(!document.getElementById('countdown').dataset.started) {
                startCountdown();
                document.getElementById('countdown').dataset.started = 'true';
            }
        });
    </script>
@endpush