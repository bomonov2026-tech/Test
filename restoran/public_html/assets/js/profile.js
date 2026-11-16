/* Profil: ism, telefon, saqlangan manzillar, restoran ma’lumoti */
(function () {
  'use strict';
  var RS = window.RS;
  var $ = function (s) { return RS.$(s); };

  function renderAddresses(list) {
    var box = $('#addressList');
    if (!list.length) {
      box.innerHTML = '<p class="muted" style="font-size:13px">Hali manzil saqlanmagan</p>';
      return;
    }
    box.innerHTML = list.map(function (a) {
      return '<div class="addr-item" style="cursor:default">' +
        '<span class="ai-main"><strong>' + RS.esc(a.title) + '</strong><small>' + RS.esc(a.address) + (a.landmark ? ' · ' + RS.esc(a.landmark) : '') + '</small></span>' +
        '<button class="ai-del" type="button" data-del="' + a.id + '" aria-label="O‘chirish">' + RS.icon('trash-2') + '</button>' +
        '</div>';
    }).join('');
    RS.icons();
  }

  function render(d) {
    var u = d.user || {};
    var name = [u.first_name, u.last_name].filter(Boolean).join(' ') || 'Mehmon';
    $('#profileName').textContent = name;
    $('#profileUsername').textContent = u.username ? '@' + u.username : 'Telegram ID: ' + u.telegram_id;
    $('#phoneInput').value = u.phone || '';
    $('#ordersStat').textContent = (d.stats.orders_count || 0) + ' ta buyurtma · ' + RS.money(d.stats.spent) + ' xarid';

    var r = d.restaurant || {};
    $('#restAbout').textContent = r.about || '';
    $('#restHours').textContent = (r.working_open || '') + ' – ' + (r.working_close || '') + ' (har kuni)';
    $('#restAddress').textContent = r.address || '';
    var phone = $('#restPhone');
    phone.textContent = r.phone || '—';
    phone.setAttribute('href', r.phone ? 'tel:' + r.phone.replace(/[^+\d]/g, '') : '#');
    $('#restTg').textContent = r.contact_telegram || '';
    renderAddresses(d.addresses || []);
  }

  function load() {
    RS.api('profile.php').then(render).catch(function (err) {
      RS.toast(RS.errorText(err), 'error');
      $('#profileName').textContent = 'Telegram orqali oching';
    });
  }

  $('#phoneForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = this.querySelector('button');
    RS.busy(btn, true);
    RS.api('profile.php', { body: { action: 'phone', phone: $('#phoneInput').value.trim() } })
      .then(function (d) { $('#phoneInput').value = d.phone; RS.toast('Telefon saqlandi', 'ok'); })
      .catch(function (err) { RS.toast(RS.errorText(err), 'error'); })
      .finally(function () { RS.busy(btn, false); });
  });

  $('#addressForm').addEventListener('submit', function (e) {
    e.preventDefault();
    var f = this;
    var field = function (n) { return f.querySelector('[name="' + n + '"]').value.trim(); };
    var body = {
      action: 'address_save',
      title: field('title'),
      address: field('address'),
      landmark: field('landmark'),
    };
    if (!body.address) { RS.toast('Manzilni kiriting', 'error'); return; }
    var btn = f.querySelector('button');
    RS.busy(btn, true);
    RS.api('profile.php', { body: body }).then(function (d) {
      renderAddresses(d.addresses || []);
      f.reset();
      RS.toast('Manzil saqlandi', 'ok');
    }).catch(function (err) {
      RS.toast(RS.errorText(err), 'error');
    }).finally(function () { RS.busy(btn, false); });
  });

  $('#addressList').addEventListener('click', function (e) {
    var del = e.target.closest('[data-del]');
    if (!del) return;
    RS.confirm('Manzilni o‘chirasizmi?').then(function (ok) {
      if (!ok) return;
      RS.api('profile.php', { body: { action: 'address_delete', id: Number(del.getAttribute('data-del')) } })
        .then(function (d) { renderAddresses(d.addresses || []); RS.toast('Manzil o‘chirildi', 'ok'); })
        .catch(function (err) { RS.toast(RS.errorText(err), 'error'); });
    });
  });

  load();
})();
