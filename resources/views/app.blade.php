<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SISFO Sekolah</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-color: #4e73df;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            color: white;
            z-index: 1030;
            transition: all 0.3s ease;
            overflow-y: auto;
        }
        
        .sidebar.active {
            margin-left: calc(-1 * var(--sidebar-width));
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .sidebar-header img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        
        .sidebar-user-info {
            font-size: 0.9rem;
            margin-top: 5px;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            padding: 15px 0;
        }
        
        .sidebar-menu .menu-title {
            padding: 10px 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: 0.2s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--sidebar-hover);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu a i {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content.active {
            margin-left: 0;
        }
        
        /* Topbar Styles */
        .topbar {
            height: 60px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .content-wrapper {
            padding: 25px;
            flex: 1;
        }
        
        .footer {
            background-color: white;
            padding: 15px 25px;
            text-align: center;
            font-size: 0.85rem;
            color: #6c757d;
            border-top: 1px solid #e3e6f0;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: calc(-1 * var(--sidebar-width));
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: var(--sidebar-width);
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="https://ui-avatars.com/api/?name=SISFO&background=4e73df&color=fff" alt="Logo">
            <h5 class="mb-0">SISFO Sekolah</h5>
            <div class="sidebar-user-info">SMAN 1 dummySejahtera</div>
        </div>
        <div class="sidebar-menu">
            <div class="menu-title">Menu Utama</div>
            <a href="#" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            
            <div class="menu-title">Administrasi</div>
            <a href="#"><i class="bi bi-people-fill"></i> Data Siswa</a>
            <a href="#"><i class="bi bi-building-fill"></i> Data Kelas</a>
            <a href="#"><i class="bi bi-person-badge-fill"></i> Data Guru</a>
            
            <div class="menu-title">Keuangan & Absensi</div>
            <a href="#"><i class="bi bi-credit-card-2-front-fill"></i> Verifikasi SPP</a>
            <a href="#"><i class="bi bi-calendar-check-fill"></i> Approval Izin</a>
            
            <div class="menu-title">Akademik & Laporan</div>
            <a href="#"><i class="bi bi-bank"></i> Bank Soal & Kuis</a>
            <a href="#"><i class="bi bi-clipboard2-data-fill"></i> Laporan Absensi</a>
            <a href="#"><i class="bi bi-cash-stack"></i> Laporan SPP</a>
            <a href="#"><i class="bi bi-bar-chart-line-fill"></i> Laporan Nilai</a>
            
            <div class="menu-title">Pengaturan</div>
            <a href="#"><i class="bi bi-gear-fill"></i> Konfigurasi</a>
            <a href="#" style="color: #ff6b6b;"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        
        <!-- Topbar -->
        <header class="topbar">
            <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle">
                <i class="bi bi-list fs-3"></i>
            </button>
            
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">@yield('title', 'Dashboard')</li>
                </ol>
            </nav>
            
            <div class="ms-auto d-flex align-items-center">
                <div class="dropdown me-3">
                    <a href="#" class="text-dark position-relative" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            3
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="#"><i class="bi bi-cash text-warning"></i> 2 SPP Menunggu Verifikasi</a>
                        <a class="dropdown-item" href="#"><i class="bi bi-envelope text-primary"></i> 1 Pengajuan Izin Baru</a>
                    </div>
                </div>
                
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=Admin+School&background=4e73df&color=fff" class="rounded-circle me-2" width="35" height="35" alt="User">
                        <span class="d-none d-lg-inline">Admin Sekolah</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profil Saya</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Pengaturan</a></li>
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
            Copyright &copy; 2024 SISFO Sekolah. All rights reserved. <br>
            <small>Versi 1.0.0 | Laravel 11 & PHP 8.4</small>
        </footer>
    </div>

    <!-- Bootstrap 5.3 JS (Popper included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <script>
        // Sidebar Toggle Logic
        document.getElementById('sidebarToggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('active');
        });

        // Auto close sidebar on mobile when clicking a link
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