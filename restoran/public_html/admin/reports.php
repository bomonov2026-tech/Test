<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$firstOfMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : $firstOfMonth;
$to = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : $today;
if ($from > $to) {
    [$from, $to] = [$to, $from];
}
$fromTs = $from . ' 00:00:00';
$toEnd = $to . ' 23:59:59';

$summary = db_one(
    "SELECT COUNT(*) AS orders_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS sales,
            SUM(status <> 'cancelled') AS valid_count,
            SUM(status = 'cancelled') AS cancelled,
            SUM(status = 'completed') AS completed,
            SUM(order_type = 'delivery' AND status <> 'cancelled') AS delivery_count,
            SUM(order_type = 'pickup' AND status <> 'cancelled') AS pickup_count
     FROM orders WHERE created_at BETWEEN :f AND :t",
    [':f' => $fromTs, ':t' => $toEnd]
);
$valid = (int) ($summary['valid_count'] ?? 0);
$avg = $valid > 0 ? (int) round((int) $summary['sales'] / $valid) : 0;
$cancelRate = (int) $summary['orders_count'] > 0 ? round((int) $summary['cancelled'] * 100 / (int) $summary['orders_count'], 1) : 0;

$daily = db_all(
    "SELECT DATE(created_at) AS day, COUNT(*) AS orders_count,
            COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS sales
     FROM orders WHERE created_at BETWEEN :f AND :t GROUP BY DATE(created_at) ORDER BY day DESC",
    [':f' => $fromTs, ':t' => $toEnd]
);

$top = db_all(
    "SELECT oi.product_name, SUM(oi.quantity) AS qty, SUM(oi.line_total) AS revenue
     FROM order_items oi JOIN orders o ON o.id = oi.order_id
     WHERE o.created_at BETWEEN :f AND :t AND o.status <> 'cancelled'
     GROUP BY oi.product_name ORDER BY qty DESC, revenue DESC LIMIT 20",
    [':f' => $fromTs, ':t' => $toEnd]
);

if (($_GET['export'] ?? '') === 'csv') {
    admin_log('export_report', 'report', $from . '_' . $to);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hisobot_' . $from . '_' . $to . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Sana', 'Buyurtmalar', 'Savdo (so‘m)']);
    foreach ($daily as $row) {
        fputcsv($out, [$row['day'], (int) $row['orders_count'], (int) $row['sales']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Mahsulot', 'Soni', 'Tushum (so‘m)']);
    foreach ($top as $row) {
        fputcsv($out, [$row['product_name'], (int) $row['qty'], (int) $row['revenue']]);
    }
    fclose($out);
    exit;
}

admin_head('Hisobotlar', 'reports', $user);
?>
<form class="toolbar" method="get" action="/admin/reports.php">
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <label class="field" style="min-width:150px"><span>Boshlanish</span><input class="input" type="date" name="from" value="<?= e($from) ?>"></label>
    <label class="field" style="min-width:150px"><span>Tugash</span><input class="input" type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="btn" type="submit">Ko‘rsatish</button>
    <a class="btn ghost" href="/admin/reports.php?from=<?= e($from) ?>&amp;to=<?= e($to) ?>&amp;export=csv">CSV yuklab olish</a>
  </div>
</form>

<section class="grid kpi">
  <div class="card kpi accent"><div class="label">Umumiy savdo</div><div class="value"><?= e(money((int) $summary['sales'])) ?></div><div class="hint"><?= e($from) ?> – <?= e($to) ?></div></div>
  <div class="card kpi"><div class="label">Buyurtmalar</div><div class="value"><?= (int) $summary['orders_count'] ?></div><div class="hint">Yakunlangan: <?= (int) $summary['completed'] ?></div></div>
  <div class="card kpi"><div class="label">O‘rtacha chek</div><div class="value"><?= e(money($avg)) ?></div><div class="hint">Bekor qilinmaganlar bo‘yicha</div></div>
  <div class="card kpi"><div class="label">Bekor qilish</div><div class="value"><?= (int) $summary['cancelled'] ?></div><div class="hint"><?= e((string) $cancelRate) ?>% buyurtmalardan</div></div>
  <div class="card kpi"><div class="label">Yetkazish / olib ketish</div><div class="value"><?= (int) $summary['delivery_count'] ?> / <?= (int) $summary['pickup_count'] ?></div><div class="hint">Bekor qilinmaganlar</div></div>
</section>

<div class="grid two" style="margin-top:14px">
  <section class="card">
    <h2>Kunlik hisobot</h2>
    <div class="table-wrap" style="margin-top:10px">
      <table>
        <thead><tr><th>Sana</th><th class="num">Buyurtma</th><th class="num">Savdo</th></tr></thead>
        <tbody>
        <?php if (!$daily): ?><tr><td colspan="3" class="empty">Bu davrda buyurtma yo‘q</td></tr><?php endif; ?>
        <?php foreach ($daily as $d): ?>
          <tr><td><?= e(date('d.m.Y', strtotime((string) $d['day']))) ?></td><td class="num"><?= (int) $d['orders_count'] ?></td><td class="num"><strong><?= e(money((int) $d['sales'])) ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="card">
    <h2>Eng ko‘p sotilgan mahsulotlar</h2>
    <div class="table-wrap" style="margin-top:10px">
      <table>
        <thead><tr><th>Mahsulot</th><th class="num">Soni</th><th class="num">Tushum</th></tr></thead>
        <tbody>
        <?php if (!$top): ?><tr><td colspan="3" class="empty">Ma’lumot yo‘q</td></tr><?php endif; ?>
        <?php foreach ($top as $t): ?>
          <tr><td><?= e($t['product_name']) ?></td><td class="num"><?= (int) $t['qty'] ?></td><td class="num"><?= e(money((int) $t['revenue'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php admin_foot();
