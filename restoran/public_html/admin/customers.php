<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);
    if (($_POST['action'] ?? '') === 'toggle_block' && $id !== null) {
        db_exec('UPDATE users SET is_blocked = 1 - is_blocked WHERE id = :id', [':id' => $id]);
        admin_log('toggle_block_customer', 'user', (string) $id);
        flash_set('ok', 'Mijoz holati o‘zgartirildi');
    }
    redirect('/admin/customers.php' . (($_POST['qs'] ?? '') !== '' ? '?' . http_build_query(['q' => substr((string) $_POST['qs'], 0, 80)]) : ''));
}

$q = v_str((string) ($_GET['q'] ?? ''), 80) ?? '';
$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE (u.first_name LIKE :q1 OR u.last_name LIKE :q2 OR u.phone LIKE :q3 OR u.username LIKE :q4 OR CAST(u.telegram_id AS CHAR) LIKE :q5)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $k) {
        $params[':' . $k] = $like;
    }
}
$total = (int) db_val("SELECT COUNT(*) FROM users u {$where}", $params);
[$limit, $offset, $page, $pages] = paginate($total, 30, (int) ($_GET['page'] ?? 1));
$rows = db_all(
    "SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
        (SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE o.user_id = u.id AND o.status <> 'cancelled') AS spent,
        (SELECT MAX(o.created_at) FROM orders o WHERE o.user_id = u.id) AS last_order
     FROM users u {$where} ORDER BY last_order DESC, u.id DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);
$qs = http_build_query(array_filter(['q' => $q]));

admin_head('Mijozlar', 'customers', $user);
?>
<div class="toolbar">
  <form method="get" action="/admin/customers.php" style="display:flex;gap:8px;flex-wrap:wrap">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Ism, telefon, @username yoki Telegram ID" style="max-width:340px">
    <button class="btn ghost" type="submit">Qidirish</button>
  </form>
  <span class="muted">Jami: <?= $total ?> ta mijoz</span>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>Telegram ID</th><th>Ism</th><th>Telefon</th><th class="num">Buyurtmalar</th><th class="num">Jami xarid</th><th>Oxirgi buyurtma</th><th>Holat</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">Mijozlar topilmadi</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><code><?= e((string) $r['telegram_id']) ?></code></td>
        <td><strong><?= e(trim($r['first_name'] . ' ' . (string) $r['last_name'])) ?></strong><?php if ($r['username']): ?><br><small class="muted">@<?= e($r['username']) ?></small><?php endif; ?></td>
        <td><?= e((string) ($r['phone'] ?? '—')) ?></td>
        <td class="num"><?= (int) $r['orders_count'] ?></td>
        <td class="num"><?= e(money((int) $r['spent'])) ?></td>
        <td><small><?= e($r['last_order'] ? substr((string) $r['last_order'], 0, 16) : '—') ?></small></td>
        <td>
          <form method="post" action="/admin/customers.php" class="inline" <?= (int) $r['is_blocked'] ? '' : 'data-confirm="Mijozni bloklaysizmi? U buyurtma bera olmaydi."' ?>>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_block">
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="qs" value="<?= e($q) ?>">
            <button class="pill <?= (int) $r['is_blocked'] ? 'off' : 'on' ?>" type="submit" style="cursor:pointer;border:0"><?= (int) $r['is_blocked'] ? 'Bloklangan · ochish' : 'Faol · bloklash' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="toolbar" style="margin-top:12px">
  <span></span>
  <?= pagination_html($page, $pages, $qs) ?>
</div>
<?php admin_foot();
