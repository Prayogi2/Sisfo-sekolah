<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - NURFA.ID</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #0d6efd;
            --primary-dark: #0a58ca;
            --primary-light: #e7f1ff;
            --sidebar-bg: #ffffff;
            --sidebar-hover: #f0f5ff;
            --content-bg: #f4f7fe;
            --text-dark: #1e3a8a;
        }
        
        body {
            background-color: var(--content-bg);
            font-family: 'Nunito', 'Segoe UI', Roboto, sans-serif;
            color: #333;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            border-right: 1px solid #e0e7ff;
            color: var(--text-dark);
            z-index: 1030;
            transition: all 0.3s ease;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(13, 110, 253, 0.05);
        }
        
        .sidebar.active { margin-left: calc(-1 * var(--sidebar-width)); }
        
        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
            border-radius: 0 0 15px 15px;
            margin-bottom: 10px;
        }
        
        .sidebar-header img {
            width: 50px; height: 50px;
            border-radius: 50%;
            margin-bottom: 10px;
            border: 2px solid rgba(255,255,255,0.5);
            background-color: white;
        }
        
        .sidebar-user-info { font-size: 0.85rem; margin-top: 5px; opacity: 0.9; }
        .sidebar-menu { padding: 10px 0; }
        
        .sidebar-menu .menu-title {
            padding: 15px 20px 5px;
            font-size: 0.75rem; text-transform: uppercase;
            font-weight: bold; color: #94a3b8; letter-spacing: 1px;
        }
        
        .sidebar-menu a {
            display: flex; align-items: center;
            padding: 12px 20px; color: #475569;
            text-decoration: none; transition: 0.2s;
            margin: 2px 10px; border-radius: 8px; font-weight: 500;
        }
        
        .sidebar-menu a:hover { background-color: var(--sidebar-hover); color: var(--primary-color); }
        .sidebar-menu a.active { background-color: var(--primary-color); color: white; box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3); }
        .sidebar-menu a i { font-size: 1.2rem; margin-right: 15px; width: 20px; text-align: center; }
        
        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); transition: all 0.3s ease; min-height: 100vh; display: flex; flex-direction: column; }
        .main-content.active { margin-left: 0; }
        
        /* Topbar */
        .topbar {
            height: 65px; background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            display: flex; align-items: center; padding: 0 25px;
            position: sticky; top: 0; z-index: 1020; border-bottom: 1px solid #eff2f7;
        }
        
        .content-wrapper { padding: 25px; flex: 1; }
        .footer { background-color: white; padding: 15px 25px; text-align: center; font-size: 0.85rem; color: #64748b; border-top: 1px solid #eff2f7; }
        
        .text-primary-custom { color: var(--primary-color) !important; }
        .bg-primary-soft { background-color: var(--primary-light) !important; }
        .card { border: none; border-radius: 12px; }
        .card-header { background-color: white; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: var(--text-dark); }
        
        @media (max-width: 992px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .sidebar.active { margin-left: 0; }
            .main-content { margin-left: 0; }
            .main-content.active { margin-left: var(--sidebar-width); }
        }
    </style>
</head>
<body>

    @php
        // Dummy Role Selector untuk UI Testing
        $role = $role ?? 'admin'; 
    @endphp

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="https://ui-avatars.com/api/?name=N&background=ffffff&color=0d6efd&bold=true" alt="Logo">
            <h5 class="mb-0 fw-bold">NURFA.ID</h5>
            <div class="sidebar-user-info">MIS Nurul Falaq</div>
        </div>
        <div class="sidebar-menu">
            @if($role == 'admin' || $role == 'guru')
                <!-- MENU ADMIN & GURU -->
                <div class="menu-title">Menu Utama</div>
                <a href="#" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                
                <div class="menu-title">Administrasi</div>
                <a href="#"><i class="bi bi-journal-bookmark-fill"></i> Buku Induk</a>
                <a href="#"><i class="bi bi-clock-history"></i> Absensi & Sakelar Telat</a>
                
                <div class="menu-title">Akademik & Keuangan</div>
                <a href="#"><i class="bi bi-credit-card-2-front-fill"></i> Verifikasi SPP</a>
                <a href="#"><i class="bi bi-file-earmark-play-fill"></i> Kuis Guru</a>
                <a href="#"><i class="bi bi-trophy-fill"></i> Prestasi & Pelanggaran</a>
                
                <div class="menu-title">Evaluasi</div>
                <a href="#"><i class="bi bi-clipboard2-data-fill"></i> Laporan</a>
                
            @elseif($role == 'wali')
                <!-- MENU WALI MURID -->
                <div class="menu-title">Menu Utama</div>
                <a href="#" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                
                <div class="menu-title">Aktivitas Anak</div>
                <a href="#"><i class="bi bi-envelope-paper-heart"></i> Ajukan Izin/Sakit</a>
                <a href="#"><i class="bi bi-cash-stack"></i> Tagihan SPP</a>
                <a href="#"><i class="bi bi-mortarboard-fill"></i> Kuis Anak</a>
                
                <div class="menu-title">Interaksi</div>
                <a href="#"><i class="bi bi-chat-dots-fill"></i> Kritik & Saran</a>
            @endif
            
            <div class="menu-title">Pengaturan</div>
            <a href="#" style="color: #ef4444;"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        
        <!-- Topbar -->
        <header class="topbar">
            <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle">
                <i class="bi bi-list fs-3 text-primary-custom"></i>
            </button>
            
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#" class="text-muted">Home</a></li>
                    <li class="breadcrumb-item active text-primary-custom fw-bold" aria-current="page">@yield('title', 'Dashboard')</li>
                </ol>
            </nav>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown me-3">
                    <a href="#" class="text-dark position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill fs-5 text-primary-custom"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                        <a class="dropdown-item" href="#"><i class="bi bi-cash text-warning me-2"></i> 2 SPP Menunggu Verifikasi</a>
                        <a class="dropdown-item" href="#"><i class="bi bi-envelope text-primary me-2"></i> 1 Pengajuan Izin Baru</a>
                    </div>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name={{ ucfirst($role)}}+User&background=0d6efd&color=fff" class="rounded-circle me-2" width="35" height="35" alt="User">
                        <span class="d-none d-lg-inline fw-semibold text-capitalize">{{ $role }} User</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="content-wrapper">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="footer">
            Copyright &copy; 2024 <strong>NURFA.ID</strong> - Digital Platform MIS Nurul Falaq. <br>
            <small>Versi 1.0.0 | Laravel 11 & PHP 8.4</small>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('active');
        });

        if (window.innerWidth <= 992) {
            document.querySelectorAll('.sidebar-menu a').forEach(function(link) {
                link.addEventListener('click', function() {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('main-content').classList.remove('active');
                });
            });
        }
    </script>
    @stack('scripts')
</body>
</html>