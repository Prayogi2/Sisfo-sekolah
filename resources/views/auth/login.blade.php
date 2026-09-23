<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NURFA.ID</title>
    <x-favicon />
    @php($logoPath = public_path('images/logo.png'))
    @php($logoUrl = asset('images/logo.png'))
    @if(file_exists($logoPath)) <link rel="icon" type="image/png" href="{{ $logoUrl }}"> @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin: 0; min-height: 100vh; font-family: 'Nunito', 'Segoe UI', sans-serif; background: #f4f7fe; }
        .wrapper { display: flex; min-height: 100vh; }
        .brand-panel { position: relative; overflow: hidden; flex: 1; background: linear-gradient(140deg, #1273ff 0%, #0d6efd 45%, #0a4bb0 100%); color: #fff; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; text-align: center; }
        /* Tekstur geometris Islami + cahaya lembut, murni CSS/SVG (tanpa aset eksternal) */
        .brand-pattern { position: absolute; inset: 0; opacity: .14; pointer-events: none; }
        .brand-glow { position: absolute; border-radius: 50%; filter: blur(70px); pointer-events: none; }
        .brand-glow-a { width: 340px; height: 340px; background: rgba(125, 196, 255, .55); top: -110px; left: -110px; }
        .brand-glow-b { width: 320px; height: 320px; background: rgba(45, 212, 191, .38); bottom: -120px; right: -90px; }
        .brand-content { position: relative; z-index: 1; width: 100%; max-width: 360px; }
        .brand-logo-wrap { position: relative; display: inline-flex; margin-bottom: 26px; }
        .brand-logo-wrap::after { content: ''; position: absolute; inset: -14px; border-radius: 50%; border: 1px dashed rgba(255,255,255,.45); animation: brand-spin 26s linear infinite; }
        @keyframes brand-spin { to { transform: rotate(360deg); } }
        .brand-divider { width: 54px; height: 3px; border-radius: 3px; background: rgba(255,255,255,.65); margin: 18px auto 22px; }
        .brand-feature { display: flex; align-items: center; gap: .7rem; text-align: left; background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.28); border-radius: 14px; padding: .6rem .85rem; margin-bottom: .6rem; font-size: .9rem; backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
        .brand-feature i { font-size: 1.05rem; opacity: .95; }
        /* min-width:0 supaya panel form boleh menyusut di layar sempit dan
           isinya tidak meluber keluar layar HP. */
        .form-panel { flex: 1; min-width: 0; background: #fff; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .login-box { width: 100%; max-width: 400px; }
        .login-logo { width: 78px; height: 78px; object-fit: cover; border-radius: 50%; border: 3px solid #e7f1ff; box-shadow: 0 4px 10px rgba(0,0,0,.08); }
        @media (max-width: 768px) { .brand-panel { display: none; } .form-panel { min-height: 100vh; padding: 24px; } }
        @media (prefers-reduced-motion: reduce) { .brand-logo-wrap::after { animation: none; } }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="brand-panel">
            <svg class="brand-pattern" width="100%" height="100%" aria-hidden="true" focusable="false">
                <defs>
                    <pattern id="nurfaTile" width="72" height="72" patternUnits="userSpaceOnUse">
                        {{-- Rub el Hizb: dua persegi bertumpuk 45 derajat --}}
                        <rect x="21" y="21" width="30" height="30" fill="none" stroke="#fff" stroke-width="1"/>
                        <rect x="21" y="21" width="30" height="30" fill="none" stroke="#fff" stroke-width="1" transform="rotate(45 36 36)"/>
                        <circle cx="36" cy="36" r="2.5" fill="#fff"/>
                        <circle cx="0" cy="0" r="1.6" fill="#fff"/>
                        <circle cx="72" cy="0" r="1.6" fill="#fff"/>
                        <circle cx="0" cy="72" r="1.6" fill="#fff"/>
                        <circle cx="72" cy="72" r="1.6" fill="#fff"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#nurfaTile)"/>
            </svg>
            <span class="brand-glow brand-glow-a"></span>
            <span class="brand-glow brand-glow-b"></span>

            <div class="brand-content">
                <div class="brand-logo-wrap">
                    @if(file_exists($logoPath))
                        <img src="{{ $logoUrl }}" alt="Logo NURFA.ID" class="rounded-circle shadow-lg p-3 bg-white" width="110" height="110" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-white text-primary fw-bold d-flex align-items-center justify-content-center" style="width:110px;height:110px;font-size:2.5rem;">NF</div>
                    @endif
                </div>

                <h1 class="fw-bold mb-1">NURFA.ID</h1>
                <p class="mb-0 opacity-75">Digital Platform Of<br>MIS Nurul Falaq</p>
                <div class="brand-divider"></div>

                <div class="brand-feature"><i class="bi bi-qr-code-scan"></i><span>Presensi harian lewat scan QR</span></div>
                <div class="brand-feature"><i class="bi bi-journal-check"></i><span>Nilai & rapor digital</span></div>
                <div class="brand-feature"><i class="bi bi-cash-coin"></i><span>Pembayaran SPP daring</span></div>
                <div class="brand-feature"><i class="bi bi-mortarboard-fill"></i><span>Kuis CBT & peringkat kelas</span></div>
            </div>
        </div>

        <div class="form-panel">
            <div class="login-box">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-2">Selamat Datang</h3>
                    <p class="text-muted mb-3">Admin/guru memakai email · siswa memakai nama</p>
                    @if(file_exists($logoPath))
                        <img src="{{ $logoUrl }}" alt="Logo NURFA.ID" class="login-logo">
                    @endif
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form action="{{ url('/login') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="identifier" class="form-label fw-semibold">Email / Nama</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person text-primary"></i></span>
                            <input id="identifier" name="identifier" type="text" value="{{ old('identifier') }}" class="form-control" placeholder="Email admin/guru atau nama siswa" autocomplete="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock text-primary"></i></span>
                            <input id="password" name="password" type="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" class="btn btn-light border" id="togglePassword" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
                        <div class="form-check"><input id="remember" name="remember" type="checkbox" class="form-check-input"><label for="remember" class="form-check-label text-muted small">Ingat saya</label></div>
                        <span class="text-muted small">Lupa password? Hubungi admin.</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Masuk</button>
                </form>

                <div class="text-center mt-4">
                    <div class="text-muted small mb-2">atau</div>
                    <a href="{{ route('presensi.scan') }}" class="btn btn-outline-primary w-100 py-2 fw-bold"><i class="bi bi-qr-code-scan me-2"></i>Scan QR Kehadiran</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            password.type = password.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
