<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - NURFA.ID</title>
    
    <!-- 1. Favicon Aman -->
    @php
        $faviconPath = public_path('images/logo.png');
        $faviconUrl = asset('images/logo.png');
    @endphp
    @if(file_exists($faviconPath))
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}" />
    @else
        <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20100%20100'%3E%3Crect%20width='100'%20height='100'%20rx='20'%20fill='%230d6efd'/%3E%3Ctext%20x='50'%20y='68'%20font-size='50'%20text-anchor='middle'%20fill='white'%20font-family='Arial'%20font-weight='bold'%3ENF%3C/text%3E%3C/svg%3E" />
    @endif

    <!-- 2. Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- 3. Bootstrap Icons (INI SANGAT PENTING AGAR IKON MUNCUL) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- 4. Custom CSS -->
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>
<body>

@php
    // Deteksi role otomatis dari URL
    $role = request()->segment(1); 
    if($role == 'wali-murid') $role = 'wali';
    if(empty($role) || !in_array($role, ['admin', 'guru', 'siswa', 'wali'])) $role = 'admin';
    
    $logoPath = public_path('images/logo.png');
    $logoUrl = asset('images/logo.png');
    
    // Definisi Menu Bottom Nav Berdasarkan Role (Sesuai Revisi Final)
    $menus = [
        'admin' => [
            ['route' => 'admin.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
            ['route' => 'admin.buku-induk', 'icon' => 'bi-journal-bookmark-fill', 'label' => 'Buku Induk'],
            ['route' => 'admin.data-guru', 'icon' => 'bi-person-badge-fill', 'label' => 'Data Guru'],
            ['route' => 'admin.data-kelas', 'icon' => 'bi-diagram-3-fill', 'label' => 'Pembagian Kelas'],
            ['route' => 'admin.verifikasi-spp', 'icon' => 'bi-credit-card-2-front-fill', 'label' => 'Verifikasi SPP'],
            ['route' => 'admin.approval-izin', 'icon' => 'bi-file-earmark-check-fill', 'label' => 'Approval Izin'],
            ['route' => 'admin.laporan', 'icon' => 'bi-bar-chart-line-fill', 'label' => 'Laporan'],
            ['route' => 'admin.prestasi-pelanggaran', 'icon' => 'bi-trophy-fill', 'label' => 'Prestasi'],
        ],
        'guru' => [
            ['route' => 'guru.data-guru', 'icon' => 'bi-person-badge-fill', 'label' => 'Data Guru'],
            ['route' => 'guru.bank-soal', 'icon' => 'bi-journal-text', 'label' => 'Bank Soal'],
            ['route' => 'guru.input-nilai', 'icon' => 'bi-pencil-square', 'label' => 'Input Nilai'],
            ['route' => 'guru.approval-izin', 'icon' => 'bi-envelope-check-fill', 'label' => 'Approval Izin'],
        ],
        'siswa' => [
            ['route' => 'siswa.jadwal', 'icon' => 'bi-calendar-week', 'label' => 'Jadwal'],
            ['route' => 'siswa.nilai-kuis', 'icon' => 'bi-award-fill', 'label' => 'Nilai & Kuis'],
            ['route' => 'siswa.presensi', 'icon' => 'bi-calendar-check-fill', 'label' => 'Presensi'],
            ['route' => 'siswa.spp', 'icon' => 'bi-credit-card-2-front-fill', 'label' => 'Bayar SPP'],
        ],
        'wali' => [
            ['route' => 'wali.dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
            ['route' => 'wali.absensi-nilai', 'icon' => 'bi-card-checklist', 'label' => 'Absensi & Nilai'],
            ['route' => 'wali.hasil-kuis', 'icon' => 'bi-mortarboard-fill', 'label' => 'Hasil Kuis'],
            ['route' => 'wali.prestasi-pelanggaran', 'icon' => 'bi-trophy-fill', 'label' => 'Prestasi'],
            ['route' => 'wali.tagihan-spp', 'icon' => 'bi-receipt', 'label' => 'Tagihan SPP'],
            ['route' => 'wali.ajukan-izin', 'icon' => 'bi-file-earmark-plus-fill', 'label' => 'Ajukan Izin'],
        ]
    ];
@endphp

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Topbar Header -->
    <header class="topbar">
        <div class="d-flex align-items-center">
            @if(file_exists($logoPath))
                <img src="{{ $logoUrl }}" class="logo-sm me-2" alt="Logo">
            @else
                <div class="logo-fallback logo-sm me-2">NF</div>
            @endif
            <h6 class="mb-0 fw-bold text-dark d-none d-md-block">@yield('title', 'Dashboard')</h6>
        </div>

        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown me-3">
                <a href="#" class="text-dark position-relative" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill fs-5 text-primary-custom"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                    <a class="dropdown-item" href="#"><i class="bi bi-cash text-warning me-2"></i> 2 SPP Menunggu</a>
                </div>
            </div>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name={{ ucfirst($role) }}&background=0d6efd&color=fff" class="rounded-circle me-2" width="35" height="35">
                    <span class="d-none d-lg-inline fw-semibold text-capitalize">{{ $role }}</span>
                    <i class="bi bi-chevron-down ms-2 small"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm">
                    <li><h6 class="dropdown-header">Switch Role (Testing)</h6></li>
                    <li><a class="dropdown-item" href="{{ route('admin.dashboard') }}"><i class="bi bi-shield-lock me-2"></i> Admin</a></li>
                    <li><a class="dropdown-item" href="{{ route('guru.data-guru') }}"><i class="bi bi-person-badge me-2"></i> Guru</a></li>
                    <li><a class="dropdown-item" href="{{ route('siswa.jadwal') }}"><i class="bi bi-mortarboard me-2"></i> Siswa</a></li>
                    <li><a class="dropdown-item" href="{{ route('wali.dashboard') }}"><i class="bi bi-people me-2"></i> Wali Murid</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('login') }}"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="content-wrapper">
        @yield('content')
    </main>

    <footer class="footer text-center">
        <small>Copyright &copy; 2024 <strong>NURFA.ID</strong> - MIS Nurul Falaq</small>
    </footer>
</div>

<!-- Bottom Navigation Bar -->
<nav class="bottom-nav">
    @foreach($menus[$role] as $menu)
        <a href="{{ route($menu['route']) }}" class="bottom-nav-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
            <i class="bi {{ $menu['icon'] }}"></i>
            <span>{{ $menu['label'] }}</span>
        </a>
    @endforeach
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>