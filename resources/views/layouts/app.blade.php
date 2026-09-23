<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - NURFA.ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('css/style.css') }}?v={{ filemtime(public_path('css/style.css')) }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

@php
    // Gunakan prefix URL pada halaman role, lalu fallback ke role akun.
    $role = request()->segment(1);
    if (! in_array($role, ['admin', 'guru', 'siswa'], true)) {
        $role = auth()->user()?->role ?? 'admin';
    }
    
    $logoPath = public_path('images/logo.png');
    $logoUrl = asset('images/logo.png');
@endphp

<!-- Sidebar Navigation -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-header">
        @if(file_exists($logoPath))
            <img src="{{ $logoUrl }}" alt="Logo" class="logo-md">
        @else
            <div class="logo-fallback logo-md">NF</div>
        @endif
        <div class="text-start">
            <h5 class="mb-0 fw-bold">NURFA.ID</h5>
            <div class="sidebar-user-info">MIS Nurul Falaq</div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        {{-- MENU ADMIN --}}
        @if($role == 'admin')
            <div class="menu-title">Menu Utama</div>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            
            <div class="menu-title">Manajemen Data</div>
            <a href="{{ route('admin.data-siswa') }}" class="{{ request()->routeIs('admin.data-siswa*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i> Data Siswa</a>
            <a href="{{ route('admin.buku-induk') }}" class="{{ request()->routeIs('admin.buku-induk') ? 'active' : '' }}"><i class="bi bi-journal-bookmark-fill"></i> Buku Induk (Siswa)</a>
            <a href="{{ route('admin.data-guru') }}" class="{{ request()->routeIs('admin.data-guru') ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i> Data Guru & Wali Kelas</a>
            <a href="{{ route('admin.pembagian-kelas') }}" class="{{ request()->routeIs('admin.pembagian-kelas') ? 'active' : '' }}"><i class="bi bi-diagram-3-fill"></i> Pembagian Kelas</a>
            <a href="{{ route('admin.data-mapel') }}" class="{{ request()->routeIs('admin.data-mapel') ? 'active' : '' }}"><i class="bi bi-book-half"></i> Kelola Mapel</a>
            <a href="{{ route('admin.akun') }}" class="{{ request()->routeIs('admin.akun') ? 'active' : '' }}"><i class="bi bi-key-fill"></i> Kelola Akun & Password</a>
            
            <div class="menu-title">Akademik & Keuangan</div>
            <a href="{{ route('admin.verifikasi-spp') }}" class="{{ request()->routeIs('admin.verifikasi-spp') ? 'active' : '' }}"><i class="bi bi-credit-card-2-front-fill"></i> Verifikasi SPP</a>
            <a href="{{ route('admin.approval-izin') }}" class="{{ request()->routeIs('admin.approval-izin') ? 'active' : '' }}"><i class="bi bi-envelope-paper-heart"></i> Approval Izin</a>
            <a href="{{ route('admin.bank-soal') }}" class="{{ request()->routeIs('admin.bank-soal') ? 'active' : '' }}"><i class="bi bi-file-earmark-play-fill"></i> Bank Soal Kuis</a>
            <a href="{{ route('admin.prestasi-pelanggaran') }}" class="{{ request()->routeIs('admin.prestasi-pelanggaran') ? 'active' : '' }}"><i class="bi bi-trophy-fill"></i> Prestasi & Pelanggaran</a>
            <a href="{{ route('admin.kritik-saran') }}" class="{{ request()->routeIs('admin.kritik-saran') ? 'active' : '' }}"><i class="bi bi-chat-square-text-fill"></i> Kritik & Saran</a>

            <div class="menu-title">Laporan & Hasil</div>
            <a href="{{ route('admin.laporan-absensi') }}" class="{{ request()->routeIs('admin.laporan-absensi') ? 'active' : '' }}"><i class="bi bi-clock-history"></i> Laporan Absensi</a>
            <a href="{{ route('admin.laporan-spp') }}" class="{{ request()->routeIs('admin.laporan-spp') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Laporan Keuangan SPP</a>
            <a href="{{ route('admin.laporan-nilai') }}" class="{{ request()->routeIs('admin.laporan-nilai') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan Nilai</a>

        {{-- MENU GURU --}}
        @elseif($role == 'guru')
            <div class="menu-title">Menu Utama</div>
            <a href="{{ route('guru.dashboard') }}" class="{{ request()->routeIs('guru.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            
            <div class="menu-title">Kelola Akademik</div>
            <a href="{{ route('guru.data-guru') }}" class="{{ request()->routeIs('guru.data-guru') ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i> Data Guru & Wali Kelas</a>
            <a href="{{ route('guru.approval-izin') }}" class="{{ request()->routeIs('guru.approval-izin') ? 'active' : '' }}"><i class="bi bi-envelope-paper-heart"></i> Approval Izin & Sakit</a>
            <a href="{{ route('guru.bank-soal') }}" class="{{ request()->routeIs('guru.bank-soal') ? 'active' : '' }}"><i class="bi bi-file-earmark-play-fill"></i> Manajemen Bank Soal & Kuis</a>
            <a href="{{ route('guru.prestasi-pelanggaran') }}" class="{{ request()->routeIs('guru.prestasi-pelanggaran') ? 'active' : '' }}"><i class="bi bi-award-fill"></i> Prestasi & Tata Tertib</a>

            <div class="menu-title">Laporan</div>
            <a href="{{ route('guru.laporan-nilai') }}" class="{{ request()->routeIs('guru.laporan-nilai') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph-fill"></i> Laporan Nilai</a>

        {{-- MENU SISWA --}}
        @elseif($role == 'siswa')
            <div class="menu-title">Menu Utama</div>
            <a href="{{ route('siswa.dashboard') }}" class="{{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard Siswa</a>

            <div class="menu-title">Absensi</div>
            <a href="{{ route('siswa.kartu-digital') }}" class="{{ request()->routeIs('siswa.kartu-digital') ? 'active' : '' }}"><i class="bi bi-qr-code-scan"></i> Kartu Digital</a>
            <a href="{{ route('siswa.absensi') }}" class="{{ request()->routeIs('siswa.absensi') ? 'active' : '' }}"><i class="bi bi-calendar-check-fill"></i> Riwayat Absensi</a>

            <div class="menu-title">Akademik</div>
            <a href="{{ route('siswa.kuis') }}" class="{{ request()->routeIs('siswa.kuis*') ? 'active' : '' }}"><i class="bi bi-mortarboard-fill"></i> Kuis & Ranking</a>
            <a href="{{ route('siswa.hasil-kuis') }}" class="{{ request()->routeIs('siswa.hasil-kuis') ? 'active' : '' }}"><i class="bi bi-trophy-fill"></i> Hasil Kuis</a>
            <a href="{{ route('siswa.prestasi-pelanggaran') }}" class="{{ request()->routeIs('siswa.prestasi-pelanggaran') ? 'active' : '' }}"><i class="bi bi-award-fill"></i> Prestasi & Tata Tertib</a>

            <div class="menu-title">Keuangan</div>
            <a href="{{ route('siswa.status-spp') }}" class="{{ request()->routeIs('siswa.status-spp') ? 'active' : '' }}"><i class="bi bi-cash-coin"></i> Status SPP</a>
            <a href="{{ route('siswa.spp') }}" class="{{ request()->routeIs('siswa.spp') ? 'active' : '' }}"><i class="bi bi-upload"></i> Bayar / Upload Bukti SPP</a>
        @endif

        <div class="menu-title">Aksi</div>
        <form action="{{ route('logout') }}" method="POST" class="d-inline px-3">
            @csrf
            <button type="submit" class="btn btn-link text-danger p-0 border-0 text-decoration-none w-100 text-start">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
            </button>
        </form>
    </div>
</nav>

<!-- Main Content Area -->
<div class="main-content" id="main-content">
    <header class="topbar">
        <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle"><i class="bi bi-list fs-3 text-primary-custom"></i></button>
        
        <div class="d-flex align-items-center">
            @if(file_exists($logoPath))
                <img src="{{ asset('images/logo.png') }}" class="logo-sm me-2" alt="Logo">
            @else
                <div class="logo-fallback logo-sm me-2">NF</div>
            @endif
            <h6 class="mb-0 fw-bold text-dark d-none d-md-block">@yield('title', 'Dashboard')</h6>
        </div>

        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown me-3">
                <a href="#" class="text-dark position-relative" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill fs-5 text-primary-custom"></i>
                    @if ($role == 'admin' && isset($adminFeedbackNotifications) && $adminFeedbackNotifications->isNotEmpty())
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">{{ $adminFeedbackNotifications->count() }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                    @if ($role == 'admin')
                        @forelse ($adminFeedbackNotifications ?? [] as $notification)
                            <a class="dropdown-item" href="{{ route('admin.kritik-saran') }}">
                                <i class="bi bi-chat-square-text text-primary me-2"></i>
                                Kritik/saran baru dari {{ $notification->data['guardian_name'] }}
                            </a>
                        @empty
                            <span class="dropdown-item text-muted">Tidak ada notifikasi baru</span>
                        @endforelse
                    @else
                        <a class="dropdown-item" href="#"><i class="bi bi-cash text-warning me-2"></i> Notifikasi Pembayaran SPP</a>
                    @endif
                </div>
            </div>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name={{ ucfirst($role) }}&background=0d6efd&color=fff" class="rounded-circle me-2" width="35" height="35" alt="Avatar">
                    <span class="d-none d-lg-inline fw-semibold text-capitalize">{{ $role }}</span>
                    <i class="bi bi-chevron-down ms-2 small"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                    @if(auth()->user()->hasRole('admin'))
                        <li><h6 class="dropdown-header">Akses Role</h6></li>
                        <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield-lock me-2"></i> Admin</a></li>
                        <li><a class="dropdown-item" href="{{ route('guru.dashboard') }}"><i class="bi bi-person-badge me-2"></i> Guru</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.akun') }}"><i class="bi bi-key me-2"></i> Kelola Password</a></li>
                    @endif
                    <li><a class="dropdown-item" href="{{ route('account.password.edit') }}"><i class="bi bi-shield-lock me-2"></i> Ganti Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger border-0 bg-transparent">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content-wrapper">
        @yield('content')
    </main>

    <footer class="footer">
        Copyright &copy; 2024 <strong>NURFA.ID</strong> - Digital Platform MIS Nurul Falaq. <br>
        <small>Versi 1.0.0 | Laravel 11</small>
    </footer>
</div>

@php
    $mobileMenus = match ($role) {
        'admin' => [
            ['route' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Beranda'],
            ['route' => 'admin.buku-induk', 'icon' => 'bi-journal-bookmark-fill', 'label' => 'Siswa'],
            ['route' => 'admin.data-guru', 'icon' => 'bi-person-badge-fill', 'label' => 'Guru'],
            ['route' => 'admin.pembagian-kelas', 'icon' => 'bi-diagram-3-fill', 'label' => 'Kelas'],
            ['route' => 'admin.data-mapel', 'icon' => 'bi-book-half', 'label' => 'Mapel'],
            ['route' => 'admin.verifikasi-spp', 'icon' => 'bi-credit-card-2-front-fill', 'label' => 'SPP'],
            ['route' => 'admin.approval-izin', 'icon' => 'bi-envelope-paper-heart', 'label' => 'Izin'],
            ['route' => 'admin.bank-soal', 'icon' => 'bi-file-earmark-play-fill', 'label' => 'Kuis'],
            ['route' => 'admin.prestasi-pelanggaran', 'icon' => 'bi-trophy-fill', 'label' => 'Prestasi'],
            ['route' => 'admin.kritik-saran', 'icon' => 'bi-chat-square-text-fill', 'label' => 'Saran'],
            ['route' => 'admin.laporan', 'icon' => 'bi-bar-chart-fill', 'label' => 'Laporan'],
            ['route' => 'admin.laporan-absensi', 'icon' => 'bi-clock-history', 'label' => 'Absensi'],
            ['route' => 'admin.laporan-spp', 'icon' => 'bi-cash-stack', 'label' => 'Laporan SPP'],
            ['route' => 'admin.laporan-nilai', 'icon' => 'bi-file-earmark-bar-graph-fill', 'label' => 'Laporan Nilai'],
            ['route' => 'admin.akun', 'icon' => 'bi-key-fill', 'label' => 'Akun'],
        ],
        'guru' => [
            ['route' => 'guru.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Beranda'],
            ['route' => 'guru.data-guru', 'icon' => 'bi-person-badge-fill', 'label' => 'Guru'],
            ['route' => 'guru.approval-izin', 'icon' => 'bi-envelope-paper-heart', 'label' => 'Izin'],
            ['route' => 'guru.bank-soal', 'icon' => 'bi-file-earmark-play-fill', 'label' => 'Kuis'],
            ['route' => 'guru.prestasi-pelanggaran', 'icon' => 'bi-award-fill', 'label' => 'Prestasi'],
            ['route' => 'guru.laporan-nilai', 'icon' => 'bi-bar-chart-fill', 'label' => 'Nilai'],
        ],
        default => [
            ['route' => 'siswa.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Beranda'],
            ['route' => 'siswa.kartu-digital', 'icon' => 'bi-qr-code-scan', 'label' => 'Kartu'],
            ['route' => 'siswa.kuis', 'icon' => 'bi-mortarboard-fill', 'label' => 'Kuis'],
            ['route' => 'siswa.status-spp', 'icon' => 'bi-cash-coin', 'label' => 'SPP'],
            ['route' => 'siswa.absensi', 'icon' => 'bi-calendar-check-fill', 'label' => 'Absensi'],
            ['route' => 'siswa.hasil-kuis', 'icon' => 'bi-trophy-fill', 'label' => 'Hasil Kuis'],
            ['route' => 'siswa.prestasi-pelanggaran', 'icon' => 'bi-award-fill', 'label' => 'Prestasi'],
            ['route' => 'siswa.spp', 'icon' => 'bi-upload', 'label' => 'Bayar SPP'],
        ],
    };
@endphp

@php
    $primaryMobileMenus = array_slice($mobileMenus, 0, 4);
    $moreMobileMenus = array_slice($mobileMenus, 4);
@endphp

<nav class="bottom-nav" aria-label="Navigasi mobile">
    @foreach ($primaryMobileMenus as $menu)
        <a href="{{ route($menu['route']) }}" class="bottom-nav-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
            <i class="bi {{ $menu['icon'] }}"></i>
            <span>{{ $menu['label'] }}</span>
        </a>
    @endforeach
    <button type="button" class="bottom-nav-item bottom-nav-more" data-bs-toggle="offcanvas" data-bs-target="#mobileMoreMenu" aria-controls="mobileMoreMenu">
        <i class="bi bi-grid-3x3-gap-fill"></i>
        <span>Menu Lainnya</span>
    </button>
</nav>

<div class="offcanvas offcanvas-bottom mobile-more-menu" tabindex="-1" id="mobileMoreMenu" aria-labelledby="mobileMoreMenuLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold" id="mobileMoreMenuLabel">Menu Lainnya</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mobile-more-grid">
            @foreach ($moreMobileMenus as $menu)
                <a href="{{ route($menu['route']) }}" class="mobile-more-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                    <i class="bi {{ $menu['icon'] }}"></i>
                    <span>{{ $menu['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('account.password.edit') }}" class="mobile-more-item">
                <i class="bi bi-shield-lock-fill"></i><span>Ganti Password</span>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('main-content').classList.toggle('active');
    });
</script>
@stack('scripts')
</body>
</html>
