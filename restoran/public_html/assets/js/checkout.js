/* Buyurtma rasmiylashtirish: ma’lumotlar, manzil, server hisobi, tasdiqlash oynasi, yuborish */
(function () {
  'use strict';
  var RS = window.RS;
  var $ = function (s) { return RS.$(s); };
  var form = $('#orderForm');
  var preview = null;
  var addresses = [];
  var selectedAddress = 'new';
  var submitting = false;

  if (!RS.cart.items().length) {
    window.location.replace('/cart.php');
    return;
  }

  function normalizePhone(v) {
    var d = String(v || '').replace(/\D+/g, '');
    if (d.length === 9) d = '998' + d;
    return d.length === 12 && d.indexOf('998') === 0 ? '+' + d : '';
  }

  function orderType() {
    var r = RS.$$('input[name="orderType"]').filter(function (x) { return x.checked; })[0];
    return r ? r.value : 'delivery';
  }

  function applyOrderTypeUi() {
    var delivery = orderType() === 'delivery';
    $('#deliveryBox').hidden = !delivery;
    RS.orderType.set(orderType());
  }

  function renderAddresses() {
    var box = $('#addressList');
    var items = addresses.map(function (a) {
      return '<button type="button" class="addr-item" role="radio" data-addr="' + a.id + '" aria-checked="' + (selectedAddress === a.id ? 'true' : 'false') + '">' +
        '<span class="ai-main"><strong>' + RS.esc(a.title) + '</strong><small>' + RS.esc(a.address) + (a.landmark ? ' · ' + RS.esc(a.landmark) : '') + '</small></span>' +
        '</button>';
    });
    items.push('<button type="button" class="addr-item" role="radio" data-addr="new" aria-checked="' + (selectedAddress === 'new' ? 'true' : 'false') + '">' +
      '<span class="ai-main"><strong>+ Yangi manzil</strong><small>Manzilni qo‘lda kiriting</small></span></button>');
    box.innerHTML = items.join('');
    box.hidden = addresses.length === 0;
    toggleManualAddress();
  }

  function toggleManualAddress() {
    var manual = selectedAddress === 'new';
    RS.$$('#deliveryBox .field, #deliveryBox .check').forEach(function (el) { el.hidden = !manual; });
  }

  $('#addressList').addEventListener('click', function (e) {
    var b = e.target.closest('[data-addr]');
    if (!b) return;
    var v = b.getAttribute('data-addr');
    selectedAddress = v === 'new' ? 'new' : Number(v);
    renderAddresses();
  });

  function renderReview(p) {
    var lines = p.lines.map(function (l) {
      var opts = l.options && l.options.length ? '<small class="muted">' + RS.esc(l.options.map(function (o) { return o.name; }).join(', ')) + '</small>' : '';
      return '<li><span><strong>' + l.qty + ' × ' + RS.esc(l.name) + '</strong>' + opts + '</span><strong>' + RS.money(l.line_total) + '</strong></li>';
    }).join('');
    $('#reviewLines').innerHTML = lines;
    $('#reviewSummary').innerHTML = summary(p);
    $('#confirmLines').innerHTML = lines;
    $('#confirmSummary').innerHTML = summary(p);
  }

  function summary(p) {
    var rows = ['<div class="row"><span>Mahsulotlar</span><strong>' + RS.money(p.subtotal) + '</strong></div>'];
    if (p.discount > 0) rows.push('<div class="row discount"><span>Chegirma</span><strong>−' + RS.money(p.discount) + '</strong></div>');
    if (orderType() === 'delivery') rows.push('<div class="row"><span>Yetkazib berish</span><strong>' + (p.delivery_fee ? RS.money(p.delivery_fee) : 'Bepul') + '</strong></div>');
    rows.push('<div class="row total"><span>Jami</span><strong>' + RS.money(p.total) + '</strong></div>');
    return rows.join('');
  }

  function setBlocked(blocked, message) {
    var notice = $('#storeNotice');
    notice.hidden = !message;
    notice.textContent = message || '';
    $('#submitBtn').disabled = !!blocked;
  }

  function loadPreview() {
    var items = RS.cart.items();
    if (!items.length) { window.location.replace('/cart.php'); return Promise.resolve(); }
    return RS.api('cart.php', {
      body: {
        items: items.map(function (i) { return { product_id: i.product_id, qty: i.qty, options: i.options || [], note: i.note || '' }; }),
        order_type: orderType(),
        promo_code: RS.promo.get(),
      },
    }).then(function (p) {
      preview = p;
      renderReview(p);
      if (!p.valid) {
        var msg = (p.errors && p.errors[0] && p.errors[0].message) || (p.promo && p.promo.message) || 'Savatda xatolik bor';
        setBlocked(true, msg + ' — savatni tekshiring.');
      } else if (p.store && !p.store.is_open) {
        setBlocked(true, p.store.message);
      } else {
        setBlocked(false, '');
      }
    }).catch(function (err) {
      RS.toast(RS.errorText(err), 'error');
    });
  }

  function loadProfile() {
    return RS.api('profile.php').then(function (d) {
      var u = d.user || {};
      var fullName = [u.first_name, u.last_name].filter(Boolean).join(' ');
      var nameEl = field('customer_name');
      var phoneEl = field('customer_phone');
      if (!nameEl.value && fullName) nameEl.value = fullName;
      if (!phoneEl.value && u.phone) phoneEl.value = u.phone;
      addresses = d.addresses || [];
      if (addresses.length) selectedAddress = addresses[0].id;
      renderAddresses();
    }).catch(function () { renderAddresses(); });
  }

  function applyStore() {
    return RS.loadStore().then(function (d) {
      if (!d) return;
      var r = d.store;
      RS.$$('input[name="orderType"]').forEach(function (input) {
        var label = input.closest('label');
        if (input.value === 'delivery' && !r.delivery_enabled) { label.hidden = true; input.checked = false; }
        if (input.value === 'pickup' && !r.pickup_enabled) { label.hidden = true; input.checked = false; }
      });
      var visible = RS.$$('input[name="orderType"]').filter(function (i) { return !i.closest('label').hidden; });
      if (visible.length && !visible.some(function (i) { return i.checked; })) visible[0].checked = true;
      applyOrderTypeUi();
      if (!r.is_open) setBlocked(true, r.message);
    });
  }

  /* ---------- Yuborish ---------- */
  function field(n) {
    var el = form.querySelector('[name="' + n + '"]');
    return el ? el : { value: '', checked: false };
  }

  function collect() {
    var name = field('customer_name').value.trim();
    var phoneRaw = field('customer_phone').value.trim();
    var type = orderType();
    var body = {
      order_type: type,
      customer_name: name,
      customer_phone: phoneRaw,
      comment: field('comment').value.trim(),
      promo_code: RS.promo.get(),
      items: RS.cart.items().map(function (i) { return { product_id: i.product_id, qty: i.qty, options: i.options || [], note: i.note || '' }; }),
    };
    if (type === 'delivery') {
      if (selectedAddress !== 'new') {
        body.address_id = selectedAddress;
      } else {
        body.address = field('address').value.trim();
        body.landmark = field('landmark').value.trim();
        body.save_address = !!field('save_address').checked;
      }
    }
    return body;
  }

  function validate() {
    var body = collect();
    if (body.customer_name.length < 2) return 'Ismingizni kiriting';
    if (!normalizePhone(body.customer_phone)) return 'Telefon raqamini to‘g‘ri kiriting (+998 XX XXX XX XX)';
    if (body.order_type === 'delivery' && !body.address_id && !body.address) return 'Yetkazib berish manzilini kiriting';
    if (!preview || !preview.valid) return 'Savat yangilanishi kerak';
    return '';
  }

  $('#submitBtn').addEventListener('click', function () {
    var err = validate();
    if (err) { RS.toast(err, 'error'); return; }
    var sheet = $('#confirmSheet');
    sheet.hidden = false;
    RS.haptic('light');
  });

  RS.$$('[data-close-sheet]').forEach(function (b) {
    b.addEventListener('click', function () { $('#confirmSheet').hidden = true; });
  });
  $('#confirmSheet').addEventListener('click', function (e) {
    if (e.target === this) this.hidden = true;
  });

  $('#confirmBtn').addEventListener('click', function () {
    if (submitting) return;
    submitting = true;
    var btn = this;
    RS.busy(btn, true);
    RS.api('orders.php', { body: Object.assign({ action: 'create' }, collect()) }).then(function (d) {
      RS.cart.clear();
      $('#confirmSheet').hidden = true;
      form.hidden = true;
      $('#checkoutBody').hidden = true;
      $('#successBox').hidden = false;
      $('#orderNoText').textContent = '#' + d.order_no;
      RS.haptic('success');
      RS.updateBadges();
      window.scrollTo(0, 0);
    }).catch(function (err) {
      RS.toast(RS.errorText(err), 'error');
      $('#confirmSheet').hidden = true;
      loadPreview();
    }).finally(function () {
      submitting = false;
      RS.busy(btn, false);
    });
  });

  RS.$$('input[name="orderType"]').forEach(function (r) {
    r.addEventListener('change', function () {
      if (!r.checked) return;
      applyOrderTypeUi();
      loadPreview();
    });
  });

  $('#checkoutBody').hidden = false;
  applyOrderTypeUi();
  Promise.all([applyStore(), loadProfile(), loadPreview()]).then(function () {
    RS.icons();
  });
})();
