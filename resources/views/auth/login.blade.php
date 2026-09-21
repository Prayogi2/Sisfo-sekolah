<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NURFA.ID (MIS Nurul Falaq)</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fe;
            height: 100vh;
            overflow: hidden;
        }
        
        .wrapper {
            display: flex;
            height: 100vh;
        }
        
        /* Left Branding Panel */
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
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            top: -50px;
            right: -50px;
        }
        
        .brand-panel::before {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            bottom: -50px;
            left: -50px;
        }
        
        /* Right Form Panel */
        .form-panel {
            flex: 1;
            background-color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }
        
        .login-box {
            width: 100%;
            max-width: 400px;
        }
        
        @media (max-width: 768px) {
            .brand-panel { display: none; }
            .wrapper { height: auto; min-height: 100vh; overflow: auto; }
            body { overflow: auto; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <!-- Brand Panel (Left - Displayed on Desktop) -->
        <div class="brand-panel text-center">
            <img src="{{ asset('images/logo.png') }}" 
                 alt="Logo MIS Nurul Falaq" 
                 class="mb-4 rounded-circle shadow-lg p-2 bg-white"
                 style="width: 110px; height: 110px; object-fit: contain;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">

            <!-- Fallback Badge -->
            <div class="mb-4 rounded-circle shadow-lg bg-white text-primary fw-bold" 
                 style="display: none; width: 110px; height: 110px; font-size: 2.5rem; align-items: center; justify-content: center; margin: 0 auto;">
                NF
            </div>

            <h1 class="fw-bold mb-2">NURFA.ID</h1>
            <p class="mb-4" style="font-size: 1.1rem; opacity: 0.9;">Digital Platform Of MIS Nurul Falaq</p>
            <div class="mt-5" style="font-size: 0.8rem; opacity: 0.7;">
                &copy; {{ date('Y') }} MIS Nurul Falaq. All rights reserved.
            </div>
        </div>

        <!-- Form Panel (Right - Mobile & Desktop) -->
        <div class="form-panel">
            <div class="login-box">
                
                <!-- Logo Utama di atas "Selamat Datang" -->
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" 
                         alt="Logo MIS Nurul Falaq" 
                         class="mb-3"
                         style="width: 85px; height: 85px; object-fit: contain;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">

                    <!-- Fallback Logo untuk Mobile -->
                    <div class="mb-3 rounded-circle shadow-sm bg-primary text-white fw-bold" 
                         style="display: none; width: 75px; height: 75px; font-size: 1.8rem; align-items: center; justify-content: center; margin: 0 auto;">
                        NF
                    </div>

                    <h4 class="fw-bold text-dark mb-1">Selamat Datang</h4>
                    <p class="text-muted small mb-0">Platform Digital MIS Nurul Falaq (NURFA.ID)</p>
                </div>

                <!-- Alert Pesan Error / Status Login -->
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Role Tabs -->
                <ul class="nav nav-pills nav-fill mb-4 bg-light p-2 rounded-3" id="roleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-semibold" id="admin-tab" data-bs-toggle="pill" data-bs-target="#admin" type="button" role="tab">
                            <i class="bi bi-shield-lock"></i> Admin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold" id="guru-tab" data-bs-toggle="pill" data-bs-target="#guru" type="button" role="tab">
                            <i class="bi bi-person-badge"></i> Guru
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold" id="siswa-tab" data-bs-toggle="pill" data-bs-target="#siswa" type="button" role="tab">
                            <i class="bi bi-mortarboard"></i> Siswa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-semibold" id="wali-tab" data-bs-toggle="pill" data-bs-target="#wali" type="button" role="tab">
                            <i class="bi bi-people"></i> Wali
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="roleTabContent">
                    <!-- Form Admin -->
                    <div class="tab-pane fade show active" id="admin" role="tabpanel">
                        <form action="{{ route('login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="admin">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Username / Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                    <input type="text" name="login" class="form-control border-start-0 ps-0" placeholder="admin@nurfa.id" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0 toggle-password-input" placeholder="******" required>
                                    <span class="input-group-text bg-light border-start-0 toggle-password-btn" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="rememberAdmin">
                                    <label class="form-check-label text-muted small" for="rememberAdmin">Ingat saya</label>
                                </div>
                                <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Admin</button>
                        </form>
                    </div>
                    
                    <!-- Form Guru -->
                    <div class="tab-pane fade" id="guru" role="tabpanel">
                         <form action="{{ route('login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="guru">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">NIP / Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                    <input type="text" name="login" class="form-control border-start-0 ps-0" placeholder="Masukkan NIP atau Email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0 toggle-password-input" placeholder="******" required>
                                    <span class="input-group-text bg-light border-start-0 toggle-password-btn" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="rememberGuru">
                                    <label class="form-check-label text-muted small" for="rememberGuru">Ingat saya</label>
                                </div>
                                <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Guru</button>
                        </form>
                    </div>

                    <!-- Form Siswa -->
                    <div class="tab-pane fade" id="siswa" role="tabpanel">
                         <form action="{{ route('login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="siswa">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">NIS / NISN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                    <input type="text" name="login" class="form-control border-start-0 ps-0" placeholder="Masukkan NIS atau NISN" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0 toggle-password-input" placeholder="******" required>
                                    <span class="input-group-text bg-light border-start-0 toggle-password-btn" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="rememberSiswa">
                                    <label class="form-check-label text-muted small" for="rememberSiswa">Ingat saya</label>
                                </div>
                                <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Siswa</button>
                        </form>
                    </div>

                    <!-- Form Wali Murid -->
                    <div class="tab-pane fade" id="wali" role="tabpanel">
                         <form action="{{ route('login') }}" method="POST">
                            @csrf
                            <input type="hidden" name="role" value="wali">
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">No. Induk Anak / Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-fill text-primary"></i></span>
                                    <input type="text" name="login" class="form-control border-start-0 ps-0" placeholder="Masukkan ID Anak / Email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted fw-semibold">Kata Sandi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-primary"></i></span>
                                    <input type="password" name="password" class="form-control border-start-0 border-end-0 ps-0 toggle-password-input" placeholder="******" required>
                                    <span class="input-group-text bg-light border-start-0 toggle-password-btn" style="cursor: pointer;"><i class="bi bi-eye-fill text-muted"></i></span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="rememberWali">
                                    <label class="form-check-label text-muted small" for="rememberWali">Ingat saya</label>
                                </div>
                                <a href="#" class="text-primary small text-decoration-none">Lupa sandi?</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Masuk sebagai Wali Murid</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script Toggle Password Visibility -->
    <script>
        document.querySelectorAll('.toggle-password-btn').forEach(button => {
            button.addEventListener('click', function () {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye-fill');
                    icon.classList.add('bi-eye-slash-fill');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash-fill');
                    icon.classList.add('bi-eye-fill');
                }
            });
        });
    </script>
</body>
</html>