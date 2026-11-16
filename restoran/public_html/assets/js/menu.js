/* Menyu: kategoriya bo‘yicha filtr, qidiruv, mahsulotlar to‘ri */
(function () {
  'use strict';
  var RS = window.RS;
  var app = document.getElementById('app');
  var state = {
    cat: app.getAttribute('data-initial-cat') || '',
    q: app.getAttribute('data-initial-q') || '',
    seq: 0,
  };
  var grid = RS.$('#productGrid');
  var chips = RS.$('#catChips');
  var empty = RS.$('#emptyState');
  var input = RS.$('#searchInput');
  var timer = null;

  function renderChips(categories) {
    var all = [{ slug: '', name: 'Hammasi' }].concat(categories.map(function (c) { return { slug: c.slug, name: c.name }; }));
    chips.innerHTML = all.map(function (c) {
      return '<button type="button" class="chip" role="tab" data-slug="' + RS.esc(c.slug) + '" aria-selected="' +
        (c.slug === state.cat ? 'true' : 'false') + '">' + RS.esc(c.name) + '</button>';
    }).join('');
  }

  function loadCategories() {
    return RS.api('categories.php').then(function (d) {
      renderChips(d.categories);
    }).catch(function () {
      renderChips([]);
    });
  }

  function loadProducts() {
    var seq = ++state.seq;
    grid.setAttribute('aria-busy', 'true');
    var params = new URLSearchParams();
    if (state.cat) params.set('category', state.cat);
    if (state.q) params.set('q', state.q);
    RS.api('products.php?action=list&' + params.toString()).then(function (d) {
      if (seq !== state.seq) return;
      var list = d.products || [];
      grid.innerHTML = list.map(RS.productCard).join('');
      empty.hidden = list.length > 0;
      RS.icons();
    }).catch(function (err) {
      if (seq !== state.seq) return;
      grid.innerHTML = '<div class="alert error" style="grid-column:1/-1">' + RS.esc(RS.errorText(err)) + '</div>';
      empty.hidden = true;
    }).finally(function () {
      grid.removeAttribute('aria-busy');
    });
  }

  function syncUrl() {
    var params = new URLSearchParams();
    if (state.cat) params.set('cat', state.cat);
    if (state.q) params.set('q', state.q);
    var qs = params.toString();
    window.history.replaceState(null, '', '/menu.php' + (qs ? '?' + qs : ''));
  }

  chips.addEventListener('click', function (e) {
    var btn = e.target.closest('.chip');
    if (!btn) return;
    state.cat = btn.getAttribute('data-slug');
    RS.$$('.chip', chips).forEach(function (c) { c.setAttribute('aria-selected', c === btn ? 'true' : 'false'); });
    syncUrl();
    loadProducts();
  });

  input.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(function () {
      state.q = input.value.trim().slice(0, 100);
      syncUrl();
      loadProducts();
    }, 300);
  });
  input.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });

  loadCategories().then(loadProducts);
})();
