<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$q = e(mb_substr((string) ($_GET['q'] ?? ''), 0, 100));
$cat = e(mb_substr((string) ($_GET['cat'] ?? ''), 0, 120));
mini_head('Menyu', 'menu');
?>
<main class="page" id="app" data-initial-cat="<?= $cat ?>" data-initial-q="<?= $q ?>">
  <header class="page-head">
    <h1>Menyu</h1>
  </header>
  <label class="search">
    <i data-lucide="search"></i>
    <input type="search" id="searchInput" placeholder="Qidirish" maxlength="100" value="<?= $q ?>" enterkeyhint="search" autocomplete="off">
  </label>
  <div class="chips" id="catChips" role="tablist" aria-label="Kategoriyalar">
    <div class="skeleton chip-sk"></div><div class="skeleton chip-sk"></div><div class="skeleton chip-sk"></div>
  </div>
  <div class="product-grid" id="productGrid" aria-live="polite">
    <div class="skeleton card-sk"></div><div class="skeleton card-sk"></div><div class="skeleton card-sk"></div><div class="skeleton card-sk"></div>
  </div>
  <p class="empty" id="emptyState" hidden>Hech narsa topilmadi. Boshqa so‘z bilan qidirib ko‘ring.</p>
</main>
<?php mini_foot('menu');
