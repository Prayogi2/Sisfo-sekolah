<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NURFA.ID</title>
    
    <!-- KODE FAVICON AMAN -->
    @php
        $faviconPath = public_path('images/logo.png');
        $faviconUrl = asset('images/logo.png');
    @endphp
    @if(file_exists($faviconPath))
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}" />
    @else
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='20'%20fill='%230d6efd'/%3E%3Ctext%20x='50'%20y='68'%20font-size='50'%20text-anchor='middle'%20fill='white'%20font-family='Arial'%20font-weight='bold'%3ENF%3C/text%3E%3C/svg%3E" />
    @endif
    <!-- AKHIR KODE FAVICON -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Nunito', 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f7fe;
            height: 100vh;
            overflow: hidden;
        }
        .wrapper { display: flex; height: 100vh; }
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .brand-panel::after {
            content: ''; position: absolute; width: 300px; height: 300px;
            border-radius: 50%; background: rgba(255,255,255,0.1); top: -50px; right: -50px;
        }
        .brand-panel::before {
            content: ''; position: absolute; width: 200px; height: 200px;
            border-radius: 50%; background: rgba(255,255,255,0.1); bottom: -50px; left: -50px;
        }
        .form-panel {
            flex: 1; background-color: white; display: flex;
            justify-content: center; align-items: center; padding: 40px;
        }
        .login-box { width: 100%; max-width: 400px; }
        .logo-fallback {
            background-color: white; color: #0d6efd; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; width: 80px; height: 80px; font-size: 2rem;
            border: 4px solid rgba(255,255,255,0.5);
        }
        /* Style untuk Logo Kecil di Form Login */
        .login-logo {
            width: 70px; height: 70px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e7f1ff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .login-logo-fallback {
            background-color: #0d6efd; color: white; font-weight: bold;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; width: 70px; height: 70px; font-size: 1.5rem;
            box-shadow: 0 4px 6px rgba(13, 110, 253, 0.3);
        }
        @media (max-width: 768px) {
            .brand-panel { display: none; }
            .wrapper { height: auto; min-height: 100vh; overflow: auto; }
            body { overflow: auto; }
        }
    </style>
</head>
<body>

@php
    // Cek ulang variabel untuk logo di body
    $logoPath = public_path('images/logo.png');
    $logoUrl = asset('images/logo.png');
@endphp

<div class="wrapper">
    <!-- Brand Panel (Left) -->
    <div class="brand-panel text-center">
        @if(file_exists($logoPath))
            <img src="{{ $logoUrl }}" alt="Logo" class="mb-4 rounded-circle shadow-lg p-3 bg-white" width="100" height="100" style="object-fit: cover;">
        @else
            <div class="logo-fallback mb-4 shadow-lg">NF</div>
        @endif
        <h1 class="fw-bold mb-3">NURFA.ID</h1>
        <p class="mb-4" style="font-size: 1.1rem; opacity: 0.9;">Digital Platform Of<br>MIS Nurul Falaq</p>
        <div class="mt-5" style="font-size: 0.8rem; opacity: 0.7;">&copy; 2024 NURFA.ID. All rights reserved.</div>
    </div>

    <!-- Form Panel (Right) -->
    <div class="form-panel">
        <div class="login-box">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-dark">Selamat Datang</h4>
                <p class="text-muted">Silakan masuk untuk melanjutkan</p>
                
                <!-- Logo di bawah kalimat Selamat Datang -->
                <div class="mb-3 mt-3">
                    @if(file_exists($logoPath))
                        <img src="{{ $logoUrl }}" alt="Logo" class="login-logo">
                    @else
                        <div class="login-logo-fallback">NF</div>
                    @endif
                </div>
            </div>

            <!-- Role Tabs -->
            <ul class="nav nav-pills nav-fill mb-4 bg-light p-2 rounded-3" id="roleTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-semibold" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin" type="button"><i class="bi bi-shield-lock"></i> Admin</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold" id="guru-tab" data-bs-toggle="pill" data-bs-target="#guru" type="button"><i class="bi bi-person-badge"></i> Guru</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold" id="siswa-tab" data-bs-toggle="pill" data-bs-target="#siswa" type="button"><i class="bi bi-mortarboard"></i> Siswa</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold" id="wali-tab" data-bs-toggle="pill" data-bs-target="#wali" type="button"><i class="bi bi-people"></i> Wali</button>
                </li>
            </ul>

            <div class="tab-content" id="roleTabContent">
                <!-- Form Admin -->
                <div class="tab-pane fade show active" id="admin" role="tabpanel">
                    <form action="{{ route('admin.dashboard') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Username / Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="admin@nurfa.id" value="admin@nurfa.id">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="******" value="password">
                                <span class="input-group-text bg-light border-start-0" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="rememberAdmin" checked><label class="form-check-label text-muted small" for="rememberAdmin">Ingat saya</label></div>
                            <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Admin</button>
                        
                        <!-- Tombol Scan QR Publik -->
                        <div class="mt-3 text-center">
                            <small class="text-muted d-block mb-2">atau</small>
                            <a href="{{ route('presensi.scan') }}" class="btn btn-outline-primary w-100 py-2 fw-bold shadow-sm">
                                <i class="bi bi-qr-code-scan me-2"></i> Scan QR Kehadiran
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Form Guru -->
                <div class="tab-pane fade" id="guru" role="tabpanel">
                     <form action="{{ route('guru.dashboard') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">NIP / Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="Masukkan NIP">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="******">
                                <span class="input-group-text bg-light border-start-0" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="rememberGuru"><label class="form-check-label text-muted small" for="rememberGuru">Ingat saya</label></div>
                            <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Guru</button>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted d-block mb-2">atau</small>
                            <a href="{{ route('presensi.scan') }}" class="btn btn-outline-primary w-100 py-2 fw-bold shadow-sm">
                                <i class="bi bi-qr-code-scan me-2"></i> Scan QR Kehadiran
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Form Siswa -->
                <div class="tab-pane fade" id="siswa" role="tabpanel">
                     <form action="{{ route('siswa.jadwal') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">NIS / NISN</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="Masukkan NIS">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="******">
                                <span class="input-group-text bg-light border-start-0" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="rememberSiswa"><label class="form-check-label text-muted small" for="rememberSiswa">Ingat saya</label></div>
                            <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Siswa</button>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted d-block mb-2">atau</small>
                            <a href="{{ route('presensi.scan') }}" class="btn btn-outline-primary w-100 py-2 fw-bold shadow-sm">
                                <i class="bi bi-qr-code-scan me-2"></i> Scan QR Kehadiran
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Form Wali Murid -->
                <div class="tab-pane fade" id="wali" role="tabpanel">
                     <form action="{{ route('wali.dashboard') }}" method="GET">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">No. Induk Anak / Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" placeholder="Masukkan ID Anak">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="******">
                                <span class="input-group-text bg-light border-start-0" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-4">
                            <div class="form-check"><input class="form-check-input" type="checkbox" id="rememberWali"><label class="form-check-label text-muted small" for="rememberWali">Ingat saya</label></div>
                            <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Wali Murid</button>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted d-block mb-2">atau</small>
                            <a href="{{ route('presensi.scan') }}" class="btn btn-outline-primary w-100 py-2 fw-bold shadow-sm">
                                <i class="bi bi-qr-code-scan me-2"></i> Scan QR Kehadiran
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>