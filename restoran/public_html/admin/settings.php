<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

const DAY_NAMES = [1 => 'Dushanba', 2 => 'Seshanba', 3 => 'Chorshanba', 4 => 'Payshanba', 5 => 'Juma', 6 => 'Shanba', 7 => 'Yakshanba'];
const TEXT_SETTINGS = [
    'restaurant_name' => 120, 'restaurant_slogan' => 160, 'restaurant_phone' => 20, 'restaurant_address' => 255,
    'restaurant_about' => 1000, 'contact_telegram' => 60, 'currency' => 20,
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $errors = [];
    $changes = [];

    foreach (TEXT_SETTINGS as $key => $max) {
        $value = v_str((string) ($_POST[$key] ?? ''), $max);
        if ($value === null) {
            $errors[] = 'Maydon juda uzun: ' . $key;
            continue;
        }
        $changes[$key] = $value;
    }
    if (isset($changes['restaurant_name']) && $changes['restaurant_name'] === '') {
        $errors[] = 'Restoran nomini kiriting';
    }
    if (($changes['currency'] ?? '') === '') {
        $changes['currency'] = 'so‘m'; // valyuta bo‘sh bo‘lib qolmasin
    }
    if (!empty($changes['restaurant_phone']) && v_phone($changes['restaurant_phone']) === null) {
        $errors[] = 'Telefon raqami +998 formatida bo‘lishi kerak';
    } elseif (!empty($changes['restaurant_phone'])) {
        $changes['restaurant_phone'] = v_phone($changes['restaurant_phone']);
    }

    $open = v_time($_POST['working_open'] ?? '');
    $close = v_time($_POST['working_close'] ?? '');
    if ($open === null || $close === null) {
        $errors[] = 'Ish vaqti HH:MM formatida bo‘lishi kerak';
    } else {
        $changes['working_open'] = $open;
        $changes['working_close'] = $close;
    }

    $daysOff = [];
    foreach ((array) ($_POST['days_off'] ?? []) as $d) {
        $n = (int) $d;
        if ($n >= 1 && $n <= 7) {
            $daysOff[$n] = $n;
        }
    }
    $changes['days_off'] = implode(',', array_values($daysOff));

    foreach (['order_enabled', 'delivery_enabled', 'pickup_enabled'] as $flag) {
        $changes[$flag] = v_bool($_POST[$flag] ?? false) ? '1' : '0';
    }
    if ($changes['delivery_enabled'] === '0' && $changes['pickup_enabled'] === '0') {
        $errors[] = 'Kamida bitta xizmat (yetkazib berish yoki olib ketish) yoqilgan bo‘lishi kerak';
    }

    $fee = v_int($_POST['delivery_fee'] ?? null, 0, 100000000);
    $min = v_int($_POST['min_order_amount'] ?? null, 0, 100000000);
    if ($fee === null || $min === null) {
        $errors[] = 'Yetkazib berish narxi va minimal summa 0 dan katta butun son bo‘lishi kerak';
    } else {
        $changes['delivery_fee'] = (string) $fee;
        $changes['min_order_amount'] = (string) $min;
    }

    $chatIds = [];
    foreach (preg_split('/[\s,;]+/', (string) ($_POST['admin_chat_ids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $cid) {
        if (preg_match('/^-?\d{5,15}$/', $cid)) {
            $chatIds[] = $cid;
        } else {
            $errors[] = 'Noto‘g‘ri Telegram ID: ' . $cid;
        }
    }
    $changes['admin_chat_ids'] = implode(',', array_unique($chatIds));

    $token = trim((string) ($_POST['bot_token'] ?? ''));
    if ($token !== '') {
        if (preg_match('/^\d{6,12}:[A-Za-z0-9_-]{30,}$/', $token) === 1) {
            $changes['bot_token'] = $token;
        } else {
            $errors[] = 'Bot tokeni formati noto‘g‘ri';
        }
    }

    $logoPath = null;
    if (isset($_FILES['logo']) && (int) ($_FILES['logo']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
        $saved = save_uploaded_image($_FILES['logo'], 'logo');
        if ($saved['ok']) {
            $logoPath = $saved['path'];
            $changes['logo'] = $logoPath;
        } else {
            $errors[] = $saved['error'];
        }
    }

    if ($errors) {
        flash_set('error', implode(' · ', array_unique($errors)));
        redirect('/admin/settings.php');
    }

    $oldLogo = setting('logo', '');
    foreach ($changes as $key => $value) {
        if ($key === 'bot_token' || $value !== setting($key, '')) {
            set_setting($key, (string) $value);
        }
    }
    if ($logoPath !== null && $oldLogo !== '') {
        delete_upload($oldLogo);
    }
    admin_log('update_settings', 'settings', null, ['keys' => array_keys($changes)]);
    flash_set('ok', 'Sozlamalar saqlandi');
    redirect('/admin/settings.php');
}

$s = settings_all(true);
$daysOff = v_weekdays_csv($s['days_off'] ?? '');
$botFromConfig = trim((string) config('telegram.bot_token', '')) !== '' && !str_starts_with((string) config('telegram.bot_token'), 'CHANGE_ME');
$botSet = $botFromConfig || trim(setting('bot_token', '')) !== '';
$secretOk = strlen((string) config('telegram.webhook_secret', '')) >= 32 && !str_starts_with((string) config('telegram.webhook_secret'), 'CHANGE_ME');
$httpsOk = str_starts_with(base_url(), 'https://');

admin_head('Sozlamalar', 'settings', $user);
?>
<div class="grid two">
  <form method="post" action="/admin/settings.php" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px;min-width:0">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

    <section class="card">
      <h2>Restoran ma’lumotlari</h2>
      <div class="form" style="margin-top:12px">
        <label class="field"><span>Restoran nomi *</span><input name="restaurant_name" required maxlength="120" value="<?= e($s['restaurant_name'] ?? '') ?>"></label>
        <label class="field"><span>Shior</span><input name="restaurant_slogan" maxlength="160" value="<?= e($s['restaurant_slogan'] ?? '') ?>"></label>
        <label class="field"><span>Telefon (+998)</span><input name="restaurant_phone" maxlength="20" value="<?= e($s['restaurant_phone'] ?? '') ?>"></label>
        <label class="field"><span>Telegram aloqa (@username)</span><input name="contact_telegram" maxlength="60" value="<?= e($s['contact_telegram'] ?? '') ?>"></label>
        <label class="field full"><span>Manzil (filial)</span><input name="restaurant_address" maxlength="255" value="<?= e($s['restaurant_address'] ?? '') ?>"></label>
        <label class="field full"><span>Restoran haqida</span><textarea name="restaurant_about" maxlength="1000"><?= e($s['restaurant_about'] ?? '') ?></textarea></label>
        <label class="field"><span>Valyuta belgisi</span><input name="currency" maxlength="20" value="<?= e($s['currency'] ?? 'so‘m') ?>"></label>
        <label class="field"><span>Logotip (JPG/PNG/WEBP)</span>
          <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
          <?php if (!empty($s['logo'])): ?><img src="/<?= e($s['logo']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:14px;margin-top:6px"><?php endif; ?>
        </label>
      </div>
    </section>

    <section class="card">
      <h2>Ish vaqti va dam olish</h2>
      <div class="form" style="margin-top:12px">
        <label class="field"><span>Ochilish</span><input type="time" name="working_open" required value="<?= e($s['working_open'] ?? '09:00') ?>"></label>
        <label class="field"><span>Yopilish</span><input type="time" name="working_close" required value="<?= e($s['working_close'] ?? '23:00') ?>"></label>
        <div class="field full"><span>Dam olish kunlari</span>
          <div class="checks">
            <?php foreach (DAY_NAMES as $n => $label): ?>
              <label class="check"><input type="checkbox" name="days_off[]" value="<?= $n ?>" <?= in_array($n, $daysOff, true) ? 'checked' : '' ?>> <?= e($label) ?></label>
            <?php endforeach; ?>
          </div>
          <span class="hint">Shu kunlari restoran yopiq hisoblanadi va buyurtma qabul qilinmaydi.</span>
        </div>
      </div>
    </section>

    <section class="card">
      <h2>Buyurtma va yetkazib berish</h2>
      <div class="form" style="margin-top:12px">
        <div class="field full"><span>Xizmatlar</span>
          <div class="checks">
            <label class="check"><input type="checkbox" name="order_enabled" value="1" <?= ($s['order_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Buyurtma qabul qilish</label>
            <label class="check"><input type="checkbox" name="delivery_enabled" value="1" <?= ($s['delivery_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Yetkazib berish</label>
            <label class="check"><input type="checkbox" name="pickup_enabled" value="1" <?= ($s['pickup_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> Olib ketish</label>
          </div>
        </div>
        <label class="field"><span>Yetkazib berish narxi (so‘m)</span><input name="delivery_fee" type="number" min="0" max="100000000" step="500" value="<?= (int) ($s['delivery_fee'] ?? 0) ?>"></label>
        <label class="field"><span>Minimal buyurtma summasi (so‘m)</span><input name="min_order_amount" type="number" min="0" max="100000000" step="500" value="<?= (int) ($s['min_order_amount'] ?? 0) ?>"></label>
      </div>
    </section>

    <section class="card">
      <h2>Telegram bot</h2>
      <div class="form" style="margin-top:12px">
        <label class="field full"><span>Bot tokeni (BotFather)</span>
          <input name="bot_token" type="password" autocomplete="off" placeholder="<?= $botSet ? 'Saqlangan — o‘zgartirish uchun yangi token kiriting' : '123456:ABC…' ?>">
          <span class="hint">Token xavfsizlik uchun ko‘rsatilmaydi. Asosiy joyi: config/config.php. Bu yerdagi qiymat faqat config bo‘sh bo‘lganda ishlatiladi.</span>
        </label>
        <label class="field full"><span>Admin Telegram ID lar (vergul bilan)</span>
          <input name="admin_chat_ids" value="<?= e($s['admin_chat_ids'] ?? '') ?>" placeholder="123456789, 987654321">
          <span class="hint">Yangi buyurtma xabari shu ID larga yuboriladi. Telegram ID ni @userinfobot orqali bilish mumkin.</span>
        </label>
      </div>
    </section>

    <div><button class="btn lg" type="submit">Sozlamalarni saqlash</button></div>
  </form>

  <aside style="display:flex;flex-direction:column;gap:18px;min-width:0">
    <section class="card">
      <h2>Tizim holati</h2>
      <ul style="list-style:none;margin:10px 0 0;padding:0;display:flex;flex-direction:column;gap:10px">
        <li><span class="pill <?= $httpsOk ? 'on' : 'off' ?>"><?= $httpsOk ? 'HTTPS' : 'HTTP' ?></span> &nbsp;<?= e(base_url()) ?></li>
        <li><span class="pill <?= $botSet ? 'on' : 'off' ?>"><?= $botSet ? 'Bot token sozlangan' : 'Bot token yo‘q' ?></span></li>
        <li><span class="pill <?= $secretOk ? 'on' : 'off' ?>"><?= $secretOk ? 'Webhook sirri OK' : 'Webhook sirri sozlanmagan' ?></span></li>
      </ul>
      <p class="hint" style="margin-top:12px">Webhook va menyu tugmasini o‘rnatish: serverda <code>php tools/setup_telegram.php</code></p>
    </section>
    <section class="card">
      <h2>Admin harakatlari</h2>
      <p class="hint" style="margin-top:6px">Muhim o‘zgarishlar jurnalga yoziladi (<code>admin_logs</code>).</p>
      <?php
      $logs = db_all(
          'SELECT l.action, l.entity, l.entity_id, l.created_at, a.username FROM admin_logs l
           LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT 12'
      );
      ?>
      <div class="table-wrap" style="margin-top:10px">
        <table>
          <thead><tr><th>Vaqt</th><th>Xodim</th><th>Amal</th></tr></thead>
          <tbody>
          <?php foreach ($logs as $log): ?>
            <tr><td><small><?= e(substr((string) $log['created_at'], 5, 11)) ?></small></td><td><small><?= e($log['username'] ?? 'tizim') ?></small></td><td><small><?= e($log['action']) ?><?= $log['entity_id'] ? ' #' . e($log['entity_id']) : '' ?></small></td></tr>
          <?php endforeach; ?>
          <?php if (!$logs): ?><tr><td colspan="3" class="empty">Jurnal bo‘sh</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </aside>
</div>
<?php admin_foot();
