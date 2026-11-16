/* Buyurtmalarim: faol / tarix, avtomatik yangilash (15 soniya), bekor qilish, takroriy buyurtma */
(function () {
  'use strict';
  var RS = window.RS;
  var list = RS.$('#orderList');
  var empty = RS.$('#ordersEmpty');
  var pager = RS.$('#ordersPager');
  var state = { scope: 'active', page: 1, pages: 1, orders: [], openIds: {} };
  var timer = null;
  var busy = false;

  function setScope(scope) {
    state.scope = scope;
    state.page = 1;
    RS.$$('[data-scope]').forEach(function (b) {
      b.setAttribute('aria-selected', b.getAttribute('data-scope') === scope ? 'true' : 'false');
    });
    load(true);
  }

  function itemsText(o) {
    return o.items.map(function (it) {
      var opts = it.options && it.options.length ? ' <span>(' + RS.esc(it.options.map(function (x) { return x.value; }).join(', ')) + ')</span>' : '';
      return '<div>' + it.quantity + ' × ' + RS.esc(it.name) + opts + '</div>';
    }).join('');
  }

  function card(o) {
    var open = !!state.openIds[o.id];
    var typeLabel = o.order_type === 'delivery' ? 'Yetkazib berish' : 'Olib ketish';
    var when = formatDate(o.created_at);
    var history = (o.history || []).map(function (h) {
      return '<li><strong>' + RS.esc(h.to_label) + '</strong><small>' + formatDate(h.at) + '</small></li>';
    }).join('');
    var actions = '';
    if (o.can_cancel) actions += '<button class="btn danger sm" type="button" data-cancel="' + o.id + '">Bekor qilish</button>';
    if (o.status === 'completed' || o.status === 'cancelled') actions += '<button class="btn ghost sm" type="button" data-repeat="' + o.id + '">' + RS.icon('refresh-cw') + ' Takroriy buyurtma</button>';
    return '<article class="order-card fade-in" data-order="' + o.id + '">' +
      '<div class="oc-head"><span class="oc-no">Buyurtma #' + o.order_no + '</span>' + RS.statusBadge(o.status, o.status_label) + '</div>' +
      '<div class="oc-meta"><span>' + RS.esc(when) + '</span><span>·</span><span>' + typeLabel + '</span></div>' +
      '<div class="oc-items">' + itemsText(o) + '</div>' +
      '<div class="oc-foot"><span class="oc-total">' + RS.money(o.total) + '</span></div>' +
      '<details' + (open ? ' open' : '') + ' data-details="' + o.id + '"><summary>Holat tarixi</summary>' +
        '<ul class="timeline">' + (history || '<li>Ma’lumot yo‘q</li>') + '</ul></details>' +
      (actions ? '<div class="oc-actions">' + actions + '</div>' : '') +
    '</article>';
  }

  RS.statusBadge = function (status, label) {
    var tone = { new: 'info', accepted: 'warn', preparing: 'warn', ready: 'accent', delivering: 'accent', completed: 'ok', cancelled: 'danger' }[status] || 'muted';
    return '<span class="badge-status tone-' + tone + '">' + RS.esc(label || status) + '</span>';
  };

  function formatDate(s) {
    if (!s) return '';
    var d = new Date(String(s).replace(' ', 'T'));
    if (isNaN(d.getTime())) return s;
    var pad = function (n) { return (n < 10 ? '0' : '') + n; };
    return pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
  }

  function render(append) {
    var html = state.orders.map(card).join('');
    list.innerHTML = html;
    empty.hidden = state.orders.length > 0;
    empty.textContent = state.scope === 'active' ? 'Faol buyurtmalar yo‘q.' : 'Buyurtmalar tarixi bo‘sh.';
    pager.innerHTML = state.page < state.pages
      ? '<button class="btn ghost block" type="button" id="moreBtn">Yana ko‘rsatish</button>' : '';
    RS.icons();
  }

  function load(reset) {
    if (busy) return Promise.resolve();
    busy = true;
    var page = reset ? 1 : state.page;
    return RS.api('orders.php?action=list&scope=' + state.scope + '&page=' + page).then(function (d) {
      state.pages = d.pages;
      state.page = d.page;
      state.orders = reset ? d.orders : state.orders.concat(d.orders);
      render();
    }).catch(function (err) {
      if (reset) list.innerHTML = '<div class="alert error">' + RS.esc(RS.errorText(err)) + '</div>';
      RS.toast(RS.errorText(err), 'error');
    }).finally(function () { busy = false; });
  }

  /* Avtomatik yangilash: holat o‘zgarganda ro‘yxat yangilanadi (ochiq tafsilotlar saqlanadi) */
  function snapshotOpen() {
    RS.$$('details[data-details]').forEach(function (d) {
      state.openIds[d.getAttribute('data-details')] = d.open;
    });
  }

  function startPolling() {
    clearInterval(timer);
    timer = setInterval(function () {
      if (document.hidden || busy) return;
      snapshotOpen();
      load(true); // ochiq tafsilotlar render paytida saqlanadi
    }, 15000);
  }

  list.addEventListener('toggle', function (e) {
    var d = e.target;
    if (d.matches && d.matches('details[data-details]')) state.openIds[d.getAttribute('data-details')] = d.open;
  }, true);

  list.addEventListener('click', function (e) {
    var cancel = e.target.closest('[data-cancel]');
    if (cancel) { cancelOrder(cancel); return; }
    var rep = e.target.closest('[data-repeat]');
    if (rep) { repeatOrder(rep); }
  });

  pager.addEventListener('click', function (e) {
    if (!e.target.closest('#moreBtn')) return;
    state.page += 1;
    load(false);
  });

  RS.$$('[data-scope]').forEach(function (b) {
    b.addEventListener('click', function () { setScope(b.getAttribute('data-scope')); });
  });

  function cancelOrder(btn) {
    RS.confirm('Buyurtmani bekor qilasizmi?').then(function (ok) {
      if (!ok) return;
      RS.busy(btn, true);
      RS.api('orders.php', { body: { action: 'cancel', id: Number(btn.getAttribute('data-cancel')) } })
        .then(function () { RS.toast('Buyurtma bekor qilindi', 'ok'); return load(true); })
        .catch(function (err) { RS.toast(RS.errorText(err), 'error'); })
        .finally(function () { RS.busy(btn, false); });
    });
  }

  function repeatOrder(btn) {
    RS.busy(btn, true);
    RS.api('orders.php', { body: { action: 'repeat', id: Number(btn.getAttribute('data-repeat')) } })
      .then(function (d) {
        d.items.forEach(function (it) { RS.cart.add({ product_id: it.product_id, qty: it.qty, options: it.options, note: it.note }); });
        if (d.skipped && d.skipped.length) RS.toast('Mavjud emas: ' + d.skipped.join(', '), 'error');
        else RS.toast('Savatga qo‘shildi', 'ok');
        window.setTimeout(function () { window.location.href = '/cart.php'; }, 600);
      })
      .catch(function (err) { RS.toast(RS.errorText(err), 'error'); RS.busy(btn, false); });
  }

  document.addEventListener('visibilitychange', function () { if (!document.hidden) load(true); });
  load(true).then(startPolling);
})();
