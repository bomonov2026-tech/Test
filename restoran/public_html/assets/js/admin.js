/* Admin panel: yon menyu, tasdiqlash oynalari, yangi buyurtma ovozli bildirishnomasi */
(function () {
  'use strict';

  var SOUND_KEY = 'restoran_admin_sound';
  var POLL_MS = 15000;

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function icons() {
    try { if (window.lucide && lucide.createIcons) lucide.createIcons(); } catch (e) { /* ixtiyoriy */ }
  }

  function toast(msg, type) {
    var box = $('#toasts');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : type === 'ok' ? ' ok' : '');
    el.textContent = msg;
    box.appendChild(el);
    setTimeout(function () { el.classList.add('out'); setTimeout(function () { el.remove(); }, 260); }, 4200);
  }

  /* ---------- Sidebar (mobil) ---------- */
  var sidebar = $('#sidebar');
  if (sidebar) {
    var overlay = document.createElement('div');
    overlay.className = 'overlay';
    sidebar.insertAdjacentElement('afterend', overlay);
    $$('[data-toggle-sidebar]').forEach(function (btn) {
      btn.addEventListener('click', function () { sidebar.classList.toggle('open'); });
    });
    overlay.addEventListener('click', function () { sidebar.classList.remove('open'); });
  }

  /* ---------- Tasdiqlash (data-confirm) ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('[data-confirm]');
    if (btn && !window.confirm(btn.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  /* ---------- Ovozli bildirishnoma ---------- */
  var audioCtx = null;
  function beep() {
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      [0, 0.22, 0.44].forEach(function (offset, i) {
        var osc = audioCtx.createOscillator();
        var gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.value = i === 1 ? 1047 : 880;
        gain.gain.setValueAtTime(0.0001, audioCtx.currentTime + offset);
        gain.gain.exponentialRampToValueAtTime(0.25, audioCtx.currentTime + offset + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + offset + 0.18);
        osc.connect(gain).connect(audioCtx.destination);
        osc.start(audioCtx.currentTime + offset);
        osc.stop(audioCtx.currentTime + offset + 0.2);
      });
    } catch (e) { /* brauzer ovozni qo‘llamasa */ }
  }

  var soundBtn = $('#soundBtn');
  var soundOn = false;
  function renderSound() {
    if (!soundBtn) return;
    soundBtn.setAttribute('aria-pressed', soundOn ? 'true' : 'false');
    soundBtn.innerHTML = '<i data-lucide="' + (soundOn ? 'volume-2' : 'volume-x') + '"></i><span>' +
      (soundOn ? 'Ovoz yoqilgan' : 'Ovoz o‘chiq') + '</span>';
    icons();
  }
  if (soundBtn) {
    try { soundOn = localStorage.getItem(SOUND_KEY) === '1'; } catch (e) { soundOn = false; }
    renderSound();
    soundBtn.addEventListener('click', function () {
      soundOn = !soundOn;
      try { localStorage.setItem(SOUND_KEY, soundOn ? '1' : '0'); } catch (e) { /* ignore */ }
      if (soundOn) beep();
      renderSound();
    });
  }

  /* Yangi buyurtmalarni tekshirish (faqat admin) */
  var body = document.body;
  var role = body.getAttribute('data-role');
  var lastId = Number(body.getAttribute('data-last-order') || 0);
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  function badgeCount(n) {
    $$('[data-new-count]').forEach(function (el) {
      el.textContent = String(n);
      el.hidden = n === 0;
    });
  }

  function poll() {
    if (document.hidden) return;
    fetch('/admin/api.php?action=new_orders_since&after=' + lastId, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.ok) return;
        badgeCount(d.active_new || 0);
        if (d.orders && d.orders.length) {
          d.orders.forEach(function (o) {
            lastId = Math.max(lastId, o.id);
            toast('Yangi buyurtma #' + o.order_no + ' — ' + o.customer_name + ' · ' + o.total_formatted, 'ok');
          });
          if (soundOn) beep();
          if (typeof window.onNewOrders === 'function') window.onNewOrders(d.orders);
        }
      })
      .catch(function () { /* tarmoq uzilsa keyingi safar */ });
  }

  if (role === 'admin') {
    badgeCount(Number(body.getAttribute('data-new-initial') || 0));
    setInterval(poll, POLL_MS);
  }

  window.RSAdmin = { toast: toast, esc: esc, icons: icons, csrf: function () { return csrf; } };

  icons();
})();
