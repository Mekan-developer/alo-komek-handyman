require('dotenv').config();

const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const PORT = Number(process.env.PORT || 3000);
// 0.0.0.0 — phone on LAN can reach the bridge; auth secret is mandatory.
const HOST = process.env.HOST || '0.0.0.0';
const GATEWAY_SECRET = process.env.GATEWAY_SECRET || '';
const OTP_EVENT_NAME = process.env.OTP_EVENT_NAME || 'otp';

if (!GATEWAY_SECRET) {
  console.error('[gateway] GATEWAY_SECRET is required — refusing to start');
  process.exit(1);
}

const app = express();
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: false },
});

/**
 * Shared secret from the Flutter OTP Listener (auth.token) or HTTP header.
 * The universal otp app uses handshake.auth.token — keep that key stable.
 */
function extractSecret(source) {
  if (!source) {
    return '';
  }

  return String(
    source.auth?.token
      || source.auth?.secret
      || source.headers?.['x-gateway-secret']
      || '',
  );
}

io.use((socket, next) => {
  if (extractSecret(socket.handshake) !== GATEWAY_SECRET) {
    return next(new Error('Unauthorized'));
  }

  return next();
});

io.on('connection', (socket) => {
  console.log(`[gateway] client connected: ${socket.id} (total: ${io.engine.clientsCount})`);

  socket.on('disconnect', () => {
    console.log(`[gateway] client disconnected: ${socket.id} (total: ${io.engine.clientsCount})`);
  });
});

app.post('/emit-otp', (req, res) => {
  if (req.get('X-Gateway-Secret') !== GATEWAY_SECRET) {
    return res.status(401).json({ message: 'Unauthorized' });
  }

  const { phone_number: phoneNumber, otp } = req.body || {};

  if (!phoneNumber || !otp) {
    return res.status(422).json({ message: 'phone_number and otp are required' });
  }

  if (io.engine.clientsCount === 0) {
    console.warn('[gateway] no SMS-gateway phone connected, OTP event dropped');

    return res.status(503).json({ message: 'No gateway client connected' });
  }

  // Include `token` so the Flutter listener's AuthTokenValidation.matches() passes
  // when a shared secret is configured in the app settings.
  io.emit(OTP_EVENT_NAME, {
    phone_number: phoneNumber,
    otp,
    token: GATEWAY_SECRET,
  });
  console.log(`[gateway] OTP emitted for ${phoneNumber}`);

  return res.json({ message: 'OTP event emitted' });
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok', clients: io.engine.clientsCount });
});

server.listen(PORT, HOST, () => {
  console.log(`[gateway] socket.io server listening on ${HOST}:${PORT}, event="${OTP_EVENT_NAME}"`);
});
