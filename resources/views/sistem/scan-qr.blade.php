<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pos Presensi - NURFA.ID</title>
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
    <style>
        body { background-color: #0f172a; color: white; font-family: 'Nunito', sans-serif; overflow: hidden; height: 100vh; margin: 0; }
        .scan-container { display: flex; height: 100vh; }
        .camera-area { flex: 2; display: flex; flex-direction: column; padding: 30px; border-right: 1px solid #1e293b; }
        .header-presensi { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .clock { font-size: 3.5rem; font-weight: bold; letter-spacing: 2px; color: #3b82f6; }
        .date-today { font-size: 1.1rem; color: #94a3b8; }
        .camera-view { flex: 1; background-color: #000; border-radius: 15px; position: relative; overflow: hidden; box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); border: 2px solid #1e293b; display: flex; justify-content: center; align-items: center; }
        .camera-placeholder { color: #475569; text-align: center; }
        .scan-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; flex-direction: column; }
        .scan-box { width: 70%; height: 70%; border: 3px solid rgba(59, 130, 246, 0.5); border-radius: 15px; position: relative; box-shadow: 0 0 0 10000px rgba(0,0,0,0.5); }
        .scan-line { position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: #3b82f6; box-shadow: 0 0 10px #3b82f6; animation: scan 2s linear infinite; }
        @keyframes scan { 0% { top: 0%; } 50% { top: 95%; } 100% { top: 0%; } }
        .logs-area { flex: 1; padding: 30px; display: flex; flex-direction: column; }
        .logs-list { flex: 1; overflow-y: auto; margin-top: 15px; padding-right: 10px; }
        .logs-list::-webkit-scrollbar { width: 6px; }
        .logs-list::-webkit-scrollbar-track { background: #1e293b; }
        .logs-list::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 5px; }
        .log-item { background-color: #1e293b; padding: 15px; border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; border-left: 4px solid transparent; }
        .log-item.success { border-left-color: #10b981; }
        .log-item.late { border-left-color: #f59e0b; }
        .result-popup { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); width: 80%; background: white; color: #0f172a; padding: 20px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: none; text-align: center; animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: translate(-50%, 100%); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
        .result-popup.show { display: block; }
        .switch-warning { display: none; }
    </style>
</head>
<body>

<div class="scan-container">
    <div class="camera-area">
        <div class="header-presensi">
            <div>
                <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-lock me-2"></i>Pos Presensi NURFA.ID</h4>
                <div class="date-today" id="dateToday">Senin, 20 Mei 2024</div>
                <small class="text-muted">Aturan Masuk: Sebelum 07.15 WIB</small>
            </div>
            <div class="text-end d-flex flex-column align-items-end gap-2">
                <!-- Tombol Kembali ke Login -->
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Kembali ke Login
                </a>
                <div class="clock" id="liveClock">08:15:23</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="sakelarTelat" checked style="transform: scale(1.2);">
                        <label class="form-check-label small text-muted" for="sakelarTelat">Mode Deteksi Telat</label>
                    </div>
                </div>
                <div class="alert alert-danger p-1 mt-1 small switch-warning" id="warningSakelar">
                    <i class="bi bi-exclamation-triangle-fill"></i> Peringatan Telat Dimatikan Admin!
                </div>
            </div>
        </div>
        
        <div class="camera-view">
            <div class="camera-placeholder">
                <i class="bi bi-camera-video-off fs-1"></i>
                <p>Camera Feed Dummy</p>
            </div>
            <div class="scan-overlay">
                <div class="scan-box"><div class="scan-line"></div></div>
                <p class="mt-3 text-primary fw-bold">Arahkan QR Code ID Card ke dalam boks</p>
            </div>

            <div class="result-popup" id="resultPopup">
                <div class="d-flex align-items-center justify-content-center">
                    <img id="popupImg" src="" class="rounded-circle me-3" width="60" height="60" alt="">
                    <div class="text-start">
                        <h5 id="popupGreeting" class="mb-0 fw-bold"></h5>
                        <small id="popupDetail" class="text-muted"></small>
                        <div class="mt-1">
                            <span id="popupTime" class="badge bg-primary"></span>
                            <span id="popupStatus" class="badge"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="logs-area">
        <h5 class="fw-bold text-white border-bottom pb-2 mb-3"><i class="bi bi-clock-history me-2 text-primary"></i>Log Aktivitas Presensi</h5>
        <div class="logs-list" id="logsList"></div>
    </div>
</div>

<!-- Audio Element untuk Suara "Ting" -->
<audio id="beepSound" src="https://www.soundjay.com/buttons/sounds/button-3.mp3" preload="auto"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Jam Real-time
    function updateClock() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('liveClock').textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Logic Sakelar Telat
    const sakelar = document.getElementById('sakelarTelat');
    const warningSakelar = document.getElementById('warningSakelar');
    sakelar.addEventListener('change', function() {
        warningSakelar.style.display = this.checked ? 'none' : 'block';
    });

    // Dummy Auto Scanner
    const dummyScans = [
        { name: 'Ahmad Fauzi', time: '07:10:00', avatarBg: '10b981' }, // Tepat Waktu
        { name: 'Siti Aminah', time: '07:20:15', avatarBg: 'f59e0b' }, // Telat
        { name: 'Budi Santoso', time: '06:45:00', avatarBg: '10b981' }  // Tepat Waktu
    ];
    let scanIndex = 0;

    function simulateScan() {
        const data = dummyScans[scanIndex % dummyScans.length];
        scanIndex++;

        // Mainkan suara "Ting"
        const beep = document.getElementById('beepSound');
        beep.play().catch(error => console.log("Autoplay dicegah browser"));

        // Tentukan status berdasarkan jam (07:15:00) dan status sakelar
        const timeParts = data.time.split(':');
        const scanTime = new Date();
        scanTime.setHours(parseInt(timeParts[0]), parseInt(timeParts[1]), parseInt(timeParts[2]));
        
        const batasMasuk = new Date();
        batasMasuk.setHours(7, 15, 0); // 07:15:00 WIB

        let isLate = scanTime > batasMasuk;
        let statusText = "HADIR";
        let statusClass = "bg-success";
        let logClass = "success";

        if(isLate && !sakelar.checked) {
            statusText = "HADIR (SISTEM)";
            statusClass = "bg-primary";
            logClass = "success";
        } else if(isLate) {
            statusText = "TELAT";
            statusClass = "bg-warning text-dark";
            logClass = "late";
        }

        // Show Popup
        const popup = document.getElementById('resultPopup');
        document.getElementById('popupGreeting').textContent = data.name;
        document.getElementById('popupGreeting').className = `mb-0 fw-bold text-${logClass === 'late' ? 'warning' : 'success'}`;
        document.getElementById('popupDetail').textContent = "Kelas VI - MIS Nurul Falaq";
        document.getElementById('popupTime').textContent = data.time + " WIB";
        document.getElementById('popupTime').className = "badge bg-primary me-1";
        
        const statusBadge = document.getElementById('popupStatus');
        statusBadge.textContent = statusText;
        statusBadge.className = `badge ${statusClass}`;
        
        document.getElementById('popupImg').src = `https://ui-avatars.com/api/?name=${data.name.replace(' ', '+')}&background=${data.avatarBg}&color=fff&bold=true`;
        popup.classList.add('show');

        // Add to Log List
        const logsList = document.getElementById('logsList');
        const logItem = document.createElement('div');
        logItem.className = `log-item ${logClass}`;
        logItem.innerHTML = `
            <img src="https://ui-avatars.com/api/?name=${data.name.replace(' ', '+')}&background=${data.avatarBg}&color=fff&bold=true" class="rounded-circle me-3" width="45" height="45" alt="">
            <div class="flex-grow-1">
                <div class="fw-bold text-white">${data.name}</div>
                <small class="text-muted">Kelas VI</small>
            </div>
            <div class="text-end">
                <div class="fw-bold text-${logClass === 'late' ? 'warning' : 'success'}">${data.time}</div>
                <small class="text-muted">${statusText}</small>
            </div>
        `;
        logsList.prepend(logItem);

        // Hide popup
        setTimeout(() => { popup.classList.remove('show'); }, 3000);
    }

    // Jalankan simulasi setiap 5 detik
    setInterval(simulateScan, 5000);
</script>
</body>
</html>