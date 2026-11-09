import test from 'node:test';
import assert from 'node:assert/strict';
import { createHmac, randomBytes } from 'node:crypto';
import { once } from 'node:events';
import { WebSocket } from 'ws';
import { verifyTicket, TicketGuard, parseAllowedOrigins } from '../server/auth.mjs';
import { createGameServer } from '../server/server.mjs';

const SECRET = 'local-test-only-secret-with-at-least-32-bytes';
const ORIGIN = 'https://telegram-app.example';
function ticket(id = '123', overrides = {}, secret = SECRET) {
  const now = Math.floor(Date.now() / 1000);
  const encoded = Buffer.from(JSON.stringify({ aud: 'stick-ops', sub: String(id), name: `O‘yinchi ${id}`,
    iat: now, exp: now + 120, jti: randomBytes(16).toString('hex'), ...overrides })).toString('base64url');
  return `${encoded}.${createHmac('sha256', secret).update(encoded).digest('base64url')}`;
}

test('PHP-compatible signed tickets validate identity and reject forgery, expiry and malformed claims', () => {
  const good = verifyTicket(ticket('456'), SECRET);
  assert.equal(good.id, '456');
  assert.equal(good.name, 'O‘yinchi 456');
  const now = Math.floor(Date.now() / 1000);
  assert.throws(() => verifyTicket(ticket('456', {}, SECRET + '-wrong'), SECRET), /imzosi/);
  assert.throws(() => verifyTicket(ticket('456', { iat: now - 150, exp: now - 1 }), SECRET), /eskirgan/);
  assert.throws(() => verifyTicket(ticket('456', { exp: now + 1000 }), SECRET));
  assert.throws(() => verifyTicket(ticket('456', { iat: now + 20, exp: now + 120 }), SECRET));
  assert.throws(() => verifyTicket(ticket('456', { aud: 'other-app' }), SECRET));
  assert.throws(() => verifyTicket(ticket('456', { sub: 'attacker' }), SECRET));
  assert.throws(() => verifyTicket(ticket('456', { name: '\u0000invalid' }), SECRET));
  assert.throws(() => verifyTicket('bad.signature', SECRET));
  assert.throws(() => verifyTicket('a'.repeat(3000), SECRET));
});

test('ticket replay guard never evicts an unexpired ticket to accept another', () => {
  const guard = new TicketGuard(1);
  const parsed = verifyTicket(ticket(), SECRET);
  guard.consume(parsed);
  assert.throws(() => guard.consume(parsed), /ishlatilgan/);
  assert.throws(() => guard.consume(verifyTicket(ticket('456'), SECRET)), /band/);
  guard.prune(parsed.expiresAt);
  assert.equal(guard.used.size, 0);
});

test('origins require exact HTTPS origins; loopback is allowed for local testing only', () => {
  assert.ok(parseAllowedOrigins([ORIGIN, 'http://127.0.0.1:8888']).has(ORIGIN));
  assert.throws(() => parseAllowedOrigins(''));
  assert.throws(() => parseAllowedOrigins('https://app.example/path'));
  assert.throws(() => parseAllowedOrigins('http://app.example'));
  assert.throws(() => parseAllowedOrigins('*'));
  assert.throws(() => createGameServer({ secret: 'short', allowedOrigins: [ORIGIN] }));
  assert.throws(() => createGameServer({ secret: 'REPLACE_WITH_A_RANDOM_SECRET_AT_LEAST_32_CHARACTERS_LONG', allowedOrigins: [ORIGIN] }), /namuna/);
});

async function fixture(t) {
  const app = createGameServer({ secret: SECRET, allowedOrigins: [ORIGIN] });
  const { port } = await app.listen(0, '127.0.0.1');
  const clients = [];
  t.after(async () => { for (const client of clients) client.ws.terminate(); await app.close(); });
  async function connect(id, token = ticket(id)) {
    const ws = new WebSocket(`ws://127.0.0.1:${port}/ws`, { origin: ORIGIN });
    const queue = [];
    const waiting = [];
    ws.on('message', raw => {
      const data = JSON.parse(raw.toString());
      const index = waiting.findIndex(w => w.type === data.type && (!w.predicate || w.predicate(data)));
      if (index === -1) queue.push(data);
      else { const match = waiting.splice(index, 1)[0]; clearTimeout(match.timeout); match.resolve(data); }
    });
    const client = { ws,
      send(data) { ws.send(JSON.stringify(data)); },
      next(type, predicate) {
        const index = queue.findIndex(data => data.type === type && (!predicate || predicate(data)));
        if (index !== -1) return Promise.resolve(queue.splice(index, 1)[0]);
        return new Promise((resolve, reject) => {
          const waiter = { type, predicate, resolve, reject };
          waiter.timeout = setTimeout(() => {
            const i = waiting.indexOf(waiter); if (i !== -1) waiting.splice(i, 1);
            reject(new Error(`Timed out waiting for ${type}`));
          }, 2500);
          waiting.push(waiter);
        });
      }
    };
    clients.push(client);
    await once(ws, 'open');
    if (token) { client.send({ type: 'auth', token }); await client.next('auth'); }
    return client;
  }
  return { app, port, connect };
}

test('WebSocket upgrade enforces origin; authentication and replay protection work over real sockets', async t => {
  const { port, connect } = await fixture(t);
  const bad = new WebSocket(`ws://127.0.0.1:${port}/ws`, { origin: 'https://evil.example' });
  bad.on('error', () => {});
  await assert.rejects(once(bad, 'open'), /403/);
  const guest = await connect('1', null);
  guest.send({ type: 'create' });
  assert.match((await guest.next('error')).message, /Avval/);
  const signed = ticket('22');
  await connect('22', signed);
  const second = await connect('22', null);
  second.send({ type: 'auth', token: signed });
  assert.match((await second.next('error')).message, /ishlatilgan/);
});

test('real 5v5 room caps capacity, enforces ownership/team limits, starts authoritative match and reconnects', async t => {
  const { app, connect } = await fixture(t);
  const host = await connect('100');
  host.send({ type: 'create' });
  const initial = (await host.next('room')).room;
  assert.equal(initial.players.length, 1);
  assert.equal(initial.hostId, '100');
  assert.match(initial.code, /^[A-Z2-9]{6}$/);
  host.send({ type: 'start' });
  assert.match((await host.next('error')).message, /5 tadan/);
  const players = [host];
  for (let i = 1; i < 10; i++) {
    const client = await connect(String(100 + i));
    client.send({ type: 'join', code: initial.code });
    const joined = (await client.next('room')).room;
    assert.equal(joined.players.length, i + 1);
    players.push(client);
  }
  const room = app.rooms.get(initial.code);
  assert.equal([...room.members.values()].filter(p => p.team === 'blue').length, 5);
  assert.equal([...room.members.values()].filter(p => p.team === 'red').length, 5);
  const outsider = await connect('999');
  outsider.send({ type: 'join', code: initial.code });
  assert.match((await outsider.next('error')).message, /10 o‘yinchi/);
  players[1].send({ type: 'start' });
  assert.match((await players[1].next('error')).message, /egasi/);
  host.send({ type: 'team', team: 'red' });
  assert.match((await host.next('error')).message, /to‘lgan/);
  host.send({ type: 'start' });
  const running = await host.next('room', message => message.room.status === 'playing');
  assert.equal(running.room.players.length, 10);
  const snapshot = (await host.next('state')).state;
  assert.equal(snapshot.phase, 'buy');
  assert.ok(snapshot.players.some(p => p.id === '100'));
  assert.equal(room.match.players.length, 10);
  // Additional client fields never become authoritative player state.
  host.send({ type: 'input', mx: 0, my: 0, angle: 0, hp: 999999, x: 999999, credits: 999999 });
  await new Promise(resolve => setTimeout(resolve, 80));
  const serverPlayer = room.match.players.find(p => p.id === '100');
  assert.equal(serverPlayer.hp, 100);
  assert.notEqual(serverPlayer.x, 999999);
  assert.notEqual(serverPlayer.credits, 999999);
  const closed = once(host.ws, 'close');
  host.ws.close(); await closed;
  const restored = await connect('100');
  assert.equal((await restored.next('room')).room.code, initial.code);
  assert.equal((await restored.next('state')).playerId, '100');
  assert.equal(room.members.get('100').connected, true);
  assert.equal(room.match.players.filter(p => p.id === '100').length, 1);
  restored.send({ type: 'leave' });
  await restored.next('left');
  assert.equal(room.members.has('100'), false);
  assert.equal(room.match.players.find(p => p.id === '100').alive, false);
  // A completed room can refill and start again without stale departed players.
  room.match.phase = 'finished'; room.match.winner = 'blue';
  await players[1].next('room', message => message.room.status === 'finished');
  outsider.send({ type: 'join', code: initial.code });
  assert.equal((await outsider.next('room')).room.players.length, 10);
  players[1].send({ type: 'start' });
  const rematch = await players[1].next('room', message => message.room.status === 'playing' && message.room.players.some(p => p.id === '999'));
  assert.equal(rematch.room.hostId, '101');
  assert.equal(room.match.players.some(p => p.id === '100'), false);
  assert.equal(room.match.players.some(p => p.id === '999'), true);
});

test('waiting host disconnect reassigns host and frees slot; HTTP exposes health only', async t => {
  const { app, connect, port } = await fixture(t);
  const host = await connect('200');
  host.send({ type: 'create' });
  const { code } = (await host.next('room')).room;
  const other = await connect('201'); other.send({ type: 'join', code }); await other.next('room');
  const closed = once(host.ws, 'close'); host.ws.close(); await closed;
  const reassigned = await other.next('room', data => data.room.hostId === '201');
  assert.equal(reassigned.room.players.length, 1);
  assert.equal(app.rooms.get(code).members.size, 1);
  assert.equal((await fetch(`http://127.0.0.1:${port}/health`)).status, 200);
  assert.equal((await fetch(`http://127.0.0.1:${port}/server/auth.mjs`)).status, 404);
});
