<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
mini_head('Buyurtmalarim', 'profile');
?>
<main class="page" id="app">
  <a class="back-link" href="/profile.php"><i data-lucide="chevron-left"></i> Profil</a>
  <header class="page-head"><h1>Buyurtmalarim</h1></header>
  <div class="seg tabs" role="tablist">
    <button type="button" role="tab" aria-selected="true" data-scope="active">Faol</button>
    <button type="button" role="tab" aria-selected="false" data-scope="history">Tarix</button>
  </div>
  <div class="order-list" id="orderList" aria-live="polite">
    <div class="skeleton order-sk"></div><div class="skeleton order-sk"></div>
  </div>
  <div id="ordersPager"></div>
  <p class="empty" id="ordersEmpty" hidden></p>
  <p class="live-hint" id="liveHint"><span class="pulse"></span> Holat avtomatik yangilanadi</p>
</main>
<?php mini_foot('orders');
