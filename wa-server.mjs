import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  jidNormalizedUser,
} from '@whiskeysockets/baileys';
import { mkdirSync, rmSync } from 'fs';
import { join } from 'path';
import { createRequire } from 'module';
import http from 'http';
import QRCode from 'qrcode-terminal';

const QRMatrix = createRequire(import.meta.url)('qrcode-terminal/vendor/QRCode');

const PORT = Number(process.env.PORT || 3001);
const SESSION_DIR = process.env.WA_SESSION_DIR || './wa-session';

const logger = {
  child: () => logger,
  info: () => {},
  warn: () => {},
  error: () => {},
  debug: () => {},
  trace: () => {},
};

const authDir = join(process.cwd(), SESSION_DIR);
const { version } = await fetchLatestBaileysVersion();

let client = null;
let connected = false;
let currentQr = null;
let connecting = false;
/** starting | waiting_qr | connected | logged_out | reconnecting */
let connectionState = 'starting';
let loggedOutAt = null;
let lastError = null;

/**
 * QR sebagai gambar SVG supaya halaman admin bisa menampilkannya langsung.
 * Dipakai encoder yang sudah ada di qrcode-terminal — QR login WhatsApp
 * tidak boleh dikirim ke layanan QR pihak ketiga karena siapa pun yang
 * memindainya bisa menautkan perangkatnya ke akun WhatsApp sekolah.
 */
function qrToSvg(text) {
  const qr = new QRMatrix(-1, 1);
  qr.addData(text);
  qr.make();

  const count = qr.getModuleCount();
  const quiet = 2;
  const size = count + quiet * 2;
  let path = '';

  for (let row = 0; row < count; row++) {
    for (let col = 0; col < count; col++) {
      if (qr.isDark(row, col)) {
        path += `M${col + quiet} ${row + quiet}h1v1h-1z`;
      }
    }
  }

  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}" shape-rendering="crispEdges" role="img" aria-label="QR login WhatsApp"><rect width="${size}" height="${size}" fill="#ffffff"/><path d="${path}" fill="#000000"/></svg>`;
}

function statusPayload() {
  return {
    ok: true,
    state: connectionState,
    connected,
    user: connected ? (client?.user?.id ?? null) : null,
    name: connected ? (client?.user?.name ?? null) : null,
    qr: currentQr ? qrToSvg(currentQr) : null,
    logged_out_at: loggedOutAt,
    last_error: lastError,
  };
}

/**
 * Hapus kredensial sesi supaya koneksi berikutnya memulai dari QR baru.
 */
function clearSession() {
  rmSync(authDir, { recursive: true, force: true });
  connected = false;
  currentQr = null;
  connectionState = 'logged_out';
  loggedOutAt = new Date().toISOString();
}

/**
 * Buat socket WhatsApp baru. Dipanggil ulang setiap koneksi putus karena
 * socket Baileys yang sudah tertutup tidak bisa dipakai lagi (mis. setelah
 * scan QR, WhatsApp selalu meminta restart dengan kode 515).
 */
async function connect() {
  if (connecting) {
    return;
  }
  connecting = true;

  let saveCreds;

  try {
    mkdirSync(authDir, { recursive: true });
    const auth = await useMultiFileAuthState(authDir);
    saveCreds = auth.saveCreds;

    client = makeWASocket({
      version,
      logger,
      printQRInTerminal: false,
      auth: {
        creds: auth.state.creds,
        keys: makeCacheableSignalKeyStore(auth.state.keys, undefined),
      },
      generateHighQualityLinkPreview: false,
      syncFullHistory: false,
      browser: ['SisfoSekolah', 'Chrome', '1.0.0'],
    });
  } catch (error) {
    connecting = false;
    lastError = error.message || 'Gagal membuka sesi WhatsApp.';
    console.log('Gagal membuat socket WhatsApp:', lastError, '- mencoba lagi...');
    setTimeout(connect, 5000);

    return;
  }

  connecting = false;

  client.ev.on('creds.update', saveCreds);

  client.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      currentQr = qr;
      connectionState = 'waiting_qr';
      lastError = null;
      console.log('QR received. Scan with your phone (WhatsApp > Perangkat Tertaut).');
      QRCode.generate(qr, { small: true });
    }

    if (connection === 'open') {
      connected = true;
      currentQr = null;
      connectionState = 'connected';
      loggedOutAt = null;
      lastError = null;
      console.log('WhatsApp connected:', client.user?.id);
    }

    if (connection === 'close') {
      connected = false;
      const statusCode = lastDisconnect?.error?.output?.statusCode;

      if (statusCode === DisconnectReason.loggedOut) {
        console.log('Session logged out (401). Clearing session, scan the new QR.');
        clearSession();
      } else {
        connectionState = 'reconnecting';
        lastError = statusCode ? `Koneksi tertutup (kode ${statusCode}).` : null;
        console.log('Connection closed due to', statusCode, '- reconnecting...');
      }

      setTimeout(connect, 2000);
    }
  });

  client.ev.on('messages.upsert', async (m) => {
    const message = m.messages[0];
    if (!message || message.key.fromMe) return;

    const from = jidNormalizedUser(message.key.remoteJid || '');
    const body = message.message?.conversation || message.message?.extendedTextMessage?.text || '';

    console.log('Incoming message from', from, ':', body);
  });
}

function sendJson(res, code, payload) {
  res.writeHead(code, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(payload));
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);

  if (req.method === 'GET' && url.pathname === '/health') {
    return sendJson(res, 200, {
      ok: true,
      connected,
      socket: connected ? client.user?.id : null,
    });
  }

  if (req.method === 'GET' && url.pathname === '/qr') {
    return sendJson(res, 200, { qr: currentQr, connected });
  }

  if (req.method === 'GET' && url.pathname === '/status') {
    return sendJson(res, 200, statusPayload());
  }

  // Keluar dari akun WhatsApp yang sekarang, lalu siapkan QR baru supaya
  // admin bisa langsung menautkan nomor lain dari halaman web.
  if (req.method === 'POST' && url.pathname === '/logout') {
    try {
      if (client && connected) {
        await client.logout();
      } else {
        client?.end?.(undefined);
      }
    } catch (error) {
      console.log('Logout error (session tetap dihapus):', error.message);
    }

    clearSession();
    connecting = false;
    setTimeout(connect, 500);

    return sendJson(res, 200, { ok: true, state: connectionState });
  }

  if (req.method === 'POST' && url.pathname === '/send-message') {
    if (!connected) {
      return sendJson(res, 503, { ok: false, message: 'WhatsApp belum terhubung.' });
    }

    try {
      const chunks = [];
      for await (const chunk of req) chunks.push(chunk);
      const body = JSON.parse(Buffer.concat(chunks).toString() || '{}');

      if (!body.to || !body.text) {
        return sendJson(res, 400, { ok: false, message: 'Field to dan text wajib diisi.' });
      }

      const recipient = body.to.includes('@') ? body.to : `${body.to}@s.whatsapp.net`;
      await client.sendMessage(recipient, { text: body.text });

      return sendJson(res, 200, { ok: true, to: recipient });
    } catch (error) {
      return sendJson(res, 500, { ok: false, message: error.message || 'Gagal mengirim WhatsApp message.' });
    }
  }

  return sendJson(res, 404, { ok: false, message: 'Route not found.' });
});

server.listen(PORT, () => {
  console.log(`Baileys server running on http://localhost:${PORT}`);
});

connect();
