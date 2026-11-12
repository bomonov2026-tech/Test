/* ==========================================================================
   TartibOn — mini-ilova qobig'i
   Tashqi kutubxonasiz. Ma'lumot PHP'dan window.TO_BOOT va ?api=me orqali.
   ========================================================================== */
(function (window, document) {
  'use strict';

  var BOOT  = window.TO_BOOT || {};
  var ICONS = window.TO_ICONS || {};

  /* ---- 1. Telegram SDK (yo'q bo'lsa ham ishlaydi) ---------------------- */
  var TG_OK = !!(window.Telegram && window.Telegram.WebApp &&
                 window.Telegram.WebApp.initData !== undefined);
  var noop = function () {};
  var tg = TG_OK ? window.Telegram.WebApp : {
    initData: '', initDataUnsafe: {}, colorScheme: 'light', themeParams: {},
    ready: noop, expand: noop, close: noop, onEvent: noop, offEvent: noop,
    disableVerticalSwipes: noop, setHeaderColor: noop, setBackgroundColor: noop,
    openLink: function (u) { window.open(u, '_blank'); },
    openTelegramLink: function (u) { window.open(u, '_blank'); },
    BackButton: { show: noop, hide: noop, onClick: noop },
    HapticFeedback: { impactOccurred: noop, notificationOccurred: noop },
    showAlert: function (m) { window.alert(m); }
  };
  ['ready', 'expand', 'disableVerticalSwipes'].forEach(function (m) {
    try { tg[m](); } catch (e) {}
  });
  function haptic(k) { try { tg.HapticFeedback.impactOccurred(k || 'light'); } catch (e) {} }

  function applyTheme() {
    var dark = tg.colorScheme === 'dark';
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    try {
      tg.setHeaderColor(dark ? '#16171B' : '#F2F3F7');
      tg.setBackgroundColor(dark ? '#16171B' : '#F2F3F7');
    } catch (e) {}
  }
  applyTheme();
  try { tg.onEvent('themeChanged', applyTheme); } catch (e) {}

  /* ---- 2. Yordamchilar -------------------------------------------------- */
  function $(s, r) { return (r || document).querySelector(s); }
  function el(tag, cls, html) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function money(n) {
    n = Math.round(Number(n) || 0);
    var sign = n < 0 ? '−' : '';
    return sign + String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }
  function svg(key) {
    return '<svg viewBox="0 0 24 24">' + (ICONS[key] || ICONS.info || '') + '</svg>';
  }
  var CHEVRON = '<svg class="cv" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';

  /* ---- 3. API ----------------------------------------------------------- */
  function api(action, payload) {
    return window.fetch((BOOT.base || '') + '?api=' + encodeURIComponent(action), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ initData: tg.initData || '', data: payload || {} })
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || j.ok !== true) throw new Error((j && j.error) || 'Xatolik yuz berdi');
      return j;
    });
  }

  /* ---- 4. Toast --------------------------------------------------------- */
  var toastTimer = null;
  function toast(text) {
    var old = $('.toast');
    if (old) old.remove();
    var n = el('div', 'toast', esc(text));
    n.setAttribute('role', 'status');
    document.body.appendChild(n);
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { n.remove(); }, 2600);
  }

  /* ---- 5. Pastki oyna --------------------------------------------------- */
  var sheetEl = null, lastFocus = null;
  function sheetOpen(title, bodyHTML, opts) {
    sheetClose();
    opts = opts || {};
    lastFocus = document.activeElement;
    var ov = el('div', 'ov');
    ov.innerHTML =
      '<section class="sheet" role="dialog" aria-modal="true" aria-label="' + esc(title) + '" tabindex="-1">' +
        '<div class="sheet__grip"></div>' +
        '<div class="sheet__h"><h2>' + esc(title) + '</h2>' +
          '<button type="button" class="sheet__x" data-close aria-label="Yopish">' +
          '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>' +
        '<div class="sheet__b">' + bodyHTML + '</div></section>';
    ov.addEventListener('click', function (e) {
      if (e.target === ov || e.target.closest('[data-close]')) sheetClose();
    });
    document.body.appendChild(ov);
    document.documentElement.classList.add('is-locked');
    sheetEl = ov;
    var panel = $('.sheet', ov);
    panel.focus();
    if (opts.onOpen) opts.onOpen(panel);
    return panel;
  }
  function sheetClose() {
    if (!sheetEl) return;
    sheetEl.remove();
    sheetEl = null;
    document.documentElement.classList.remove('is-locked');
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sheetEl) sheetClose();
  });

  /* ---- 6. Holat --------------------------------------------------------- */
  var state = {
    page: 'home',
    ready: false,
    user: { first_name: '—' },
    stats: BOOT.stats || { channels: 0, groups: 0, subscribers: 0 },
    wallet: { balance: 0, hold: 0, min: 5000, max: 3000000 },
    catalog: BOOT.catalog || [],
    running: 0,
    next: null,
    recent: [],
    support: BOOT.support || ''
  };
  function category(id) {
    for (var i = 0; i < state.catalog.length; i++) {
      if (state.catalog[i].id === id) return state.catalog[i];
    }
    return null;
  }
  function service(id) {
    for (var i = 0; i < state.catalog.length; i++) {
      var it = state.catalog[i].items;
      for (var j = 0; j < it.length; j++) if (it[j].id === id) return it[j];
    }
    return null;
  }
  /* ro'yxatdagi ikonka rangi: yoqiladigan xizmat yashil/kulrang,
     qolgani bo'lim ma'nosiga qarab ko'k yoki binafsha */
  function iconClass(cat, item) {
    if (item.toggle) return item.on ? 'ic--green' : 'ic--grey';
    if (cat.kind === 'digital') return 'ic--violet';
    return 'ic--blue';
  }

  /* ---- 7. Bo'laklar ----------------------------------------------------- */
  function tilesHTML(cat) {
    return '<section class="sec">' +
      '<div class="sec__h"><b>' + esc(cat.title) + '</b><span>' + esc(cat.subtitle) + '</span></div>' +
      '<div class="sec__g">' + cat.items.map(function (s) {
        return '<button type="button" class="tile" data-service="' + esc(s.id) + '">' +
          '<span class="chip' + (s.on ? ' chip--on' : '') + '">' + svg(s.icon) +
          (s.on ? '<i class="chip__dot"></i>' : '') + '</span>' +
          '<b>' + esc(s.title) + '</b></button>';
      }).join('') + '</div></section>';
  }
  function listHTML(cat) {
    return '<div class="gh">' + esc(cat.title.toUpperCase()) + '</div><div class="card">' +
      cat.items.map(function (s) {
        var ic = '<span class="ic ' + iconClass(cat, s) + '">' + svg(s.icon) + '</span>';
        if (s.toggle) {
          return '<div class="row"><span class="ic ' + iconClass(cat, s) + '">' + svg(s.icon) + '</span>' +
            '<b>' + esc(s.title) + '</b>' +
            '<button type="button" class="sw" role="switch" data-toggle="' + esc(s.id) + '" ' +
            'aria-checked="' + (s.on ? 'true' : 'false') + '" ' +
            'aria-label="' + esc(s.title) + '"></button></div>';
        }
        return '<button type="button" class="row" data-service="' + esc(s.id) + '">' + ic +
          '<b>' + esc(s.title) + '</b>' +
          (s.value ? '<em>' + esc(s.value) + '</em>' : '') + CHEVRON + '</button>';
      }).join('') + '</div>';
  }

  /* ---- 8. Sahifalar ----------------------------------------------------- */
  var PAGES = {};

  PAGES.home = function () {
    var s = state.stats;
    var nx = state.next
      ? 'Keyingisi ' + esc(state.next.time) + ' · ' + esc(state.next.title) : '';
    return '' +
      '<div class="logo" role="img" aria-label="TartibOn"><span class="logo__w">' +
        '<span>T</span><span style="margin-left:-.079em">a</span><span>r</span>' +
        '<span style="margin-left:.029em">t</span><span style="margin-left:.018em">i</span><span>b</span>' +
        '<i class="logo__o" aria-hidden="true"><svg viewBox="0 0 24 24">' +
          '<path class="r" d="M7.2 6.6a8 8 0 1 0 9.6 0"/><path class="b" d="M12 2.6v8"/>' +
        '</svg></i><span>n</span></span></div>' +
      '<div class="stat">' +
        '<div><b>' + money(s.channels) + '</b><span>Kanal</span></div><i></i>' +
        '<div><b>' + money(s.groups) + '</b><span>Guruh</span></div><i></i>' +
        '<div><b>' + money(s.subscribers) + '</b><span>Obunachi</span></div>' +
      '</div>' +
      '<div class="quick">' +
        quickBtn('addChannel', 'Kanal') + quickBtn('addGroup', 'Guruh') + quickBtn('topup', "To'ldirish") +
      '</div>' +
      (state.running
        ? '<div class="now"><span class="now__dot"></span>' +
          '<b>' + state.running + ' ta xizmat ishlab turibdi</b>' +
          (nx ? '<em>' + nx + '</em>' : '') + '</div>'
        : '') +
      ['manage', 'daily', 'digital'].map(function (id) {
        var c = category(id);
        return c ? tilesHTML(c) : '';
      }).join('');
  };
  function quickBtn(action, label) {
    return '<button type="button" data-action="' + action + '">' +
      '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>' + esc(label) + '</button>';
  }

  PAGES.tools = function () {
    return '<h1 class="h1">Xizmatlar</h1>' +
      ['manage', 'daily', 'digital', 'help'].map(function (id) {
        var c = category(id);
        return c ? listHTML(c) : '';
      }).join('');
  };

  PAGES.bal = function () {
    var w = state.wallet;
    var rows = state.recent.map(function (r) {
      var pos = r.amount >= 0;
      return '<div class="row"><span class="ic ' + (pos ? 'ic--green' : 'ic--grey') + '">' +
        (pos ? '+' : '−') + '</span><b>' + esc(r.title) + '</b>' +
        '<em' + (pos ? ' class="pos"' : '') + '>' + (pos ? '+' : '') + money(r.amount) + '</em></div>';
    }).join('');
    return '' +
      '<h1 class="h1">Balans</h1>' +
      '<div class="bal"><span>Mavjud mablag‘</span>' +
        '<b>' + (state.ready ? money(w.balance) : '…') + "<em>so'm</em></b>" +
        (w.hold ? '<i>' + money(w.hold) + " so'm muzlatilgan</i>" : '') +
      '</div>' +
      '<div class="gh" style="margin-top:14px">AMALLAR</div><div class="card">' +
        walletRow('topup', 'currency', "Balansni to'ldirish", 'ic--blue') +
        walletRow('withdraw', 'digital', 'Pul yechish', 'ic--blue') +
        walletRow('transfer', 'group', "Do'stga o'tkazish", 'ic--blue') +
        walletRow('donate', 'gift', "Qo'llab-quvvatlash", 'ic--violet') +
      '</div>' +
      (rows ? '<div class="gh">SO‘NGGI AMALLAR</div><div class="card">' + rows + '</div>' : '');
  };
  function walletRow(action, icon, label, cls) {
    return '<button type="button" class="row" data-action="' + action + '">' +
      '<span class="ic ' + cls + '">' + svg(icon) + '</span><b>' + esc(label) + '</b>' + CHEVRON + '</button>';
  }

  PAGES.me = function () {
    var u = state.user;
    var initial = String(u.first_name || u.username || 'T').trim().charAt(0).toUpperCase();
    return '' +
      '<h1 class="h1">Profil</h1>' +
      '<div class="prof"><div class="prof__av" aria-hidden="true">' + esc(initial) + '</div>' +
        '<div class="prof__t"><b>' + esc(u.first_name || 'Foydalanuvchi') + '</b>' +
          (u.username ? '<small>@' + esc(u.username) + '</small>' : '') +
          '<div class="tags"><span class="tag">ID: ' + esc(u.id || '—') + '</span>' +
          (u.admin ? '<span class="tag tag--blue">Admin</span>' : '') + '</div></div></div>' +
      '<div class="gh" style="margin-top:14px">HISOBIM</div><div class="card">' +
        walletRow2('my-channel', 'channel', 'Kanal va guruhlarim', (u.chats || 0) + ' ta') +
        walletRow2('referral', 'group', 'Taklif havolalari', '') +
        walletRow2('about', 'info', 'Ilova haqida', '') +
      '</div>';
  };
  function walletRow2(id, icon, label, value) {
    return '<button type="button" class="row" data-service="' + esc(id) + '">' +
      '<span class="ic ic--blue">' + svg(icon) + '</span><b>' + esc(label) + '</b>' +
      (value ? '<em>' + esc(value) + '</em>' : '') + CHEVRON + '</button>';
  }

  /* ---- 9. Router -------------------------------------------------------- */
  var view = null, nav = null;
  function go(page, push) {
    if (!PAGES[page]) page = 'home';
    sheetClose();
    state.page = page;
    document.documentElement.classList.toggle('is-home', page === 'home');
    view.innerHTML = '<div class="page is-on">' + PAGES[page]() + '</div>';
    window.scrollTo(0, 0);
    Array.prototype.forEach.call(nav.querySelectorAll('[data-tab]'), function (b) {
      if (b.dataset.tab === page) b.setAttribute('aria-current', 'page');
      else b.removeAttribute('aria-current');
    });
    if (push !== false && location.hash !== '#' + page) {
      try { history.pushState({ page: page }, '', '#' + page); } catch (e) {}
    }
    try { page === 'home' ? tg.BackButton.hide() : tg.BackButton.show(); } catch (e) {}
  }
  window.addEventListener('popstate', function () {
    go((location.hash || '#home').slice(1), false);
  });
  try { tg.BackButton.onClick(function () { go('home'); }); } catch (e) {}

  /* ---- 10. Amallar ------------------------------------------------------ */
  var ACTIONS = {
    addChannel: function () { openAddChat('channel'); },
    addGroup:   function () { openAddChat('group'); },
    topup:      openTopup,
    withdraw:   function () { stub('Pul yechish', 'Pul yechish arizasi'); },
    transfer:   function () { stub("Do'stga o'tkazish", 'Boshqa foydalanuvchiga pul o‘tkazish'); },
    donate:     function () { stub("Qo'llab-quvvatlash", 'Bot rivoji uchun hissa'); }
  };

  function openAddChat(kind) {
    var word = kind === 'channel' ? 'Kanal' : 'Guruh';
    sheetOpen(word + ' qo‘shish',
      '<p class="note" style="margin-top:0">Botni ' + word.toLowerCase() +
      'ga administrator qilib qo‘shing, so‘ng quyidagi tugmani bosing — ' +
      'ro‘yxat avtomatik yangilanadi.</p><div style="height:14px"></div>' +
      '<button type="button" class="btn" data-link="https://t.me/' + esc(BOOT.bot || '') +
      '?start' + (kind === 'channel' ? 'channel' : 'group') + '">Telegramda ochish</button>');
  }

  function openTopup() {
    var w = state.wallet;
    var sums = [10000, 25000, 50000, 100000, 250000, 500000];
    sheetOpen("Balansni to'ldirish",
      '<label class="fld"><span>Summa (so‘m)</span>' +
      '<input type="tel" inputmode="numeric" id="tp-sum" placeholder="' + money(w.min) + '"></label>' +
      '<div class="chips">' + sums.map(function (s) {
        return '<button type="button" data-sum="' + s + '">' + money(s) + '</button>';
      }).join('') + '</div>' +
      '<button type="button" class="btn" id="tp-go">Davom etish</button>' +
      '<p class="note">Eng kam: ' + money(w.min) + " so'm · eng ko'p: " + money(w.max) + " so'm.</p>",
      { onOpen: function (p) {
          var input = $('#tp-sum', p);
          p.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-sum]');
            if (chip) { input.value = money(chip.dataset.sum); haptic(); }
          });
          input.addEventListener('input', function () {
            var raw = input.value.replace(/\D/g, '');
            input.value = raw ? money(raw) : '';
          });
          $('#tp-go', p).addEventListener('click', function () {
            var amount = Number(input.value.replace(/\D/g, ''));
            if (!amount) return toast('Summani kiriting');
            if (amount < w.min) return toast('Eng kam ' + money(w.min) + " so'm");
            if (amount > w.max) return toast('Eng ko‘p ' + money(w.max) + " so'm");
            var btn = this;
            btn.disabled = true;
            api('topup_create', { amount: amount }).then(function (r) {
              sheetClose();
              toast(r.message || 'Ariza yuborildi');
              if (r.wallet) { state.wallet = r.wallet; if (state.page === 'bal') go('bal', false); }
            })['catch'](function (err) { btn.disabled = false; toast(err.message); });
          });
        } });
  }

  function stub(title, text) {
    sheetOpen(title, '<p class="note" style="margin-top:0">' + esc(text) +
      '. Bu bo‘lim hali ulanmagan — server tomonidagi amal qo‘shilgach ishga tushadi.</p>');
  }

  var HANDLERS = {};
  function openService(id) {
    if (HANDLERS[id]) return HANDLERS[id]();
    var s = service(id);
    if (!s) return;
    sheetOpen(s.title,
      '<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">' +
      '<span class="chip">' + svg(s.icon) + '</span><div><b style="font-size:17px">' + esc(s.title) + '</b>' +
      '<div style="color:var(--muted);font-size:13.5px">' + esc(s.sub || '') + '</div></div></div>' +
      '<p class="note" style="margin-top:0">Bu xizmat sahifasi hali ulanmagan.</p>');
  }

  /* xizmatni yoqish/o'chirish — joyida, sahifaga kirmasdan */
  function toggleService(btn, id) {
    var on = btn.getAttribute('aria-checked') !== 'true';
    btn.setAttribute('aria-checked', on ? 'true' : 'false');   /* darhol ko'rinsin */
    btn.disabled = true;
    var svc = service(id);
    if (svc) svc.on = on;
    var icon = btn.parentNode.querySelector('.ic');
    if (icon) { icon.classList.toggle('ic--green', on); icon.classList.toggle('ic--grey', !on); }

    api('service_toggle', { id: id, on: on }).then(function (r) {
      btn.disabled = false;
      state.running = r.running;
      haptic();
    })['catch'](function (err) {
      btn.disabled = false;
      btn.setAttribute('aria-checked', on ? 'false' : 'true');  /* orqaga qaytarish */
      if (svc) svc.on = !on;
      if (icon) { icon.classList.toggle('ic--green', !on); icon.classList.toggle('ic--grey', on); }
      toast(err.message);
    });
  }

  /* ---- 11. Bitta global tinglovchi (passive) ---------------------------- */
  function onTap(e) {
    var t = e.target, n;
    if ((n = t.closest('[data-toggle]'))) { return toggleService(n, n.dataset.toggle); }
    if ((n = t.closest('[data-tab]')))    { haptic(); return go(n.dataset.tab); }
    if ((n = t.closest('[data-action]'))) {
      haptic();
      var fn = ACTIONS[n.dataset.action];
      return fn ? fn() : toast('Amal topilmadi');
    }
    if ((n = t.closest('[data-service]'))) { haptic(); return openService(n.dataset.service); }
    if ((n = t.closest('[data-link]'))) {
      haptic();
      var url = n.dataset.link;
      try { /t\.me\//.test(url) ? tg.openTelegramLink(url) : tg.openLink(url); }
      catch (err) { window.open(url, '_blank'); }
      return;
    }
    if (t.closest('[data-close-app]')) { try { tg.close(); } catch (err) {} }
  }

  /* ---- 12. Ishga tushirish ---------------------------------------------- */
  function loadMe() {
    return api('me').then(function (r) {
      state.ready   = true;
      state.user    = r.user || state.user;
      state.stats   = r.stats || state.stats;
      state.wallet  = r.wallet || state.wallet;
      state.running = r.running || 0;
      state.next    = r.next || null;
      state.recent  = r.recent || [];
      if (r.catalog && r.catalog.length) state.catalog = r.catalog;
      go(state.page, false);
    })['catch'](function (err) {
      state.ready = true;
      toast(err.message || 'Ma‘lumot yuklanmadi');
    });
  }
  function boot() {
    view = $('#view');
    nav  = $('#nav');
    document.addEventListener('click', onTap, { passive: true });
    go((location.hash || '#home').slice(1), false);
    loadMe();
    if (!TG_OK && !BOOT.demo) toast('Telegram ichida ochilmadi — namoyish rejimi');
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else { boot(); }

  /* ---- 13. Tashqi API --------------------------------------------------- */
  window.TartibOn = {
    go: go, toast: toast, sheet: sheetOpen, closeSheet: sheetClose,
    api: api, state: state, money: money,
    /* yangi xizmat sahifasini ulash: TartibOn.service('stats', fn) */
    service: function (id, fn) { HANDLERS[id] = fn; }
  };
})(window, document);
