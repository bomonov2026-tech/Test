/* Same-origin host/iframe bridge. The game never receives BOT_TOKEN or the HMAC secret. */
(function () {
  'use strict';
  var active = false, generation = 0, busy = false;
  window.opsMount = function () {
    document.body.classList.add('ops-active');
    if (active) return;
    active = true;
    generation++;
    var frame = document.createElement('iframe');
    frame.id = 'ops-frame';
    frame.title = 'STICK OPS — 5v5';
    frame.src = 'stick-ops/client/index.html?v=1';
    frame.allow = 'fullscreen';
    document.getElementById('p-ops').appendChild(frame);
    try { tg.expand(); } catch (_) {}
  };
  window.opsUnmount = function () {
    if (!active) return;
    active = false;
    generation++;
    busy = false;
    document.body.classList.remove('ops-active');
    var frame = document.getElementById('ops-frame');
    if (frame) frame.remove();
  };
  window.addEventListener('message', function (event) {
    var frame = document.getElementById('ops-frame');
    if (!active || !frame || event.origin !== location.origin || event.source !== frame.contentWindow || !event.data) return;
    if (event.data.type === 'ops:exit') { window.go('home'); return; }
    if (event.data.type !== 'ops:ready' || busy) return;
    busy = true;
    var version = generation;
    function reply(data) {
      if (version !== generation || !active || !frame.isConnected) return;
      busy = false;
      var message = Object.assign({}, data, {
        type: 'ops:session',
        initialRoom: window.OPS_PENDING_ROOM || '',
        botUsername: data.botUsername || (typeof BOTU === 'string' ? BOTU : '')
      });
      frame.contentWindow.postMessage(message, location.origin);
    }
    window.api('ops_session').then(function (data) {
      reply(data || { ok: false, error: 'Server bilan aloqa bo‘lmadi. Mashq rejimini tanlang.' });
    }).catch(function () {
      reply({ ok: false, error: 'Server bilan aloqa bo‘lmadi. Mashq rejimini tanlang.' });
    });
  });
  window.addEventListener('pagehide', function () { window.opsUnmount(); });
  window.addEventListener('pageshow', function (event) {
    var page = document.getElementById('p-ops');
    if (event.persisted && page && page.classList.contains('on')) window.opsMount();
  });
})();
