import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
  jidNormalizedUser,
} from '@whiskeysockets/baileys';
import { mkdirSync } from 'fs';
import { join } from 'path';
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

mkdirSync(SESSION_DIR, { recursive: true });

const authDir = join(process.cwd(), SESSION_DIR);
const { state, saveCreds } = await useMultiFileAuthState(authDir);
const { version } = await fetchLatestBaileysVersion();

const client = makeWASocket({
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

async function sendJson(res, code, payload) {
  res.writeHead(code, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(payload));
}

async function startServer() {
  const http = await import('http');

  const server = http.createServer(async (req, res) => {
    const url = new URL(req.url, `http://${req.headers.host}`);

    if (req.method === 'GET' && url.pathname === '/health') {
      return sendJson(res, 200, {
        ok: true,
        connected: !!client.user,
        socket: client.user ? client.user.id : null,
      });
    }

    if (req.method === 'GET' && url.pathname === '/qr') {
      const qr = globalThis.__waQr || null;
      return sendJson(res, 200, {
        qr: qr,
        connected: !!client.user,
      });
    }

    if (req.method === 'POST' && url.pathname === '/send-message') {
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
}

client.ev.on('creds.update', saveCreds);

client.ev.on('connection.update', (update) => {
  const { connection, lastDisconnect, qr } = update;

  if (qr) {
    globalThis.__waQr = qr;
    console.log('QR received. Scan with your phone.');
    QRCode.generate(qr, { small: true });
  }

  if (connection === 'close') {
    const shouldReconnect = lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut;
    console.log('Connection closed due to', lastDisconnect?.error?.output?.statusCode, 'reconnect?', shouldReconnect);

    if (shouldReconnect) {
      setTimeout(() => {
        client.ws?.close();
      }, 3000);
    }
  }

  if (connection === 'open') {
    globalThis.__waQr = null;
    console.log('WhatsApp connected:', client.user?.id);
  }
});

client.ev.on('messages.upsert', async (m) => {
  const message = m.messages[0];
  if (!message || message.key.fromMe) return;

  const from = jidNormalizedUser(message.key.remoteJid || '');
  const body = message.message?.conversation || message.message?.extendedTextMessage?.text || '';

  console.log('Incoming message from', from, ':', body);
});

startServer();
