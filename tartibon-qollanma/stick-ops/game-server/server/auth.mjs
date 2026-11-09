import { createHmac, timingSafeEqual } from 'node:crypto';

const TOKEN_LIMIT = 2048;

export function validateSecret(secret) {
  if (typeof secret !== 'string' || Buffer.byteLength(secret) < 32) {
    throw new Error('OPS_SHARED_SECRET kamida 32 bayt bo‘lishi kerak.');
  }
  if (/^REPLACE_/i.test(secret)) {
    throw new Error('OPS_SHARED_SECRET namuna qiymatini yangi tasodifiy sir bilan almashtiring.');
  }
  return secret;
}

export function parseAllowedOrigins(value) {
  const values = Array.isArray(value) ? value : typeof value === 'string' ? value.split(',') : [];
  const result = new Set();
  for (const raw of values) {
    const item = String(raw).trim();
    if (!item) continue;
    let url;
    try { url = new URL(item); } catch { throw new Error('OPS_ALLOWED_ORIGINS manzili noto‘g‘ri.'); }
    const local = ['localhost', '127.0.0.1', '[::1]'].includes(url.hostname);
    if (url.origin !== item || url.username || url.password || (url.protocol !== 'https:' && !(local && url.protocol === 'http:'))) {
      throw new Error('OPS_ALLOWED_ORIGINS uchun aniq HTTPS origin kiriting (masalan, https://example.uz).');
    }
    result.add(url.origin);
  }
  if (!result.size) throw new Error('OPS_ALLOWED_ORIGINS majburiy.');
  return result;
}

/** Match the PHP base64url(payload) + HMAC-SHA256 ticket format exactly. */
export function verifyTicket(token, secret, now = Math.floor(Date.now() / 1000)) {
  if (typeof token !== 'string' || token.length > TOKEN_LIMIT) throw new Error('Kirish bileti noto‘g‘ri.');
  const pieces = token.split('.');
  if (pieces.length !== 2 || !pieces.every(p => /^[A-Za-z0-9_-]+$/.test(p))) throw new Error('Kirish bileti noto‘g‘ri.');
  const [encoded, signature] = pieces;
  const actual = Buffer.from(signature, 'base64url');
  const expected = createHmac('sha256', secret).update(encoded).digest();
  if (actual.length !== expected.length || !timingSafeEqual(actual, expected)) throw new Error('Kirish imzosi noto‘g‘ri.');
  let payload;
  try { payload = JSON.parse(Buffer.from(encoded, 'base64url').toString('utf8')); } catch { throw new Error('Kirish bileti noto‘g‘ri.'); }
  if (!payload || typeof payload !== 'object' || payload.aud !== 'stick-ops' ||
      typeof payload.sub !== 'string' || !/^[1-9]\d{0,19}$/.test(payload.sub) ||
      typeof payload.name !== 'string' || !payload.name.trim() || [...payload.name].length > 64 || /[\u0000-\u001f\u007f]/.test(payload.name) ||
      !Number.isSafeInteger(payload.iat) || !Number.isSafeInteger(payload.exp) ||
      payload.iat > now + 5 || payload.exp <= now || payload.exp <= payload.iat || payload.exp - payload.iat > 180 ||
      typeof payload.jti !== 'string' || !/^[a-f0-9]{32,128}$/i.test(payload.jti)) {
    throw new Error('Kirish bileti eskirgan yoki noto‘g‘ri. Ilovani qayta oching.');
  }
  return { id: payload.sub, name: payload.name.trim(), expiresAt: payload.exp, nonce: payload.jti };
}

/** Never evict unexpired nonces to make room: that would allow ticket replay. */
export class TicketGuard {
  constructor(limit = 20000) { this.used = new Map(); this.limit = limit; }
  prune(now = Math.floor(Date.now() / 1000)) {
    for (const [key, expiry] of this.used) if (expiry <= now) this.used.delete(key);
  }
  consume(ticket, now = Math.floor(Date.now() / 1000)) {
    this.prune(now);
    if (this.used.has(ticket.nonce)) throw new Error('Bu kirish bileti allaqachon ishlatilgan. Qayta ulaning.');
    if (this.used.size >= this.limit) throw new Error('Server band. Birozdan keyin qayta ulaning.');
    this.used.set(ticket.nonce, ticket.expiresAt);
  }
}
