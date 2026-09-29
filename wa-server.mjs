import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  jidNormalizedUser,
} from '@whiskeysockets/baileys';
import { mkdirSync, rmSync } from 'fs';
import { join } from 'path';
import http from 'http';
import QRCode from 'qrcode-terminal';

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

/**
 * Buat socket WhatsApp baru. Dipanggil ulang setiap koneksi putus karena
 * socket Baileys yang sudah tertutup tidak bisa dipakai lagi (mis. setelah
 * scan QR, WhatsApp selalu meminta restart dengan kode 515).
 */
async function connect() {
  mkdirSync(authDir, { recursive: true });
  const { state, saveCreds } = await useMultiFileAuthState(authDir);

  client = makeWASocket({
    version,
    logger,
    printQRInTerminal: false,
    auth: {
      creds: state.creds,
      keys: makeCacheableSignalKeyStore(state.keys, undefined),
    },
    generateHighQualityLinkPreview: false,
    syncFullHistory: false,
    browser: ['SisfoSekolah', 'Chrome', '1.0.0'],
  });

  client.ev.on('creds.update', saveCreds);

  client.ev.on('connection.update', (update) => {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      currentQr = qr;
      console.log('QR received. Scan with your phone (WhatsApp > Perangkat Tertaut).');
      QRCode.generate(qr, { small: true });
    }

    if (connection === 'open') {
      connected = true;
      currentQr = null;
      console.log('WhatsApp connected:', client.user?.id);
    }

    if (connection === 'close') {
      connected = false;
      const statusCode = lastDisconnect?.error?.output?.statusCode;

      if (statusCode === DisconnectReason.loggedOut) {
        console.log('Session logged out (401). Clearing session, scan the new QR.');
        rmSync(authDir, { recursive: true, force: true });
      } else {
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
