/* Mahsulot sahifasi: rasm, tavsif, qo‘shimchalar, miqdor, izoh, savatga qo‘shish */
(function () {
  'use strict';
  var RS = window.RS;
  var app = document.getElementById('app');
  var id = Number(app.getAttribute('data-product-id')) || 0;
  var view = RS.$('#productView');
  var product = null;
  var selected = {};   // groupId -> Set(valueId)
  var qty = 1;

  function optionsTotal() {
    var sum = 0;
    if (!product) return 0;
    product.groups.forEach(function (g) {
      g.values.forEach(function (v) {
        if (selected[g.id] && selected[g.id].has(v.id)) sum += v.price;
      });
    });
    return sum;
  }

  function selectedIds() {
    var ids = [];
    Object.keys(selected).forEach(function (gid) {
      selected[gid].forEach(function (vid) { ids.push(vid); });
    });
    return ids;
  }

  function missingRequired() {
    return product.groups.filter(function (g) {
      return g.required && !(selected[g.id] && selected[g.id].size);
    });
  }

  function render() {
    var p = product;
    var unit = p.price + optionsTotal();
    var total = unit * qty;
    var img = p.image
      ? '<img src="' + RS.esc(p.image) + '" alt="' + RS.esc(p.name) + '">'
      : RS.icon('utensils');

    var groupsHtml = p.groups.map(function (g) {
      var type = g.type === 'single' ? 'radio' : 'checkbox';
      var head = '<div class="opt-head"><span>' + RS.esc(g.name) + '</span><small>' +
        (g.required ? 'Majburiy' : 'Ixtiyoriy') + (g.type === 'single' ? ' · 1 ta' : ' · bir nechta') + '</small></div>';
      var opts = g.values.map(function (v) {
        var checked = selected[g.id] && selected[g.id].has(v.id);
        return '<label class="opt' + (v.is_available ? '' : ' disabled') + '">' +
          '<input type="' + type + '" name="g' + g.id + '" value="' + v.id + '" data-group="' + g.id + '"' +
          (checked ? ' checked' : '') + (v.is_available ? '' : ' disabled') + '>' +
          '<span class="opt-name">' + RS.esc(v.name) + (v.is_available ? '' : ' (tugagan)') + '</span>' +
          '<span class="opt-price">' + (v.price ? '+ ' + RS.money(v.price) : 'Bepul') + '</span></label>';
      }).join('');
      return '<div class="opt-group" data-gid="' + g.id + '">' + head + opts + '</div>';
    }).join('');

    view.innerHTML =
      '<div class="hero">' + img + '</div>' +
      '<div class="fade-in" style="display:flex;flex-direction:column;gap:10px;margin-top:14px">' +
        '<div class="row-between" style="align-items:flex-start">' +
          '<h1 class="product-title">' + RS.esc(p.name) + '</h1>' +
          '<button class="pc-fav" style="position:static" type="button" id="favBtn" aria-pressed="' + (p.is_favorite ? 'true' : 'false') + '" aria-label="Sevimlilarga qo‘shish">' + RS.icon('heart') + '</button>' +
        '</div>' +
        '<div class="product-price-row">' +
          (p.discount_percent ? '<span class="pc-old">' + RS.money(p.base_price) + '</span>' : '') +
          '<span class="big-price" id="unitPrice">' + RS.money(unit) + '</span>' +
          '<span class="muted">/ ' + RS.esc(p.unit) + '</span>' +
          (p.discount_percent ? '<span class="tag off">−' + p.discount_percent + '%</span>' : '') +
        '</div>' +
        (p.is_available ? '' : '<div class="alert error">Bu mahsulot hozir mavjud emas</div>') +
        (p.description ? '<p class="product-desc">' + RS.esc(p.description) + '</p>' : '') +
        groupsHtml +
        '<div class="card-box"><label class="field"><span>Izoh (ixtiyoriy)</span>' +
          '<textarea id="noteInput" rows="2" maxlength="255" placeholder="Masalan: achchiqsiz">' + RS.esc(state.note || '') + '</textarea></label></div>' +
        '<div class="qty-row">' +
          '<span class="box-title" style="margin:0">Miqdor</span>' +
          '<div class="stepper">' +
            '<button type="button" id="qtyMinus" aria-label="Kamaytirish"' + (qty <= 1 ? ' disabled' : '') + '>' + RS.icon('minus') + '</button>' +
            '<output id="qtyOut" aria-live="polite">' + qty + '</output>' +
            '<button type="button" id="qtyPlus" aria-label="Oshirish"' + (qty >= 99 ? ' disabled' : '') + '>' + RS.icon('plus') + '</button>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<div class="product-actions" style="margin-top:18px">' +
        '<button class="btn primary block lg" type="button" id="addBtn"' + (p.is_available ? '' : ' disabled') + '>' +
          (p.is_available ? 'Savatga qo‘shish · ' + RS.money(total) : 'Mavjud emas') + '</button>' +
      '</div>';
    RS.icons();
  }

  var state = { note: '' };

  function refreshTotals() {
    var unit = product.price + optionsTotal();
    var up = RS.$('#unitPrice');
    if (up) up.textContent = RS.money(unit);
    var btn = RS.$('#addBtn');
    if (btn && product.is_available) btn.textContent = 'Savatga qo‘shish · ' + RS.money(unit * qty);
    var out = RS.$('#qtyOut');
    if (out) out.textContent = String(qty);
    var minus = RS.$('#qtyMinus');
    if (minus) minus.disabled = qty <= 1;
    var plus = RS.$('#qtyPlus');
    if (plus) plus.disabled = qty >= 99;
  }

  view.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches || !input.matches('input[data-group]')) return;
    var gid = input.getAttribute('data-group');
    var vid = Number(input.value);
    var group = product.groups.filter(function (g) { return String(g.id) === gid; })[0];
    if (!selected[gid]) selected[gid] = new Set();
    if (group && group.type === 'single') {
      selected[gid].clear();
      if (input.checked) selected[gid].add(vid);
    } else {
      if (input.checked) selected[gid].add(vid); else selected[gid].delete(vid);
    }
    refreshTotals();
  });

  view.addEventListener('input', function (e) {
    if (e.target.id === 'noteInput') state.note = e.target.value.slice(0, 255);
  });

  view.addEventListener('click', function (e) {
    var t = e.target.closest('button');
    if (!t) return;
    if (t.id === 'qtyMinus') { qty = Math.max(1, qty - 1); refreshTotals(); }
    else if (t.id === 'qtyPlus') { qty = Math.min(99, qty + 1); refreshTotals(); }
    else if (t.id === 'favBtn') { RS.toggleFavorite(t); }
    else if (t.id === 'addBtn') { addToCart(t); }
  });

  function addToCart(btn) {
    var missing = missingRequired();
    if (missing.length) {
      RS.toast('“' + missing[0].name + '” bo‘yicha tanlov qiling', 'error');
      var el = RS.$('.opt-group[data-gid="' + missing[0].id + '"]');
      if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    var note = (RS.$('#noteInput') ? RS.$('#noteInput').value : '').trim().slice(0, 255);
    RS.cart.add({
      product_id: product.id,
      qty: qty,
      options: selectedIds(),
      note: note,
      name: product.name,
      image: product.image,
      price: product.price,
    });
    RS.haptic('success');
    RS.toast('Savatga qo‘shildi', 'ok');
    var cartCount = RS.cart.count();
    btn.textContent = 'Savatda: ' + cartCount + ' ta · Savatga o‘tish';
    btn.onclick = function () { window.location.href = '/cart.php'; };
  }

  function load() {
    if (!id) {
      view.innerHTML = '<p class="empty">Mahsulot topilmadi.</p>';
      return;
    }
    RS.api('products.php?action=product&id=' + id).then(function (d) {
      product = d.product;
      document.title = product.name + ' · ' + document.title.split('·').pop().trim();
      render();
    }).catch(function (err) {
      view.innerHTML = '<div class="alert error">' + RS.esc(RS.errorText(err)) + '</div>';
    });
  }

  load();
})();
