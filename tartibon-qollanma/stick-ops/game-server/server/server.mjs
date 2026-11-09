import http from 'node:http';
import { randomInt } from 'node:crypto';
import { pathToFileURL } from 'node:url';
import { WebSocket, WebSocketServer } from 'ws';
import { createMatch, stepMatch, buyWeapon, snapshotFor } from '../shared/game.js';
import { validateSecret, parseAllowedOrigins, verifyTicket, TicketGuard } from './auth.mjs';

const ROOM_LIMIT = 100;
const ROOM_CAPACITY = 10;
const RECONNECT_MS = 20000;
const ROOM_TTL_MS = 10 * 60 * 1000;
const ZERO_INPUT = Object.freeze({ mx: 0, my: 0, angle: 0, fire: false, reload: false, interact: false });
const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

export function createGameServer(options = {}) {
  const secret = validateSecret(options.secret);
  const allowedOrigins = parseAllowedOrigins(options.allowedOrigins);
  const rooms = new Map();
  const memberships = new Map();
  const sockets = new Map();
  const tickets = new TicketGuard();
  let shuttingDown = false;
  let tickCount = 0;
  let lastTick = performance.now();

  const server = http.createServer((request, response) => {
    response.setHeader('Content-Type', 'application/json; charset=utf-8');
    response.setHeader('Cache-Control', 'no-store');
    response.setHeader('X-Content-Type-Options', 'nosniff');
    if (request.method === 'GET' && request.url === '/health') {
      response.writeHead(shuttingDown ? 503 : 200);
      response.end(JSON.stringify({ ok: !shuttingDown, service: 'stick-ops', rooms: rooms.size }));
    } else {
      response.writeHead(404);
      response.end(JSON.stringify({ ok: false, error: 'Topilmadi' }));
    }
  });
  const wss = new WebSocketServer({ noServer: true, maxPayload: 4096, perMessageDeflate: false });

  server.on('upgrade', (request, socket, head) => {
    if (shuttingDown || request.url !== '/ws' || !allowedOrigins.has(request.headers.origin) || wss.clients.size >= 1100) {
      socket.end('HTTP/1.1 403 Forbidden\r\nConnection: close\r\nContent-Length: 0\r\n\r\n');
      return;
    }
    wss.handleUpgrade(request, socket, head, ws => wss.emit('connection', ws, request));
  });

  function send(ws, data) {
    if (!ws || ws.readyState !== WebSocket.OPEN) return;
    if (ws.bufferedAmount > 256 * 1024) { ws.close(1013, 'Ulanish sekin. Qayta ulaning.'); return; }
    ws.send(JSON.stringify(data));
  }
  function fail(ws, message) { send(ws, { type: 'error', message }); }
  function publicRoom(room) {
    return { code: room.code, hostId: room.hostId, status: room.status, maxPlayers: ROOM_CAPACITY,
      players: [...room.members.values()].map(p => ({ id: p.id, name: p.name, team: p.team, connected: !!p.connected })) };
  }
  function broadcastRoom(room) {
    const data = { type: 'room', room: publicRoom(room) };
    for (const member of room.members.values()) send(sockets.get(member.id), data);
  }
  function publishStates(room) {
    if (!room.match) return;
    for (const member of room.members.values()) {
      if (member.connected) send(sockets.get(member.id), { type: 'state', playerId: member.id, state: snapshotFor(room.match, member.id) });
    }
  }
  function nextHost(room) {
    if (!room.members.get(room.hostId)?.connected) room.hostId = [...room.members.values()].find(p => p.connected)?.id || [...room.members.keys()][0] || null;
  }
  function deleteRoom(room) {
    for (const member of room.members.values()) if (memberships.get(member.id) === room.code) memberships.delete(member.id);
    rooms.delete(room.code);
  }
  function leaveRoom(id) {
    const code = memberships.get(id), room = rooms.get(code);
    memberships.delete(id);
    if (!room) return;
    room.members.delete(id);
    room.inputs.delete(id);
    const player = room.match?.players.find(p => p.id === id);
    if (player) { player.connected = false; player.alive = false; player.hp = 0; }
    nextHost(room);
    room.lastActivity = Date.now();
    if (!room.members.size) deleteRoom(room); else broadcastRoom(room);
  }
  function newCode() {
    for (let tries = 0; tries < 30; tries++) {
      let code = '';
      for (let i = 0; i < 6; i++) code += ALPHABET[randomInt(ALPHABET.length)];
      if (!rooms.has(code)) return code;
    }
    throw new Error('Xona kodini yaratib bo‘lmadi. Qayta urinib ko‘ring.');
  }
  function addMember(room, user) {
    const blue = [...room.members.values()].filter(p => p.team === 'blue').length;
    const red = room.members.size - blue;
    room.members.set(user.id, { ...user, team: blue <= red ? 'blue' : 'red', connected: true, disconnectedAt: null });
    memberships.set(user.id, room.code);
    room.lastActivity = Date.now();
    broadcastRoom(room);
  }

  wss.on('connection', ws => {
    ws.user = null;
    ws.alive = true;
    ws.windowStarted = Date.now(); ws.inputCount = 0; ws.actionCount = 0; ws.messageCount = 0;
    const authTimeout = setTimeout(() => ws.close(4001, 'Kirish vaqti tugadi.'), 5000);
    authTimeout.unref();
    ws.on('pong', () => { ws.alive = true; });
    ws.on('error', () => {});
    ws.on('message', (raw, binary) => {
      const now = Date.now();
      if (now - ws.windowStarted >= 1000) {
        ws.windowStarted = now; ws.inputCount = 0; ws.actionCount = 0; ws.messageCount = 0;
      }
      // Count malformed frames too, before parsing or generating an error response.
      if (++ws.messageCount > 130) { ws.close(1008, 'Juda ko‘p so‘rov.'); return; }
      if (binary) { ws.close(1003, 'Faqat JSON xabarlar.'); return; }
      let data;
      try { data = JSON.parse(raw.toString()); } catch { fail(ws, 'JSON xabari noto‘g‘ri.'); return; }
      if (!data || typeof data !== 'object' || Array.isArray(data) || typeof data.type !== 'string') { fail(ws, 'Xabar turi noto‘g‘ri.'); return; }
      if (data.type === 'input') {
        if (++ws.inputCount > 60) { if (ws.inputCount > 120) ws.close(1008, 'Juda ko‘p so‘rov.'); return; }
      } else if (++ws.actionCount > 10) { fail(ws, 'Juda ko‘p so‘rov. Biroz kuting.'); return; }

      if (!ws.user) {
        if (data.type !== 'auth') { fail(ws, 'Avval ilova orqali kiring.'); return; }
        try {
          const user = verifyTicket(data.token, secret);
          tickets.consume(user);
          clearTimeout(authTimeout);
          // A fresh ticket replaces the old tab; only this socket can control the player.
          const old = sockets.get(user.id);
          ws.user = { id: user.id, name: user.name };
          sockets.set(user.id, ws);
          if (old && old !== ws) old.close(4009, 'Boshqa oynada ochildi.');
          send(ws, { type: 'auth', playerId: user.id, name: user.name });
          const room = rooms.get(memberships.get(user.id));
          if (room) {
            const member = room.members.get(user.id);
            if (member) {
              member.connected = true; member.disconnectedAt = null; member.name = user.name;
              const player = room.match?.players.find(p => p.id === user.id);
              if (player) player.connected = true;
              nextHost(room); broadcastRoom(room);
              if (room.match) send(ws, { type: 'state', playerId: user.id, state: snapshotFor(room.match, user.id) });
            }
          }
        } catch (error) { fail(ws, error.message); ws.close(4001, 'Kirish rad etildi.'); }
        return;
      }
      if (sockets.get(ws.user.id) !== ws) return;
      const id = ws.user.id;
      let room = rooms.get(memberships.get(id));
      if (data.type === 'auth') { fail(ws, 'Siz allaqachon kirgansiz.'); return; }
      if (data.type === 'create') {
        if (room) { fail(ws, 'Avval joriy xonadan chiqing.'); return; }
        if (rooms.size >= ROOM_LIMIT) { fail(ws, 'Xonalar to‘lgan. Keyinroq urinib ko‘ring.'); return; }
        const code = newCode();
        room = { code, hostId: id, status: 'waiting', members: new Map(), inputs: new Map(), match: null,
          createdAt: now, lastActivity: now, finishedAt: null };
        rooms.set(code, room); addMember(room, ws.user); return;
      }
      if (data.type === 'join') {
        const code = typeof data.code === 'string' ? data.code.trim().toUpperCase() : '';
        if (!/^[A-Z2-9]{6}$/.test(code)) { fail(ws, '6 belgili xona kodini kiriting.'); return; }
        if (room) {
          if (room.code === code) send(ws, { type: 'room', room: publicRoom(room) });
          else fail(ws, 'Avval joriy xonadan chiqing.');
          return;
        }
        room = rooms.get(code);
        if (!room) { fail(ws, 'Xona topilmadi.'); return; }
        if (room.status === 'playing') { fail(ws, 'Bu xonada jang boshlangan.'); return; }
        if (room.members.size >= ROOM_CAPACITY) { fail(ws, 'Xonada 10 o‘yinchi bor.'); return; }
        addMember(room, ws.user); return;
      }
      if (data.type === 'leave') { leaveRoom(id); send(ws, { type: 'left' }); return; }
      if (!room) { fail(ws, 'Avval xona yarating yoki unga kiring.'); return; }
      room.lastActivity = now;
      if (data.type === 'team') {
        if (room.status !== 'waiting' && room.status !== 'finished') { fail(ws, 'Jang paytida jamoani o‘zgartirib bo‘lmaydi.'); return; }
        if (data.team !== 'blue' && data.team !== 'red') { fail(ws, 'Jamoa noto‘g‘ri.'); return; }
        const member = room.members.get(id);
        if (member.team !== data.team && [...room.members.values()].filter(p => p.team === data.team).length >= 5) { fail(ws, 'Bu jamoa to‘lgan.'); return; }
        member.team = data.team; broadcastRoom(room); return;
      }
      if (data.type === 'start') {
        if (room.hostId !== id) { fail(ws, 'Jangni faqat xona egasi boshlaydi.'); return; }
        if (room.status !== 'waiting' && room.status !== 'finished') { fail(ws, 'Jang allaqachon boshlangan.'); return; }
        const roster = [...room.members.values()];
        if (roster.length !== 10 || roster.some(p => !p.connected) || roster.filter(p => p.team === 'blue').length !== 5) {
          fail(ws, 'Boshlash uchun ikkala jamoada 5 tadan ulangan o‘yinchi kerak.'); return;
        }
        room.match = createMatch(roster.map(p => ({ id: p.id, name: p.name, team: p.team, connected: true, bot: false })));
        room.inputs.clear(); room.status = 'playing'; room.finishedAt = null;
        broadcastRoom(room); publishStates(room); return;
      }
      if (!room.match || room.status !== 'playing') { fail(ws, 'Jang hali boshlanmagan.'); return; }
      if (data.type === 'input') {
        if (!Number.isFinite(data.mx) || !Number.isFinite(data.my) || !Number.isFinite(data.angle)) return;
        room.inputs.set(id, { mx: Math.max(-1, Math.min(1, data.mx)), my: Math.max(-1, Math.min(1, data.my)),
          angle: ((data.angle % (2 * Math.PI)) + 2 * Math.PI) % (2 * Math.PI),
          fire: data.fire === true, reload: data.reload === true, interact: data.interact === true, receivedAt: now });
        return;
      }
      if (data.type === 'buy') {
        if (typeof data.weapon !== 'string' || data.weapon.length > 20) { fail(ws, 'Qurol noto‘g‘ri.'); return; }
        const result = buyWeapon(room.match, id, data.weapon);
        if (result === false || result?.ok === false) fail(ws, result?.error || 'Qurolni hozir sotib olib bo‘lmaydi.');
        else { send(ws, { type: 'bought', weapon: data.weapon }); publishStates(room); }
        return;
      }
      fail(ws, 'Noma’lum amal.');
    });
    ws.on('close', () => {
      clearTimeout(authTimeout);
      if (!ws.user || sockets.get(ws.user.id) !== ws) return;
      const id = ws.user.id; sockets.delete(id);
      const room = rooms.get(memberships.get(id));
      if (!room) return;
      if (room.status === 'waiting' || room.status === 'finished') { leaveRoom(id); return; }
      const member = room.members.get(id);
      if (member) { member.connected = false; member.disconnectedAt = Date.now(); }
      room.inputs.set(id, { ...ZERO_INPUT, receivedAt: Date.now() });
      const player = room.match?.players.find(p => p.id === id);
      if (player) player.connected = false;
      nextHost(room); broadcastRoom(room);
    });
  });

  const ticker = setInterval(() => {
    const current = performance.now();
    const dt = Math.min(0.1, Math.max(0, (current - lastTick) / 1000)); lastTick = current; tickCount++;
    const now = Date.now();
    for (const room of rooms.values()) {
      if (room.status !== 'playing' || !room.match) continue;
      for (const member of room.members.values()) {
        if (!member.connected && member.disconnectedAt && now - member.disconnectedAt > RECONNECT_MS) {
          const player = room.match.players.find(p => p.id === member.id);
          if (player) { player.alive = false; player.hp = 0; }
        }
      }
      for (const [id, input] of room.inputs) if (!input.receivedAt || now - input.receivedAt > 500) room.inputs.set(id, { ...ZERO_INPUT, receivedAt: now });
      stepMatch(room.match, room.inputs, dt);
      if (room.match.phase === 'finished') {
        room.status = 'finished'; room.finishedAt = now;
        // Finished rooms can refill before a rematch; departed players must not hold slots forever.
        for (const member of room.members.values()) if (!member.connected) {
          room.members.delete(member.id); room.inputs.delete(member.id);
          if (memberships.get(member.id) === room.code) memberships.delete(member.id);
        }
        nextHost(room); broadcastRoom(room);
      }
      if (tickCount % 2 === 0 || room.status === 'finished') publishStates(room);
    }
  }, 1000 / 30);
  ticker.unref();
  const heartbeat = setInterval(() => {
    for (const ws of wss.clients) {
      if (!ws.alive) { ws.terminate(); continue; }
      ws.alive = false; if (ws.readyState === WebSocket.OPEN) ws.ping();
    }
    tickets.prune();
    const now = Date.now();
    for (const room of rooms.values()) {
      const allOffline = ![...room.members.values()].some(p => p.connected);
      if ((allOffline && now - room.lastActivity > ROOM_TTL_MS) ||
          (room.status === 'finished' && now - room.finishedAt > ROOM_TTL_MS)) {
        for (const member of room.members.values()) send(sockets.get(member.id), { type: 'left', reason: 'Xona muddati tugadi.' });
        deleteRoom(room);
      }
    }
  }, 15000);
  heartbeat.unref();

  return {
    server, wss, rooms,
    listen(port = options.port ?? 8080, host = options.host ?? '0.0.0.0') {
      return new Promise((resolve, reject) => {
        server.once('error', reject);
        server.listen(port, host, () => { server.off('error', reject); resolve(server.address()); });
      });
    },
    async close() {
      if (shuttingDown) return;
      shuttingDown = true; clearInterval(ticker); clearInterval(heartbeat);
      for (const ws of wss.clients) ws.terminate();
      await new Promise(resolve => wss.close(resolve));
      if (server.listening) await new Promise(resolve => server.close(resolve));
      rooms.clear(); sockets.clear(); memberships.clear();
    }
  };
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  let app;
  try {
    const port = Number(process.env.PORT || 8080);
    if (!Number.isInteger(port) || port < 1 || port > 65535) throw new Error('PORT noto‘g‘ri.');
    app = createGameServer({ secret: process.env.OPS_SHARED_SECRET, allowedOrigins: process.env.OPS_ALLOWED_ORIGINS, port });
    const address = await app.listen();
    console.log(`STICK OPS serveri ishga tushdi: ${address.port}`);
    for (const signal of ['SIGINT', 'SIGTERM']) process.once(signal, async () => {
      await app.close(); process.exit(0);
    });
  } catch (error) {
    console.error(error.message); if (app) await app.close(); process.exitCode = 1;
  }
}
