/* Savat: ro‘yxat, miqdor, o‘chirish, promo-kod, buyurtma turi, server bo‘yicha hisob-kitob */
(function () {
  'use strict';
  var RS = window.RS;
  var $ = function (s) { return RS.$(s); };
  var timer = null;

  function showEmpty(isEmpty) {
    $('#cartEmpty').hidden = !isEmpty;
    $('#cartBody').hidden = isEmpty;
  }

  function lineHtml(local, server, index) {
    var name = server ? server.name : (local.name || 'Mahsulot #' + local.product_id);
    var image = server ? server.image : (local.image || null);
    var thumb = image ? '<img src="' + RS.esc(image) + '" alt="" loading="lazy">' : RS.icon('utensils');
    var opts = server && server.options && server.options.length
      ? '<div class="cl-opts">' + server.options.map(function (o) { return RS.esc(o.name); }).join(', ') + '</div>'
      : '';
    var note = local.note ? '<div class="cl-note">“' + RS.esc(local.note) + '”</div>' : '';
    var sum = server ? RS.money(server.line_total) : '—';
    var unitLine = server ? '<small class="muted">' + RS.money(server.unit_price + server.options_total) + ' × ' + local.qty + '</small>' : '';
    return '<li class="cart-line' + (server ? '' : ' bad') + '" data-index="' + index + '">' +
      '<div class="thumb">' + thumb + '</div>' +
      '<div class="cl-main">' +
        '<div class="cl-top"><div class="cl-name">' + RS.esc(name) + '</div>' +
        '<button class="remove" type="button" data-remove="' + index + '" aria-label="O‘chirish">' + RS.icon('trash-2') + '</button></div>' +
        opts + note +
        '<div class="cl-bottom">' +
          '<div class="stepper" role="group" aria-label="Miqdor">' +
            '<button type="button" data-qty="' + index + ':-1" aria-label="Kamaytirish"' + (local.qty <= 1 ? ' disabled' : '') + '>' + RS.icon('minus') + '</button>' +
            '<output>' + local.qty + '</output>' +
            '<button type="button" data-qty="' + index + ':1" aria-label="Oshirish"' + (local.qty >= 99 ? ' disabled' : '') + '>' + RS.icon('plus') + '</button>' +
          '</div>' +
          '<div style="text-align:right"><div class="cl-sum">' + sum + '</div>' + unitLine + '</div>' +
        '</div>' +
      '</div></li>';
  }

  function summaryHtml(p) {
    var rows = [];
    rows.push('<div class="row"><span>Mahsulotlar</span><strong>' + RS.money(p.subtotal) + '</strong></div>');
    if (p.discount > 0) rows.push('<div class="row discount"><span>Chegirma</span><strong>−' + RS.money(p.discount) + '</strong></div>');
    if (RS.orderType.get() === 'delivery') {
      rows.push('<div class="row"><span>Yetkazib berish</span><strong>' + (p.delivery_fee ? RS.money(p.delivery_fee) : 'Bepul') + '</strong></div>');
    }
    rows.push('<div class="row total"><span>Jami</span><strong>' + RS.money(p.total) + '</strong></div>');
    return rows.join('');
  }

  function applyPromoMessage(p) {
    var msg = $('#promoMsg');
    if (!p.promo) { msg.textContent = ''; msg.className = 'hint'; return; }
    msg.textContent = p.promo.message;
    msg.className = 'hint ' + (p.promo.valid ? 'ok' : 'err');
  }

  function renderErrors(p) {
    var box = $('#cartErrors');
    var msgs = (p.errors || []).map(function (e) { return e.message; });
    if (!msgs.length && p.promo && !p.promo.valid) msgs.push(p.promo.message);
    if (p.min_order && p.subtotal < p.min_order) {
      msgs.push('Minimal buyurtma summasi: ' + RS.money(p.min_order));
    }
    box.hidden = msgs.length === 0;
    box.innerHTML = msgs.length ? '<ul>' + msgs.map(function (m) { return '<li>' + RS.esc(m) + '</li>'; }).join('') + '</ul>' : '';
  }

  function render(preview) {
    var items = RS.cart.items();
    showEmpty(items.length === 0);
    if (!items.length) return;

    var serverLines = {};
    if (preview && preview.lines) preview.lines.forEach(function (l) { serverLines[l.index] = l; });

    $('#cartLines').innerHTML = items.map(function (it, i) {
      return lineHtml(it, serverLines[i] || null, i);
    }).join('');

    if (preview) {
      $('#cartSummary').innerHTML = summaryHtml(preview);
      applyPromoMessage(preview);
      renderErrors(preview);
      var canCheckout = !!preview.valid && preview.store && preview.store.is_open !== false;
      var btn = $('#checkoutBtn');
      btn.classList.toggle('disabled', !canCheckout);
      btn.setAttribute('aria-disabled', canCheckout ? 'false' : 'true');
      btn.style.opacity = canCheckout ? '' : '.5';
      btn.style.pointerEvents = canCheckout ? '' : 'none';
    }
    RS.icons();
  }

  function preview(withLoading) {
    var items = RS.cart.items();
    if (!items.length) { showEmpty(true); return Promise.resolve(null); }
    var body = {
      items: items.map(function (i) { return { product_id: i.product_id, qty: i.qty, options: i.options || [], note: i.note || '' }; }),
      order_type: RS.orderType.get(),
      promo_code: RS.promo.get(),
    };
    return RS.api('cart.php', { body: body }).then(function (p) {
      render(p);
      return p;
    }).catch(function (err) {
      render(null);
      RS.toast(RS.errorText(err), 'error');
      return null;
    });
  }

  function schedule() {
    clearTimeout(timer);
    timer = setTimeout(function () { preview(); }, 150);
  }

  /* Miqdor va o‘chirish */
  $('#cartLines').addEventListener('click', function (e) {
    var rm = e.target.closest('[data-remove]');
    if (rm) {
      RS.cart.remove(Number(rm.getAttribute('data-remove')));
      RS.toast('Mahsulot olib tashlandi', 'ok');
      preview();
      return;
    }
    var q = e.target.closest('[data-qty]');
    if (q) {
      var parts = q.getAttribute('data-qty').split(':');
      var idx = Number(parts[0]);
      var delta = Number(parts[1]);
      var item = RS.cart.items()[idx];
      if (item) RS.cart.setQty(idx, item.qty + delta);
      schedule();
    }
  });

  /* Buyurtma turi */
  RS.$$('input[name="orderType"]').forEach(function (r) {
    r.checked = r.value === RS.orderType.get();
    r.addEventListener('change', function () {
      if (r.checked) { RS.orderType.set(r.value); preview(); }
    });
  });

  /* Promo-kod */
  $('#promoInput').value = RS.promo.get();
  $('#promoForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var code = $('#promoInput').value.trim().toUpperCase().slice(0, 40);
    RS.promo.set(code);
    preview().then(function (p) {
      if (!p) return;
      if (code && p.promo && p.promo.valid) RS.toast('Promo-kod qo‘llandi', 'ok');
      else if (code && p.promo && !p.promo.valid) RS.toast(p.promo.message, 'error');
      if (!code) RS.toast('Promo-kod olib tashlandi', 'ok');
    });
  });

  window.addEventListener('cart:changed', function () { /* boshqa sahifadan */ });
  window.addEventListener('pageshow', function () { preview(); });

  preview();
})();
