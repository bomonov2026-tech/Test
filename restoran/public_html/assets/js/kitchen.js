/* Oshxona ekrani: faol buyurtmalar, AJAX orqali avtomatik yangilanish (10 soniya) */
(function () {
  'use strict';

  var board = document.getElementById('board');
  var liveEl = document.getElementById('liveState');
  var lastEl = document.getElementById('lastUpdate');
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var busy = false;
  var LABELS = { new: 'Yangi', accepted: 'Qabul qilindi', preparing: 'Tayyorlanmoqda', ready: 'Tayyor' };

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function toast(msg, type) {
    if (window.RSAdmin) window.RSAdmin.toast(msg, type);
  }

  function card(o) {
    var cls = o.status === 'new' ? 'new' : o.status === 'preparing' ? 'preparing' : o.status === 'ready' ? 'ready' : '';
    var late = o.minutes >= 20;
    var items = o.items.map(function (it) {
      var opts = it.options && it.options.length ? '<small>+ ' + esc(it.options.join(', ')) + '</small>' : '';
      var note = it.note ? '<small>Izoh: ' + esc(it.note) + '</small>' : '';
      return '<li><b>' + it.quantity + '×</b><div>' + esc(it.name) + opts + note + '</div></li>';
    }).join('');
    var comment = o.comment ? '<div class="k-note">Mijoz izohi: ' + esc(o.comment) + '</div>' : '';
    var action;
    if (o.next.indexOf('preparing') !== -1) {
      action = '<button class="btn ok" type="button" data-id="' + o.id + '" data-status="preparing">Tayyorlashni boshlash</button>';
    } else if (o.next.indexOf('ready') !== -1) {
      action = '<button class="btn ok" type="button" data-id="' + o.id + '" data-status="ready">Tayyor bo‘ldi</button>';
    } else {
      action = '<div class="muted">Tayyor — topshirish kutilmoqda</div>';
    }
    var time = String(o.created_at || '').slice(11, 16);
    return '<article class="k-card ' + cls + '">' +
      '<div class="k-head"><span class="k-no">#' + o.order_no + '</span><span class="k-time' + (late ? ' late' : '') + '">' + o.minutes + ' daq</span></div>' +
      '<div class="k-meta"><span>' + (o.order_type === 'delivery' ? 'Yetkazish' : 'Olib ketish') + '</span><span>·</span><span>' +
      esc(LABELS[o.status] || o.status) + '</span><span>·</span><span>' + esc(time) + '</span></div>' +
      '<ul class="k-items">' + items + '</ul>' + comment +
      '<div class="k-actions">' + action + '</div>' +
      '</article>';
  }

  function render(orders) {
    if (!orders.length) {
      board.innerHTML = '<div class="k-empty">Hozircha faol buyurtmalar yo‘q</div>';
      return;
    }
    board.innerHTML = orders.map(card).join('');
  }

  function load() {
    if (busy) return;
    busy = true;
    fetch('/admin/api.php?action=kitchen_orders', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        if (!res.ok || !res.d || !res.d.ok) throw new Error((res.d && res.d.error) || 'Xato');
        render(res.d.orders);
        liveEl.textContent = 'Jonli · ' + res.d.orders.length + ' ta faol';
        lastEl.textContent = 'Yangilandi: ' + new Date().toLocaleTimeString('uz-UZ', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
      })
      .catch(function (err) {
        liveEl.textContent = 'Aloqa uzildi';
        if (!board.children.length || board.querySelector('.k-empty')) {
          board.innerHTML = '<div class="k-empty">' + esc(err.message || 'Yuklashda xatolik') + '</div>';
        }
      })
      .finally(function () { busy = false; });
  }

  function setStatus(btn) {
    var id = Number(btn.getAttribute('data-id'));
    var status = btn.getAttribute('data-status');
    btn.disabled = true;
    fetch('/admin/api.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf, Accept: 'application/json' },
      body: JSON.stringify({ action: 'set_status', id: id, status: status }),
    }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        if (!res.ok || !res.d || !res.d.ok) throw new Error((res.d && res.d.error) || 'Xato');
        toast(status === 'preparing' ? 'Tayyorlash boshlandi' : 'Buyurtma tayyor', 'ok');
        load();
      })
      .catch(function (err) {
        toast(err.message || 'Xatolik', 'error');
        btn.disabled = false;
      });
  }

  board.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-status]');
    if (btn) setStatus(btn);
  });

  load();
  setInterval(function () { if (!document.hidden) load(); }, 10000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(); });
})();
