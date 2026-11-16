<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

$status = (string) ($_GET['status'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$payment = (string) ($_GET['payment'] ?? '');
$q = v_str((string) ($_GET['q'] ?? ''), 80) ?? '';
$date = (string) ($_GET['date'] ?? '');
$where = [];
$params = [];
if (array_key_exists($status, ORDER_STATUS_LABELS)) {
    $where[] = 'o.status = :st';
    $params[':st'] = $status;
}
if (in_array($type, ['delivery', 'pickup'], true)) {
    $where[] = 'o.order_type = :ty';
    $params[':ty'] = $type;
}
if (in_array($payment, ['paid', 'unpaid'], true)) {
    $where[] = 'o.payment_status = :ps';
    $params[':ps'] = $payment;
}
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(o.customer_name LIKE :q1 OR o.customer_phone LIKE :q2 OR o.order_no = :qn)';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':qn'] = (int) preg_replace('/\D/', '', $q);
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $where[] = 'o.created_at >= :d1 AND o.created_at < DATE_ADD(:d2, INTERVAL 1 DAY)';
    $params[':d1'] = $date . ' 00:00:00';
    $params[':d2'] = $date;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int) db_val("SELECT COUNT(*) FROM orders o {$whereSql}", $params);
[$limit, $offset, $page, $pages] = paginate($total, 25, (int) ($_GET['page'] ?? 1));
$rows = db_all(
    "SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count,
            (SELECT GROUP_CONCAT(CONCAT(i.quantity, ' × ', i.product_name) SEPARATOR ', ') FROM order_items i WHERE i.order_id = o.id) AS items_text
     FROM orders o {$whereSql} ORDER BY o.created_at DESC, o.id DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);

$qs = http_build_query(array_filter(['status' => $status, 'type' => $type, 'payment' => $payment, 'q' => $q, 'date' => $date]));

admin_head('Buyurtmalar', 'orders', $user);
?>
<div class="toolbar">
  <form method="get" action="/admin/orders.php" style="display:flex;gap:8px;flex-wrap:wrap;width:100%">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Raqam, mijoz yoki telefon" style="max-width:260px">
    <select class="input" name="status" aria-label="Status">
      <option value="">Barcha statuslar</option>
      <?php foreach (ORDER_STATUS_LABELS as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="type" aria-label="Turi">
      <option value="">Barcha turlar</option>
      <option value="delivery" <?= $type === 'delivery' ? 'selected' : '' ?>>Yetkazib berish</option>
      <option value="pickup" <?= $type === 'pickup' ? 'selected' : '' ?>>Olib ketish</option>
    </select>
    <select class="input" name="payment" aria-label="To‘lov">
      <option value="">Barcha to‘lovlar</option>
      <option value="unpaid" <?= $payment === 'unpaid' ? 'selected' : '' ?>>To‘lanmagan</option>
      <option value="paid" <?= $payment === 'paid' ? 'selected' : '' ?>>To‘langan</option>
    </select>
    <input class="input" type="date" name="date" value="<?= e($date) ?>" aria-label="Sana">
    <button class="btn" type="submit">Filtrlash</button>
    <a class="btn ghost" href="/admin/orders.php">Tozalash</a>
  </form>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>Mijoz</th><th>Telefon</th><th>Mahsulotlar</th><th class="num">Jami</th><th>Turi</th><th>To‘lov</th><th>Status</th><th>Sana</th></tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
      <tr><td colspan="9" class="empty">Buyurtmalar topilmadi</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $o): ?>
      <tr>
        <td><a href="/admin/order.php?id=<?= (int) $o['id'] ?>"><strong>#<?= (int) $o['order_no'] ?></strong></a></td>
        <td><?= e($o['customer_name']) ?></td>
        <td><?= e($o['customer_phone']) ?></td>
        <td style="max-width:280px"><small><?= e(mb_strimwidth((string) $o['items_text'], 0, 110, '…')) ?></small></td>
        <td class="num"><strong><?= e(money((int) $o['total'])) ?></strong></td>
        <td><?= $o['order_type'] === 'delivery' ? 'Yetkazish' : 'Olib ketish' ?></td>
        <td><span class="pill <?= $o['payment_status'] === 'paid' ? 'on' : 'off' ?>"><?= $o['payment_status'] === 'paid' ? 'To‘langan' : 'Naqd · kutilmoqda' ?></span></td>
        <td><?= status_badge((string) $o['status']) ?></td>
        <td><small><?= e(substr((string) $o['created_at'], 0, 16)) ?></small></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="toolbar">
  <span class="muted">Jami: <?= $total ?> ta buyurtma</span>
  <?= pagination_html($page, $pages, $qs) ?>
</div>
<?php admin_foot();
