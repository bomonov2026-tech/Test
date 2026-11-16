<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

$id = v_int($_GET['id'] ?? $_POST['id'] ?? null, 1, PHP_INT_MAX);
$order = $id === null ? null : order_row($id);
if ($order === null) {
    http_response_code(404);
    admin_head('Buyurtma topilmadi', 'orders', $user);
    echo '<div class="card empty">Buyurtma topilmadi. <a href="/admin/orders.php">Ro‘yxatga qaytish</a></div>';
    admin_foot();
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    $back = '/admin/order.php?id=' . $id;

    if ($action === 'status') {
        $to = (string) ($_POST['to'] ?? '');
        $note = v_str((string) ($_POST['note'] ?? ''), 250) ?? '';
        $result = change_order_status($id, $to, ['type' => 'admin', 'id' => (int) $user['id']], $note !== '' ? $note : null);
        if (isset($result['error'])) {
            flash_set('error', $result['error']);
        } else {
            flash_set('ok', 'Status o‘zgartirildi: ' . order_status_label($result['to']));
        }
    } elseif ($action === 'payment') {
        $pay = (string) ($_POST['payment'] ?? '');
        if (in_array($pay, ['paid', 'unpaid'], true)) {
            db_exec('UPDATE orders SET payment_status = :p WHERE id = :id', [':p' => $pay, ':id' => $id]);
            admin_log('payment_status', 'order', (string) $id, ['payment' => $pay]);
            flash_set('ok', $pay === 'paid' ? 'Naqd to‘lov qayd etildi' : 'To‘lov holati qaytarildi');
        }
    } elseif ($action === 'note') {
        $note = v_str((string) ($_POST['admin_note'] ?? ''), 500);
        if ($note === null) {
            flash_set('error', 'Izoh juda uzun');
        } else {
            db_exec('UPDATE orders SET admin_note = :n WHERE id = :id', [':n' => $note !== '' ? $note : null, ':id' => $id]);
            admin_log('order_note', 'order', (string) $id);
            flash_set('ok', 'Izoh saqlandi');
        }
    }
    redirect($back);
}

$order = order_row($id);
$full = order_full($order);
$items = order_items_map([$id])[$id] ?? [];
$next = allowed_next_statuses((string) $order['status'], (string) $order['order_type']);

admin_head('Buyurtma #' . $order['order_no'], 'orders', $user);
?>
<div class="toolbar">
  <a class="btn ghost sm" href="/admin/orders.php">‹ Buyurtmalar</a>
  <div><?= status_badge((string) $order['status']) ?></div>
</div>

<div class="grid two">
  <div style="display:flex;flex-direction:column;gap:18px;min-width:0">
    <section class="card">
      <div class="section-title"><h2>Buyurtma #<?= (int) $order['order_no'] ?></h2><small class="muted"><?= e($order['created_at']) ?></small></div>
      <dl class="kv">
        <dt>Mijoz</dt><dd><?= e($order['customer_name']) ?></dd>
        <dt>Telefon</dt><dd><a href="tel:<?= e(preg_replace('/[^+\d]/', '', (string) $order['customer_phone'])) ?>"><?= e($order['customer_phone']) ?></a></dd>
        <dt>Turi</dt><dd><?= $order['order_type'] === 'delivery' ? 'Yetkazib berish' : 'Olib ketish' ?></dd>
        <?php if ($order['order_type'] === 'delivery'): ?>
          <dt>Manzil</dt><dd><?= e($order['address']) ?></dd>
          <dt>Mo‘ljal</dt><dd><?= e($order['landmark'] ?: '—') ?></dd>
        <?php endif; ?>
        <dt>Izoh (mijoz)</dt><dd><?= e($order['comment'] ?: '—') ?></dd>
        <dt>To‘lov</dt><dd>Naqd pul · <span class="pill <?= $order['payment_status'] === 'paid' ? 'on' : 'off' ?>"><?= $order['payment_status'] === 'paid' ? 'To‘langan' : 'To‘lanmagan' ?></span></dd>
        <?php if ($order['promo_code']): ?><dt>Promo-kod</dt><dd><?= e($order['promo_code']) ?></dd><?php endif; ?>
        <?php if ($order['cancel_reason']): ?><dt>Bekor sababi</dt><dd><?= e($order['cancel_reason']) ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="card">
      <h2>Mahsulotlar</h2>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Mahsulot</th><th class="num">Narx</th><th class="num">Soni</th><th class="num">Summa</th></tr></thead>
          <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <strong><?= e($it['product_name']) ?></strong>
                <?php if ($it['options']): ?>
                  <br><small class="muted">+ <?= e(implode(', ', array_map(static fn(array $o): string => (string) $o['value_name'], $it['options']))) ?></small>
                <?php endif; ?>
                <?php if (!empty($it['note'])): ?><br><small>Izoh: <?= e($it['note']) ?></small><?php endif; ?>
              </td>
              <td class="num"><?= e(money((int) $it['unit_price'] + (int) $it['options_total'])) ?></td>
              <td class="num"><?= (int) $it['quantity'] ?> <?= e($it['unit']) ?></td>
              <td class="num"><strong><?= e(money((int) $it['line_total'])) ?></strong></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="summary-rows" style="margin-top:14px;max-width:340px;margin-left:auto">
        <div><span>Mahsulotlar</span><strong><?= e(money((int) $order['subtotal'])) ?></strong></div>
        <?php if ((int) $order['discount_amount'] > 0): ?><div><span>Chegirma</span><strong>−<?= e(money((int) $order['discount_amount'])) ?></strong></div><?php endif; ?>
        <?php if ((int) $order['delivery_fee'] > 0): ?><div><span>Yetkazib berish</span><strong><?= e(money((int) $order['delivery_fee'])) ?></strong></div><?php endif; ?>
        <div class="total"><span>Jami</span><span><?= e(money((int) $order['total'])) ?></span></div>
      </div>
    </section>
  </div>

  <div style="display:flex;flex-direction:column;gap:18px;min-width:0">
    <section class="card">
      <h2>Holatni boshqarish</h2>
      <?php if (!$next): ?>
        <p class="muted">Bu buyurtma yakuniy holatda.</p>
      <?php else: ?>
        <?php foreach ($next as $to): ?>
          <form method="post" action="/admin/order.php" style="margin-bottom:10px" <?= $to === 'cancelled' ? 'data-confirm="Buyurtmani bekor qilasizmi?"' : '' ?>>
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <?php if ($to === 'cancelled'): ?>
              <input class="input" name="note" maxlength="250" placeholder="Bekor qilish sababi" style="margin-bottom:8px">
            <?php endif; ?>
            <button class="btn block <?= $to === 'cancelled' ? 'danger' : ($to === 'completed' ? 'ok' : '') ?>" type="submit"><?= e(order_status_label($to)) ?> — o‘tkazish</button>
          </form>
        <?php endforeach; ?>
      <?php endif; ?>
      <hr style="border:0;border-top:1px solid var(--card-line);margin:14px 0">
      <form method="post" action="/admin/order.php">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="payment">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="payment" value="<?= $order['payment_status'] === 'paid' ? 'unpaid' : 'paid' ?>">
        <button class="btn <?= $order['payment_status'] === 'paid' ? 'ghost' : 'ok' ?> block" type="submit">
          <?= $order['payment_status'] === 'paid' ? 'To‘lanmagan deb belgilash' : 'Naqd to‘lov qabul qilindi' ?>
        </button>
      </form>
    </section>

    <section class="card">
      <h2>Ichki izoh</h2>
      <form method="post" action="/admin/order.php" class="form" style="grid-template-columns:1fr">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="note">
        <input type="hidden" name="id" value="<?= $id ?>">
        <label class="field"><span>Faqat xodimlar uchun</span><textarea name="admin_note" maxlength="500"><?= e($order['admin_note']) ?></textarea></label>
        <button class="btn ghost" type="submit">Saqlash</button>
      </form>
    </section>

    <section class="card">
      <h2>Holat tarixi</h2>
      <ol style="margin:0;padding:0 0 0 18px;display:flex;flex-direction:column;gap:10px">
        <?php foreach ($full['history'] as $h): ?>
          <li>
            <strong><?= e($h['to_label']) ?></strong>
            <small class="muted">· <?= e($h['at']) ?> · <?= e(['system' => 'tizim', 'customer' => 'mijoz', 'admin' => 'admin', 'kitchen' => 'oshxona'][$h['by']] ?? $h['by']) ?></small>
            <?php if (!empty($h['note'])): ?><br><small><?= e($h['note']) ?></small><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</div>
<?php admin_foot();
