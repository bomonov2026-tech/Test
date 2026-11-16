<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

$periodParam = (string) ($_GET['period'] ?? 'day');
$period = in_array($periodParam, ['day', 'week', 'month'], true) ? $periodParam : 'day';
$today = new DateTimeImmutable('today');
$start = match ($period) {
    'week' => $today->modify('monday this week'),
    'month' => $today->modify('first day of this month'),
    default => $today,
};
$from = $start->format('Y-m-d 00:00:00');
$periodLabel = match ($period) { 'week' => 'Shu hafta', 'month' => 'Shu oy', default => 'Bugun' };

$live = db_one(
    "SELECT
        SUM(status = 'new') AS new_count,
        SUM(status IN ('accepted','preparing')) AS preparing_count,
        SUM(status = 'ready') AS ready_count,
        SUM(status = 'delivering') AS delivering_count
     FROM orders WHERE status IN ('new','accepted','preparing','ready','delivering')"
);

$stats = db_one(
    "SELECT COUNT(*) AS orders_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS sales,
            SUM(status = 'completed') AS completed,
            SUM(status = 'cancelled') AS cancelled,
            SUM(status <> 'cancelled') AS valid_count
     FROM orders WHERE created_at >= :from",
    [':from' => $from]
);
$avg = (int) ($stats['valid_count'] ?? 0) > 0 ? (int) round((int) $stats['sales'] / (int) $stats['valid_count']) : 0;

$top = db_all(
    "SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS revenue
     FROM order_items oi JOIN orders o ON o.id = oi.order_id
     WHERE o.created_at >= :from AND o.status <> 'cancelled'
     GROUP BY oi.product_name ORDER BY qty DESC, revenue DESC LIMIT 8",
    [':from' => $from]
);
$maxQty = max(1, ...array_map(static fn(array $r): int => (int) $r['qty'], $top ?: [['qty' => 1]]));

$recent = db_all(
    'SELECT o.*, (SELECT COUNT(*) FROM order_items i WHERE i.order_id = o.id) AS items_count
     FROM orders o ORDER BY o.created_at DESC, o.id DESC LIMIT 10'
);

admin_head('Bosh sahifa', 'dashboard', $user);
?>
<div class="toolbar">
  <div class="seg" role="tablist" aria-label="Davr">
    <a href="?period=day" class="<?= $period === 'day' ? 'active' : '' ?>">Kun</a>
    <a href="?period=week" class="<?= $period === 'week' ? 'active' : '' ?>">Hafta</a>
    <a href="?period=month" class="<?= $period === 'month' ? 'active' : '' ?>">Oy</a>
  </div>
  <span class="muted"><?= e($periodLabel) ?> · <?= e($start->format('d.m.Y')) ?> – <?= e($today->format('d.m.Y')) ?></span>
</div>

<section class="grid kpi" aria-label="Ko‘rsatkichlar">
  <div class="card kpi accent"><div class="label">Yangi buyurtmalar</div><div class="value"><?= (int) ($live['new_count'] ?? 0) ?></div><div class="hint">Hozir tasdiqlanmagan</div></div>
  <div class="card kpi"><div class="label">Tayyorlanmoqda</div><div class="value"><?= (int) ($live['preparing_count'] ?? 0) ?></div><div class="hint">Oshxonada</div></div>
  <div class="card kpi"><div class="label">Tayyor / yo‘lda</div><div class="value"><?= (int) ($live['ready_count'] ?? 0) + (int) ($live['delivering_count'] ?? 0) ?></div><div class="hint">Topshirilishi kutilmoqda</div></div>
  <div class="card kpi"><div class="label">Buyurtmalar (<?= e(strtolower($periodLabel)) ?>)</div><div class="value"><?= (int) ($stats['orders_count'] ?? 0) ?></div><div class="hint">Barcha statuslar</div></div>
  <div class="card kpi accent"><div class="label">Savdo (<?= e(strtolower($periodLabel)) ?>)</div><div class="value"><?= e(money((int) $stats['sales'])) ?></div><div class="hint">O‘rtacha chek: <?= e(money($avg)) ?></div></div>
  <div class="card kpi"><div class="label">Yakunlangan</div><div class="value"><?= (int) ($stats['completed'] ?? 0) ?></div><div class="hint">Muvaffaqiyatli</div></div>
  <div class="card kpi"><div class="label">Bekor qilingan</div><div class="value"><?= (int) ($stats['cancelled'] ?? 0) ?></div><div class="hint">Davr ichida</div></div>
</section>

<div class="grid two">
  <section class="card">
    <div class="section-title"><h2>Oxirgi buyurtmalar</h2><a class="btn ghost sm" href="/admin/orders.php">Barchasi</a></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Mijoz</th><th>Turi</th><th class="num">Jami</th><th>Status</th><th>Vaqt</th></tr></thead>
        <tbody>
        <?php if (!$recent): ?>
          <tr><td colspan="6" class="empty">Hozircha buyurtmalar yo‘q</td></tr>
        <?php endif; ?>
        <?php foreach ($recent as $o): ?>
          <tr>
            <td><a href="/admin/order.php?id=<?= (int) $o['id'] ?>"><strong>#<?= (int) $o['order_no'] ?></strong></a></td>
            <td><?= e($o['customer_name']) ?><br><small class="muted"><?= e($o['customer_phone']) ?></small></td>
            <td><?= $o['order_type'] === 'delivery' ? 'Yetkazish' : 'Olib ketish' ?></td>
            <td class="num"><?= e(money((int) $o['total'])) ?></td>
            <td><?= status_badge((string) $o['status']) ?></td>
            <td><small><?= e(substr((string) $o['created_at'], 5, 11)) ?></small></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <div class="section-title"><h2>Eng ko‘p sotilgan</h2><small class="muted"><?= e($periodLabel) ?></small></div>
    <?php if (!$top): ?>
      <p class="empty">Ma’lumot yo‘q</p>
    <?php else: ?>
      <div class="bars">
        <?php foreach ($top as $row): ?>
          <div class="bar-row">
            <span title="<?= e($row['product_name']) ?>" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($row['product_name']) ?></span>
            <div class="bar-track"><div class="bar-fill" style="width: <?= (int) round((int) $row['qty'] * 100 / $maxQty) ?>%"></div></div>
            <strong><?= (int) $row['qty'] ?> ta</strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php admin_foot();
