/* Bosh sahifa: bannerlar, kategoriyalar, mashhur / tavsiya / yangi bo‘limlari, qidiruv */
(function () {
  'use strict';
  var RS = window.RS;
  var $ = function (s) { return document.querySelector(s); };

  function renderBanners(list) {
    var box = $('#banners');
    if (!list || !list.length) { box.innerHTML = ''; box.hidden = true; return; }
    box.innerHTML = list.map(function (b) {
      var target = b.product_id ? '/product.php?id=' + b.product_id : '/menu.php';
      return '<a class="banner" href="' + RS.esc(target) + '">' +
        (b.image ? '<img src="' + RS.esc(b.image) + '" alt="" loading="lazy">' : '') +
        '<span class="banner-text"><strong>' + RS.esc(b.title) + '</strong>' +
        (b.subtitle ? '<small>' + RS.esc(b.subtitle) + '</small>' : '') + '</span></a>';
    }).join('');
  }

  function renderCategories(list) {
    var box = $('#categories');
    if (!list || !list.length) {
      box.innerHTML = '<p class="empty">Kategoriyalar hozircha yo‘q</p>';
      return;
    }
    box.innerHTML = list.map(function (c) {
      var ico = c.image
        ? '<img src="' + RS.esc(c.image) + '" alt="" loading="lazy">'
        : RS.icon(iconFor(c.slug));
      return '<a class="cat-item" href="/menu.php?cat=' + encodeURIComponent(c.slug) + '">' +
        '<span class="cat-ico">' + ico + '</span>' +
        '<span>' + RS.esc(c.name) + '</span><small>' + c.count + ' ta</small></a>';
    }).join('');
  }

  function iconFor(slug) {
    var map = {
      shashlik: 'flame', somsa: 'cookie', kabob: 'beef', 'milliy-taomlar': 'soup', osh: 'utensils',
      lagmon: 'utensils-crossed', salatlar: 'leaf', ichimliklar: 'cup-soda', shirinliklar: 'cake-slice'
    };
    return map[slug] || 'utensils';
  }

  function renderSection(id, list, sectionKey) {
    var box = $('#' + id);
    var section = box.closest('section');
    if (!list || !list.length) {
      if (section) section.hidden = true;
      return;
    }
    box.innerHTML = list.map(RS.productCard).join('');
    if (section) section.hidden = false;
  }

  function load() {
    RS.api('products.php?action=home').then(function (d) {
      renderBanners(d.banners);
      renderCategories(d.categories);
      renderSection('featured', d.featured);
      renderSection('recommended', d.recommended);
      renderSection('newItems', d.new);
      RS.icons();
    }).catch(function (err) {
      $('#categories').innerHTML = '<div class="alert error" style="grid-column:1/-1">' + RS.esc(RS.errorText(err)) + '</div>';
      $('#banners').hidden = true;
      RS.toast(RS.errorText(err), 'error');
    });
  }

  function search() {
    var q = $('#searchInput').value.trim();
    if (q) window.location.href = '/menu.php?q=' + encodeURIComponent(q.slice(0, 100));
  }

  $('#searchInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); search(); }
  });
  $('#searchInput').addEventListener('search', search);

  load();
})();
