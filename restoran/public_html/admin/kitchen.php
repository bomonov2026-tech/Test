<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin', 'kitchen']);
admin_head('Oshxona', 'kitchen', $user);
?>
<div class="toolbar">
  <span class="live" id="liveState">Ulanmoqda…</span>
  <span class="muted" id="lastUpdate"></span>
</div>
<div class="kitchen-grid" id="board" aria-live="polite">
  <div class="k-empty">Yuklanmoqda…</div>
</div>
<?php admin_foot('kitchen');
