<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pos Presensi - NURFA.ID</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { background-color: #0f172a; color: white; font-family: 'Nunito', sans-serif; overflow: hidden; height: 100vh; margin: 0; }
        .scan-container { display: flex; height: 100vh; }

        .camera-area { flex: 2; display: flex; flex-direction: column; padding: 30px; border-right: 1px solid #1e293b; }
        .header-presensi { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .clock { font-size: 3.5rem; font-weight: bold; letter-spacing: 2px; color: #3b82f6; }
        .date-today { font-size: 1.1rem; color: #94a3b8; }

        .camera-view {
            flex: 1; background-color: #000; border-radius: 15px; position: relative;
            overflow: hidden; box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
            border: 2px solid #1e293b; display: flex; justify-content: center; align-items: center;
        }
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
        .log-item.error { border-left-color: #ef4444; }

        .result-popup {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); width: 80%;
            background: white; color: #0f172a; padding: 20px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: none; text-align: center; animation: popIn 0.3s ease;
        }
        @keyframes popIn { from { transform: translate(-50%, 100%); opacity: 0; } to { transform: translate(-50%, 0); opacity: 1; } }
        .result-popup.show { display: block; }

        #scanInput { position: absolute; opacity: 0; pointer-events: none; }
    </style>
</head>
<body>

    <div class="scan-container">
        <div class="camera-area">
            <div class="header-presensi">
                <div>
                    <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-lock me-2"></i>Pos Presensi NURFA.ID</h4>
                    <div class="date-today" id="dateToday"></div>
                    <small class="text-muted">Aturan Masuk: Sebelum 07.15 WIB</small>
                </div>
                <div class="text-end">
                    <div class="clock" id="liveClock">--:--:--</div>
                    <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
                        <form action="{{ route('admin.laporan-absensi.toggle-late-blocking') }}" method="POST" class="form-check form-switch m-0" onchange="this.submit()">
                            @csrf
                            <input type="hidden" name="enabled" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="sakelarTelat" style="transform: scale(1.2);"
                                @checked($lateScanBlockingEnabled) @disabled(! auth()->user()->hasRole('admin'))>
                            <label class="form-check-label small text-muted" for="sakelarTelat">Blokir Scan Telat</label>
                        </form>
                    </div>
                </div>
            </div>

            <div class="camera-view">
                <div class="camera-placeholder">
                    <i class="bi bi-qr-code-scan fs-1"></i>
                    <p>Menunggu Scan Alat Presensi...</p>
                </div>

                <div class="scan-overlay">
                    <div class="scan-box">
                        <div class="scan-line"></div>
                    </div>
                    <p class="mt-3 text-primary fw-bold">Arahkan alat scanner ke QR Code Kartu Siswa</p>
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

    <!-- Input tersembunyi yang menangkap ketikan dari alat scanner (bertindak sebagai keyboard) -->
    <input type="text" id="scanInput" autocomplete="off" autofocus>

    <!-- Audio Element untuk Suara "Ting" -->
    <audio id="beepSound" src="https://www.soundjay.com/buttons/sounds/button-3.mp3" preload="auto"></audio>
    <audio id="errorSound" src="https://www.soundjay.com/buttons/sounds/button-10.mp3" preload="auto"></audio>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('liveClock').textContent = `${h}:${m}:${s}`;
            document.getElementById('dateToday').textContent = `${dayNames[now.getDay()]}, ${now.getDate()} ${monthNames[now.getMonth()]} ${now.getFullYear()}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Alat scanner QR fisik (USB/Bluetooth) bekerja sebagai keyboard: ia
        // "mengetik" isi QR lalu menekan Enter. Input ini selalu difokuskan
        // supaya ketikan dari alat tertangkap di sini, bukan di elemen lain.
        const scanInput = document.getElementById('scanInput');
        function refocusInput() { scanInput.focus(); }
        refocusInput();
        document.addEventListener('click', refocusInput);
        window.addEventListener('blur', refocusInput);

        scanInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') return;
            event.preventDefault();

            const token = scanInput.value.trim();
            scanInput.value = '';
            if (!token) return;

            processScan(token);
        });

        function processScan(token) {
            fetch(@json(route('scan-qr.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ qr_token: token }),
            })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => ok ? showSuccess(data) : showError(data.message ?? 'QR Code tidak dikenali.'))
                .catch(() => showError('Gagal terhubung ke server.'));
        }

        function showSuccess(data) {
            document.getElementById('beepSound').play().catch(() => {});

            const statusMap = {
                hadir: { text: 'HADIR', class: 'bg-success', log: 'success' },
                telat: { text: 'TELAT', class: 'bg-warning text-dark', log: 'late' },
                alpa: { text: 'ALPA (Scan Telat Diblokir)', class: 'bg-danger', log: 'error' },
                izin: { text: 'IZIN', class: 'bg-info', log: 'success' },
            };
            const eventLabel = data.event === 'check_out' ? ' - Pulang' : (data.event === 'duplicate' ? ' - Sudah Presensi' : ' - Masuk');
            const status = statusMap[data.status] ?? statusMap.hadir;

            const popup = document.getElementById('resultPopup');
            document.getElementById('popupGreeting').textContent = data.student.name;
            document.getElementById('popupGreeting').className = `mb-0 fw-bold text-${status.log === 'late' ? 'warning' : (status.log === 'error' ? 'danger' : 'success')}`;
            document.getElementById('popupDetail').textContent = `Kelas ${data.student.classroom}${eventLabel}`;
            document.getElementById('popupTime').textContent = data.time + ' WIB';
            document.getElementById('popupStatus').textContent = status.text;
            document.getElementById('popupStatus').className = `badge ${status.class}`;
            document.getElementById('popupImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.student.name)}&background=10b981&color=fff&bold=true`;
            popup.classList.add('show');
            setTimeout(() => popup.classList.remove('show'), 3000);

            addLogItem(data.student.name, data.student.classroom, data.time, status.text, status.log);
        }

        function showError(message) {
            document.getElementById('errorSound').play().catch(() => {});

            const popup = document.getElementById('resultPopup');
            document.getElementById('popupGreeting').textContent = 'Scan Gagal';
            document.getElementById('popupGreeting').className = 'mb-0 fw-bold text-danger';
            document.getElementById('popupDetail').textContent = message;
            document.getElementById('popupTime').textContent = '';
            document.getElementById('popupStatus').textContent = '';
            document.getElementById('popupImg').src = 'https://ui-avatars.com/api/?name=%21&background=ef4444&color=fff&bold=true';
            popup.classList.add('show');
            setTimeout(() => popup.classList.remove('show'), 3000);

            addLogItem('Scan Gagal', message, new Date().toLocaleTimeString('id-ID'), 'Ditolak', 'error');
        }

        function addLogItem(name, detail, time, statusText, logClass) {
            const logsList = document.getElementById('logsList');
            const logItem = document.createElement('div');
            logItem.className = `log-item ${logClass}`;
            logItem.innerHTML = `
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=${logClass === 'error' ? 'ef4444' : '10b981'}&color=fff&bold=true" class="rounded-circle me-3" width="45" height="45" alt="">
                <div class="flex-grow-1">
                    <div class="fw-bold text-white">${name}</div>
                    <small class="text-muted">${detail}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-${logClass === 'late' ? 'warning' : (logClass === 'error' ? 'danger' : 'success')}">${time}</div>
                    <small class="text-muted">${statusText}</small>
                </div>
            `;
            logsList.prepend(logItem);
        }
    </script>
</body>
</html>
