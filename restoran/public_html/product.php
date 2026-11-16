<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$id = v_int($_GET['id'] ?? null, 1, PHP_INT_MAX) ?? 0;
mini_head('Mahsulot', 'menu');
?>
<main class="page page-product" id="app" data-product-id="<?= (int) $id ?>">
  <a class="back-link" href="/menu.php"><i data-lucide="chevron-left"></i> Menyuga qaytish</a>
  <div id="productView">
    <div class="skeleton hero-sk"></div>
    <div class="skeleton line-sk"></div>
    <div class="skeleton line-sk short"></div>
  </div>
</main>
<?php mini_foot('product');
