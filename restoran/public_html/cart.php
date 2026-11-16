<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';
mini_head('Savat', 'cart');
?>
<main class="page" id="app">
  <header class="page-head"><h1>Savat</h1></header>
  <div id="cartEmpty" class="empty-card" hidden>
    <i data-lucide="shopping-bag"></i>
    <h2>Savat bo‘sh</h2>
    <p class="muted">Menyudan taom tanlang va savatga qo‘shing.</p>
    <a class="btn primary" href="/menu.php">Menyuga o‘tish</a>
  </div>
  <div id="cartBody" hidden>
    <div class="seg" role="radiogroup" aria-label="Buyurtma turi">
      <label><input type="radio" name="orderType" value="delivery" checked><span><i data-lucide="truck"></i> Yetkazib berish</span></label>
      <label><input type="radio" name="orderType" value="pickup"><span><i data-lucide="store"></i> Olib ketish</span></label>
    </div>
    <div id="cartLines" class="cart-lines"></div>
    <form class="promo" id="promoForm" autocomplete="off">
      <input type="text" id="promoInput" placeholder="Promo-kod" maxlength="40" aria-label="Promo-kod">
      <button class="btn ghost" type="submit">Qo‘llash</button>
    </form>
    <div id="promoMsg" class="hint" aria-live="polite"></div>
    <div class="summary" id="cartSummary"></div>
    <div id="cartErrors" class="alert error" hidden></div>
    <a class="btn primary block lg" id="checkoutBtn" href="/checkout.php">Buyurtma rasmiylashtirish</a>
  </div>
</main>
<?php mini_foot('cart');
