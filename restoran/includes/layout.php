<?php
declare(strict_types=1);

/**
 * Umumiy HTML shell: mijoz mini app va admin panel uchun sarlavha/quyi qism.
 */

function mini_head(string $title, string $active = ''): void
{
    send_security_headers('mini');
    $brand = e(setting('restaurant_name', 'Dasturxon'));
    ?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1, user-scalable=no">
<meta name="color-scheme" content="light">
<meta name="theme-color" content="#ffffff">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · <?= $brand ?></title>
<link rel="icon" href="/assets/images/icon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=3">
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script src="/assets/vendor/lucide/lucide.min.js?v=1" defer></script>
<script src="/assets/js/common.js?v=3" defer></script>
</head>
<body class="mini" data-page="<?= e($active) ?>">
<div class="toasts" id="toasts" aria-live="polite"></div>
    <?php
}

function mini_foot(string $pageScript = ''): void
{
    ?>
<nav class="tabbar" aria-label="Asosiy menyu">
  <a href="/" data-tab="home"><i data-lucide="house"></i><span>Bosh sahifa</span></a>
  <a href="/menu.php" data-tab="menu"><i data-lucide="utensils-crossed"></i><span>Menyu</span></a>
  <a href="/cart.php" data-tab="cart"><i data-lucide="shopping-bag"></i><span>Savat</span><b class="badge" data-cart-badge hidden>0</b></a>
  <a href="/profile.php" data-tab="profile"><i data-lucide="user"></i><span>Profil</span></a>
</nav>
<?php if ($pageScript !== ''): ?>
<script src="/assets/js/<?= e($pageScript) ?>.js?v=3" defer></script>
<?php endif; ?>
</body>
</html>
    <?php
}

/** Admin panel sarlavhasi: yon menyu (rolga qarab) + yuqori panel. */
function admin_head(string $title, string $active, array $user): void
{
    send_security_headers('admin');
    $items = [
        ['dashboard', 'Bosh sahifa', 'layout-dashboard', '/admin/dashboard.php', ['admin']],
        ['orders', 'Buyurtmalar', 'receipt', '/admin/orders.php', ['admin']],
        ['kitchen', 'Oshxona', 'chef-hat', '/admin/kitchen.php', ['admin', 'kitchen']],
        ['products', 'Mahsulotlar', 'package', '/admin/products.php', ['admin']],
        ['categories', 'Kategoriyalar', 'tags', '/admin/categories.php', ['admin']],
        ['banners', 'Bannerlar', 'image', '/admin/banners.php', ['admin']],
        ['customers', 'Mijozlar', 'users', '/admin/customers.php', ['admin']],
        ['reports', 'Hisobotlar', 'file-text', '/admin/reports.php', ['admin']],
        ['staff', 'Xodimlar', 'shield', '/admin/staff.php', ['admin']],
        ['settings', 'Sozlamalar', 'settings', '/admin/settings.php', ['admin']],
    ];
    $brand = e(setting('restaurant_name', 'Dasturxon'));
    $lastOrderId = (int) db_val('SELECT COALESCE(MAX(id), 0) FROM orders');
    $newCount = (int) db_val("SELECT COUNT(*) FROM orders WHERE status = 'new'");
    ?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($title) ?> · Admin · <?= $brand ?></title>
<link rel="icon" href="/assets/images/icon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/admin.css?v=3">
<script src="/assets/vendor/lucide/lucide.min.js?v=1" defer></script>
<script src="/assets/js/admin.js?v=3" defer></script>
</head>
<body class="admin" data-role="<?= e($user['role']) ?>" data-last-order="<?= $lastOrderId ?>" data-new-initial="<?= $newCount ?>">
<div class="admin-shell">
  <aside class="sidebar" id="sidebar">
    <div class="brand"><span class="brand-mark"><i data-lucide="utensils"></i></span><span><?= $brand ?><small>Boshqaruv paneli</small></span></div>
    <nav class="side-nav">
      <?php foreach ($items as [$key, $label, $icon, $href, $roles]): ?>
        <?php if (!in_array($user['role'], $roles, true)) { continue; } ?>
        <a href="<?= e($href) ?>" class="<?= $active === $key ? 'active' : '' ?>">
          <i data-lucide="<?= e($icon) ?>"></i><span><?= e($label) ?></span>
          <?php if ($key === 'orders'): ?><b class="nav-badge" data-new-count hidden>0</b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <div class="who"><strong><?= e($user['full_name'] ?: $user['username']) ?></strong><small><?= $user['role'] === 'admin' ? 'Administrator' : 'Oshpaz' ?></small></div>
      <form method="post" action="/admin/logout.php">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn ghost block" type="submit"><i data-lucide="log-out"></i> Chiqish</button>
      </form>
    </div>
  </aside>
  <div class="admin-main">
    <header class="topbar">
      <button class="icon-btn only-mobile" type="button" data-toggle-sidebar aria-label="Menyu"><i data-lucide="menu"></i></button>
      <h1><?= e($title) ?></h1>
      <div class="topbar-actions">
        <?php if ($user['role'] === 'admin'): ?>
        <button class="btn ghost sm" type="button" id="soundBtn" data-sound aria-pressed="false"><i data-lucide="volume-x"></i><span>Ovoz o‘chiq</span></button>
        <?php endif; ?>
      </div>
    </header>
    <main class="content">
      <?php foreach (flash_pull() as $flash): ?>
        <div class="alert <?= $flash['type'] === 'ok' ? 'ok' : 'error' ?>"><?= e($flash['msg']) ?></div>
      <?php endforeach; ?>
    <?php
}

function admin_foot(string $pageScript = ''): void
{
    ?>
    </main>
  </div>
</div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<?php if ($pageScript !== ''): ?>
<script src="/assets/js/<?= e($pageScript) ?>.js?v=3" defer></script>
<?php endif; ?>
</body>
</html>
    <?php
}

/** Buyurtma statusi uchun rangli nishon (badge). */
function status_badge(string $status): string
{
    $tone = match ($status) {
        'new' => 'info',
        'accepted', 'preparing' => 'warn',
        'ready', 'delivering' => 'accent',
        'completed' => 'ok',
        'cancelled' => 'danger',
        default => 'muted',
    };
    return '<span class="badge-status tone-' . $tone . '">' . e(order_status_label($status)) . '</span>';
}

/** Sahifalash: [limit, offset, page] */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return [$perPage, ($page - 1) * $perPage, $page, $pages];
}

function pagination_html(int $page, int $pages, string $baseQuery = ''): string
{
    if ($pages <= 1) {
        return '';
    }
    $sep = $baseQuery === '' ? '?' : '?' . $baseQuery . '&';
    $html = '<nav class="pager">';
    if ($page > 1) {
        $html .= '<a class="btn ghost sm" href="' . e($sep . 'page=' . ($page - 1)) . '">‹ Oldingi</a>';
    }
    $html .= '<span>' . $page . ' / ' . $pages . '</span>';
    if ($page < $pages) {
        $html .= '<a class="btn ghost sm" href="' . e($sep . 'page=' . ($page + 1)) . '">Keyingi ›</a>';
    }
    return $html . '</nav>';
}
