<?php
declare(strict_types=1);

/**
 * Dasturxon — Telegram Wallet uslubidagi mini app. BITTA FAYL:
 * CSS, HTML va JavaScript shu faylda. Ma’lumotlar mavjud /api/*.php dan olinadi.
 */

require dirname(__DIR__) . '/includes/bootstrap.php';

$nonce = base64_encode(random_bytes(16));
send_security_headers('mini', $nonce);

$brand = e(setting('restaurant_name', 'Dasturxon'));
?>
<!doctype html>
<html lang="uz" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#f2f2f7">
<meta name="robots" content="noindex">
<title><?= $brand ?></title>
<link rel="icon" href="/assets/images/icon.svg" type="image/svg+xml">
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<style>
:root {
  --bg: #f2f2f7; --card: #ffffff; --text: #000000; --sub: #8e8e93;
  --sep: rgba(60, 60, 67, .14); --chip: #e9e9ef; --accent: #f28c28; --accent-ink: #c96b12;
  --accent-soft: #fff1e2; --ok: #34c759; --bad: #ff3b30; --bar: rgba(255, 255, 255, .9);
  --r: 18px; --r-sm: 14px; --safe-b: env(safe-area-inset-bottom, 0px); --safe-t: env(safe-area-inset-top, 0px);
}
:root[data-theme="dark"] {
  --bg: #000000; --card: #1c1c1e; --text: #ffffff; --sub: #8e8e93;
  --sep: rgba(84, 84, 88, .65); --chip: #2c2c2e; --accent-soft: #3a2a18; --accent-ink: #ffb066; --bar: rgba(28, 28, 30, .92);
}
@media (prefers-color-scheme: dark) {
  :root:not([data-theme="light"]) {
    --bg: #000000; --card: #1c1c1e; --text: #ffffff; --sub: #8e8e93;
    --sep: rgba(84, 84, 88, .65); --chip: #2c2c2e; --accent-soft: #3a2a18; --accent-ink: #ffb066; --bar: rgba(28, 28, 30, .92);
  }
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
[hidden] { display: none !important; }
html, body { margin: 0; background: var(--bg); color: var(--text); }
body { font: 15px/1.35 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; min-height: 100vh; padding-top: var(--safe-t); }
button, input, textarea { font: inherit; color: inherit; }
button { cursor: pointer; border: 0; background: none; padding: 0; }
button:disabled { opacity: .45; cursor: not-allowed; }
a { color: var(--accent-ink); text-decoration: none; }
h1, h2, h3, p { margin: 0; }
.app { max-width: 560px; margin: 0 auto; padding: 14px 16px calc(92px + var(--safe-b)); min-height: 100vh; }
.ic { width: 22px; height: 22px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; flex: none; }
.muted, small { color: var(--sub); font-size: 13px; }
.title { font-size: 30px; font-weight: 800; letter-spacing: -.02em; line-height: 1.1; margin: 4px 0 6px; }
.title.small { font-size: 26px; margin: 2px 0 14px; }
.sub { display: flex; align-items: center; gap: 7px; color: var(--sub); font-size: 13px; }
.dot { width: 8px; height: 8px; border-radius: 50%; background: var(--sub); }
.dot.open { background: var(--ok); } .dot.closed { background: var(--bad); }
.top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.hello { display: flex; align-items: center; gap: 10px; }
.hello strong { display: block; font-size: 16px; }
.hello small { display: block; }
.avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--accent-soft); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; font-size: 18px; flex: none; }
.avatar.lg { width: 60px; height: 60px; font-size: 24px; }
.round { position: relative; width: 48px; height: 48px; border-radius: 50%; background: var(--card); display: grid; place-items: center; color: var(--text); flex: none; }
.round.accent { background: var(--accent-soft); color: var(--accent-ink); width: 56px; height: 56px; }
.round.plain { background: transparent; }
.dot-badge { position: absolute; top: 2px; right: 2px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px; background: var(--accent); color: #fff; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; font-style: normal; }
.hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; background: var(--accent); color: #fff; border-radius: 22px; padding: 18px; margin: 14px 0 16px; min-height: 150px; cursor: pointer; }
.hero small { color: rgba(255,255,255,.85); font-size: 13px; }
.hero h2 { font-size: 22px; font-weight: 800; margin: 4px 0; letter-spacing: -.01em; }
.hero p { font-size: 14px; color: rgba(255,255,255,.9); }
.hero .pill { background: #fff; color: var(--accent-ink); font-weight: 700; font-size: 13px; padding: 8px 12px; border-radius: 999px; white-space: nowrap; }
.actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; margin-bottom: 20px; }
.actions button { display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; }
.actions .round { width: 58px; height: 58px; background: var(--card); color: var(--accent-ink); }
.sec-head { display: flex; align-items: baseline; justify-content: space-between; margin: 20px 4px 10px; }
.sec-head h3 { font-size: 19px; font-weight: 800; letter-spacing: -.01em; }
.sec-head a, .sec-head small { font-size: 14px; color: var(--accent-ink); font-weight: 600; }
.sec-head small { color: var(--sub); font-weight: 500; }
.cats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px 6px; }
.cat { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; text-align: center; }
.cat-ico { width: 60px; height: 60px; border-radius: 50%; display: grid; place-items: center; font-size: 22px; font-weight: 800; background: hsl(var(--h) 65% 92%); color: hsl(var(--h) 50% 36%); }
.card { background: var(--card); border-radius: var(--r); overflow: hidden; }
.card-list { background: var(--card); border-radius: var(--r); overflow: hidden; }
.row { display: flex; align-items: center; gap: 12px; padding: 12px 14px; position: relative; cursor: pointer; }
.row + .row::before { content: ""; position: absolute; top: 0; left: 82px; right: 0; border-top: 1px solid var(--sep); }
.row.static { cursor: default; }
.row-main { flex: 1; min-width: 0; }
.row-title { font-weight: 650; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.row-sub { color: var(--sub); font-size: 13px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: 2px; }
.row-price { font-weight: 700; font-size: 14px; margin-top: 4px; }
.row-price span { color: var(--sub); font-weight: 500; font-size: 12px; }
.old { text-decoration: line-through; color: var(--sub); font-weight: 500; margin-right: 6px; }
.thumb { width: 58px; height: 58px; border-radius: 14px; overflow: hidden; flex: none; display: grid; place-items: center; background: var(--chip); }
.thumb img { width: 100%; height: 100%; object-fit: cover; }
.ph { width: 100%; height: 100%; display: grid; place-items: center; font-weight: 800; font-size: 22px; background: hsl(var(--h) 65% 92%); color: hsl(var(--h) 50% 36%); }
.add { width: 36px; height: 36px; border-radius: 50%; background: var(--accent); color: #fff; display: grid; place-items: center; flex: none; }
.add .ic { width: 18px; height: 18px; stroke-width: 2.4; }
.chips { display: flex; gap: 8px; overflow-x: auto; margin: 0 -16px 14px; padding: 2px 16px; scrollbar-width: none; }
.chips::-webkit-scrollbar { display: none; }
.chip { flex: none; height: 36px; padding: 0 16px; border-radius: 999px; background: var(--chip); font-weight: 600; font-size: 14px; }
.chip.on { background: var(--text); color: var(--bg); }
.search { display: flex; align-items: center; gap: 8px; background: var(--card); border-radius: var(--r-sm); padding: 0 14px; height: 46px; margin-bottom: 12px; color: var(--sub); }
.search input { flex: 1; min-width: 0; border: 0; background: transparent; outline: none; font-size: 16px; color: var(--text); }
.segmented { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: 3px; background: var(--chip); border-radius: 12px; padding: 3px; margin-bottom: 14px; }
.segmented button { height: 38px; border-radius: 9px; font-weight: 650; font-size: 14px; color: var(--sub); display: flex; align-items: center; justify-content: center; gap: 6px; }
.segmented button.on { background: var(--card); color: var(--text); box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.segmented .ic { width: 17px; height: 17px; }
.btn { height: 50px; border-radius: var(--r-sm); padding: 0 18px; font-weight: 700; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-align: center; }
.btn.primary { background: var(--accent); color: #fff; }
.btn.ghost { background: var(--chip); color: var(--text); }
.btn.danger { background: rgba(255,59,48,.12); color: var(--bad); }
.btn.lg { height: 56px; font-size: 17px; }
.btn.block { width: 100%; }
.btn.sm { height: 38px; font-size: 14px; border-radius: 11px; padding: 0 14px; }
.bottom-bar { position: fixed; left: 0; right: 0; bottom: calc(64px + var(--safe-b)); padding: 10px 16px; background: linear-gradient(to top, var(--bg) 70%, transparent); pointer-events: none; }
.bottom-bar .btn { pointer-events: auto; max-width: 528px; margin: 0 auto; display: flex; width: 100%; box-shadow: 0 6px 18px rgba(0,0,0,.12); }
.field { display: flex; flex-direction: column; gap: 6px; }
.field > span { font-size: 13px; color: var(--sub); font-weight: 600; margin-left: 4px; }
.field input, .field textarea, .inp { width: 100%; background: var(--card); border: 0; border-radius: var(--r-sm); padding: 13px 14px; font-size: 16px; outline: none; color: var(--text); }
.field input:focus, .field textarea:focus, .inp:focus { box-shadow: inset 0 0 0 1.5px var(--accent); }
.field textarea { min-height: 70px; resize: vertical; }
.form { display: flex; flex-direction: column; gap: 10px; padding: 14px; }
.form-row { display: flex; gap: 8px; }
.form-row .inp { flex: 1; min-width: 0; }
.stepper { display: inline-flex; align-items: center; gap: 4px; background: var(--chip); border-radius: 999px; padding: 4px; }
.stepper button { width: 36px; height: 36px; border-radius: 50%; background: var(--card); display: grid; place-items: center; }
.stepper button .ic { width: 18px; height: 18px; }
.stepper output { min-width: 34px; text-align: center; font-weight: 800; font-size: 16px; }
.stepper.sm button { width: 30px; height: 30px; }
.stepper.sm output { min-width: 28px; font-size: 14px; }
.kv { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 14px; }
.kv b { font-weight: 700; white-space: nowrap; }
.round.on { color: var(--accent); }
.round.on .ic { fill: currentColor; }
.summary { padding: 6px 0; }
.summary .line { display: flex; justify-content: space-between; padding: 10px 14px; color: var(--sub); }
.summary .line strong { color: var(--text); font-weight: 650; }
.summary .line.total { color: var(--text); font-size: 18px; font-weight: 800; border-top: 1px solid var(--sep); margin-top: 4px; }
.summary .line.disc strong { color: var(--ok); }
.note-bad { color: var(--bad); font-size: 13px; padding: 0 4px; }
.notice { background: rgba(255,59,48,.1); color: var(--bad); border-radius: var(--r-sm); padding: 12px 14px; font-size: 14px; margin-bottom: 12px; }
.empty { text-align: center; padding: 42px 18px; color: var(--sub); }
.empty .ic { width: 46px; height: 46px; color: var(--accent); margin-bottom: 8px; }
.empty h2 { color: var(--text); font-size: 20px; margin-bottom: 6px; }
.empty .btn { margin-top: 14px; }
.skel { background: var(--card); border-radius: var(--r); height: 84px; margin-bottom: 10px; animation: pulse 1.3s ease-in-out infinite; }
@keyframes pulse { 50% { opacity: .55; } }
.pv-img { border-radius: 24px; overflow: hidden; aspect-ratio: 4 / 3; background: var(--chip); margin-bottom: 14px; }
.pv-img img { width: 100%; height: 100%; object-fit: cover; }
.pv-img .ph { font-size: 64px; }
.price-line { display: flex; align-items: baseline; gap: 8px; margin: 6px 0 12px; }
.price-line strong { font-size: 24px; font-weight: 800; }
.price-line span { color: var(--sub); }
.groups { display: flex; flex-direction: column; gap: 14px; margin: 16px 0; }
.group .sec-head { margin: 0 4px 8px; }
.group .sec-head small { font-size: 12px; }
.opt { display: flex; align-items: center; gap: 12px; width: 100%; padding: 13px 14px; position: relative; text-align: left; }
.opt + .opt::before { content: ""; position: absolute; top: 0; left: 46px; right: 0; border-top: 1px solid var(--sep); }
.opt .tick { width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--sub); display: grid; place-items: center; flex: none; color: #fff; }
.opt.on .tick { background: var(--accent); border-color: var(--accent); }
.opt.on .tick .ic { width: 14px; height: 14px; stroke-width: 3; }
.opt-name { flex: 1; font-weight: 550; }
.opt-price { color: var(--sub); font-size: 14px; }
.opt.off { opacity: .45; }
.stepper-row { display: flex; align-items: center; justify-content: space-between; background: var(--card); border-radius: var(--r); padding: 10px 12px 10px 14px; margin: 14px 0; }
.stepper-row b { font-weight: 650; }
.hint { font-size: 13px; color: var(--sub); min-height: 16px; padding: 0 4px; margin: 6px 0 10px; }
.hint.ok { color: var(--ok); } .hint.err { color: var(--bad); }
.order { background: var(--card); border-radius: var(--r); padding: 14px; display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px; }
.order-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.order-head strong { font-size: 17px; }
.order-items { list-style: none; margin: 0; padding: 0; font-size: 14px; display: flex; flex-direction: column; gap: 3px; }
.order-items span { color: var(--sub); }
.order-foot { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.order-foot strong { font-size: 17px; }
.order-acts { display: flex; gap: 8px; flex-wrap: wrap; }
.badge { display: inline-flex; align-items: center; height: 26px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.b-info { background: rgba(0,122,255,.13); color: #007aff; }
.b-warn { background: rgba(255,149,0,.15); color: #c77700; }
.b-accent { background: var(--accent-soft); color: var(--accent-ink); }
.b-ok { background: rgba(52,199,89,.15); color: #248a3d; }
.b-bad { background: rgba(255,59,48,.12); color: var(--bad); }
.b-muted { background: var(--chip); color: var(--sub); }
.timeline { list-style: none; margin: 4px 0 0; padding: 0 0 0 16px; border-left: 2px solid var(--sep); display: flex; flex-direction: column; gap: 10px; }
.timeline li { position: relative; font-size: 14px; font-weight: 600; }
.timeline li::before { content: ""; position: absolute; left: -22px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--accent); }
.timeline small { display: block; font-weight: 400; }
.live { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: var(--sub); margin: 8px 0; }
.live i { width: 7px; height: 7px; border-radius: 50%; background: var(--ok); animation: pulse 1.8s infinite; }
.stats { display: grid; grid-template-columns: 1fr 1fr; text-align: center; padding: 16px 8px; margin-bottom: 14px; }
.stats strong { display: block; font-size: 20px; font-weight: 800; }
.stats small { font-size: 12px; }
.profile { display: flex; align-items: center; gap: 14px; padding: 16px; margin-bottom: 14px; }
.profile strong { display: block; font-size: 18px; }
.info-row { display: flex; align-items: center; gap: 12px; padding: 13px 14px; position: relative; }
.info-row + .info-row::before { content: ""; position: absolute; top: 0; left: 48px; right: 0; border-top: 1px solid var(--sep); }
.info-row .round { width: 34px; height: 34px; background: var(--accent-soft); color: var(--accent-ink); }
.info-row .round .ic { width: 18px; height: 18px; }
.info-row div { min-width: 0; }
.info-row small { display: block; }
.info-row a { color: var(--accent-ink); font-weight: 600; }
.tabbar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 20; height: calc(64px + var(--safe-b)); padding-bottom: var(--safe-b); background: var(--bar); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border-top: 1px solid var(--sep); display: grid; grid-template-columns: repeat(4, 1fr); }
.tabbar button { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; font-size: 11px; font-weight: 600; color: var(--sub); }
.tabbar button.on { color: var(--accent); }
.tabbar .ic { width: 24px; height: 24px; }
.tabbar .dot-badge { top: 4px; right: calc(50% - 22px); }
.sheet-bg { position: fixed; inset: 0; z-index: 50; background: rgba(0,0,0,.4); display: flex; align-items: flex-end; justify-content: center; }
.sheet { width: 100%; max-width: 560px; max-height: 84vh; overflow: auto; background: var(--bg); border-radius: 26px 26px 0 0; padding: 12px 16px calc(18px + var(--safe-b)); animation: up .25s ease; }
@keyframes up { from { transform: translateY(30px); opacity: 0; } }
.sheet .grab { width: 40px; height: 5px; border-radius: 3px; background: var(--sep); margin: 0 auto 14px; }
.sheet h2 { font-size: 20px; font-weight: 800; margin-bottom: 12px; }
.sheet .card-list + .card-list { margin-top: 10px; }
.sheet-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
.toast { position: fixed; top: calc(12px + var(--safe-t)); left: 50%; transform: translate(-50%, -140%); z-index: 100; background: var(--text); color: var(--bg); padding: 12px 18px; border-radius: 14px; font-size: 14px; font-weight: 600; max-width: calc(100% - 32px); transition: transform .3s ease; box-shadow: 0 8px 24px rgba(0,0,0,.18); }
.toast.show { transform: translate(-50%, 0); }
.toast.error { background: var(--bad); color: #fff; }
.toast.success { background: var(--ok); color: #fff; }
.success { text-align: center; padding: 30px 8px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.success .round { width: 84px; height: 84px; background: rgba(52,199,89,.15); color: var(--ok); }
.success .round .ic { width: 40px; height: 40px; stroke-width: 2.6; }
.success .btn { width: 100%; margin-top: 4px; }
.back { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.back .round { width: 42px; height: 42px; }
.back strong { font-size: 16px; max-width: 60%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* Faqat opacity: transform ishlatilmaydi, aks holda position:fixed (tugma, varaq) noto‘g‘ri joylashadi */
.fade { animation: fade .25s ease both; }
@keyframes fade { from { opacity: 0; } }
@media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; transition: none !important; } }
</style>
</head>
<body>
<div class="app" id="view" aria-live="polite"><div class="skel"></div><div class="skel"></div></div>

<nav class="tabbar" aria-label="Asosiy">
  <button data-tab="home" data-go="#/home"><svg class="ic" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>Bosh</button>
  <button data-tab="menu" data-go="#/menu"><svg class="ic" viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>Menyu</button>
  <button data-tab="cart" data-go="#/cart"><svg class="ic" viewBox="0 0 24 24"><path d="M6 7h12l1 13H5zM9 7V5a3 3 0 0 1 6 0v2"/></svg>Savat<b class="dot-badge" data-badge hidden>0</b></button>
  <button data-tab="profile" data-go="#/profile"><svg class="ic" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>Profil</button>
</nav>
<div class="toast" id="toast" role="status"></div>

<script nonce="<?= $nonce ?>">
(function () {
  'use strict';

  /* ---------- Telegram WebApp ---------- */
  var tg = window.Telegram && window.Telegram.WebApp ? window.Telegram.WebApp : null;
  if (tg) {
    try {
      tg.ready(); tg.expand();
      if (tg.setHeaderColor) tg.setHeaderColor('secondary_bg_color');
      if (tg.setBackgroundColor) tg.setBackgroundColor('#f2f2f7');
      document.documentElement.setAttribute('data-theme', tg.colorScheme === 'dark' ? 'dark' : 'light');
      if (tg.onEvent) tg.onEvent('themeChanged', function () {
        document.documentElement.setAttribute('data-theme', tg.colorScheme === 'dark' ? 'dark' : 'light');
      });
    } catch (e) { /* eski Telegram */ }
  }

  /* ---------- Yordamchilar ---------- */
  var CART_KEY = 'restoran_cart_v1', PROMO_KEY = 'restoran_promo_v1', TYPE_KEY = 'restoran_order_type_v1';
  var view = document.getElementById('view');
  var toastEl = document.getElementById('toast');
  var toastTimer = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function money(n) { return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m'; }
  function hue(s) { var h = 0; String(s).split('').forEach(function (c) { h = (h * 31 + c.charCodeAt(0)) % 360; }); return h; }
  function initial(s) { return esc(String(s || '?').trim().charAt(0).toUpperCase() || '?'); }
  function normPhone(v) {
    var d = String(v || '').replace(/\D+/g, '');
    if (d.length === 9) d = '998' + d;
    return d.length === 12 && d.indexOf('998') === 0 ? '+' + d : '';
  }
  function toast(msg, kind) {
    toastEl.textContent = msg;
    toastEl.className = 'toast show ' + (kind || '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.className = 'toast'; }, 2400);
    try { if (tg && tg.HapticFeedback) tg.HapticFeedback.notificationOccurred(kind === 'error' ? 'error' : 'success'); } catch (e) {}
  }

  var ICON = {
    back: '<path d="m15 6-6 6 6 6"/>',
    grid: '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
    bag: '<path d="M6 7h12l1 13H5zM9 7V5a3 3 0 0 1 6 0v2"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    heart: '<path d="M12 21s-7.5-4.6-9.5-9.2A5.4 5.4 0 0 1 12 6.6a5.4 5.4 0 0 1 9.5 5.2C19.5 16.4 12 21 12 21z"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    pin: '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    chev: '<path d="m9 6 6 6-6 6"/>',
    check: '<path d="M5 12.5 10 17 19 7"/>',
    x: '<path d="M6 6l12 12M18 6 6 18"/>',
    trash: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
    truck: '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
    store: '<path d="M4 9l1-5h14l1 5M4 9v11h16V9M4 9h16M9 20v-6h6v6"/>',
    receipt: '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>',
    refresh: '<path d="M20 11a8 8 0 0 0-14.9-3M4 13a8 8 0 0 0 14.9 3M20 4v5h-5M4 20v-5h5"/>',
    cash: '<rect x="2.5" y="6" width="19" height="12" rx="3"/><circle cx="12" cy="12" r="2.6"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    send: '<path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/>'
  };
  function ic(name, cls) {
    return '<svg class="ic ' + (cls || '') + '" viewBox="0 0 24 24" aria-hidden="true">' + (ICON[name] || '') + '</svg>';
  }

  /* ---------- API ---------- */
  function api(path, opts) {
    opts = opts || {};
    var headers = { Accept: 'application/json' };
    if (tg && tg.initData) headers['X-Init-Data'] = tg.initData;
    var init = { method: opts.method || (opts.body !== undefined ? 'POST' : 'GET'), headers: headers, cache: 'no-store' };
    if (opts.body !== undefined) { headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(opts.body); }
    return fetch('/api/' + path, init).then(function (res) {
      return res.json().catch(function () { return null; }).then(function (data) {
        if (!res.ok || !data || data.ok === false) {
          var err = new Error((data && data.error) || 'Xatolik (' + res.status + ')');
          err.status = res.status;
          throw err;
        }
        return data;
      });
    }, function () { throw new Error('Internet aloqasi yo‘q'); });
  }
  var settingsCache = null;
  function settings() {
    if (!settingsCache) {
      settingsCache = api('settings.php').catch(function (e) { settingsCache = null; throw e; });
    }
    return settingsCache;
  }
  function fv(form, name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; }
  function errText(e) { return e && e.status === 401 ? 'Ilovani Telegram orqali oching' : (e && e.message) || 'Xatolik'; }

  /* ---------- Savat (localStorage) ---------- */
  var cart = {
    items: function () {
      try { var v = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); return Array.isArray(v) ? v.filter(function (i) { return i && i.product_id > 0; }) : []; }
      catch (e) { return []; }
    },
    save: function (list) { try { localStorage.setItem(CART_KEY, JSON.stringify(list.slice(0, 50))); } catch (e) {} updateBadge(); },
    key: function (i) { return [i.product_id, (i.options || []).slice().sort(function (a, b) { return a - b; }).join(','), i.note || ''].join('|'); },
    count: function () { return this.items().reduce(function (s, i) { return s + (Number(i.qty) || 0); }, 0); },
    add: function (item) {
      var list = this.items(), k = this.key(item);
      var f = list.filter(function (i) { return cart.key(i) === k; })[0];
      if (f) f.qty = Math.min(99, f.qty + item.qty); else list.push(item);
      this.save(list);
    },
    set: function (idx, qty) { var l = this.items(); if (l[idx]) { l[idx].qty = Math.max(1, Math.min(99, qty)); this.save(l); } },
    remove: function (idx) { var l = this.items(); l.splice(idx, 1); this.save(l); },
    clear: function () { try { localStorage.removeItem(CART_KEY); localStorage.removeItem(PROMO_KEY); } catch (e) {} updateBadge(); }
  };
  function updateBadge() {
    var n = cart.count();
    document.querySelectorAll('[data-badge]').forEach(function (b) { b.textContent = n > 99 ? '99+' : String(n); b.hidden = n === 0; });
  }
  function promoStored() { try { return localStorage.getItem(PROMO_KEY) || ''; } catch (e) { return ''; } }
  function orderType() { try { return localStorage.getItem(TYPE_KEY) === 'pickup' ? 'pickup' : 'delivery'; } catch (e) { return 'delivery'; } }
  function setOrderType(t) { try { localStorage.setItem(TYPE_KEY, t === 'pickup' ? 'pickup' : 'delivery'); } catch (e) {} }
  function cartPayload(items) {
    return items.map(function (i) { return { product_id: i.product_id, qty: i.qty, options: i.options || [], note: i.note || '' }; });
  }
  function preview(items, type, promo) {
    return api('cart.php', { body: { items: cartPayload(items), order_type: type, promo_code: promo || '' } });
  }

  /* ---------- Rasm / qatorlar ---------- */
  function thumb(p) {
    if (p.image) return '<img src="' + esc(p.image) + '" alt="" loading="lazy">';
    return '<span class="ph" style="--h:' + hue(p.name) + '">' + initial(p.name) + '</span>';
  }
  function rowHtml(p) {
    var disc = p.discount_percent ? '<span class="old">' + money(p.base_price) + '</span>' : '';
    return '<div class="row" data-go="#/product/' + p.id + '">' +
      '<div class="thumb">' + thumb(p) + '</div>' +
      '<div class="row-main"><div class="row-title">' + esc(p.name) + '</div>' +
      '<div class="row-sub">' + esc(p.description) + '</div>' +
      '<div class="row-price">' + disc + money(p.price) + ' <span>/ ' + esc(p.unit) + '</span></div></div>' +
      (p.is_available
        ? '<button class="add" data-add="' + p.id + '" data-req="' + (p.required_options ? 1 : 0) + '" aria-label="Savatga qo‘shish">' + ic('plus') + '</button>'
        : '<span class="muted">Tugagan</span>') +
      '</div>';
  }
  function listSection(title, items) {
    if (!items || !items.length) return '';
    return '<section><div class="sec-head"><h3>' + esc(title) + '</h3></div><div class="card-list">' +
      items.map(rowHtml).join('') + '</div></section>';
  }
  function skelRows(n) { var s = ''; for (var i = 0; i < n; i++) s += '<div class="skel"></div>'; return s; }
  function emptyHtml(icon, title, text, href, label) {
    return '<div class="empty">' + ic(icon) + '<h2>' + esc(title) + '</h2><p>' + esc(text) + '</p>' +
      (href ? '<button class="btn primary" data-go="' + href + '">' + esc(label) + '</button>' : '') + '</div>';
  }
  function statusBadge(s, label) {
    var tone = { new: 'info', accepted: 'warn', preparing: 'warn', ready: 'accent', delivering: 'accent', completed: 'ok', cancelled: 'bad' }[s] || 'muted';
    return '<span class="badge b-' + tone + '">' + esc(label || s) + '</span>';
  }

  /* ---------- Sahifa holati ---------- */
  var onPage = null;          // sahifa ichidagi click handler (true qaytarsa, global ishlamaydi)
  var pollTimer = null;
  var routes = {};
  function stopPoll() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }
  function parseHash() {
    var raw = location.hash.replace(/^#/, '');
    var parts = raw.split('?');
    return { parts: parts[0].split('/').filter(Boolean), query: new URLSearchParams(parts[1] || '') };
  }
  function render() {
    var r = parseHash();
    var name = r.parts[0] || 'home';
    var fn = routes[name] || routes.home;
    onPage = null;
    view.oninput = null; view.onsubmit = null; view.onchange = null; view.onclick = null;
    var staleSheet = document.getElementById('sheet');
    if (staleSheet) staleSheet.remove();
    stopPoll();
    document.querySelectorAll('.tabbar [data-tab]').forEach(function (b) {
      var tab = { product: 'menu', checkout: 'cart', orders: 'profile' }[name] || name;
      b.classList.toggle('on', b.getAttribute('data-tab') === tab);
    });
    view.className = 'app';
    view.innerHTML = '<div class="skel"></div><div class="skel"></div>';
    window.scrollTo(0, 0);
    var token = Date.now() + Math.random();
    view.dataset.token = token;
    Promise.resolve().then(function () { return fn(r); }).then(function () {}, function (e) {
      if (view.dataset.token !== String(token)) return;
      view.innerHTML = '<div class="notice">' + esc(errText(e)) + '</div>' + emptyHtml('info', 'Xatolik yuz berdi', 'Qaytadan urinib ko‘ring.', '#/home', 'Bosh sahifa');
    });
  }
  // Har bir sahifa o‘z ichida token bilan tekshiradi: eski javob yangi sahifani buzmasin.
  function guard(fn) {
    return function (r) {
      var token = view.dataset.token;
      return fn(r).then(function () {}).catch(function (e) { if (view.dataset.token === token) throw e; });
    };
  }

  /* ---------- Bosh sahifa ---------- */
  routes.home = guard(function () {
    return Promise.all([api('products.php?action=home'), settings()]).then(function (res) {
      var home = res[0], st = res[1], r = st.restaurant, store = st.store;
      var user = (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) || {};
      var first = user.first_name || 'mehmon';
      var banner = home.banners[0];
      var heroTarget = banner && banner.product_id ? '#/product/' + banner.product_id : '#/menu';
      view.innerHTML = '<div class="fade">' +
        '<header class="top"><div class="hello"><span class="avatar">' + initial(first) + '</span><div><small>Salom,</small><strong>' + esc(first) + '</strong></div></div>' +
        '<button class="round" data-go="#/cart" aria-label="Savat">' + ic('bag') + '<b class="dot-badge" data-badge hidden>0</b></button></header>' +
        '<h1 class="title">' + esc(r.name) + '</h1>' +
        '<p class="sub"><span class="dot ' + (store.is_open ? 'open' : 'closed') + '"></span>' +
          esc(store.is_open ? 'Ochiq · ' + store.working_open + ' – ' + store.working_close : store.message) + '</p>' +
        '<section class="hero" data-go="' + heroTarget + '"><div><small>' + (banner ? 'Bugungi taklif' : 'Restoran') + '</small>' +
          '<h2>' + esc(banner ? banner.title : r.name) + '</h2><p>' + esc(banner ? banner.subtitle : r.slogan) + '</p></div>' +
          '<span class="pill">Ko‘rish</span></section>' +
        '<nav class="actions">' +
          '<button data-go="#/menu"><span class="round">' + ic('grid') + '</span>Menyu</button>' +
          '<button data-go="#/cart"><span class="round">' + ic('bag') + '</span>Savat</button>' +
          '<button data-go="#/orders"><span class="round">' + ic('receipt') + '</span>Buyurtma</button>' +
          '<button data-go="#/profile"><span class="round">' + ic('user') + '</span>Profil</button>' +
        '</nav>' +
        '<section><div class="sec-head"><h3>Kategoriyalar</h3><a href="#/menu">Hammasi</a></div><div class="cats">' +
          home.categories.map(function (c) {
            return '<a class="cat" href="#/menu?cat=' + encodeURIComponent(c.slug) + '"><span class="cat-ico" style="--h:' + hue(c.name) + '">' + initial(c.name) + '</span><span>' + esc(c.name) + '</span></a>';
          }).join('') + '</div></section>' +
        listSection('Mashhur taomlar', home.featured) +
        listSection('Tavsiya etilgan', home.recommended) +
        listSection('Yangi', home.new) +
        '</div>';
      updateBadge();
    });
  });

  /* ---------- Menyu ---------- */
  routes.menu = guard(function (r) {
    var state = { cat: r.query.get('cat') || '', q: r.query.get('q') || '' };
    return api('categories.php').then(function (cd) {
      view.innerHTML = '<div class="fade"><h1 class="title small">Menyu</h1>' +
        '<label class="search">' + ic('search') + '<input id="search" type="search" maxlength="100" placeholder="Taom qidirish" value="' + esc(state.q) + '" autocomplete="off"></label>' +
        '<div class="chips" id="chips">' + chip('', 'Hammasi', state.cat) +
          cd.categories.map(function (c) { return chip(c.slug, c.name, state.cat); }).join('') + '</div>' +
        '<div class="card-list" id="list">' + skelRows(3) + '</div></div>';
      var timer = null;
      function replaceHash() {
        var p = new URLSearchParams();
        if (state.cat) p.set('cat', state.cat);
        if (state.q) p.set('q', state.q);
        history.replaceState(null, '', '#/menu' + (p.toString() ? '?' + p : ''));
      }
      function load() {
        var p = new URLSearchParams({ action: 'list' });
        if (state.cat) p.set('category', state.cat);
        if (state.q) p.set('q', state.q);
        return api('products.php?' + p.toString()).then(function (d) {
          var list = document.getElementById('list');
          if (!list) return;
          list.innerHTML = d.products.length ? d.products.map(rowHtml).join('') : '<div class="empty" style="background:var(--card)">Hech narsa topilmadi</div>';
        }).catch(function (e) { toast(errText(e), 'error'); });
      }
      view.onclick = function (e) {
        var c = e.target.closest('[data-slug]');
        if (!c) return;
        state.cat = c.getAttribute('data-slug');
        document.querySelectorAll('#chips .chip').forEach(function (x) { x.classList.toggle('on', x === c); });
        replaceHash(); load();
      };
      view.oninput = function (e) {
        if (e.target.id !== 'search') return;
        clearTimeout(timer);
        timer = setTimeout(function () { state.q = e.target.value.trim().slice(0, 100); replaceHash(); load(); }, 300);
      };
      return load();
    });
  });
  function chip(slug, name, current) {
    return '<button class="chip' + (slug === current ? ' on' : '') + '" data-slug="' + esc(slug) + '">' + esc(name) + '</button>';
  }

  /* ---------- Mahsulot ---------- */
  routes.product = guard(function (r) {
    var id = Number(r.parts[1]) || 0;
    return api('products.php?action=product&id=' + id).then(function (d) {
      var p = d.product;
      var sel = {}, qty = 1, fav = !!p.is_favorite, note = '';
      function total() {
        var o = 0;
        p.groups.forEach(function (g) { g.values.forEach(function (v) { if (sel[g.id] && sel[g.id][v.id]) o += v.price; }); });
        return (p.price + o) * qty;
      }
      function selectedIds() {
        var ids = [];
        Object.keys(sel).forEach(function (g) { Object.keys(sel[g]).forEach(function (v) { if (sel[g][v]) ids.push(Number(v)); }); });
        return ids;
      }
      function groupsHtml() {
        return p.groups.map(function (g) {
          return '<section class="group"><div class="sec-head"><h3>' + esc(g.name) + '</h3><small>' +
            (g.required ? 'Majburiy' : 'Ixtiyoriy') + ' · ' + (g.type === 'single' ? 'bitta' : 'bir nechta') + '</small></div><div class="card-list">' +
            g.values.map(function (v) {
              var on = !!(sel[g.id] && sel[g.id][v.id]);
              return '<button class="opt' + (on ? ' on' : '') + (v.is_available ? '' : ' off') + '" data-opt="' + g.id + ':' + v.id + ':' + (g.type === 'single' ? 1 : 0) + '"' + (v.is_available ? '' : ' disabled') + '>' +
                '<span class="tick">' + (on ? ic('check') : '') + '</span><span class="opt-name">' + esc(v.name) + '</span>' +
                '<span class="opt-price">' + (v.price ? '+ ' + money(v.price) : 'Bepul') + '</span></button>';
            }).join('') + '</div></section>';
        }).join('');
      }
      function draw() {
        var g = document.getElementById('groups'); if (g) g.innerHTML = groupsHtml();
        var t = document.getElementById('total'); if (t) t.textContent = money(total());
        var q = document.getElementById('qty'); if (q) q.textContent = String(qty);
        var u = document.getElementById('unit'); if (u) u.textContent = money(p.price);
        var f = document.getElementById('fav'); if (f) f.classList.toggle('on', fav);
      }
      view.innerHTML = '<div class="fade">' +
        '<div class="back"><button class="round" data-go="#/menu" aria-label="Orqaga">' + ic('back') + '</button>' +
        '<strong>' + esc(p.name) + '</strong><button class="round" id="fav" data-act="fav" aria-label="Sevimli">' + ic('heart') + '</button></div>' +
        '<div class="pv-img">' + (p.image ? '<img src="' + esc(p.image) + '" alt="">' : '<span class="ph" style="--h:' + hue(p.name) + '">' + initial(p.name) + '</span>') + '</div>' +
        '<h1 class="title small">' + esc(p.name) + '</h1>' +
        (p.description ? '<p class="muted" style="margin-bottom:6px">' + esc(p.description) + '</p>' : '') +
        '<div class="price-line"><strong id="unit">' + money(p.price) + '</strong><span>/ ' + esc(p.unit) + '</span></div>' +
        (p.is_available ? '' : '<div class="notice">Bu mahsulot hozir mavjud emas</div>') +
        '<div class="groups" id="groups"></div>' +
        '<label class="field"><span>Izoh (ixtiyoriy)</span><textarea id="note" maxlength="255" placeholder="Masalan: achchiqsiz"></textarea></label>' +
        '<div class="stepper-row"><b>Miqdor</b><div class="stepper"><button data-act="qty" data-d="-1" aria-label="Kamaytirish">' + ic('minus') + '</button>' +
        '<output id="qty">1</output><button data-act="qty" data-d="1" aria-label="Oshirish">' + ic('plus') + '</button></div></div>' +
        '<div style="height:90px"></div>' +
        '<div class="bottom-bar"><button class="btn primary" data-act="addcart" id="addbtn"' + (p.is_available ? '' : ' disabled') + '>' +
        (p.is_available ? 'Savatga qo‘shish · <span id="total">' + money(total()) + '</span>' : 'Mavjud emas') + '</button></div></div>';
      draw();

      onPage = function (e) {
        var o = e.target.closest('[data-opt]');
        if (o) {
          var parts = o.getAttribute('data-opt').split(':');
          var g = parts[0], v = Number(parts[1]), single = parts[2] === '1';
          sel[g] = sel[g] || {};
          if (single) { var was = !!sel[g][v]; sel[g] = {}; if (!was) sel[g][v] = true; }
          else sel[g][v] = !sel[g][v];
          draw(); return true;
        }
        var a = e.target.closest('[data-act]');
        if (!a) return false;
        var act = a.getAttribute('data-act');
        if (act === 'qty') { qty = Math.max(1, Math.min(99, qty + Number(a.getAttribute('data-d')))); draw(); return true; }
        if (act === 'fav') {
          var next = !fav;
          fav = next; draw();
          api('products.php', { body: { action: 'favorite', product_id: p.id, on: next } }).then(function () {
            toast(next ? 'Sevimlilarga qo‘shildi' : 'Sevimlilardan olib tashlandi', 'success');
          }).catch(function (err) { fav = !next; draw(); toast(errText(err), 'error'); });
          return true;
        }
        if (act === 'addcart') {
          var missing = p.groups.filter(function (gr) { return gr.required && !Object.keys(sel[gr.id] || {}).some(function (k) { return sel[gr.id][k]; }); });
          if (missing.length) { toast('“' + missing[0].name + '” bo‘yicha tanlov qiling', 'error'); return true; }
          var n = document.getElementById('note');
          cart.add({ product_id: p.id, qty: qty, options: selectedIds(), note: n ? n.value.trim().slice(0, 255) : '', name: p.name });
          toast('Savatga qo‘shildi', 'success');
          setTimeout(function () { location.hash = '#/cart'; }, 450);
          return true;
        }
        return false;
      };
      view.oninput = function (e) { if (e.target.id === 'note') note = e.target.value; };
    });
  });

  /* ---------- Savat ---------- */
  routes.cart = guard(function () {
    var items = cart.items();
    if (!items.length) { view.innerHTML = '<div class="fade"><h1 class="title small">Savat</h1>' + emptyHtml('bag', 'Savat bo‘sh', 'Menyudan taom tanlang va savatga qo‘shing.', '#/menu', 'Menyuga o‘tish') + '</div>'; return Promise.resolve(); }
    var type = orderType(), promo = promoStored();
    return preview(items, type, promo).then(function (p) {
      var serverLines = {};
      p.lines.forEach(function (l) { serverLines[l.index] = l; });
      var lines = items.map(function (it, i) {
        var l = serverLines[i];
        var name = l ? l.name : (it.name || 'Mahsulot #' + it.product_id);
        var opts = l && l.options.length ? '<small>' + esc(l.options.map(function (o) { return o.name; }).join(', ')) + '</small>' : '';
        return '<div class="row static' + (l ? '' : ' ') + '" style="align-items:flex-start">' +
          '<div class="thumb">' + (l && l.image ? '<img src="' + esc(l.image) + '" alt="">' : '<span class="ph" style="--h:' + hue(name) + '">' + initial(name) + '</span>') + '</div>' +
          '<div class="row-main"><div class="row-title">' + esc(name) + '</div>' + opts +
          (it.note ? '<small>“' + esc(it.note) + '”</small>' : '') +
          '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;gap:8px">' +
            '<div class="stepper sm"><button data-qty="' + i + ':-1" aria-label="Kamaytirish">' + ic('minus') + '</button><output>' + it.qty + '</output>' +
            '<button data-qty="' + i + ':1" aria-label="Oshirish">' + ic('plus') + '</button></div>' +
            '<div style="text-align:right"><div class="row-price">' + (l ? money(l.line_total) : '—') + '</div></div></div></div>' +
          '<button class="round plain" style="width:34px;height:34px" data-rm="' + i + '" aria-label="O‘chirish">' + ic('trash') + '</button></div>';
      });
      var disc = p.discount > 0 ? '<div class="line disc"><span>Chegirma</span><strong>−' + money(p.discount) + '</strong></div>' : '';
      var fee = type === 'delivery' ? '<div class="line"><span>Yetkazib berish</span><strong>' + (p.delivery_fee ? money(p.delivery_fee) : 'Bepul') + '</strong></div>' : '';
      var errs = (p.errors || []).map(function (x) { return x.message; });
      if (p.min_order && p.subtotal < p.min_order) errs.push('Minimal buyurtma summasi: ' + money(p.min_order));
      view.innerHTML = '<div class="fade"><h1 class="title small">Savat</h1>' +
        '<div class="segmented" data-otype-wrap>' +
          '<button data-otype="delivery" class="' + (type === 'delivery' ? 'on' : '') + '">' + ic('truck') + 'Yetkazish</button>' +
          '<button data-otype="pickup" class="' + (type === 'pickup' ? 'on' : '') + '">' + ic('store') + 'Olib ketish</button></div>' +
        '<div class="card-list" style="margin-bottom:12px">' + lines.join('') + '</div>' +
        '<div class="form" style="padding:0;flex-direction:row;margin-bottom:6px">' +
          '<input class="inp" id="promo" maxlength="40" placeholder="Promo-kod" value="' + esc(promo) + '" autocomplete="off" style="text-transform:uppercase">' +
          '<button class="btn ghost sm" data-act="promo" style="height:48px">Qo‘llash</button></div>' +
        '<p class="hint ' + (p.promo ? (p.promo.valid ? 'ok' : 'err') : '') + '">' + esc(p.promo ? p.promo.message : '') + '</p>' +
        (errs.length ? '<div class="notice">' + errs.map(esc).join('<br>') + '</div>' : '') +
        '<section class="card summary" style="margin-bottom:14px"><div class="line"><span>Mahsulotlar</span><strong>' + money(p.subtotal) + '</strong></div>' +
          disc + fee + '<div class="line total"><span>Jami</span><strong>' + money(p.total) + '</strong></div></section>' +
        '<div style="height:90px"></div>' +
        '<div class="bottom-bar"><button class="btn primary" data-go="#/checkout"' + (p.valid && p.store.is_open ? '' : ' disabled') + '>Rasmiylashtirish · ' + money(p.total) + '</button></div></div>';

      onPage = function (e) {
        var ot = e.target.closest('[data-otype]');
        if (ot) { setOrderType(ot.getAttribute('data-otype')); routes.cart({ parts: ['cart'] }); return true; }
        var q = e.target.closest('[data-qty]');
        if (q) { var pq = q.getAttribute('data-qty').split(':'); cart.set(Number(pq[0]), (items[Number(pq[0])].qty) + Number(pq[1])); routes.cart({ parts: ['cart'] }); return true; }
        var rm = e.target.closest('[data-rm]');
        if (rm) { cart.remove(Number(rm.getAttribute('data-rm'))); toast('Mahsulot olib tashlandi', 'success'); routes.cart({ parts: ['cart'] }); return true; }
        var pr = e.target.closest('[data-act="promo"]');
        if (pr) {
          var code = (document.getElementById('promo').value || '').trim().toUpperCase().slice(0, 40);
          try { code ? localStorage.setItem(PROMO_KEY, code) : localStorage.removeItem(PROMO_KEY); } catch (err) {}
          routes.cart({ parts: ['cart'] });
          return true;
        }
        return false;
      };
    });
  });

  /* ---------- Rasmiylashtirish ---------- */
  var draft = null;
  var checkoutState = null;
  function freshDraft() {
    return { type: orderType(), name: '', phone: '', addrId: 'new', address: '', landmark: '', comment: '', save: false, loaded: false };
  }
  routes.checkout = guard(function () {
    var items = cart.items();
    if (!items.length) { location.hash = '#/cart'; return Promise.resolve(); }
    if (!draft) draft = freshDraft();
    draft.type = orderType();
    return Promise.all([preview(items, draft.type, promoStored()), api('profile.php')]).then(function (res) {
      var p = res[0], prof = res[1], u = prof.user || {};
      var addresses = prof.addresses || [];
      if (!draft.loaded) {
        draft.name = [u.first_name, u.last_name].filter(Boolean).join(' ');
        draft.phone = u.phone || '';
        draft.addrId = addresses[0] ? addresses[0].id : 'new';
        draft.loaded = true;
      }
      checkoutState = { p: p, addresses: addresses };
      var delivery = draft.type === 'delivery';
      var addrHtml = addresses.map(function (a) {
        return '<div class="row" data-addr="' + a.id + '"><span class="round" style="width:34px;height:34px;background:var(--accent-soft);color:var(--accent-ink)">' + ic('pin') + '</span>' +
          '<div class="row-main"><div class="row-title">' + esc(a.title) + '</div><div class="row-sub">' + esc(a.address) + (a.landmark ? ' · ' + esc(a.landmark) : '') + '</div></div>' +
          '<span class="opt-tick" style="' + (String(draft.addrId) === String(a.id) ? '' : 'opacity:.3') + '">' + (String(draft.addrId) === String(a.id) ? ic('check') : '') + '</span></div>';
      }).join('');
      addrHtml += '<div class="row" data-addr="new"><span class="round" style="width:34px;height:34px;background:var(--chip)">' + ic('plus') + '</span>' +
        '<div class="row-main"><div class="row-title">Yangi manzil</div></div>' +
        '<span style="' + (draft.addrId === 'new' ? '' : 'opacity:.3') + '">' + (draft.addrId === 'new' ? ic('check') : '') + '</span></div>';

      var lines = p.lines.map(function (l) {
        return '<div class="kv"><span>' + l.qty + ' × ' + esc(l.name) + '</span><b>' + money(l.line_total) + '</b></div>';
      }).join('');
      var summaryHtml = '<div class="line"><span>Mahsulotlar</span><strong>' + money(p.subtotal) + '</strong></div>' +
        (p.discount > 0 ? '<div class="line disc"><span>Chegirma</span><strong>−' + money(p.discount) + '</strong></div>' : '') +
        (delivery ? '<div class="line"><span>Yetkazib berish</span><strong>' + (p.delivery_fee ? money(p.delivery_fee) : 'Bepul') + '</strong></div>' : '') +
        '<div class="line total"><span>Jami</span><strong>' + money(p.total) + '</strong></div>';

      view.innerHTML = '<div class="fade">' +
        '<div class="back"><button class="round" data-go="#/cart" aria-label="Orqaga">' + ic('back') + '</button><strong>Rasmiylashtirish</strong><span style="width:42px"></span></div>' +
        '<div class="segmented" style="margin-top:10px">' +
          '<button data-type="delivery" class="' + (delivery ? 'on' : '') + '">' + ic('truck') + 'Yetkazish</button>' +
          '<button data-type="pickup" class="' + (!delivery ? 'on' : '') + '">' + ic('store') + 'Olib ketish</button></div>' +
        '<div class="sec-head"><h3>Mijoz</h3></div>' +
        '<div class="card form">' +
          '<label class="field"><span>Ism</span><input data-f="name" value="' + esc(draft.name) + '" maxlength="120" autocomplete="name"></label>' +
          '<label class="field"><span>Telefon</span><input data-f="phone" type="tel" value="' + esc(draft.phone) + '" maxlength="20" placeholder="+998 90 123 45 67" autocomplete="tel"></label>' +
        '</div>' +
        (delivery
          ? '<div class="sec-head"><h3>Manzil</h3></div><div class="card-list" style="margin-bottom:10px">' + addrHtml + '</div>' +
            (draft.addrId === 'new'
              ? '<div class="card form">' +
                '<label class="field"><span>Manzil</span><textarea data-f="address" maxlength="255" placeholder="Shahar, tuman, ko‘cha, uy">' + esc(draft.address) + '</textarea></label>' +
                '<label class="field"><span>Mo‘ljal</span><input data-f="landmark" maxlength="255" value="' + esc(draft.landmark) + '"></label>' +
                '<label class="row static" style="padding:4px 0"><span class="row-main">Bu manzilni saqlash</span><input type="checkbox" data-f="save" ' + (draft.save ? 'checked' : '') + '></label></div>'
              : '')
          : '') +
        '<div class="sec-head"><h3>Izoh</h3></div>' +
        '<div class="card form"><textarea data-f="comment" maxlength="500" placeholder="Qo‘shimcha izoh (ixtiyoriy)">' + esc(draft.comment) + '</textarea></div>' +
        '<div class="sec-head"><h3>To‘lov</h3></div>' +
        '<div class="card kv"><span style="display:flex;align-items:center;gap:10px">' + ic('cash') + '<span><b>Naqd pul</b><small style="display:block">Topshirilganda to‘lanadi</small></span></span></div>' +
        '<section class="card summary" style="margin-top:14px">' + summaryHtml + '</section>' +
        '<p class="hint" id="co-msg"></p>' +
        '<div style="height:90px"></div>' +
        '<div class="bottom-bar"><button class="btn primary" id="co-go"' + (p.valid && p.store.is_open ? '' : ' disabled') + '>Buyurtmani tasdiqlash · ' + money(p.total) + '</button></div>' +
        '<div class="sheet-bg" id="sheet" hidden><div class="sheet" role="dialog" aria-modal="true"><div class="grab"></div>' +
          '<h2>Buyurtmani tasdiqlaysizmi?</h2><div class="card-list">' + lines + '</div><div class="card summary" style="margin-top:10px">' + summaryHtml + '</div>' +
          '<div class="sheet-actions"><button class="btn ghost" data-act="close-sheet">Bekor qilish</button><button class="btn primary" data-act="place" id="place-btn">Tasdiqlash</button></div>' +
        '</div></div></div>';

      // Varaqni body ga ko‘chiramiz: sahifa wrapperining stacking context ichida tabbar ustidan chiqmasligi uchun
      document.body.appendChild(document.getElementById('sheet'));
      if (!p.valid) document.getElementById('co-msg').textContent = 'Savatni tekshiring: ' + ((p.errors && p.errors[0] && p.errors[0].message) || (p.promo && p.promo.message) || '');
      if (p.store && !p.store.is_open) document.getElementById('co-msg').textContent = p.store.message;

      function fieldsSync(e) {
        var f = e.target.getAttribute && e.target.getAttribute('data-f');
        if (!f) return;
        draft[f] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
      }
      view.oninput = fieldsSync;
      view.onchange = fieldsSync;
      onPage = function (e) {
        var t = e.target.closest('[data-type]');
        if (t) { draft.type = t.getAttribute('data-type'); setOrderType(draft.type); routes.checkout({ parts: ['checkout'] }); return true; }
        var ad = e.target.closest('[data-addr]');
        if (ad) { draft.addrId = ad.getAttribute('data-addr') === 'new' ? 'new' : Number(ad.getAttribute('data-addr')); routes.checkout({ parts: ['checkout'] }); return true; }
        if (e.target.closest('#co-go')) { openSheet(); return true; }
        if (e.target.closest('[data-act="close-sheet"]')) { document.getElementById('sheet').hidden = true; return true; }
        if (e.target.closest('[data-act="place"]')) { place(); return true; }
        return false;
      };
      view.onsubmit = null;
    });
  });
  function openSheet() {
    var err = validateDraft();
    if (err) { toast(err, 'error'); return; }
    document.getElementById('sheet').hidden = false;
  }
  function validateDraft() {
    if (!draft.name || draft.name.trim().length < 2) return 'Ismingizni kiriting';
    if (!normPhone(draft.phone)) return 'Telefon raqamini to‘g‘ri kiriting (+998 XX XXX XX XX)';
    if (draft.type === 'delivery' && draft.addrId === 'new' && !String(draft.address || '').trim()) return 'Yetkazib berish manzilini kiriting';
    if (!checkoutState || !checkoutState.p.valid) return 'Savatni tekshiring';
    if (checkoutState.p.store && !checkoutState.p.store.is_open) return checkoutState.p.store.message;
    return '';
  }
  function place() {
    var err = validateDraft();
    if (err) { toast(err, 'error'); return; }
    var btn = document.getElementById('place-btn');
    btn.disabled = true;
    var body = {
      action: 'create', order_type: draft.type, customer_name: draft.name.trim(), customer_phone: draft.phone.trim(),
      comment: String(draft.comment || '').trim(), promo_code: promoStored(), items: cartPayload(cart.items())
    };
    if (draft.type === 'delivery') {
      if (draft.addrId !== 'new') body.address_id = draft.addrId;
      else { body.address = String(draft.address).trim(); body.landmark = String(draft.landmark || '').trim(); body.save_address = !!draft.save; }
    }
    api('orders.php', { body: body }).then(function (d) {
      cart.clear();
      draft = null;
      view.innerHTML = '<div class="fade success"><div class="round">' + ic('check') + '</div>' +
        '<h2>Buyurtma qabul qilindi</h2><p class="muted">Buyurtma raqami: <b style="color:var(--text)">#' + d.order_no + '</b></p>' +
        '<p class="muted">Holat o‘zgarganda Telegram orqali xabar olasiz.</p>' +
        '<button class="btn primary" data-go="#/orders">Buyurtmalarim</button>' +
        '<button class="btn ghost" data-go="#/menu">Yana buyurtma berish</button></div>';
      onPage = null; view.oninput = null; view.onchange = null;
      updateBadge();
    }).catch(function (e) {
      toast(errText(e), 'error');
      btn.disabled = false;
      document.getElementById('sheet').hidden = true;
      if (e.status === 409 || e.status === 422) routes.checkout({ parts: ['checkout'] });
    });
  }

  /* ---------- Buyurtmalar ---------- */
  var orderScope = 'active', openOrders = {};
  routes.orders = guard(function () {
    view.innerHTML = '<div class="fade"><h1 class="title small">Buyurtmalar</h1>' +
      '<div class="segmented" data-scope-wrap><button data-scope="active" class="on">Faol</button><button data-scope="history">Tarix</button></div>' +
      '<div id="olist">' + skelRows(2) + '</div><p class="live"><i></i>Holat avtomatik yangilanadi</p></div>';
    function load(silent) {
      return api('orders.php?action=list&scope=' + orderScope + '&page=1').then(function (d) {
        var el = document.getElementById('olist');
        if (!el) return;
        el.innerHTML = d.orders.length
          ? d.orders.map(orderCard).join('')
          : emptyHtml('receipt', orderScope === 'active' ? 'Faol buyurtmalar yo‘q' : 'Tarix bo‘sh', 'Buyurtmalaringiz shu yerda ko‘rinadi.', '#/menu', 'Menyuga o‘tish');
      }).catch(function (e) { if (!silent) toast(errText(e), 'error'); });
    }
    onPage = function (e) {
      var sc = e.target.closest('[data-scope]');
      if (sc) {
        orderScope = sc.getAttribute('data-scope');
        document.querySelectorAll('[data-scope]').forEach(function (b) { b.classList.toggle('on', b === sc); });
        load(); return true;
      }
      var tg2 = e.target.closest('[data-toggle]');
      if (tg2) { var id = tg2.getAttribute('data-toggle'); openOrders[id] = !openOrders[id]; load(true); return true; }
      var cn = e.target.closest('[data-cancel]');
      if (cn) {
        if (!window.confirm('Buyurtmani bekor qilasizmi?')) return true;
        api('orders.php', { body: { action: 'cancel', id: Number(cn.getAttribute('data-cancel')) } })
          .then(function () { toast('Buyurtma bekor qilindi', 'success'); load(); })
          .catch(function (err) { toast(errText(err), 'error'); });
        return true;
      }
      var rp = e.target.closest('[data-repeat]');
      if (rp) {
        api('orders.php', { body: { action: 'repeat', id: Number(rp.getAttribute('data-repeat')) } }).then(function (d) {
          d.items.forEach(function (it) { cart.add({ product_id: it.product_id, qty: it.qty, options: it.options, note: it.note }); });
          if (d.skipped && d.skipped.length) toast('Mavjud emas: ' + d.skipped.join(', '), 'error');
          else toast('Savatga qo‘shildi', 'success');
          setTimeout(function () { location.hash = '#/cart'; }, 400);
        }).catch(function (err) { toast(errText(err), 'error'); });
        return true;
      }
      return false;
    };
    return load().then(function () {
      pollTimer = setInterval(function () { if (!document.hidden) load(true); }, 15000);
    });
  });
  function orderCard(o) {
    var open = !!openOrders[o.id];
    var items = o.items.map(function (it) {
      var opts = it.options && it.options.length ? ' <span>(' + esc(it.options.map(function (x) { return x.value; }).join(', ')) + ')</span>' : '';
      return '<li>' + it.quantity + ' × ' + esc(it.name) + opts + '</li>';
    }).join('');
    var when = String(o.created_at || '').replace('T', ' ').slice(0, 16);
    var hist = (o.history || []).map(function (h) { return '<li>' + esc(h.to_label) + '<small>' + esc(String(h.at).slice(0, 16)) + '</small></li>'; }).join('');
    var acts = '';
    if (o.can_cancel) acts += '<button class="btn danger sm" data-cancel="' + o.id + '">Bekor qilish</button>';
    if (o.status === 'completed' || o.status === 'cancelled') acts += '<button class="btn ghost sm" data-repeat="' + o.id + '">' + ic('refresh') + ' Takroriy</button>';
    return '<article class="order"><div class="order-head"><strong>#' + o.order_no + '</strong>' + statusBadge(o.status, o.status_label) + '</div>' +
      '<small>' + esc(when) + ' · ' + (o.order_type === 'delivery' ? 'Yetkazib berish' : 'Olib ketish') + '</small>' +
      '<ul class="order-items">' + items + '</ul>' +
      '<div class="order-foot"><strong>' + money(o.total) + '</strong><button class="btn ghost sm" data-toggle="' + o.id + '">' + (open ? 'Yopish' : 'Tafsilot') + '</button></div>' +
      (open ? '<ul class="timeline" style="margin-top:6px">' + (hist || '<li>Ma’lumot yo‘q</li>') + '</ul>' : '') +
      (acts ? '<div class="order-acts">' + acts + '</div>' : '') + '</article>';
  }

  /* ---------- Profil ---------- */
  routes.profile = guard(function () {
    return Promise.all([api('profile.php'), settings()]).then(function (res) {
      var d = res[0], st = res[1], u = d.user, r = st.restaurant;
      var name = [u.first_name, u.last_name].filter(Boolean).join(' ') || 'Mehmon';
      var addr = d.addresses.length ? d.addresses.map(function (a) {
        return '<div class="row static"><span class="round" style="width:34px;height:34px;background:var(--accent-soft);color:var(--accent-ink)">' + ic('pin') + '</span>' +
          '<div class="row-main"><div class="row-title">' + esc(a.title) + '</div><div class="row-sub">' + esc(a.address) + '</div></div>' +
          '<button class="round plain" style="width:34px;height:34px" data-deladdr="' + a.id + '" aria-label="O‘chirish">' + ic('trash') + '</button></div>';
      }).join('') : '<div class="row static muted">Hali manzil saqlanmagan</div>';
      view.innerHTML = '<div class="fade"><h1 class="title small">Profil</h1>' +
        '<section class="card profile"><span class="avatar lg">' + initial(name) + '</span><div><strong>' + esc(name) + '</strong><small>' + (u.username ? '@' + esc(u.username) : 'Telegram ID: ' + esc(u.telegram_id)) + '</small></div></section>' +
        '<section class="card stats"><div><strong>' + d.stats.orders_count + '</strong><small>Buyurtma</small></div><div><strong>' + money(d.stats.spent) + '</strong><small>Jami xarid</small></div></section>' +
        '<div class="sec-head"><h3>Telefon</h3></div>' +
        '<form class="card form" data-form="phone"><div class="form-row"><input class="inp" name="phone" type="tel" value="' + esc(u.phone) + '" placeholder="+998 90 123 45 67" maxlength="20"><button class="btn primary sm" type="submit" style="height:48px">Saqlash</button></div></form>' +
        '<div class="sec-head"><h3>Manzillar</h3></div><div class="card-list">' + addr + '</div>' +
        '<form class="card form" data-form="addr" style="margin-top:10px"><input class="inp" name="title" maxlength="60" placeholder="Nomi (masalan: Uy)">' +
        '<textarea class="inp" name="address" rows="2" maxlength="255" placeholder="Manzil" required></textarea>' +
        '<input class="inp" name="landmark" maxlength="255" placeholder="Mo‘ljal (ixtiyoriy)"><button class="btn primary" type="submit">Manzil qo‘shish</button></form>' +
        '<div class="sec-head"><h3>Restoran</h3></div><div class="card-list">' +
          '<div class="info-row"><span class="round">' + ic('clock') + '</span><div><small>Ish vaqti</small>' + esc(st.store.working_open + ' – ' + st.store.working_close) + '</div></div>' +
          '<div class="info-row"><span class="round">' + ic('pin') + '</span><div><small>Manzil</small>' + esc(r.address) + '</div></div>' +
          '<div class="info-row"><span class="round">' + ic('phone') + '</span><div><small>Telefon</small><a href="tel:' + esc(String(r.phone).replace(/[^+\d]/g, '')) + '">' + esc(r.phone) + '</a></div></div>' +
          (r.contact_telegram ? '<div class="info-row"><span class="round">' + ic('send') + '</span><div><small>Telegram</small>' + esc(r.contact_telegram) + '</div></div>' : '') +
        '</div>' +
        (r.about ? '<p class="muted" style="margin:12px 4px">' + esc(r.about) + '</p>' : '') +
        '<div style="height:20px"></div><button class="btn ghost block" data-go="#/orders">Buyurtmalar tarixi</button></div>';

      view.onsubmit = function (e) {
        e.preventDefault();
        var f = e.target;
        if (f.getAttribute('data-form') === 'phone') {
          var phone = fv(f, 'phone');
          if (!normPhone(phone)) { toast('Telefon raqamini to‘g‘ri kiriting', 'error'); return; }
          api('profile.php', { body: { action: 'phone', phone: phone } }).then(function () { toast('Telefon saqlandi', 'success'); })
            .catch(function (err) { toast(errText(err), 'error'); });
        } else if (f.getAttribute('data-form') === 'addr') {
          var addrText = fv(f, 'address');
          if (!addrText) { toast('Manzilni kiriting', 'error'); return; }
          api('profile.php', { body: { action: 'address_save', title: fv(f, 'title'), address: addrText, landmark: fv(f, 'landmark') } })
            .then(function () { toast('Manzil saqlandi', 'success'); routes.profile({ parts: ['profile'] }); })
            .catch(function (err) { toast(errText(err), 'error'); });
        }
      };
      onPage = function (e) {
        var del = e.target.closest('[data-deladdr]');
        if (!del) return false;
        api('profile.php', { body: { action: 'address_delete', id: Number(del.getAttribute('data-deladdr')) } })
          .then(function () { toast('Manzil o‘chirildi', 'success'); routes.profile({ parts: ['profile'] }); })
          .catch(function (err) { toast(errText(err), 'error'); });
        return true;
      };
    });
  });

  /* ---------- Global harakatlar ---------- */
  document.addEventListener('click', function (e) {
    if (onPage && onPage(e) === true) return;
    var add = e.target.closest('[data-add]');
    if (add) {
      e.preventDefault();
      var id = Number(add.getAttribute('data-add'));
      if (add.getAttribute('data-req') === '1') { location.hash = '#/product/' + id; return; }
      cart.add({ product_id: id, qty: 1, options: [], note: '' });
      toast('Savatga qo‘shildi', 'success');
      return;
    }
    var go = e.target.closest('[data-go]');
    if (go) {
      var target = go.getAttribute('data-go');
      if (location.hash === target) render(); else location.hash = target;
    }
  });
  window.addEventListener('hashchange', render);
  window.addEventListener('pageshow', updateBadge);

  if (!tg || !tg.initData) {
    toast('Buyurtma uchun Telegram orqali oching', 'error');
  }
  if (!location.hash) location.hash = '#/home';
  updateBadge();
  render();
})();
</script>
</body>
</html>
