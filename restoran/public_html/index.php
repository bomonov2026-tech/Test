<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$name = e(setting('restaurant_name', 'Dasturxon'));
$address = e(setting('restaurant_address', ''));
mini_head('Bosh sahifa', 'home');
?>
<main class="page" id="app">
  <header class="shop-head">
    <div class="shop">
      <div class="shop-logo" aria-hidden="true">
        <?php $logo = setting('logo', ''); ?>
        <?php if ($logo !== ''): ?><img src="/<?= e($logo) ?>" alt=""><?php else: ?><i data-lucide="utensils"></i><?php endif; ?>
      </div>
      <div class="shop-meta">
        <h1 class="shop-name"><?= $name ?></h1>
        <p class="shop-addr"><i data-lucide="map-pin"></i><span><?= $address ?></span></p>
        <span class="store-status" data-store-status><span class="dot"></span><span data-store-text>Yuklanmoqda…</span></span>
      </div>
    </div>
    <a class="cart-fab" href="/cart.php" aria-label="Savat">
      <i data-lucide="shopping-bag"></i><b class="badge" data-cart-badge hidden>0</b>
    </a>
  </header>

  <label class="search">
    <i data-lucide="search"></i>
    <input type="search" id="searchInput" placeholder="Taom yoki ichimlik qidirish" maxlength="100" enterkeyhint="search" autocomplete="off">
  </label>

  <section class="banners" id="banners" aria-label="Aksiyalar"><div class="skeleton banner-sk"></div></section>

  <section>
    <div class="section-head"><h2>Kategoriyalar</h2><a href="/menu.php">Hammasi</a></div>
    <div class="cat-grid" id="categories"><div class="skeleton cat-sk"></div><div class="skeleton cat-sk"></div><div class="skeleton cat-sk"></div><div class="skeleton cat-sk"></div></div>
  </section>

  <section data-section="featured">
    <div class="section-head"><h2>Mashhur taomlar</h2></div>
    <div class="hscroll" id="featured"></div>
  </section>
  <section data-section="recommended">
    <div class="section-head"><h2>Tavsiya etilgan</h2></div>
    <div class="hscroll" id="recommended"></div>
  </section>
  <section data-section="new">
    <div class="section-head"><h2>Yangi mahsulotlar</h2></div>
    <div class="hscroll" id="newItems"></div>
  </section>
</main>
<?php mini_foot('home');
