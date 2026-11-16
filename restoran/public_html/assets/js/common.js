/* Mijoz Mini App — umumiy yadro: API, savat, xabarnomalar, mahsulot kartochkalari */
(function () {
  'use strict';

  var CART_KEY = 'restoran_cart_v1';
  var PROMO_KEY = 'restoran_promo_v1';
  var TYPE_KEY = 'restoran_order_type_v1';
  var tg = (window.Telegram && window.Telegram.WebApp) || null;

  var RS = (window.RS = window.RS || {});
  RS.tg = tg;

  RS.$ = function (sel, root) { return (root || document).querySelector(sel); };
  RS.$$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  RS.esc = function (s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };

  RS.money = function (n) {
    return String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m';
  };

  RS.icon = function (name) { return '<i data-lucide="' + RS.esc(name) + '" aria-hidden="true"></i>'; };

  RS.icons = function () {
    try { if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons(); } catch (e) { /* ikonkalar ixtiyoriy */ }
  };

  RS.query = function (name) {
    return new URLSearchParams(window.location.search).get(name) || '';
  };

  RS.haptic = function (kind) {
    try {
      if (!tg || !tg.HapticFeedback) return;
      if (kind === 'success' || kind === 'error' || kind === 'warning') tg.HapticFeedback.notificationOccurred(kind);
      else tg.HapticFeedback.impactOccurred(kind || 'light');
    } catch (e) { /* ixtiyoriy */ }
  };

  RS.confirm = function (message) {
    return new Promise(function (resolve) {
      if (tg && typeof tg.showConfirm === 'function') {
        try { tg.showConfirm(message, function (ok) { resolve(!!ok); }); return; } catch (e) { /* pastga */ }
      }
      resolve(window.confirm(message));
    });
  };

  RS.toast = function (message, type) {
    var box = document.getElementById('toasts');
    if (!box) return;
    var el = document.createElement('div');
    el.className = 'toast' + (type === 'error' ? ' error' : type === 'ok' ? ' ok' : '');
    el.setAttribute('role', 'status');
    el.textContent = message;
    box.appendChild(el);
    if (type === 'error') RS.haptic('error'); else if (type === 'ok') RS.haptic('success');
    setTimeout(function () {
      el.classList.add('out');
      setTimeout(function () { el.remove(); }, 260);
    }, 2900);
  };

  RS.busy = function (btn, on) {
    if (!btn) return;
    btn.classList.toggle('btn-loading', !!on);
    btn.disabled = !!on;
  };

  RS.errorText = function (err) {
    if (err && err.status === 401) return 'Ilovani Telegram orqali oching.';
    return (err && err.message) || 'Xatolik yuz berdi. Qayta urinib ko‘ring.';
  };

  /* ---------- API ---------- */
  RS.api = function (path, opts) {
    opts = opts || {};
    var headers = { Accept: 'application/json' };
    if (tg && tg.initData) headers['X-Init-Data'] = tg.initData;
    var method = opts.method || (opts.body !== undefined ? 'POST' : 'GET');
    var init = { method: method, headers: headers, cache: 'no-store', credentials: 'same-origin' };
    if (opts.body !== undefined) {
      headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(opts.body);
    }
    return fetch('/api/' + path, init).then(function (res) {
      return res.json().catch(function () { return null; }).then(function (data) {
        if (!res.ok || !data || data.ok === false) {
          var err = new Error((data && data.error) || ('Xatolik (' + res.status + ')'));
          err.status = res.status;
          err.data = data;
          throw err;
        }
        return data;
      });
    }, function () {
      throw new Error('Internet aloqasi yo‘q. Qayta urinib ko‘ring.');
    });
  };

  /* ---------- Savat (localStorage) ----------
     Savat faqat ko‘rinish uchun saqlanadi. Narxlar har safar serverda qayta hisoblanadi. */
  RS.cart = {
    items: function () {
      try {
        var v = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
        return Array.isArray(v) ? v.filter(function (i) { return i && Number(i.product_id) > 0; }) : [];
      } catch (e) { return []; }
    },
    save: function (items) {
      try { localStorage.setItem(CART_KEY, JSON.stringify(items.slice(0, 50))); } catch (e) { /* xotira to‘la */ }
      RS.updateBadges();
    },
    key: function (i) {
      return [i.product_id, (i.options || []).slice().sort(function (a, b) { return a - b; }).join(','), i.note || ''].join('|');
    },
    count: function () {
      return RS.cart.items().reduce(function (s, i) { return s + (Number(i.qty) || 0); }, 0);
    },
    add: function (item) {
      var items = RS.cart.items();
      var key = RS.cart.key(item);
      var found = items.filter(function (i) { return RS.cart.key(i) === key; })[0];
      if (found) found.qty = Math.min(99, found.qty + item.qty);
      else items.push(item);
      RS.cart.save(items);
      return items;
    },
    setQty: function (index, qty) {
      var items = RS.cart.items();
      if (!items[index]) return;
      items[index].qty = Math.max(1, Math.min(99, qty));
      RS.cart.save(items);
    },
    remove: function (index) {
      var items = RS.cart.items();
      items.splice(index, 1);
      RS.cart.save(items);
    },
    clear: function () {
      try { localStorage.removeItem(CART_KEY); localStorage.removeItem(PROMO_KEY); } catch (e) { /* ignore */ }
      RS.updateBadges();
    },
  };

  RS.promo = {
    get: function () { try { return localStorage.getItem(PROMO_KEY) || ''; } catch (e) { return ''; } },
    set: function (v) { try { v ? localStorage.setItem(PROMO_KEY, v) : localStorage.removeItem(PROMO_KEY); } catch (e) { /* ignore */ } },
  };

  RS.orderType = {
    get: function () { try { return localStorage.getItem(TYPE_KEY) === 'pickup' ? 'pickup' : 'delivery'; } catch (e) { return 'delivery'; } },
    set: function (v) { try { localStorage.setItem(TYPE_KEY, v === 'pickup' ? 'pickup' : 'delivery'); } catch (e) { /* ignore */ } },
  };

  RS.updateBadges = function () {
    var n = RS.cart.count();
    RS.$$('[data-cart-badge]').forEach(function (b) {
      b.textContent = n > 99 ? '99+' : String(n);
      b.hidden = n === 0;
    });
  };

  /* ---------- Mahsulot kartochkasi ---------- */
  RS.productCard = function (p) {
    var tags = [];
    if (p.discount_percent) tags.push('<span class="tag off">−' + p.discount_percent + '%</span>');
    if (p.is_new) tags.push('<span class="tag accent">Yangi</span>');
    if (p.is_combo) tags.push('<span class="tag">Kombo</span>');
    var img = p.image
      ? '<img src="' + RS.esc(p.image) + '" alt="" loading="lazy" decoding="async">'
      : RS.icon('utensils');
    var old = p.discount_percent ? '<span class="pc-old">' + RS.money(p.base_price) + '</span>' : '';
    return '<article class="product-card' + (p.is_available ? '' : ' unavailable') + '" data-open="' + p.id + '">' +
      '<div class="pc-img">' + img + '<div class="pc-tags">' + tags.join('') + '</div></div>' +
      '<button class="pc-fav" type="button" data-fav="' + p.id + '" aria-pressed="' + (p.is_favorite ? 'true' : 'false') + '" aria-label="Sevimlilarga qo‘shish">' + RS.icon('heart') + '</button>' +
      '<div class="pc-body">' +
        '<div class="pc-name">' + RS.esc(p.name) + '</div>' +
        '<div class="pc-desc">' + RS.esc(p.description) + '</div>' +
        '<div class="pc-foot">' +
          '<div class="pc-price">' + old + RS.money(p.price) + '<small>' + RS.esc(p.unit) + '</small></div>' +
          '<button class="add-btn" type="button" data-add="' + p.id + '" data-required="' + (p.required_options ? 1 : 0) + '"' +
            (p.is_available ? '' : ' disabled') + ' aria-label="Savatga qo‘shish">' + RS.icon('plus') + '</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  };

  /* Kartochkalar bo‘yicha global harakatlar (qo‘shish, sevimli, ochish) */
  document.addEventListener('click', function (ev) {
    var addBtn = ev.target.closest && ev.target.closest('[data-add]');
    if (addBtn) {
      ev.preventDefault();
      ev.stopPropagation();
      var id = Number(addBtn.getAttribute('data-add'));
      if (addBtn.getAttribute('data-required') === '1') {
        window.location.href = '/product.php?id=' + id;
        return;
      }
      RS.cart.add({ product_id: id, qty: 1, options: [], note: '' });
      RS.toast('Savatga qo‘shildi', 'ok');
      RS.haptic('light');
      return;
    }
    var favBtn = ev.target.closest && ev.target.closest('[data-fav]');
    if (favBtn) {
      ev.preventDefault();
      ev.stopPropagation();
      toggleFavorite(favBtn);
      return;
    }
    var card = ev.target.closest && ev.target.closest('[data-open]');
    if (card) {
      window.location.href = '/product.php?id=' + card.getAttribute('data-open');
    }
  });

  function toggleFavorite(btn) {
    var on = btn.getAttribute('aria-pressed') !== 'true';
    var id = Number(btn.getAttribute('data-fav'));
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    RS.api('products.php', { method: 'POST', body: { action: 'favorite', product_id: id, on: on } })
      .then(function () {
        RS.toast(on ? 'Sevimlilarga qo‘shildi' : 'Sevimlilardan olib tashlandi', 'ok');
      })
      .catch(function (err) {
        btn.setAttribute('aria-pressed', on ? 'false' : 'true');
        RS.toast(RS.errorText(err), 'error');
      });
  }
  RS.toggleFavorite = toggleFavorite;

  /* ---------- Do‘kon holati ---------- */
  RS.loadStore = function () {
    return RS.api('settings.php').then(function (d) {
      RS.store = d;
      RS.$$('[data-store-status]').forEach(function (el) {
        el.classList.toggle('open', !!d.store.is_open);
        el.classList.toggle('closed', !d.store.is_open);
        var t = el.querySelector('[data-store-text]');
        if (t) {
          t.textContent = d.store.is_open
            ? 'Ochiq · ' + d.store.working_open + ' – ' + d.store.working_close
            : d.store.message;
        }
      });
      return d;
    }).catch(function () { return null; });
  };

  /* ---------- Telegram WebApp sozlamalari ---------- */
  function setViewport() {
    var h = (tg && tg.viewportStableHeight) || window.innerHeight;
    document.documentElement.style.setProperty('--tg-vh', h + 'px');
  }

  function initTelegram() {
    setViewport();
    window.addEventListener('resize', setViewport);
    if (!tg) return;
    try {
      tg.ready();
      tg.expand();
      if (tg.setHeaderColor) tg.setHeaderColor('#ffffff');
      if (tg.setBackgroundColor) tg.setBackgroundColor('#ffffff');
      if (tg.onEvent) tg.onEvent('viewportChanged', setViewport);
      if (tg.BackButton) {
        if (RS.$('.back-link')) {
          tg.BackButton.show();
          tg.BackButton.onClick(function () { window.history.length > 1 ? window.history.back() : (window.location.href = '/'); });
        } else {
          tg.BackButton.hide();
        }
      }
    } catch (e) { /* eski Telegram versiyasi */ }
  }

  function markActiveTab() {
    var page = document.body.getAttribute('data-page');
    RS.$$('.tabbar a').forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('data-tab') === page);
    });
  }

  function init() {
    initTelegram();
    markActiveTab();
    RS.updateBadges();
    RS.icons();
    if (RS.$('[data-store-status]')) RS.loadStore();
    window.addEventListener('storage', RS.updateBadges);
    window.addEventListener('pageshow', RS.updateBadges);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
