<?php
declare(strict_types=1);

/* ==========================================================================
 *  PO’LATHON SHASHLIK & SOMSA — bitta fayl: Mini App + Admin + Xodimlar
 *  Talab: PHP 7.4+ (pdo_mysql yoki pdo_sqlite, curl, fileinfo, json)
 * ========================================================================== */

/* ============== SOZLAMALAR — hostingda shu qiymatlarni to‘ldiring ============== */
const BOT_TOKEN      = 'BOT_TOKEN';            // @BotFather token
const ADMIN_CHAT_ID  = 'ADMIN_CHAT_ID';        // asosiy admin Telegram ID (buyurtma xabarlari)
const ADMIN_USER     = 'admin';                // admin panel login
const ADMIN_PASSWORD = 'ChangeThisPassword123!'; // admin paroli — DARHOL o‘zgartiring
const DEBUG          = false;                  // production’da false
const MAX_STAFF      = 5;                      // xodimlar soni chegarasi
const DB_HOST = 'localhost';
const DB_NAME = 'DB_NAME';                     // bo‘sh yoki 'DB_NAME' qolsa SQLite ishlatiladi
const DB_USER = 'DB_USER';
const DB_PASS = 'DB_PASSWORD';
const DATA_DIR   = __DIR__ . '/data';
const UPLOAD_DIR = __DIR__ . '/uploads';

const STATUSES = [
  'new' => 'Yangi', 'accepted' => 'Qabul qilindi', 'cooking' => 'Tayyorlanmoqda', 'ready' => 'Tayyor',
  'delivering' => 'Yo‘lda', 'done' => 'Yakunlandi', 'cancelled' => 'Bekor qilindi',
];
const ACTIVE_STATUSES = "'new','accepted','cooking','ready','delivering'";
const DEFAULTS = [
  'name' => 'Po’lathon Shashlik & Somsa', 'phone' => '+998 90 000 00 00',
  'delivery_fee' => '10000', 'lat' => '41.2995', 'lng' => '69.2401',
  'promo_code' => 'POLAT5', 'promo_percent' => '5', 'accepting' => '1',
  'hero_text' => 'Mazali taomlar sizni kutmoqda!',
];

/* ============== YORDAMCHILAR ============== */
function is_mysql(): bool { return DB_NAME !== '' && DB_NAME !== 'DB_NAME'; }
function sql_today(): string { return is_mysql() ? 'CURDATE()' : "date('now')"; }

function json_out(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function fail(string $msg, int $code = 400): void { json_out(['ok' => false, 'error' => $msg], $code); }
function json_input(): array { return json_decode((string)file_get_contents('php://input'), true) ?: []; }

/* ============== BAZA ============== */
function schema_sql(): array {
  if (is_mysql()) {
    $e = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    return [
      "CREATE TABLE IF NOT EXISTS settings (skey VARCHAR(100) PRIMARY KEY, sval TEXT)$e",
      "CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, telegram_id BIGINT UNIQUE NOT NULL, name VARCHAR(200), username VARCHAR(100), created_at DATETIME DEFAULT CURRENT_TIMESTAMP)$e",
      "CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, icon VARCHAR(20), sort_order INT DEFAULT 0, active TINYINT DEFAULT 1)$e",
      "CREATE TABLE IF NOT EXISTS products (id INT AUTO_INCREMENT PRIMARY KEY, category_id INT, name VARCHAR(160) NOT NULL, description TEXT, price INT NOT NULL, image VARCHAR(500), featured TINYINT DEFAULT 0, available TINYINT DEFAULT 1, active TINYINT DEFAULT 1)$e",
      "CREATE TABLE IF NOT EXISTS extras (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, price INT DEFAULT 0, active TINYINT DEFAULT 1)$e",
      "CREATE TABLE IF NOT EXISTS orders (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, customer_name VARCHAR(150), phone VARCHAR(30), order_type VARCHAR(20), address TEXT, note TEXT, payment VARCHAR(20), subtotal INT, discount INT DEFAULT 0, delivery_fee INT DEFAULT 0, total INT, status VARCHAR(20) DEFAULT 'new', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, lat DOUBLE NULL, lng DOUBLE NULL, accepted_by BIGINT NULL, accepted_at DATETIME NULL)$e",
      "CREATE TABLE IF NOT EXISTS order_items (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT, product_id INT, product_name VARCHAR(160), price INT, qty INT, extras VARCHAR(300), line_total INT)$e",
      "CREATE TABLE IF NOT EXISTS staff (id INT AUTO_INCREMENT PRIMARY KEY, telegram_id BIGINT UNIQUE NOT NULL, name VARCHAR(100), active TINYINT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)$e",
    ];
  }
  return [
    "CREATE TABLE IF NOT EXISTS settings (skey TEXT PRIMARY KEY, sval TEXT)",
    "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, telegram_id INTEGER UNIQUE NOT NULL, name TEXT, username TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
    "CREATE TABLE IF NOT EXISTS categories (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, icon TEXT, sort_order INTEGER DEFAULT 0, active INTEGER DEFAULT 1)",
    "CREATE TABLE IF NOT EXISTS products (id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, name TEXT NOT NULL, description TEXT, price INTEGER NOT NULL, image TEXT, featured INTEGER DEFAULT 0, available INTEGER DEFAULT 1, active INTEGER DEFAULT 1)",
    "CREATE TABLE IF NOT EXISTS extras (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, price INTEGER DEFAULT 0, active INTEGER DEFAULT 1)",
    "CREATE TABLE IF NOT EXISTS orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, customer_name TEXT, phone TEXT, order_type TEXT, address TEXT, note TEXT, payment TEXT, subtotal INTEGER, discount INTEGER DEFAULT 0, delivery_fee INTEGER DEFAULT 0, total INTEGER, status TEXT DEFAULT 'new', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, lat DOUBLE, lng DOUBLE, accepted_by INTEGER, accepted_at DATETIME)",
    "CREATE TABLE IF NOT EXISTS order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, product_name TEXT, price INTEGER, qty INTEGER, extras TEXT, line_total INTEGER)",
    "CREATE TABLE IF NOT EXISTS staff (id INTEGER PRIMARY KEY AUTOINCREMENT, telegram_id INTEGER UNIQUE NOT NULL, name TEXT, active INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)",
  ];
}

/* Eski bazaga ustunlarni qo‘shish (yangi o‘rnatishda xato bo‘lsa e’tiborsiz qoldiriladi) */
function migrate_v2(): array {
  return [
    'ALTER TABLE orders ADD COLUMN lat DOUBLE',
    'ALTER TABLE orders ADD COLUMN lng DOUBLE',
    'ALTER TABLE orders ADD COLUMN accepted_by BIGINT',
    'ALTER TABLE orders ADD COLUMN accepted_at DATETIME',
  ];
}

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
  if (is_mysql()) {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opt);
  } else {
    if (!is_dir(DATA_DIR)) {
      mkdir(DATA_DIR, 0750, true);
      file_put_contents(DATA_DIR . '/.htaccess', "Require all denied\nDeny from all\n");
    }
    $pdo = new PDO('sqlite:' . DATA_DIR . '/restaurant.sqlite', null, null, $opt);
    $pdo->exec('PRAGMA foreign_keys = ON');
  }
  foreach (schema_sql() as $q) $pdo->exec($q);

  $ver = (int)$pdo->query("SELECT sval FROM settings WHERE skey = 'schema_v'")->fetchColumn();
  if ($ver < 2) {
    foreach (migrate_v2() as $q) { try { $pdo->exec($q); } catch (Throwable $e) { /* ustun bor */ } }
    setting_set_raw($pdo, 'schema_v', '2');
  }
  if (!(int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn()) seed($pdo);
  foreach (DEFAULTS as $k => $v) {
    $s = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE skey = ?');
    $s->execute([$k]);
    if (!(int)$s->fetchColumn()) setting_set_raw($pdo, $k, $v);
  }
  return $pdo;
}

function setting_set_raw(PDO $p, string $k, string $v): void {
  $p->prepare('DELETE FROM settings WHERE skey = ?')->execute([$k]);
  $p->prepare('INSERT INTO settings(skey, sval) VALUES(?, ?)')->execute([$k, $v]);
}

function settings_all(bool $reset = false): array {
  static $c = null;
  if ($c === null || $reset) {
    $c = [];
    foreach (db()->query('SELECT skey, sval FROM settings')->fetchAll() as $r) $c[$r['skey']] = (string)$r['sval'];
  }
  return $c;
}
function setting(string $k, string $def = ''): string { $a = settings_all(); return isset($a[$k]) ? $a[$k] : $def; }
function setting_set(string $k, string $v): void { setting_set_raw(db(), $k, $v); settings_all(true); }

function seed(PDO $p): void {
  $cats = [['Shashlik', '🍢'], ['Somsa', '🥟'], ['Kabob', '🍖'], ['Osh', '🍛'], ['Salatlar', '🥗'], ['Ichimliklar', '🥤'], ['Shirinliklar', '🍰']];
  $c = $p->prepare('INSERT INTO categories(name, icon, sort_order) VALUES(?,?,?)');
  foreach ($cats as $i => $x) $c->execute([$x[0], $x[1], $i + 1]);
  $prods = [
    [1, 'Qo‘y go‘shtidan shashlik', 'Yangi tayyorlangan, mazali va yumshoq', 28000, 1],
    [1, 'Mol go‘shtidan shashlik', 'Maxsus retsept bo‘yicha', 32000, 0],
    [1, 'Tovuq shashlik', 'Yengil va sersuv', 22000, 0],
    [2, 'Go‘shtli somsa', 'An’anaviy tandir somsasi', 15000, 1],
    [2, 'Qovoqli somsa', 'Mazali qovoq bilan', 13000, 0],
    [3, 'Lula kabob', 'Qo‘y go‘shti, ziravorlar bilan', 26000, 0],
    [4, 'Palov', 'An’anaviy o‘zbek palovi', 35000, 1],
    [4, 'Lag‘mon', 'Qo‘lda cho‘zilgan xamir, sabzavotli', 30000, 0],
    [5, 'Ko‘k salat', 'Yangi sabzavotlar', 12000, 0],
    [6, 'Ayron', 'Sovutilgan, 0.5 l', 8000, 0],
    [6, 'Coca-Cola', '0.5 l', 10000, 0],
    [7, 'Medovik', 'Uy tortisi', 18000, 0],
  ];
  $s = $p->prepare('INSERT INTO products(category_id, name, description, price, featured) VALUES(?,?,?,?,?)');
  foreach ($prods as $x) $s->execute($x);
  $e = $p->prepare('INSERT INTO extras(name, price) VALUES(?,?)');
  foreach ([['Piyoz (bepul)', 0], ['Achchiq sous', 2000], ['Non', 1000], ['Qo‘shimcha go‘sht', 15000]] as $x) $e->execute($x);
}

function public_cfg(): array {
  return [
    'name' => setting('name'), 'phone' => setting('phone'),
    'delivery_fee' => (int)setting('delivery_fee', '0'),
    'lat' => (float)setting('lat', '41.2995'), 'lng' => (float)setting('lng', '69.2401'),
    'promo_code' => strtoupper(setting('promo_code')), 'promo_percent' => (int)setting('promo_percent', '0'),
    'accepting' => setting('accepting', '1') === '1', 'hero_text' => setting('hero_text'),
  ];
}

/* ============== TELEGRAM ============== */
function bot_ok(): bool { return BOT_TOKEN !== 'BOT_TOKEN' && BOT_TOKEN !== ''; }
function admin_chat(): ?string { return is_numeric(ADMIN_CHAT_ID) ? ADMIN_CHAT_ID : null; }
function self_url(): string {
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
}
function app_url(): string { return rtrim(dirname(self_url()), '/') . '/'; }
function staff_url(): string { return app_url() . '?staff=1'; }

function tg_api(string $method, array $params): array {
  if (!bot_ok()) return ['ok' => false];
  $ch = curl_init('https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 6,
  ]);
  $raw = curl_exec($ch);
  curl_close($ch);
  $j = is_string($raw) ? json_decode($raw, true) : null;
  return is_array($j) ? $j : ['ok' => false];
}
function tg_send($chat, string $text, array $extra = []): void {
  if (!$chat) return;
  tg_api('sendMessage', ['chat_id' => $chat, 'text' => $text] + $extra);
}
function tg_open_btn(string $text, string $url): array {
  return ['reply_markup' => ['inline_keyboard' => [[['text' => $text, 'web_app' => ['url' => $url]]]]]];
}
function tg_location($chat, float $lat, float $lng): void {
  if (!$chat) return;
  tg_api('sendLocation', ['chat_id' => $chat, 'latitude' => $lat, 'longitude' => $lng]);
}

/* Birinchi so‘rovda webhook va menyu tugmasini avtomatik o‘rnatadi */
function ensure_bot_installed(): void {
  if (!bot_ok()) return;
  if (!is_dir(DATA_DIR)) @mkdir(DATA_DIR, 0750, true);
  $flag = DATA_DIR . '/install.json';
  $st = is_file($flag) ? (json_decode((string)file_get_contents($flag), true) ?: []) : [];
  if (!empty($st['done']) || (time() - (int)($st['last'] ?? 0)) < 600) return;
  $a = tg_api('setWebhook', ['url' => self_url(), 'drop_pending_updates' => true]);
  $b = tg_api('setChatMenuButton', ['menu_button' => ['type' => 'web_app', 'text' => 'Menyu', 'web_app' => ['url' => app_url()]]]);
  file_put_contents($flag, json_encode(['done' => !empty($a['ok']) && !empty($b['ok']), 'last' => time()]));
}

function handle_bot_update(array $upd): void {
  $msg = $upd['message'] ?? null;
  if (!$msg || !isset($msg['text']) || !bot_ok()) return;
  $cmd = explode('@', strtolower(explode(' ', trim((string)$msg['text']))[0]))[0];
  if ($cmd !== '/start' && $cmd !== '/menu') return;
  $rows = [[['text' => '🍽 Menyuni ochish', 'web_app' => ['url' => app_url()]]]];
  $tid = (int)($msg['from']['id'] ?? 0);
  if ($tid && staff_row($tid)) $rows[] = [['text' => '📋 Xodim paneli', 'web_app' => ['url' => staff_url()]]];
  tg_api('sendMessage', [
    'chat_id' => $msg['chat']['id'],
    'text' => 'Assalomu alaykum! ' . setting('name') . ' ga xush kelibsiz.',
    'reply_markup' => ['inline_keyboard' => $rows],
  ]);
}

/* ============== FOYDALANUVCHI / XODIM / ADMIN ============== */
function tg_user(): ?array {
  $init = $_SERVER['HTTP_X_TELEGRAM_INIT_DATA'] ?? '';
  if ($init !== '' && valid_init_data($init)) {
    parse_str($init, $p);
    $u = json_decode($p['user'] ?? '{}', true);
    if (!empty($u['id'])) return $u;
  }
  if (DEBUG) return ['id' => 999000001, 'first_name' => 'Demo', 'last_name' => 'mijoz', 'username' => null];
  return null;
}
function valid_init_data(string $init): bool {
  if (!bot_ok()) return false;
  parse_str($init, $d);
  if (!isset($d['hash'])) return false;
  $hash = $d['hash'];
  unset($d['hash']);
  if (empty($d['auth_date']) || abs(time() - (int)$d['auth_date']) > 86400) return false;
  ksort($d);
  $parts = [];
  foreach ($d as $k => $v) $parts[] = "$k=$v";
  $secret = hash_hmac('sha256', BOT_TOKEN, 'WebAppData', true);
  return hash_equals($hash, hash_hmac('sha256', implode("\n", $parts), $secret));
}
function upsert_user(array $tg): array {
  $pdo = db();
  $sql = is_mysql()
    ? 'INSERT INTO users(telegram_id, name, username) VALUES(?,?,?) ON DUPLICATE KEY UPDATE name = VALUES(name), username = VALUES(username)'
    : 'INSERT INTO users(telegram_id, name, username) VALUES(?,?,?) ON CONFLICT(telegram_id) DO UPDATE SET name = excluded.name, username = excluded.username';
  $pdo->prepare($sql)->execute([(int)$tg['id'], trim(($tg['first_name'] ?? '') . ' ' . ($tg['last_name'] ?? '')), $tg['username'] ?? null]);
  $s = $pdo->prepare('SELECT * FROM users WHERE telegram_id = ?');
  $s->execute([(int)$tg['id']]);
  return $s->fetch();
}
function staff_row(int $tid): ?array {
  $s = db()->prepare('SELECT * FROM staff WHERE telegram_id = ? AND active = 1');
  $s->execute([$tid]);
  $r = $s->fetch();
  return $r ?: null;
}
function staff_user(): array {
  $tg = tg_user();
  if (!$tg) fail('Telegram sessiyasi tasdiqlanmadi. Botdan oching.', 401);
  $s = staff_row((int)$tg['id']);
  if (!$s) fail('Siz xodim sifatida ro‘yxatda emassiz.', 403);
  return $s;
}
function active_staff(): array { return db()->query('SELECT * FROM staff WHERE active = 1')->fetchAll(); }
function admin_guard(): void {
  if (empty($_SESSION['admin'])) fail('Avval tizimga kiring', 401);
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_ADMIN'])) fail('Ruxsat yo‘q', 403);
}

/* ============== BUYURTMA YORDAMCHILARI ============== */
function with_items(array $orders): array {
  $st = db()->prepare('SELECT product_name, qty, extras, price FROM order_items WHERE order_id = ? ORDER BY id');
  foreach ($orders as &$o) { $st->execute([$o['id']]); $o['items'] = $st->fetchAll(); }
  unset($o);
  return $orders;
}
function notify_customer(int $oid, string $text): void {
  $s = db()->prepare('SELECT u.telegram_id FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?');
  $s->execute([$oid]);
  $tid = (int)($s->fetchColumn() ?: 0);
  if ($tid > 1000) tg_send($tid, $text);
}
function notify_new_order(int $oid, string $name, string $phone, string $type, string $addr, string $note,
                          string $pay, int $total, array $lines, ?float $lat, ?float $lng): void {
  $txt = "🆕 Yangi buyurtma #$oid\n"
       . "Mijoz: $name\nTelefon: $phone\n"
       . ($type === 'delivery' ? "Yetkazib berish: $addr\n" : "Olib ketish\n")
       . ($note !== '' ? "Izoh: $note\n" : '')
       . 'To‘lov: ' . ($pay === 'card' ? 'karta' : 'naqd') . "\nTaomlar:\n";
  foreach ($lines as $l) $txt .= "- {$l[3]}× {$l[1]}" . ($l[4] !== '' ? " ({$l[4]})" : '') . "\n";
  $txt .= 'Jami: ' . number_format($total, 0, '.', ' ') . ' ' . setting('currency', 'so‘m');
  if ($lat !== null && $lng !== null) $txt .= "\nXarita: https://www.google.com/maps?q=$lat,$lng";
  if ($c = admin_chat()) tg_send($c, $txt);
  foreach (active_staff() as $s) {
    $tid = (int)$s['telegram_id'];
    tg_send($tid, $txt, tg_open_btn('📋 Buyurtmalar', staff_url()));
    if ($lat !== null && $lng !== null) tg_location($tid, $lat, $lng);
  }
}

/* ============== API ============== */
function handle_api(string $a): void {
  $pdo = db();
  $method = $_SERVER['REQUEST_METHOD'];

  switch ($a) {
    /* ---- Mijoz ---- */
    case 'menu':
      json_out([
        'ok' => true,
        'cfg' => public_cfg(),
        'categories' => $pdo->query('SELECT id, name, icon FROM categories WHERE active = 1 ORDER BY sort_order, id')->fetchAll(),
        'products' => $pdo->query('SELECT p.id, p.category_id, p.name, p.description, p.price, p.image, p.featured
                                   FROM products p JOIN categories c ON c.id = p.category_id
                                   WHERE p.active = 1 AND p.available = 1 AND c.active = 1 ORDER BY p.featured DESC, p.id')->fetchAll(),
        'extras' => $pdo->query('SELECT id, name, price FROM extras WHERE active = 1 ORDER BY id')->fetchAll(),
      ]);

    case 'orders':
      $tg = tg_user();
      if (!$tg) fail('Telegram sessiyasi tasdiqlanmadi. Botdan oching.', 401);
      $u = upsert_user($tg);
      $st = $pdo->prepare('SELECT id, order_type, address, subtotal, discount, delivery_fee, total, status, created_at
                           FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 30');
      $st->execute([$u['id']]);
      json_out(['ok' => true, 'user' => ['name' => $u['name'], 'username' => $u['username']],
                'orders' => with_items($st->fetchAll())]);

    case 'order':
      if ($method !== 'POST') fail('Noto‘g‘ri so‘rov', 405);
      if (setting('accepting', '1') !== '1') fail('Hozir buyurtma qabul qilinmayapti.', 503);
      $tg = tg_user();
      if (!$tg) fail('Telegram sessiyasi tasdiqlanmadi. Botdan oching.', 401);
      $u = upsert_user($tg);
      $b = json_input();
      $items = is_array($b['items'] ?? null) ? $b['items'] : [];
      if (!$items || count($items) > 30) fail('Savat bo‘sh yoki mahsulotlar soni noto‘g‘ri.', 422);
      $type = ($b['order_type'] ?? 'delivery') === 'pickup' ? 'pickup' : 'delivery';
      $name = mb_substr(trim((string)($b['name'] ?? '')), 0, 100);
      $phone = mb_substr(trim((string)($b['phone'] ?? '')), 0, 30);
      $addr = mb_substr(trim((string)($b['address'] ?? '')), 0, 300);
      $note = mb_substr(trim((string)($b['note'] ?? '')), 0, 300);
      $pay = ($b['payment'] ?? 'cash') === 'card' ? 'card' : 'cash';
      $lat = isset($b['lat']) && is_numeric($b['lat']) ? (float)$b['lat'] : null;
      $lng = isset($b['lng']) && is_numeric($b['lng']) ? (float)$b['lng'] : null;
      if ($name === '') fail('Ismingizni kiriting.', 422);
      if (!preg_match('/^\+?[0-9 ()\-]{7,25}$/', $phone)) fail('Telefon raqamini to‘g‘ri kiriting.', 422);
      if ($type === 'delivery') {
        if ($addr === '') fail('Yetkazib berish manzilini kiriting.', 422);
        if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) fail('Xaritadan yetkazib berish joyini belgilang.', 422);
      } else {
        $lat = null; $lng = null;
      }

      $pdo->beginTransaction();
      try {
        $ids = array_values(array_unique(array_map(function ($x) { return (int)($x['id'] ?? 0); }, $items)));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT p.id, p.name, p.price FROM products p JOIN categories c ON c.id = p.category_id
                             WHERE p.active = 1 AND p.available = 1 AND c.active = 1 AND p.id IN ($ph)");
        $st->execute($ids);
        $map = [];
        foreach ($st->fetchAll() as $p) $map[(int)$p['id']] = $p;
        $extras = [];
        foreach ($pdo->query('SELECT id, name, price FROM extras WHERE active = 1')->fetchAll() as $e) $extras[(int)$e['id']] = $e;

        $subtotal = 0;
        $lines = [];
        foreach ($items as $it) {
          $id = (int)($it['id'] ?? 0);
          $qty = max(1, min(50, (int)($it['qty'] ?? 1)));
          if (!isset($map[$id])) throw new RuntimeException('Mahsulot mavjud emas yoki tugagan.');
          $price = (int)$map[$id]['price'];
          $exNames = [];
          foreach ((array)($it['extras'] ?? []) as $eid) {
            $eid = (int)$eid;
            if (isset($extras[$eid])) { $price += (int)$extras[$eid]['price']; $exNames[] = $extras[$eid]['name']; }
          }
          $subtotal += $price * $qty;
          $lines[] = [$id, $map[$id]['name'], $price, $qty, implode(', ', $exNames)];
        }
        $fee = $type === 'delivery' ? (int)setting('delivery_fee', '0') : 0;
        $code = strtoupper(trim((string)($b['promo'] ?? '')));
        $pct = ($code !== '' && $code === strtoupper(setting('promo_code'))) ? (int)setting('promo_percent', '0') : 0;
        $discount = (int)round($subtotal * $pct / 100);
        $total = $subtotal - $discount + $fee;

        $pdo->prepare('INSERT INTO orders(user_id, customer_name, phone, order_type, address, note, payment,
                       subtotal, discount, delivery_fee, total, status, lat, lng)
                       VALUES(?,?,?,?,?,?,?,?,?,?,?,\'new\',?,?)')
            ->execute([$u['id'], $name, $phone, $type, $addr, $note, $pay, $subtotal, $discount, $fee, $total, $lat, $lng]);
        $oid = (int)$pdo->lastInsertId();
        $si = $pdo->prepare('INSERT INTO order_items(order_id, product_id, product_name, price, qty, extras, line_total)
                             VALUES(?,?,?,?,?,?,?)');
        foreach ($lines as $l) $si->execute([$oid, $l[0], $l[1], $l[2], $l[3], $l[4], $l[2] * $l[3]]);
        $pdo->commit();

        notify_new_order($oid, $name, $phone, $type, $addr, $note, $pay, $total, $lines, $lat, $lng);
        json_out(['ok' => true, 'order_id' => $oid, 'total' => $total]);
      } catch (Throwable $e) {
        $pdo->rollBack();
        $msg = ($e instanceof RuntimeException) ? $e->getMessage() : (DEBUG ? $e->getMessage() : 'Buyurtmani saqlashda xatolik.');
        fail($msg, 422);
      }

    /* ---- Xodimlar ---- */
    case 'staff_orders':
      $me = staff_user();
      $rows = $pdo->query("SELECT o.id, o.customer_name, o.phone, o.order_type, o.address, o.note, o.payment, o.total,
                           o.status, o.created_at, o.lat, o.lng, o.accepted_by, s.name AS accepted_name
                           FROM orders o LEFT JOIN staff s ON s.telegram_id = o.accepted_by
                           WHERE o.status IN (" . ACTIVE_STATUSES . ") ORDER BY o.id DESC LIMIT 60")->fetchAll();
      json_out(['ok' => true, 'me' => ['name' => $me['name']], 'orders' => with_items($rows)]);

    case 'staff_accept':
      if ($method !== 'POST') fail('Noto‘g‘ri so‘rov', 405);
      $me = staff_user();
      $id = (int)(json_input()['id'] ?? 0);
      $st = $pdo->prepare("UPDATE orders SET status = 'accepted', accepted_by = ?, accepted_at = ? WHERE id = ? AND status = 'new'");
      $st->execute([(int)$me['telegram_id'], date('Y-m-d H:i:s'), $id]);
      if ($st->rowCount() < 1) fail('Bu buyurtma allaqachon qabul qilingan yoki topilmadi.', 409);
      notify_customer($id, "Buyurtma #$id qabul qilindi ✅ Tez orada tayyorlanadi.");
      if ($c = admin_chat()) tg_send($c, "✅ Buyurtma #$id xodim «{$me['name']}» tomonidan qabul qilindi.");
      json_out(['ok' => true]);

    /* ---- Admin ---- */
    case 'admin_login':
      if ($method !== 'POST') fail('Noto‘g‘ri so‘rov', 405);
      if (empty($_SERVER['HTTP_X_ADMIN'])) fail('Ruxsat yo‘q', 403);
      $b = json_input();
      $ok = hash_equals(ADMIN_USER, (string)($b['username'] ?? '')) && hash_equals(ADMIN_PASSWORD, (string)($b['password'] ?? ''));
      if (!$ok) { sleep(1); fail('Login yoki parol noto‘g‘ri.', 401); }
      session_regenerate_id(true);
      $_SESSION['admin'] = true;
      json_out(['ok' => true]);

    case 'admin_logout':
      $_SESSION = [];
      session_destroy();
      json_out(['ok' => true]);

    case 'admin_stats':
      admin_guard();
      $t = $pdo->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) s FROM orders
                        WHERE DATE(created_at) = " . sql_today() . " AND status <> 'cancelled'")->fetch();
      json_out(['ok' => true,
        'today_orders' => (int)$t['c'], 'today_revenue' => (int)$t['s'],
        'active' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN (" . ACTIVE_STATUSES . ")")->fetchColumn(),
        'done' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'done'")->fetchColumn(),
        'customers' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'staff' => (int)$pdo->query('SELECT COUNT(*) FROM staff WHERE active = 1')->fetchColumn(),
      ]);

    case 'admin_orders':
      admin_guard();
      $rows = $pdo->query('SELECT o.id, o.customer_name, o.phone, o.order_type, o.address, o.note, o.payment, o.total,
                           o.status, o.created_at, o.lat, o.lng, o.accepted_at, s.name AS accepted_name
                           FROM orders o LEFT JOIN staff s ON s.telegram_id = o.accepted_by
                           ORDER BY o.id DESC LIMIT 100')->fetchAll();
      json_out(['ok' => true, 'orders' => with_items($rows), 'statuses' => STATUSES]);

    case 'admin_order_status':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $st = (string)($b['status'] ?? '');
      if (!isset(STATUSES[$st])) fail('Noto‘g‘ri holat.', 422);
      $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$st, $id]);
      notify_customer($id, "Buyurtma #$id holati: " . STATUSES[$st]);
      json_out(['ok' => true]);

    case 'admin_order_accept':
      admin_guard();
      $id = (int)(json_input()['id'] ?? 0);
      $st = $pdo->prepare("UPDATE orders SET status = 'accepted', accepted_at = ? WHERE id = ? AND status = 'new'");
      $st->execute([date('Y-m-d H:i:s'), $id]);
      if ($st->rowCount() < 1) fail('Buyurtma topilmadi yoki allaqachon qabul qilingan.', 409);
      notify_customer($id, "Buyurtma #$id qabul qilindi ✅ Tez orada tayyorlanadi.");
      json_out(['ok' => true]);

    case 'admin_order_location':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $lat = $b['lat'] ?? null;
      $lng = $b['lng'] ?? null;
      if (!is_numeric($lat) || !is_numeric($lng) || abs((float)$lat) > 90 || abs((float)$lng) > 180) fail('Koordinata noto‘g‘ri.', 422);
      $lat = (float)$lat; $lng = (float)$lng;
      $st = $pdo->prepare('UPDATE orders SET lat = ?, lng = ? WHERE id = ?');
      $st->execute([$lat, $lng, $id]);
      $cur = $pdo->prepare('SELECT customer_name, phone, status FROM orders WHERE id = ?');
      $cur->execute([$id]);
      $o = $cur->fetch();
      if (!$o) fail('Buyurtma topilmadi.', 404);
      $txt = "📍 Buyurtma #$id manzili belgilandi.\nMijoz: {$o['customer_name']} · {$o['phone']}";
      if ($c = admin_chat()) { tg_send($c, $txt); tg_location($c, $lat, $lng); }
      foreach (active_staff() as $s) { tg_send((int)$s['telegram_id'], $txt); tg_location((int)$s['telegram_id'], $lat, $lng); }
      json_out(['ok' => true]);

    case 'admin_menu':
      admin_guard();
      json_out([
        'ok' => true,
        'categories' => $pdo->query('SELECT id, name, icon, sort_order, active FROM categories WHERE active = 1 ORDER BY sort_order, id')->fetchAll(),
        'products' => $pdo->query('SELECT p.*, c.name AS category FROM products p LEFT JOIN categories c ON c.id = p.category_id
                                   WHERE p.active = 1 ORDER BY p.id DESC')->fetchAll(),
        'extras' => $pdo->query('SELECT id, name, price FROM extras WHERE active = 1 ORDER BY id')->fetchAll(),
      ]);

    case 'admin_category_save':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $name = mb_substr(trim((string)($b['name'] ?? '')), 0, 100);
      if ($name === '') fail('Kategoriya nomini kiriting.', 422);
      $icon = mb_substr(trim((string)($b['icon'] ?? '🍽')), 0, 20);
      $sort = (int)($b['sort_order'] ?? 0);
      if ($id > 0) $pdo->prepare('UPDATE categories SET name=?, icon=?, sort_order=? WHERE id=?')->execute([$name, $icon, $sort, $id]);
      else $pdo->prepare('INSERT INTO categories(name, icon, sort_order, active) VALUES(?,?,?,1)')->execute([$name, $icon, $sort]);
      json_out(['ok' => true]);

    case 'admin_category_delete':
      admin_guard();
      $pdo->prepare('UPDATE categories SET active = 0 WHERE id = ?')->execute([(int)(json_input()['id'] ?? 0)]);
      json_out(['ok' => true]);

    case 'admin_product_save':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $name = mb_substr(trim((string)($b['name'] ?? '')), 0, 160);
      $price = (int)($b['price'] ?? 0);
      $img = trim((string)($b['image'] ?? ''));
      if ($name === '' || $price < 0) fail('Nomi va narxini to‘g‘ri kiriting.', 422);
      if ($img !== '' && !preg_match('#^(uploads/[a-f0-9]{24}\.(jpg|png|webp)|https?://[^\s]+)$#i', $img)) fail('Rasm noto‘g‘ri.', 422);
      $vals = [(int)($b['category_id'] ?? 0), $name, mb_substr(trim((string)($b['description'] ?? '')), 0, 500),
               $price, $img !== '' ? $img : null, !empty($b['featured']) ? 1 : 0, !empty($b['available']) ? 1 : 0];
      if ($id > 0) $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, image=?, featured=?, available=? WHERE id=?')->execute(array_merge($vals, [$id]));
      else $pdo->prepare('INSERT INTO products(category_id, name, description, price, image, featured, available, active) VALUES(?,?,?,?,?,?,?,1)')->execute($vals);
      json_out(['ok' => true]);

    case 'admin_product_delete':
      admin_guard();
      $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([(int)(json_input()['id'] ?? 0)]);
      json_out(['ok' => true]);

    case 'admin_extra_save':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $name = mb_substr(trim((string)($b['name'] ?? '')), 0, 100);
      $price = (int)($b['price'] ?? 0);
      if ($name === '' || $price < 0) fail('Qo‘shimcha nomini va narxini kiriting.', 422);
      if ($id > 0) $pdo->prepare('UPDATE extras SET name=?, price=? WHERE id=?')->execute([$name, $price, $id]);
      else $pdo->prepare('INSERT INTO extras(name, price, active) VALUES(?,?,1)')->execute([$name, $price]);
      json_out(['ok' => true]);

    case 'admin_extra_delete':
      admin_guard();
      $pdo->prepare('UPDATE extras SET active = 0 WHERE id = ?')->execute([(int)(json_input()['id'] ?? 0)]);
      json_out(['ok' => true]);

    case 'admin_upload':
      admin_guard();
      if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) fail('Fayl yuklanmadi.', 422);
      if ($_FILES['file']['size'] > 4 * 1024 * 1024) fail('Fayl 4 MB dan kichik bo‘lsin.', 422);
      $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['file']['tmp_name']);
      $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
      if (!$ext) fail('Faqat JPG, PNG yoki WEBP rasm.', 422);
      if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
      if (!is_file(UPLOAD_DIR . '/.htaccess')) file_put_contents(UPLOAD_DIR . '/.htaccess', "Options -Indexes\nphp_flag engine off\n");
      $name = bin2hex(random_bytes(12)) . '.' . $ext;
      if (!move_uploaded_file($_FILES['file']['tmp_name'], UPLOAD_DIR . '/' . $name)) fail('Saqlashda xatolik.', 500);
      json_out(['ok' => true, 'path' => 'uploads/' . $name]);

    case 'admin_staff':
      admin_guard();
      json_out(['ok' => true, 'max' => MAX_STAFF,
        'staff' => $pdo->query('SELECT id, telegram_id, name, active FROM staff ORDER BY active DESC, id')->fetchAll()]);

    case 'admin_staff_add':
      admin_guard();
      $b = json_input();
      $tid = preg_replace('/\D/', '', (string)($b['telegram_id'] ?? ''));
      if (strlen($tid) < 5 || strlen($tid) > 15) fail('Telegram ID noto‘g‘ri (faqat raqam).', 422);
      $name = trim((string)($b['name'] ?? ''));
      $name = $name === '' ? 'Xodim' : mb_substr($name, 0, 100);
      $ex = $pdo->prepare('SELECT * FROM staff WHERE telegram_id = ?');
      $ex->execute([(int)$tid]);
      $row = $ex->fetch();
      $cnt = (int)$pdo->query('SELECT COUNT(*) FROM staff WHERE active = 1')->fetchColumn();
      if ((!$row || (int)$row['active'] !== 1) && $cnt >= MAX_STAFF) fail('Xodimlar soni ko‘pi bilan ' . MAX_STAFF . ' ta.', 422);
      if ($row) $pdo->prepare('UPDATE staff SET name = ?, active = 1 WHERE telegram_id = ?')->execute([$name, (int)$tid]);
      else $pdo->prepare('INSERT INTO staff(telegram_id, name, active) VALUES(?,?,1)')->execute([(int)$tid, $name]);
      tg_send((int)$tid, 'Siz ' . setting('name') . ' xodimi etib tayinlandingiz. Buyurtmalarni quyidagi tugma orqali qabul qiling.',
              tg_open_btn('📋 Buyurtmalar', staff_url()));
      json_out(['ok' => true]);

    case 'admin_staff_save':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $active = !empty($b['active']) ? 1 : 0;
      if ($active) {
        $cur = $pdo->prepare('SELECT active FROM staff WHERE id = ?');
        $cur->execute([$id]);
        $was = (int)$cur->fetchColumn();
        $cnt = (int)$pdo->query('SELECT COUNT(*) FROM staff WHERE active = 1')->fetchColumn();
        if (!$was && $cnt >= MAX_STAFF) fail('Xodimlar soni ko‘pi bilan ' . MAX_STAFF . ' ta.', 422);
      }
      $name = mb_substr(trim((string)($b['name'] ?? 'Xodim')), 0, 100);
      $pdo->prepare('UPDATE staff SET name = ?, active = ? WHERE id = ?')->execute([$name, $active, $id]);
      json_out(['ok' => true]);

    case 'admin_staff_delete':
      admin_guard();
      $pdo->prepare('DELETE FROM staff WHERE id = ?')->execute([(int)(json_input()['id'] ?? 0)]);
      json_out(['ok' => true]);

    case 'admin_settings':
      admin_guard();
      json_out(['ok' => true, 'cfg' => public_cfg()]);

    case 'admin_settings_save':
      admin_guard();
      $b = json_input();
      $text = ['name', 'phone', 'promo_code', 'hero_text'];
      foreach ($text as $k) if (isset($b[$k])) setting_set($k, mb_substr(trim((string)$b[$k]), 0, 200));
      if (isset($b['delivery_fee'])) {
        if (!ctype_digit((string)$b['delivery_fee'])) fail('Yetkazib berish narxi raqam bo‘lsin.', 422);
        setting_set('delivery_fee', (string)(int)$b['delivery_fee']);
      }
      if (isset($b['promo_percent'])) {
        $p = (int)$b['promo_percent'];
        if ($p < 0 || $p > 100) fail('Chegirma 0 dan 100 gacha bo‘lsin.', 422);
        setting_set('promo_percent', (string)$p);
      }
      foreach (['lat' => 90, 'lng' => 180] as $k => $lim) {
        if (isset($b[$k])) {
          if (!is_numeric($b[$k]) || abs((float)$b[$k]) > $lim) fail('Koordinata noto‘g‘ri.', 422);
          setting_set($k, (string)(float)$b[$k]);
        }
      }
      if (array_key_exists('accepting', $b)) setting_set('accepting', !empty($b['accepting']) ? '1' : '0');
      json_out(['ok' => true]);
  }
  fail('Noma’lum so‘rov', 404);
}

/* ============== ISHGA TUSHIRISH ============== */
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
                             'secure' => !empty($_SERVER['HTTPS'])]);
  session_start();
}
if (isset($_GET['api'])) {
  try { handle_api((string)$_GET['api']); }
  catch (Throwable $e) {
    $detail = ($e instanceof PDOException || DEBUG) ? mb_substr($e->getMessage(), 0, 200) : 'PHP sozlamasini tekshiring';
    json_out(['ok' => false, 'error' => 'Server xatosi: ' . $detail], 500);
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['admin'])) {
  $raw = json_decode((string)file_get_contents('php://input'), true);
  if (is_array($raw) && isset($raw['update_id'])) {
    handle_bot_update($raw);
    http_response_code(200);
    echo 'OK';
    exit;
  }
}
ensure_bot_installed();
if (isset($_GET['admin'])) render_admin();
elseif (isset($_GET['staff'])) render_staff();
else render_app();

/* ==========================================================================
 *  SAHIFA 1: MIJOZ MINI APP
 * ========================================================================== */
function render_app(): void {
  header('Content-Type: text/html; charset=utf-8');
  echo <<<'HTML'
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">
<meta name="theme-color" content="#1d4ed8">
<title>Po’lathon Shashlik & Somsa</title>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
:root{--p:#1d4ed8;--p2:#3b82f6;--ink:#0f172a;--mut:#64748b;--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ok:#16a34a;--red:#dc2626;--r:20px}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.45 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
button,input,textarea{font:inherit;color:inherit}
button{cursor:pointer;border:0;background:none;padding:0}
.app{max-width:480px;margin:0 auto;min-height:100vh;padding-bottom:104px}
.hidden{display:none!important}
.hero{position:relative;height:270px;border-radius:0 0 32px 32px;overflow:hidden;color:#fff;background:linear-gradient(160deg,#1e3a8a,#1d4ed8 60%,#3b82f6)}
.hero .bg{position:absolute;inset:0;background-size:cover;background-position:center;opacity:.55;transform:scale(1.05)}
.hero::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,.15),rgba(15,23,42,.75))}
.hero .in{position:relative;z-index:2;padding:16px 20px}
.hbar{display:flex;align-items:center;gap:12px}
.logo{width:44px;height:44px;border-radius:14px;background:rgba(255,255,255,.95);display:grid;place-items:center;font-size:24px;color:var(--p);font-weight:900}
.hbar b{font-size:16px;letter-spacing:.2px}
.hbar small{display:block;opacity:.8;font-size:12px}
.hero h1{margin:58px 0 6px;font-size:28px;line-height:1.1;font-weight:800;text-shadow:0 2px 14px rgba(0,0,0,.25)}
.hero p{margin:0;opacity:.9}
.search{margin:-28px 16px 0;position:relative;z-index:3;background:#fff;border-radius:18px;box-shadow:0 14px 34px rgba(15,23,42,.14);display:flex;align-items:center;gap:10px;padding:0 16px;height:54px;cursor:pointer}
.search span{color:var(--mut)}
.search input{flex:1;border:0;outline:0;background:none;font-size:15px;pointer-events:none}
.promo{margin:16px;border-radius:20px;padding:18px;color:#fff;background:linear-gradient(120deg,#1d4ed8,#6366f1);display:flex;align-items:center;justify-content:space-between;gap:12px;box-shadow:0 12px 28px rgba(79,70,229,.3);cursor:pointer}
.promo b{font-size:16px;display:block}
.promo small{opacity:.9}
.promo .go{width:40px;height:40px;border-radius:50%;background:#fff;color:var(--p);display:grid;place-items:center;font-size:20px;flex:none;font-weight:700}
.section{padding:4px 16px 8px}
.sh{display:flex;justify-content:space-between;align-items:center;margin:18px 0 12px}
.sh h2{font-size:18px;margin:0;font-weight:800}
.link{color:var(--p);font-size:14px;font-weight:700;cursor:pointer}
.cats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.cat{background:var(--card);border-radius:18px;padding:14px 4px 12px;text-align:center;font-size:12px;font-weight:700;box-shadow:0 2px 8px rgba(15,23,42,.05);cursor:pointer}
.cat span{display:block;font-size:28px;margin-bottom:4px}
.item{display:flex;gap:12px;background:var(--card);border-radius:var(--r);padding:10px;margin-bottom:12px;box-shadow:0 2px 12px rgba(15,23,42,.06);cursor:pointer;align-items:center}
.thumb{width:96px;height:96px;border-radius:16px;flex:none;display:grid;place-items:center;font-size:44px;overflow:hidden;background:linear-gradient(135deg,#fde68a,#fb923c)}
.thumb img{width:100%;height:100%;object-fit:cover}
.item .info{flex:1;min-width:0}
.item h3{margin:0 0 4px;font-size:15px;line-height:1.25;font-weight:700}
.item p{margin:0;color:var(--mut);font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.price{color:var(--p);font-weight:800;margin-top:8px}
.addb{width:38px;height:38px;border-radius:14px;background:linear-gradient(135deg,var(--p),var(--p2));color:#fff;font-size:22px;display:grid;place-items:center;flex:none;box-shadow:0 8px 16px rgba(29,78,216,.3)}
.tabs{display:flex;gap:8px;overflow-x:auto;padding:0 16px 12px;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.chip{padding:9px 16px;border-radius:999px;background:#fff;font-weight:700;font-size:14px;white-space:nowrap;cursor:pointer;box-shadow:0 2px 6px rgba(15,23,42,.06)}
.chip.on{background:var(--p);color:#fff}
.bar{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;position:sticky;top:0;z-index:5;background:var(--bg)}
.bar h2{font-size:18px;margin:0;text-align:center;flex:1;font-weight:800}
.bar .sp{width:40px}
.ib{width:40px;height:40px;border-radius:14px;background:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 2px 8px rgba(15,23,42,.08)}
.pad{padding:0 16px}
.card{background:var(--card);border-radius:var(--r);padding:14px;box-shadow:0 2px 12px rgba(15,23,42,.06);margin-bottom:12px}
.ci{display:flex;gap:12px;align-items:center}
.ci .thumb{width:80px;height:80px;font-size:36px;border-radius:14px}
.ci .info{flex:1;min-width:0}
.ci h3{margin:0 0 4px;font-size:15px;font-weight:700}
.qty{display:inline-flex;align-items:center;background:#eef2ff;border-radius:12px;overflow:hidden}
.qty button{width:34px;height:34px;font-size:18px;font-weight:800;color:var(--p)}
.qty span{min-width:30px;text-align:center;font-weight:800}
.trash{width:38px;height:38px;border-radius:12px;color:var(--red);font-size:17px;display:grid;place-items:center;background:#fef2f2}
.row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;color:var(--mut)}
.row b{color:var(--ink)}
.total{display:flex;justify-content:space-between;align-items:center;padding-top:12px;margin-top:6px;border-top:1px dashed var(--line);font-weight:800;font-size:16px}
.total b{font-size:20px;color:var(--ink)}
.promoin{display:flex;gap:8px;align-items:center;background:#fff;border-radius:16px;padding:6px 6px 6px 14px;box-shadow:0 2px 10px rgba(15,23,42,.06);margin-bottom:12px}
.promoin input{flex:1;border:0;outline:0;background:none;text-transform:uppercase;padding:8px 0;font-size:15px}
.gobtn{width:42px;height:42px;border-radius:13px;background:var(--p);color:#fff;font-size:18px;flex:none;font-weight:800}
.seg{display:grid;grid-template-columns:1fr 1fr;gap:6px;background:#e2e8f0;border-radius:16px;padding:5px;margin-bottom:14px}
.seg button{padding:11px 8px;border-radius:12px;font-weight:700;color:#475569;font-size:14px}
.seg button.on{background:#fff;color:var(--p);box-shadow:0 2px 8px rgba(15,23,42,.12)}
.field{display:block;margin-bottom:12px}
.field span{display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:#334155}
.field input,.field textarea{width:100%;padding:13px 14px;border:1.5px solid var(--line);border-radius:14px;background:#fff;outline:0;font-size:15px}
.field input:focus,.field textarea:focus{border-color:var(--p2)}
.primary{display:block;width:100%;padding:16px;border-radius:18px;background:linear-gradient(120deg,var(--p),var(--p2));color:#fff;font-weight:800;font-size:16px;box-shadow:0 12px 24px rgba(29,78,216,.3);text-align:center}
.primary:disabled{opacity:.6}
.ghostbtn{display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:12px;background:#eef2ff;color:var(--p);font-weight:700;font-size:14px}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#f1f5f9;color:#475569;white-space:nowrap}
.badge.new{background:#fef3c7;color:#92400e}
.badge.accepted,.badge.cooking{background:#dbeafe;color:#1e40af}
.badge.ready,.badge.delivering{background:#dcfce7;color:#166534}
.badge.done{background:#e0e7ff;color:#3730a3}
.badge.cancelled{background:#fee2e2;color:#991b1b}
.ot{font-size:13px;color:var(--mut);margin-top:4px}
.oc h3{margin:0;font-size:16px;display:flex;justify-content:space-between;align-items:center;gap:8px}
.oc .items{margin:10px 0;font-size:14px;line-height:1.6}
.steps{margin:12px 0 0;padding:0;list-style:none}
.steps li{display:flex;gap:10px;align-items:center;padding:4px 0;font-size:13px;color:var(--mut)}
.steps li i{width:12px;height:12px;border-radius:50%;background:#cbd5e1;flex:none}
.steps li.on{color:var(--ink);font-weight:600}
.steps li.on i{background:var(--ok)}
.steps li.cur i{background:var(--p);box-shadow:0 0 0 4px rgba(29,78,216,.18)}
.empty{text-align:center;color:var(--mut);padding:44px 16px}
.empty span{font-size:52px;display:block;margin-bottom:8px}
.prof{text-align:center;padding:22px 0 6px}
.avatar{width:86px;height:86px;border-radius:50%;margin:0 auto 10px;background:linear-gradient(135deg,var(--p),var(--p2));color:#fff;display:grid;place-items:center;font-size:34px;font-weight:800}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}
.stat{background:#fff;border-radius:18px;padding:14px;text-align:center;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.stat b{display:block;font-size:19px}
.stat small{color:var(--mut)}
.mrow{display:flex;justify-content:space-between;align-items:center;padding:15px 4px;border-bottom:1px solid var(--line);cursor:pointer;font-weight:600}
.mrow:last-child{border:0}
.tabbar{position:fixed;left:50%;transform:translateX(-50%);bottom:0;width:100%;max-width:480px;background:rgba(255,255,255,.96);backdrop-filter:blur(10px);display:grid;grid-template-columns:repeat(5,1fr);padding:8px 4px calc(8px + env(safe-area-inset-bottom));box-shadow:0 -8px 24px rgba(15,23,42,.08);z-index:20;border-radius:22px 22px 0 0}
.tabbar button{display:flex;flex-direction:column;align-items:center;gap:2px;font-size:11px;color:#94a3b8;font-weight:700;position:relative;padding:4px 0}
.tabbar .ic{font-size:21px;line-height:1}
.tabbar button.on{color:var(--p)}
.tabbar .cnt{position:absolute;top:0;left:calc(50% + 6px);background:var(--red);color:#fff;border-radius:999px;font-size:10px;min-width:17px;height:17px;display:grid;place-items:center;padding:0 4px}
.sheetwrap{position:fixed;inset:0;background:rgba(15,23,42,.5);display:flex;align-items:flex-end;justify-content:center;z-index:40}
.sheet{width:100%;max-width:480px;background:#fff;border-radius:28px 28px 0 0;max-height:92vh;overflow:auto;animation:up .25s ease-out}
@keyframes up{from{transform:translateY(40px);opacity:.4}to{transform:none;opacity:1}}
.hero2{height:230px;display:grid;place-items:center;font-size:120px;background:linear-gradient(135deg,#fde68a,#fb923c);position:relative}
.hero2 img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.sheet .close{position:absolute;top:14px;right:14px;width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.95);font-size:16px;z-index:2;box-shadow:0 2px 8px rgba(0,0,0,.15)}
.sheet .body{padding:20px 20px 24px}
.sheet h2{margin:0 0 6px;font-size:22px;font-weight:800}
.sheet .desc{color:var(--mut);margin:0 0 14px}
.sheet .big{color:var(--p);font-size:22px;font-weight:800}
.sheet h4{margin:18px 0 6px;font-size:15px}
.chk{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--line);cursor:pointer}
.chk input{width:20px;height:20px;accent-color:var(--p)}
.chk span{flex:1}
.chk em{font-style:normal;color:var(--mut);font-size:13px}
.row2{display:flex;align-items:center;justify-content:space-between;margin:16px 0}
.map{height:250px;border-radius:20px;overflow:hidden;margin-bottom:10px;background:#e2e8f0;position:relative;box-shadow:0 2px 10px rgba(15,23,42,.08)}
.mapfb{height:100%;display:grid;place-items:center;text-align:center;padding:16px;color:var(--mut);font-size:14px}
.pin{position:absolute;left:50%;top:50%;transform:translate(-50%,-100%);font-size:34px;pointer-events:none;z-index:500}
.geo{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px}
.geo small{color:var(--mut);font-size:13px}
.toast{position:fixed;left:50%;bottom:112px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:12px 18px;border-radius:16px;font-size:14px;z-index:60;opacity:0;transition:.25s;pointer-events:none;max-width:90%;text-align:center}
.toast.show{opacity:1}
.closed{background:#fef2f2;color:#991b1b;border-radius:16px;padding:12px 14px;margin-bottom:12px;font-weight:600}
</style>
</head>
<body>
<div class="app" id="app"></div>
<nav class="tabbar hidden" id="tabbar"></nav>
<div class="sheetwrap hidden" id="sheet"></div>
<div class="toast" id="toast"></div>
<script>
const tg = window.Telegram && Telegram.WebApp;
if (tg) { tg.ready(); tg.expand(); }
const INIT = tg ? tg.initData : '';
const $ = (s, r = document) => r.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m';
const ld = (k, d) => { try { return JSON.parse(localStorage.getItem('pl_' + k)) ?? d; } catch (e) { return d; } };
const sv = (k, v) => { try { localStorage.setItem('pl_' + k, JSON.stringify(v)); } catch (e) {} };
const LBL = {new:'Yangi', accepted:'Qabul qilindi', cooking:'Tayyorlanmoqda', ready:'Tayyor', delivering:'Yo‘lda', done:'Yakunlandi', cancelled:'Bekor qilindi'};
const FLOW = ['new', 'accepted', 'cooking', 'ready', 'delivering', 'done'];

const S = {
  tab: 'home', cfg: {name:'', delivery_fee: 0, lat: 41.2995, lng: 69.2401, promo_code: '', promo_percent: 0, accepting: true, hero_text: '', phone: ''},
  cats: [], products: [], extras: [], cart: ld('cart', []), catId: 0, q: '',
  orderType: 'delivery', payment: 'cash', promo: '', promoOk: false,
  orders: null, user: null, ordersTab: 'active', geo: null, map: null, marker: null,
};

async function api(name, body) {
  const opt = { method: body ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json', 'X-Telegram-Init-Data': INIT } };
  if (body) opt.body = JSON.stringify(body);
  const r = await fetch('?api=' + encodeURIComponent(name), opt);
  const text = await r.text();
  let j;
  try { j = JSON.parse(text); }
  catch (e) {
    const snip = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
    throw new Error('Server xatosi (HTTP ' + r.status + '): ' + (snip || 'javob bo‘sh'));
  }
  if (!j.ok) throw new Error(j.error || 'Xatolik');
  return j;
}

const prod = id => S.products.find(p => p.id == id);
const exById = id => S.extras.find(e => e.id == id);
const keyOf = (id, ex) => id + '|' + [...ex].sort((a, b) => a - b).join(',');
const unitPrice = it => { const p = prod(it.id); if (!p) return 0; return Number(p.price) + it.extras.reduce((s, e) => s + Number(exById(e)?.price || 0), 0); };
const subtotal = () => S.cart.reduce((s, i) => s + unitPrice(i) * i.qty, 0);
const promoPct = () => S.promoOk ? Number(S.cfg.promo_percent) : 0;
const discountAmt = () => Math.round(subtotal() * promoPct() / 100);
const deliveryAmt = () => (S.orderType === 'delivery' && S.cart.length) ? Number(S.cfg.delivery_fee) : 0;
const totalAmt = () => subtotal() - discountAmt() + deliveryAmt();
const cartCount = () => S.cart.reduce((s, i) => s + i.qty, 0);
const iconOf = id => S.cats.find(c => c.id == id)?.icon || '🍽';

function toast(msg) {
  const t = $('#toast'); t.textContent = msg; t.classList.add('show');
  clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove('show'), 2000);
}
function thumb(p) {
  return `<div class="thumb">${p.image ? `<img loading="lazy" src="${esc(p.image)}" alt="">` : esc(iconOf(p.category_id))}</div>`;
}
function emptyHTML(icon, text) { return `<div class="empty"><span>${icon}</span>${esc(text)}</div>`; }

/* ---------- VIEWS ---------- */
function homeHTML() {
  const feat = S.products.filter(p => p.featured == 1);
  const list = (feat.length ? feat : S.products).slice(0, 6);
  const heroImg = S.products.find(p => p.image)?.image;
  return `
  <section class="hero">
    ${heroImg ? `<div class="bg" style="background-image:url('${esc(heroImg)}')"></div>` : ''}
    <div class="in">
      <div class="hbar"><div class="logo">P</div><div><b>${esc(S.cfg.name)}</b><small>Shashlik • Somsa • Milliy taomlar</small></div></div>
      <h1>${esc(S.cfg.hero_text || 'Mazali taomlar sizni kutmoqda!')}</h1>
      <p>Eng yaxshi taomlar bir joyda</p>
    </div>
  </section>
  <div class="search" data-act="gomenu"><span>🔍</span><input placeholder="Taom yoki kategoriya qidirish..." readonly></div>
  ${S.cfg.promo_code ? `<div class="promo" data-act="gomenu">
    <div><b>Birinchi buyurtmaga ${S.cfg.promo_percent}% chegirma!</b><small>Promo-kod: ${esc(S.cfg.promo_code)}</small></div>
    <div class="go">›</div></div>` : ''}
  <div class="section">
    <div class="sh"><h2>Kategoriyalar</h2><span class="link" data-act="cat" data-v="0">Barchasi</span></div>
    <div class="cats">${S.cats.map(c => `<div class="cat" data-act="cat" data-v="${c.id}"><span>${esc(c.icon)}</span>${esc(c.name)}</div>`).join('')}</div>
    <div class="sh"><h2>Ommabop taomlar</h2><span class="link" data-act="gomenu">Barchasi</span></div>
    ${list.map(itemHTML).join('') || emptyHTML('🍽', 'Hozircha taomlar yo‘q')}
  </div>`;
}
function itemHTML(p) {
  return `<div class="item" data-act="open" data-v="${p.id}">
    ${thumb(p)}
    <div class="info"><h3>${esc(p.name)}</h3><p>${esc(p.description)}</p><div class="price">${money(p.price)}</div></div>
    <button class="addb" data-act="quick" data-v="${p.id}" aria-label="Qo‘shish">+</button>
  </div>`;
}
function menuListHTML() {
  const q = S.q.trim().toLowerCase();
  const list = S.products.filter(p => (!S.catId || p.category_id == S.catId) &&
    (!q || p.name.toLowerCase().includes(q) || (p.description || '').toLowerCase().includes(q)));
  return list.map(itemHTML).join('') || emptyHTML('🔎', 'Hech narsa topilmadi');
}
function menuHTML() {
  return `<div class="bar"><span class="sp"></span><h2>Menyu</h2><span class="sp"></span></div>
  <div class="pad"><div class="search" style="margin:0 0 14px;box-shadow:0 2px 10px rgba(15,23,42,.06)"><span>🔍</span>
    <input id="q" placeholder="Qidirish..." value="${esc(S.q)}" autocomplete="off" style="pointer-events:auto"></div></div>
  <div class="tabs">
    <span class="chip ${!S.catId ? 'on' : ''}" data-act="cat" data-v="0">Barchasi</span>
    ${S.cats.map(c => `<span class="chip ${S.catId == c.id ? 'on' : ''}" data-act="cat" data-v="${c.id}">${esc(c.icon)} ${esc(c.name)}</span>`).join('')}
  </div>
  <div class="pad" id="list">${menuListHTML()}</div>`;
}

function cartHTML() {
  if (!S.cart.length) return `<div class="bar"><span class="sp"></span><h2>Savat</h2><span class="sp"></span></div>
    <div class="pad">${emptyHTML('🛒', 'Savat bo‘sh. Taom qo‘shing!')}
    <button class="primary" data-act="tab" data-v="menu">Menyuga o‘tish</button></div>`;
  const items = S.cart.map(i => {
    const p = prod(i.id); if (!p) return '';
    const ex = i.extras.map(e => exById(e)?.name).filter(Boolean).join(', ');
    return `<div class="card ci">${thumb(p)}
      <div class="info"><h3>${esc(p.name)}</h3>${ex ? `<div class="ot">+ ${esc(ex)}</div>` : ''}
        <div class="price" style="margin:6px 0 10px">${money(unitPrice(i))}</div>
        <div class="qty"><button data-act="qty" data-v="${esc(i.key)}" data-d="-1">−</button><span>${i.qty}</span><button data-act="qty" data-v="${esc(i.key)}" data-d="1">+</button></div>
      </div>
      <button class="trash" data-act="del" data-v="${esc(i.key)}" aria-label="O‘chirish">🗑</button></div>`;
  }).join('');
  return `<div class="bar"><span class="sp"></span><h2>Savat</h2><span class="sp"></span></div>
  <div class="pad">
    ${!S.cfg.accepting ? '<div class="closed">Hozir buyurtma qabul qilinmayapti</div>' : ''}
    ${items}
    <div class="seg" style="margin-top:6px">
      <button class="${S.orderType === 'delivery' ? 'on' : ''}" data-act="otype" data-v="delivery">🛵 Yetkazib berish</button>
      <button class="${S.orderType === 'pickup' ? 'on' : ''}" data-act="otype" data-v="pickup">🏃 Olib ketish</button>
    </div>
    <div class="promoin"><span>🏷</span>
      <input id="promo" placeholder="Promo-kod kiriting" value="${esc(S.promo)}" autocomplete="off">
      <button class="gobtn" data-act="promo">→</button></div>
    ${S.promoOk ? `<div class="ot" style="color:var(--ok);margin:-4px 4px 12px;font-weight:700">Promo-kod qo‘llandi: −${S.cfg.promo_percent}%</div>` : ''}
    <div class="card">
      <div class="row">Mahsulotlar <b>${money(subtotal())}</b></div>
      <div class="row">Yetkazib berish <b>${money(deliveryAmt())}</b></div>
      ${discountAmt() ? `<div class="row">Chegirma <b style="color:var(--ok)">−${money(discountAmt())}</b></div>` : ''}
      <div class="total"><span>Jami</span><b>${money(totalAmt())}</b></div>
    </div>
    <button class="primary" data-act="checkout" ${!S.cfg.accepting ? 'disabled' : ''}>Buyurtma berish →</button>
  </div>`;
}

function checkoutHTML() {
  const delivery = S.orderType === 'delivery';
  return `<div class="bar"><button class="ib" data-act="tab" data-v="cart">‹</button><h2>Rasmiylashtirish</h2><span class="sp"></span></div>
  <div class="pad">
    <div class="card">
      <div class="row">Buyurtma turi <b>${delivery ? 'Yetkazib berish' : 'Olib ketish'}</b></div>
      <div class="row">Taomlar soni <b>${cartCount()} ta</b></div>
      <div class="total"><span>To‘lov jami</span><b>${money(totalAmt())}</b></div>
    </div>
    <label class="field"><span>Ism</span><input id="f_name" value="${esc(S.user?.name || '')}" autocomplete="name"></label>
    <label class="field"><span>Telefon</span><input id="f_phone" placeholder="+998 90 123 45 67" inputmode="tel" autocomplete="tel"></label>
    ${delivery ? `
    <label class="field"><span>Manzil (ko‘cha, uy, xonadon)</span><textarea id="f_addr" rows="2"></textarea></label>
    <div class="field"><span>Yetkazib berish joyi</span>
      <div class="map" id="mapbox"><div class="mapfb">Xarita yuklanmoqda…</div></div>
      <div class="geo"><small id="geoTxt">${S.geo ? `📍 ${S.geo.lat}, ${S.geo.lng}` : 'Xaritada joyni belgilang yoki “Joriy joylashuv” ni bosing'}</small>
        <button class="ghostbtn" data-act="locate">📍 Joriy joylashuv</button></div>
    </div>` : ''}
    <label class="field"><span>Izoh (ixtiyoriy)</span><input id="f_note" placeholder="Masalan: eshik oldida"></label>
    <div class="seg">
      <button class="${S.payment === 'cash' ? 'on' : ''}" data-act="pay" data-v="cash">💵 Naqd</button>
      <button class="${S.payment === 'card' ? 'on' : ''}" data-act="pay" data-v="card">💳 Karta</button>
    </div>
    <button class="primary" id="submitBtn" data-act="submit">Buyurtmani tasdiqlash</button>
  </div>`;
}

function ordersHTML() {
  if (!S.orders) return `<div class="bar"><span class="sp"></span><h2>Buyurtmalarim</h2><span class="sp"></span></div><div class="pad">${emptyHTML('⏳', 'Yuklanmoqda...')}</div>`;
  const active = S.orders.filter(o => !['done', 'cancelled'].includes(o.status));
  const hist = S.orders.filter(o => ['done', 'cancelled'].includes(o.status));
  const list = S.ordersTab === 'active' ? active : hist;
  return `<div class="bar"><span class="sp"></span><h2>Buyurtmalarim</h2><span class="sp"></span></div>
  <div class="pad">
    <div class="seg">
      <button class="${S.ordersTab === 'active' ? 'on' : ''}" data-act="ordtab" data-v="active">Faol (${active.length})</button>
      <button class="${S.ordersTab === 'history' ? 'on' : ''}" data-act="ordtab" data-v="history">Tarix (${hist.length})</button>
    </div>
    ${list.map(orderHTML).join('') || emptyHTML('🧾', S.ordersTab === 'active' ? 'Faol buyurtmalar yo‘q' : 'Tarix bo‘sh')}
  </div>`;
}
function orderHTML(o) {
  const items = (o.items || []).map(i => `${i.qty} × ${esc(i.product_name)}${i.extras ? ` <span class="ot">(${esc(i.extras)})</span>` : ''}`).join('<br>');
  const idx = FLOW.indexOf(o.status);
  const steps = o.status === 'cancelled' ? '' : `<ul class="steps">${FLOW.map((s, i) =>
    `<li class="${i <= idx ? 'on' : ''} ${i === idx ? 'cur' : ''}"><i></i>${LBL[s]}</li>`).join('')}</ul>`;
  return `<div class="card oc">
    <h3>#${o.id} <span class="badge ${esc(o.status)}">${esc(LBL[o.status] || o.status)}</span></h3>
    <div class="ot">${esc(o.created_at)} · ${o.order_type === 'delivery' ? 'Yetkazib berish' : 'Olib ketish'}</div>
    <div class="items">${items}</div>
    <div class="row" style="padding:0"><span>Jami</span><b>${money(o.total)}</b></div>
    ${steps}
  </div>`;
}
function profileHTML() {
  const o = S.orders || [];
  const spent = o.filter(x => x.status !== 'cancelled').reduce((s, x) => s + Number(x.total), 0);
  const name = S.user?.name || 'Mehmon';
  return `<div class="bar"><span class="sp"></span><h2>Profil</h2><span class="sp"></span></div>
  <div class="pad">
    <div class="prof"><div class="avatar">${esc(name.charAt(0).toUpperCase())}</div>
      <b style="font-size:18px">${esc(name)}</b>
      <div class="ot">${S.user?.username ? '@' + esc(S.user.username) : 'Telegram foydalanuvchisi'}</div></div>
    <div class="stats">
      <div class="stat"><b>${o.length}</b><small>Buyurtmalar</small></div>
      <div class="stat"><b>${money(spent)}</b><small>Jami xarid</small></div>
    </div>
    <div class="card" style="padding:4px 16px">
      <div class="mrow" data-act="ordtab" data-v="history">Buyurtmalar tarixi <span>›</span></div>
      <div class="mrow" data-act="tab" data-v="orders">Faol buyurtmalar <span>›</span></div>
      <div class="mrow" data-act="tab" data-v="menu">Menyu <span>›</span></div>
      ${S.cfg.phone ? `<div class="mrow"><a href="tel:${esc(S.cfg.phone.replace(/\s/g, ''))}" style="color:inherit;text-decoration:none">Aloqa: ${esc(S.cfg.phone)}</a><span>›</span></div>` : ''}
    </div>
    <div class="ot" style="text-align:center;margin-top:18px">${esc(S.cfg.name)}</div>
  </div>`;
}

/* ---------- ROUTER ---------- */
const TABS = [['home', '🏠', 'Bosh sahifa'], ['menu', '🍽', 'Menyu'], ['cart', '🛒', 'Savat'], ['orders', '🧾', 'Buyurtmalar'], ['profile', '👤', 'Profil']];
function renderTabbar() {
  const bar = $('#tabbar');
  bar.classList.toggle('hidden', S.tab === 'checkout');
  bar.innerHTML = TABS.map(([k, ic, lb]) => `<button class="${S.tab === k ? 'on' : ''}" data-act="tab" data-v="${k}">
    <span class="ic">${ic}</span>${lb}${k === 'cart' && cartCount() ? `<span class="cnt">${cartCount()}</span>` : ''}</button>`).join('');
}
function render() {
  const H = {home: homeHTML, menu: menuHTML, cart: cartHTML, checkout: checkoutHTML, orders: ordersHTML, profile: profileHTML}[S.tab];
  const y = window.scrollY;
  $('#app').innerHTML = S.tab === 'home' ? H() : `<div style="padding-top:6px">${H()}</div>`;
  renderTabbar();
  if (S.tab === 'checkout') initMap(); else window.scrollTo(0, S.tab === 'home' ? y : 0);
}
function go(tab) {
  S.tab = tab;
  if (tab === 'orders' || tab === 'profile') loadOrders();
  render();
}

/* ---------- XARITA ---------- */
function setGeo(lat, lng) {
  S.geo = { lat: +lat.toFixed(6), lng: +lng.toFixed(6) };
  const t = $('#geoTxt');
  if (t) t.textContent = `📍 ${S.geo.lat}, ${S.geo.lng}`;
}
function initMap() {
  const box = $('#mapbox');
  if (!box) return;
  if (S.map) { S.map.remove(); S.map = null; }
  if (typeof L === 'undefined') {
    box.innerHTML = '<div class="mapfb">Xarita yuklanmadi. “Joriy joylashuv” tugmasidan foydalaning.</div>';
    return;
  }
  const c = S.geo ? [S.geo.lat, S.geo.lng] : [S.cfg.lat, S.cfg.lng];
  S.map = L.map(box, { zoomControl: false, attributionControl: false }).setView(c, S.geo ? 17 : 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(S.map);
  S.marker = L.marker(c, { draggable: true }).addTo(S.map);
  S.marker.on('dragend', () => { const p = S.marker.getLatLng(); setGeo(p.lat, p.lng); });
  S.map.on('click', e => { S.marker.setLatLng(e.latlng); setGeo(e.latlng.lat, e.latlng.lng); });
  setTimeout(() => S.map && S.map.invalidateSize(), 250);
}
function locate() {
  if (!navigator.geolocation) return toast('Joylashuv qo‘llab-quvvatlanmaydi');
  navigator.geolocation.getCurrentPosition(p => {
    const { latitude: la, longitude: lo } = p.coords;
    setGeo(la, lo);
    if (S.map) { S.map.setView([la, lo], 17); S.marker.setLatLng([la, lo]); }
    toast('Joylashuv belgilandi ✓');
  }, () => toast('Joylashuvni aniqlab bo‘lmadi. Xaritadan belgilang'), { enableHighAccuracy: true, timeout: 10000 });
}

/* ---------- SAVAT ---------- */
function addToCart(id, extras = [], qty = 1) {
  const key = keyOf(id, extras);
  const ex = S.cart.find(i => i.key === key);
  if (ex) ex.qty = Math.min(50, ex.qty + qty);
  else S.cart.push({ key, id: Number(id), extras: extras.map(Number).sort((a, b) => a - b), qty });
  sv('cart', S.cart);
  toast('Savatga qo‘shildi ✓');
  renderTabbar();
}
function changeQty(key, d) {
  const it = S.cart.find(i => i.key === key);
  if (!it) return;
  it.qty += d;
  if (it.qty < 1) S.cart = S.cart.filter(i => i !== it);
  sv('cart', S.cart);
  render();
}

/* ---------- MAHSULOT OYNASI ---------- */
let SH = null;
function openProduct(id) {
  const p = prod(id); if (!p) return;
  SH = { id: p.id, qty: 1, extras: new Set() };
  renderSheet();
  $('#sheet').classList.remove('hidden');
}
function renderSheet() {
  const p = prod(SH.id);
  const unit = Number(p.price) + [...SH.extras].reduce((s, e) => s + Number(exById(e)?.price || 0), 0);
  $('#sheet').innerHTML = `<div class="sheet" role="dialog">
    <div class="hero2">${p.image ? `<img src="${esc(p.image)}" alt="">` : esc(iconOf(p.category_id))}
      <button class="close" data-act="closesheet" aria-label="Yopish">✕</button></div>
    <div class="body">
      <h2>${esc(p.name)}</h2>
      <p class="desc">${esc(p.description)}</p>
      <div class="big">${money(p.price)}</div>
      ${S.extras.length ? '<h4>Qo‘shimchalar</h4>' + S.extras.map(e => `
        <label class="chk"><input type="checkbox" data-act="ex" data-v="${e.id}" ${SH.extras.has(e.id) ? 'checked' : ''}>
          <span>${esc(e.name)}</span><em>${Number(e.price) ? '+' + money(e.price) : ''}</em></label>`).join('') : ''}
      <div class="row2">
        <div class="qty"><button data-act="pdqty" data-d="-1">−</button><span>${SH.qty}</span><button data-act="pdqty" data-d="1">+</button></div>
        <b style="font-size:18px">${money(unit * SH.qty)}</b>
      </div>
      <button class="primary" data-act="pdadd">Savatga qo‘shish · ${money(unit * SH.qty)}</button>
    </div></div>`;
}
function closeSheet() { $('#sheet').classList.add('hidden'); SH = null; }

/* ---------- BUYURTMA YUBORISH ---------- */
async function submitOrder() {
  const val = id => ($('#' + id)?.value || '').trim();
  const body = {
    items: S.cart.map(i => ({ id: i.id, qty: i.qty, extras: i.extras })),
    order_type: S.orderType, name: val('f_name'), phone: val('f_phone'), address: val('f_addr'),
    note: val('f_note'), payment: S.payment, promo: S.promoOk ? S.promo : '',
    lat: S.orderType === 'delivery' && S.geo ? S.geo.lat : null,
    lng: S.orderType === 'delivery' && S.geo ? S.geo.lng : null,
  };
  if (!body.name) return toast('Ismingizni kiriting');
  if (!/^\+?[0-9 ()\-]{7,25}$/.test(body.phone)) return toast('Telefon raqamini to‘g‘ri kiriting');
  if (S.orderType === 'delivery' && !body.address) return toast('Manzilni kiriting');
  if (S.orderType === 'delivery' && !S.geo) return toast('Xaritadan yetkazib berish joyini belgilang');
  const btn = $('#submitBtn'); btn.disabled = true; btn.textContent = 'Yuborilmoqda…';
  try {
    const r = await api('order', body);
    S.cart = []; sv('cart', S.cart);
    S.promo = ''; S.promoOk = false; S.orders = null; S.geo = null;
    toast('Buyurtma #' + r.order_id + ' qabul qilindi 🎉');
    go('orders');
  } catch (e) {
    toast(e.message);
    btn.disabled = false; btn.textContent = 'Buyurtmani tasdiqlash';
  }
}

async function loadOrders() {
  try {
    const r = await api('orders');
    S.orders = r.orders; S.user = r.user;
    if (S.tab === 'orders' || S.tab === 'profile') render();
  } catch (e) {
    S.orders = S.orders || [];
    if (S.tab === 'orders') $('#app').innerHTML = `<div class="pad">${emptyHTML('🔒', e.message)}</div>`;
  }
}

/* ---------- HODISALAR ---------- */
document.addEventListener('click', e => {
  if (e.target.id === 'sheet') return closeSheet();
  const el = e.target.closest('[data-act]');
  if (!el) return;
  const a = el.dataset.act, v = el.dataset.v;
  switch (a) {
    case 'tab': go(v); break;
    case 'gomenu': S.catId = 0; go('menu'); break;
    case 'cat': S.catId = Number(v); if (S.tab !== 'menu') go('menu'); else render(); break;
    case 'open': openProduct(Number(v)); break;
    case 'quick': addToCart(Number(v)); renderTabbar(); break;
    case 'qty': changeQty(v, Number(el.dataset.d)); break;
    case 'del': S.cart = S.cart.filter(i => i.key !== v); sv('cart', S.cart); render(); break;
    case 'otype': S.orderType = v; render(); break;
    case 'promo': {
      const code = ($('#promo')?.value || '').trim().toUpperCase();
      S.promo = code;
      S.promoOk = !!code && code === S.cfg.promo_code;
      toast(S.promoOk ? 'Promo-kod qo‘llandi ✓' : 'Bunday promo-kod yo‘q');
      render(); break;
    }
    case 'checkout':
      if (!S.cart.length) return toast('Savat bo‘sh');
      go('checkout'); break;
    case 'pay': S.payment = v; render(); break;
    case 'locate': locate(); break;
    case 'submit': submitOrder(); break;
    case 'ordtab': S.ordersTab = v; if (S.tab !== 'orders') go('orders'); else render(); break;
    case 'closesheet': closeSheet(); break;
    case 'pdqty': SH.qty = Math.max(1, Math.min(50, SH.qty + Number(el.dataset.d))); renderSheet(); break;
    case 'pdadd': addToCart(SH.id, [...SH.extras], SH.qty); closeSheet(); break;
  }
});
document.addEventListener('change', e => {
  if (e.target.dataset.act === 'ex') {
    const id = Number(e.target.dataset.v);
    e.target.checked ? SH.extras.add(id) : SH.extras.delete(id);
    renderSheet();
  }
});
document.addEventListener('input', e => {
  if (e.target.id === 'q') { S.q = e.target.value; $('#list').innerHTML = menuListHTML(); }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && e.target.id === 'promo') document.querySelector('[data-act="promo"]').click();
  if (e.key === 'Escape') closeSheet();
});

/* ---------- START ---------- */
async function boot() {
  try {
    const r = await api('menu');
    S.cfg = r.cfg; S.cats = r.categories; S.products = r.products; S.extras = r.extras;
  } catch (e) {
    $('#app').innerHTML = `<div class="pad" style="padding-top:40px">${emptyHTML('⚠️', e.message)}</div>`;
    return;
  }
  render();
  loadOrders();
  setInterval(() => { if (S.tab === 'orders') loadOrders(); }, 20000);
}
window.addEventListener('DOMContentLoaded', boot);
</script>
</body>
</html>
HTML;
}

/* ==========================================================================
 *  SAHIFA 2: XODIM PANELI (faqat yangi buyurtmani qabul qiladi)
 * ========================================================================== */
function render_staff(): void {
  header('Content-Type: text/html; charset=utf-8');
  echo <<<'HTML'
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1d4ed8">
<title>Xodim paneli</title>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<style>
:root{--p:#1d4ed8;--ink:#0f172a;--mut:#64748b;--bg:#f1f5f9;--ok:#16a34a}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.45 -apple-system,Segoe UI,Roboto,sans-serif}
button{font:inherit;cursor:pointer;border:0}
.top{position:sticky;top:0;z-index:5;background:linear-gradient(120deg,#1e3a8a,var(--p));color:#fff;padding:16px;display:flex;justify-content:space-between;align-items:center}
.top b{font-size:17px}
.top small{opacity:.85}
.top button{background:rgba(255,255,255,.18);color:#fff;border-radius:12px;padding:8px 12px;font-weight:700}
.wrap{max-width:560px;margin:0 auto;padding:14px 14px 40px}
.card{background:#fff;border-radius:20px;padding:16px;margin-bottom:14px;box-shadow:0 2px 12px rgba(15,23,42,.07)}
.hd{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:8px}
.hd h3{margin:0;font-size:16px}
.badge{padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#f1f5f9;color:#475569;white-space:nowrap}
.badge.new{background:#fef3c7;color:#92400e}
.badge.accepted{background:#dbeafe;color:#1e40af}
.muted{color:var(--mut);font-size:14px}
.items{margin:10px 0;line-height:1.6}
.addr{background:#f8fafc;border-radius:14px;padding:12px;margin:10px 0;font-size:14px}
.links{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.links a{flex:1;text-align:center;padding:10px;border-radius:12px;background:#eef2ff;color:var(--p);font-weight:700;text-decoration:none;font-size:14px}
.acc{width:100%;margin-top:12px;padding:14px;border-radius:14px;background:linear-gradient(120deg,var(--p),#3b82f6);color:#fff;font-weight:800;font-size:16px}
.acc:disabled{opacity:.6}
.ok{color:var(--ok);font-weight:700}
.empty{text-align:center;color:var(--mut);padding:50px 10px}
.toast{position:fixed;left:50%;bottom:20px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:12px 18px;border-radius:14px;font-size:14px;z-index:9;opacity:0;transition:.25s;pointer-events:none}
.toast.show{opacity:1}
</style>
</head>
<body>
<div class="top"><div><b>📋 Xodim paneli</b><br><small id="who">Yuklanmoqda…</small></div><button id="refresh">⟳ Yangilash</button></div>
<div class="wrap" id="list"></div>
<div class="toast" id="toast"></div>
<script>
const tg = window.Telegram && Telegram.WebApp;
if (tg) { tg.ready(); tg.expand(); }
const INIT = tg ? tg.initData : '';
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m';
const LBL = {new:'Yangi', accepted:'Qabul qilindi', cooking:'Tayyorlanmoqda', ready:'Tayyor', delivering:'Yo‘lda'};
let ORDERS = [];

async function api(name, body) {
  const opt = { method: body ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json', 'X-Telegram-Init-Data': INIT } };
  if (body) opt.body = JSON.stringify(body);
  const r = await fetch('?api=' + encodeURIComponent(name), opt);
  const text = await r.text();
  let j;
  try { j = JSON.parse(text); } catch (e) { throw new Error('Server xatosi (HTTP ' + r.status + ')'); }
  if (!j.ok) throw new Error(j.error || 'Xatolik');
  return j;
}
function toast(m) { const t = $('#toast'); t.textContent = m; t.classList.add('show'); clearTimeout(t._t); t._t = setTimeout(() => t.classList.remove('show'), 2200); }

function card(o) {
  const items = (o.items || []).map(i => `${i.qty} × ${esc(i.product_name)}${i.extras ? ` <span class="muted">(${esc(i.extras)})</span>` : ''}`).join('<br>');
  const map = o.lat != null && o.lng != null ? `https://www.google.com/maps?q=${o.lat},${o.lng}` : null;
  const isNew = o.status === 'new';
  return `<div class="card">
    <div class="hd"><h3>#${o.id} · ${esc(o.customer_name)}</h3><span class="badge ${esc(o.status)}">${esc(LBL[o.status] || o.status)}</span></div>
    <div class="muted">${o.order_type === 'delivery' ? '🛵 Yetkazib berish' : '🏃 Olib ketish'} · ${o.payment === 'card' ? 'Karta' : 'Naqd'} · ${esc(o.created_at)}</div>
    <div class="items">${items}</div>
    ${o.address ? `<div class="addr">📍 ${esc(o.address)}</div>` : ''}
    ${o.note ? `<div class="muted">Izoh: ${esc(o.note)}</div>` : ''}
    <div class="hd" style="margin-top:8px"><b>${money(o.total)}</b>
      <a href="tel:${esc(String(o.phone).replace(/\s/g, ''))}" style="color:var(--p);font-weight:700;text-decoration:none">☎️ ${esc(o.phone)}</a></div>
    ${map ? `<div class="links"><a href="${map}" target="_blank" rel="noopener">🗺 Xaritada ochish</a></div>` : ''}
    ${o.accepted_name ? `<div class="muted" style="margin-top:8px">Qabul qilgan: <b>${esc(o.accepted_name)}</b></div>` : ''}
    ${isNew ? `<button class="acc" data-accept="${o.id}">✅ Qabul qilish</button>` : ''}
  </div>`;
}

function render() {
  $('#list').innerHTML = ORDERS.length ? ORDERS.map(card).join('') : '<div class="empty">📭<br>Hozircha faol buyurtmalar yo‘q</div>';
}
async function load() {
  try {
    const r = await api('staff_orders');
    ORDERS = r.orders;
    $('#who').textContent = 'Xodim: ' + r.me.name + ' · faol buyurtmalar: ' + ORDERS.length;
    render();
  } catch (e) {
    $('#who').textContent = '—';
    $('#list').innerHTML = `<div class="empty">⚠️<br>${esc(e.message)}</div>`;
  }
}
document.addEventListener('click', async e => {
  const b = e.target.closest('[data-accept]');
  if (b) {
    b.disabled = true;
    try { await api('staff_accept', { id: Number(b.dataset.accept) }); toast('Buyurtma qabul qilindi ✓'); load(); }
    catch (err) { toast(err.message); b.disabled = false; load(); }
  }
  if (e.target.id === 'refresh') load();
});
load();
setInterval(load, 15000);
</script>
</body>
</html>
HTML;
}

/* ==========================================================================
 *  SAHIFA 3: ADMIN PANEL (asosiy admin)
 * ========================================================================== */
function render_admin(): void {
  header('Content-Type: text/html; charset=utf-8');
  echo <<<'HTML'
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Po’lathon</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.omap{height:320px;border-radius:14px;overflow:hidden;margin:10px 0;background:#e2e8f0}
:root{--p:#1d4ed8;--p2:#3b82f6;--ink:#0f172a;--mut:#64748b;--bg:#f1f5f9;--line:#e2e8f0;--red:#dc2626;--ok:#16a34a}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.45 -apple-system,Segoe UI,Roboto,sans-serif}
button,input,select,textarea{font:inherit;color:inherit}
a{color:var(--p)}
.layout{display:flex;min-height:100vh}
.side{width:240px;background:#0f172a;color:#e2e8f0;padding:20px 14px;flex:none;position:sticky;top:0;height:100vh}
.side .brand{display:flex;align-items:center;gap:10px;padding:6px 8px 20px;font-weight:800;font-size:16px;color:#fff}
.side .brand i{width:38px;height:38px;border-radius:12px;background:linear-gradient(135deg,var(--p),var(--p2));display:grid;place-items:center;font-style:normal}
.nav button{display:flex;width:100%;align-items:center;gap:10px;padding:11px 12px;border-radius:12px;margin-bottom:4px;background:none;text-align:left;font-weight:600;color:#cbd5e1}
.nav button.on,.nav button:hover{background:rgba(59,130,246,.18);color:#fff}
.nav .out{margin-top:20px;color:#fca5a5}
.main{flex:1;min-width:0;padding:24px 28px 60px}
.head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap}
.head h1{margin:0;font-size:22px;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px}
.kpi{background:#fff;border-radius:18px;padding:18px;box-shadow:0 2px 10px rgba(15,23,42,.05)}
.kpi small{color:var(--mut);font-weight:600}
.kpi b{display:block;font-size:24px;margin-top:6px}
.card{background:#fff;border-radius:18px;padding:18px;box-shadow:0 2px 10px rgba(15,23,42,.05);margin-bottom:14px}
.card h3{margin:0 0 12px;font-size:16px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.muted{color:var(--mut)}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between}
input,select,textarea{padding:10px 12px;border:1.5px solid var(--line);border-radius:12px;background:#fff;outline:0;min-width:0}
input:focus,textarea:focus,select:focus{border-color:var(--p2)}
.btn{padding:10px 16px;border-radius:12px;border:0;background:linear-gradient(120deg,var(--p),var(--p2));color:#fff;font-weight:700;cursor:pointer;white-space:nowrap}
.btn.ghost{background:#eef2ff;color:var(--p)}
.btn.red{background:#fee2e2;color:#991b1b}
.btn.sm{padding:7px 11px;font-size:13px;border-radius:10px}
.badge{padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#f1f5f9;color:#475569;white-space:nowrap}
.badge.new{background:#fef3c7;color:#92400e}.badge.accepted,.badge.cooking{background:#dbeafe;color:#1e40af}
.badge.ready,.badge.delivering{background:#dcfce7;color:#166534}.badge.done{background:#e0e7ff;color:#3730a3}.badge.cancelled{background:#fee2e2;color:#991b1b}
.order{border:1px solid var(--line);border-radius:16px;padding:14px;margin-bottom:12px}
.order .items{margin:8px 0;line-height:1.6}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);vertical-align:middle}
th{color:var(--mut);font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
.thumb{width:52px;height:52px;border-radius:12px;background:linear-gradient(135deg,#fde68a,#fb923c);display:grid;place-items:center;font-size:24px;overflow:hidden}
.thumb img{width:100%;height:100%;object-fit:cover}
.login{max-width:400px;margin:12vh auto;background:#fff;padding:28px;border-radius:22px;box-shadow:0 14px 40px rgba(15,23,42,.12)}
.login h2{margin:0 0 16px;font-size:22px}
.login input{width:100%;margin-bottom:10px}
.login .btn{width:100%;padding:13px}
.err{color:var(--red);min-height:18px;margin:6px 0;font-size:13px}
.modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:grid;place-items:center;padding:16px;z-index:50}
.modal .box{background:#fff;border-radius:20px;padding:22px;width:100%;max-width:520px;max-height:92vh;overflow:auto}
.modal h3{margin:0 0 14px;font-size:18px}
.f{display:block;margin-bottom:12px}
.f>span{display:block;font-size:12px;font-weight:700;color:#334155;margin-bottom:5px}
.f input,.f textarea,.f select{width:100%}
.check{display:flex;align-items:center;gap:8px;font-weight:600;margin:8px 0}
.preview{width:100%;height:160px;border-radius:14px;background:#f1f5f9 center/cover no-repeat;display:grid;place-items:center;color:var(--mut);margin-bottom:8px;overflow:hidden}
.toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:11px 16px;border-radius:12px;z-index:60;font-weight:600}
.tabs-m{display:none}
@media (max-width:860px){
  .layout{display:block}
  .side{display:none}
  .tabs-m{display:flex;gap:6px;overflow-x:auto;background:#0f172a;padding:10px;position:sticky;top:0;z-index:9}
  .tabs-m button{flex:none;background:rgba(255,255,255,.1);color:#fff;border:0;border-radius:10px;padding:8px 12px;font-weight:700}
  .tabs-m button.on{background:var(--p)}
  .main{padding:16px 14px 60px}
  th.hide-m,td.hide-m{display:none}
}
</style>
</head>
<body>
<div id="root"></div>
<script>
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m';
const A = { tab: 'dash', ok: false, msg: '', stats: {}, orders: [], statuses: {}, filter: 'all',
            cats: [], products: [], extras: [], staff: [], maxStaff: 5, cfg: {} };
const TABS = [['dash', '📊', 'Bosh sahifa'], ['orders', '🧾', 'Buyurtmalar'], ['menu', '🍽', 'Menyu'], ['staff', '👥', 'Xodimlar'], ['settings', '⚙️', 'Sozlamalar']];
const LBL = {new:'Yangi', accepted:'Qabul qilindi', cooking:'Tayyorlanmoqda', ready:'Tayyor', delivering:'Yo‘lda', done:'Yakunlandi', cancelled:'Bekor qilindi'};

async function aapi(name, body, form) {
  const opt = { method: (body || form) ? 'POST' : 'GET', headers: { 'X-Admin': '1' }, credentials: 'same-origin' };
  if (form) opt.body = form;
  else if (body) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
  const r = await fetch('?admin=1&api=' + encodeURIComponent(name), opt);
  const text = await r.text();
  let j;
  try { j = JSON.parse(text); } catch (e) { const er = new Error('Server xatosi (HTTP ' + r.status + ')'); er.code = r.status; throw er; }
  if (!j.ok) { const er = new Error(j.error || 'Xatolik'); er.code = r.status; throw er; }
  return j;
}
function toast(m) {
  const d = document.createElement('div'); d.className = 'toast'; d.textContent = m;
  document.body.appendChild(d); setTimeout(() => d.remove(), 1800);
}
function closeModal() { document.querySelector('.modal')?.remove(); }
function openModal(html) {
  closeModal();
  const m = document.createElement('div');
  m.className = 'modal';
  m.innerHTML = `<div class="box">${html}</div>`;
  m.addEventListener('click', e => { if (e.target === m) closeModal(); });
  document.body.appendChild(m);
}

/* ---------- LOGIN ---------- */
function loginHTML() {
  return `<div class="login"><h2>🍢 Po’lathon Admin</h2>
    <input id="u" placeholder="Login" autocomplete="username">
    <input id="p" type="password" placeholder="Parol" autocomplete="current-password">
    <div class="err">${esc(A.msg)}</div>
    <button class="btn" data-act="login">Kirish</button></div>`;
}

/* ---------- LAYOUT ---------- */
function shell(body) {
  return `<div class="layout">
    <aside class="side">
      <div class="brand"><i>P</i>Po’lathon Admin</div>
      <nav class="nav">${TABS.map(([k, ic, lb]) => `<button class="${A.tab === k ? 'on' : ''}" data-tab="${k}">${ic} ${lb}</button>`).join('')}
        <button class="out" data-act="logout">🚪 Chiqish</button></nav>
    </aside>
    <div style="flex:1;min-width:0">
      <div class="tabs-m">${TABS.map(([k, ic, lb]) => `<button class="${A.tab === k ? 'on' : ''}" data-tab="${k}">${ic} ${lb}</button>`).join('')}
        <button data-act="logout">🚪</button></div>
      <main class="main">${body}</main>
    </div></div>`;
}

/* ---------- BOSH SAHIFA ---------- */
function dashHTML() {
  const s = A.stats;
  return `<div class="head"><h1>Bosh sahifa</h1></div>
  <div class="grid">
    <div class="kpi"><small>Bugungi buyurtmalar</small><b>${s.today_orders ?? '—'}</b></div>
    <div class="kpi"><small>Bugungi savdo</small><b>${money(s.today_revenue)}</b></div>
    <div class="kpi"><small>Faol buyurtmalar</small><b>${s.active ?? '—'}</b></div>
    <div class="kpi"><small>Xodimlar</small><b>${s.staff ?? 0} / ${A.maxStaff}</b></div>
    <div class="kpi"><small>Mijozlar</small><b>${s.customers ?? '—'}</b></div>
    <div class="kpi"><small>Yakunlangan</small><b>${s.done ?? '—'}</b></div>
  </div>
  <div class="card"><h3>So‘nggi buyurtmalar <span class="btn ghost sm" data-tab="orders" style="cursor:pointer">Barchasi →</span></h3>${ordersList(A.orders.slice(0, 6))}</div>`;
}

/* ---------- BUYURTMALAR ---------- */
function statusSelect(o) {
  return `<select data-status="${o.id}">${Object.entries(A.statuses).map(([k]) =>
    `<option value="${k}" ${k === o.status ? 'selected' : ''}>${esc(LBL[k] || k)}</option>`).join('')}</select>`;
}
function ordersList(list) {
  if (!list.length) return '<p class="muted">Buyurtmalar yo‘q</p>';
  return list.map(o => {
    const map = o.lat != null ? `<a href="https://www.google.com/maps?q=${o.lat},${o.lng}" target="_blank" rel="noopener">🗺 Xarita</a>` : '';
    return `<div class="order">
    <div class="row"><b>#${o.id} — ${esc(o.customer_name)}</b><span class="badge ${esc(o.status)}">${esc(LBL[o.status] || o.status)}</span></div>
    <div class="muted" style="margin-top:4px">${esc(o.phone)} · ${o.order_type === 'delivery' ? '🛵 ' + esc(o.address) : '🏃 Olib ketish'} · ${o.payment === 'card' ? 'Karta' : 'Naqd'} · ${esc(o.created_at)}</div>
    <div class="items">${(o.items || []).map(i => `${i.qty} × ${esc(i.product_name)}${i.extras ? ` <span class="muted">(${esc(i.extras)})</span>` : ''}`).join('<br>')}</div>
    ${o.note ? `<div class="muted">Izoh: ${esc(o.note)}</div>` : ''}
    ${o.accepted_name ? `<div class="muted">Qabul qilgan: <b>${esc(o.accepted_name)}</b></div>` : ''}
    <div class="row" style="margin-top:10px"><div class="row">${map}<button class="btn ghost sm" data-act="o-loc" data-v="${o.id}">📍 Lokatsiyani belgilash</button></div>
      <div class="row"><b>${money(o.total)}</b>${statusSelect(o)}</div></div>
    ${o.status === 'new' ? `<button class="btn" style="width:100%;margin-top:10px;padding:13px" data-act="o-accept" data-v="${o.id}">✅ Buyurtmani qabul qilish</button>` : ''}
  </div>`;
  }).join('');
}
function ordersHTML() {
  const list = A.filter === 'all' ? A.orders : A.orders.filter(o => o.status === A.filter);
  return `<div class="head"><h1>Buyurtmalar (${list.length})</h1>
    <select id="flt"><option value="all">Hammasi</option>${Object.keys(A.statuses).map(k =>
      `<option value="${k}" ${A.filter === k ? 'selected' : ''}>${esc(LBL[k] || k)}</option>`).join('')}</select></div>
    ${ordersList(list)}`;
}

/* ---------- BUYURTMA LOKATSIYASI (xarita) ---------- */
let LOC = { id: 0, geo: null, map: null, marker: null };
function orderLocation(id) {
  const o = A.orders.find(x => x.id == id);
  if (!o) return;
  LOC = { id: o.id, geo: null, map: null, marker: null };
  openModal(`<h3>📍 Buyurtma #${o.id} — lokatsiya</h3>
    <p class="muted" style="margin-top:0">Xaritada yetkazib berish joyini belgilang yoki suring. Saqlangach xodimlarga Telegram'da lokatsiya yuboriladi.</p>
    <div class="omap" id="omap"></div>
    <div class="row"><small id="locTxt" class="muted">${o.lat != null ? `Hozirgi: ${o.lat}, ${o.lng}` : 'Lokatsiya belgilanmagan'}</small>
      <button class="btn ghost sm" data-act="o-loc-locate">📍 Mening joyim</button></div>
    <div class="row" style="margin-top:12px"><button class="btn ghost" data-act="close">Bekor</button>
      <button class="btn" data-act="o-loc-save">💾 Saqlash</button></div>`);
  const start = o.lat != null ? [o.lat, o.lng] : [A.cfg.lat, A.cfg.lng];
  if (typeof L === 'undefined') { $('#omap').innerHTML = '<div style="padding:20px" class="muted">Xarita yuklanmadi (internet kerak).</div>'; return; }
  LOC.map = L.map('omap').setView(start, 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(LOC.map);
  LOC.marker = L.marker(start, { draggable: true }).addTo(LOC.map);
  if (o.lat != null) LOC.geo = { lat: o.lat, lng: o.lng };
  LOC.marker.on('dragend', () => setLoc(LOC.marker.getLatLng().lat, LOC.marker.getLatLng().lng));
  LOC.map.on('click', e => { LOC.marker.setLatLng(e.latlng); setLoc(e.latlng.lat, e.latlng.lng); });
  setTimeout(() => LOC.map && LOC.map.invalidateSize(), 200);
}
function setLoc(lat, lng) {
  LOC.geo = { lat: +lat.toFixed(6), lng: +lng.toFixed(6) };
  const t = $('#locTxt'); if (t) t.textContent = `Tanlandi: ${LOC.geo.lat}, ${LOC.geo.lng}`;
}
function locateAdmin() {
  if (!navigator.geolocation) return toast('Joylashuv qo‘llab-quvvatlanmaydi');
  navigator.geolocation.getCurrentPosition(p => {
    const { latitude: la, longitude: lo } = p.coords;
    setLoc(la, lo);
    if (LOC.map) { LOC.map.setView([la, lo], 17); LOC.marker.setLatLng([la, lo]); }
  }, () => toast('Joylashuvni aniqlab bo‘lmadi'), { enableHighAccuracy: true, timeout: 10000 });
}

/* ---------- MENYU ---------- */
function menuHTML() {
  const catName = id => A.cats.find(c => c.id == id)?.name || '';
  return `<div class="head"><h1>Menyu</h1><div class="row">
      <button class="btn ghost" data-act="cat-new">+ Kategoriya</button>
      <button class="btn ghost" data-act="extra-new">+ Qo‘shimcha</button>
      <button class="btn" data-act="prod-new">+ Taom qo‘shish</button></div></div>
  <div class="card" style="overflow:auto"><h3>Kategoriyalar</h3>
    <table><tr><th>Icon</th><th>Nomi</th><th>Tartib</th><th></th></tr>
    ${A.cats.map(c => `<tr><td style="font-size:22px">${esc(c.icon)}</td><td>${esc(c.name)}</td><td>${c.sort_order}</td>
      <td style="text-align:right"><button class="btn ghost sm" data-act="cat-edit" data-v="${c.id}">Tahrirlash</button>
      <button class="btn red sm" data-act="cat-del" data-v="${c.id}">O‘chirish</button></td></tr>`).join('')}</table></div>
  <div class="card" style="overflow:auto"><h3>Taomlar (${A.products.length})</h3>
    <table><tr><th>Rasm</th><th>Nomi</th><th class="hide-m">Kategoriya</th><th>Narx</th><th>Mavjud</th><th></th></tr>
    ${A.products.map(p => `<tr>
      <td><div class="thumb">${p.image ? `<img src="${esc(p.image)}" alt="">` : '🍽'}</div></td>
      <td><b>${esc(p.name)}</b>${p.featured == 1 ? ' ⭐' : ''}<div class="muted">${esc(p.description)}</div></td>
      <td class="hide-m">${esc(p.category || catName(p.category_id))}</td>
      <td style="white-space:nowrap"><b>${money(p.price)}</b></td>
      <td><input type="checkbox" data-avail="${p.id}" ${p.available == 1 ? 'checked' : ''}></td>
      <td style="text-align:right;white-space:nowrap"><button class="btn ghost sm" data-act="prod-edit" data-v="${p.id}">Tahrirlash</button>
        <button class="btn red sm" data-act="prod-del" data-v="${p.id}">O‘chirish</button></td></tr>`).join('')}</table></div>
  <div class="card" style="overflow:auto"><h3>Qo‘shimchalar</h3>
    <table><tr><th>Nomi</th><th>Narx</th><th></th></tr>
    ${A.extras.map(e => `<tr><td>${esc(e.name)}</td><td>${money(e.price)}</td>
      <td style="text-align:right"><button class="btn ghost sm" data-act="extra-edit" data-v="${e.id}">Tahrirlash</button>
      <button class="btn red sm" data-act="extra-del" data-v="${e.id}">O‘chirish</button></td></tr>`).join('')}</table></div>`;
}
function productForm(p = {}) {
  const opts = A.cats.map(c => `<option value="${c.id}" ${c.id == p.category_id ? 'selected' : ''}>${esc(c.icon)} ${esc(c.name)}</option>`).join('');
  const prev = p.image ? `style="background-image:url('${esc(p.image)}')"` : '';
  openModal(`<h3>${p.id ? 'Taomni tahrirlash' : 'Yangi taom'}</h3>
    <div class="preview" id="prev" ${prev}>${p.image ? '' : 'Rasm yo‘q'}</div>
    <label class="f"><span>Rasm (JPG/PNG/WEBP, 4 MB gacha)</span><input type="file" id="pf" accept="image/jpeg,image/png,image/webp"></label>
    <input type="hidden" id="pimg" value="${esc(p.image || '')}">
    <label class="f"><span>Nomi</span><input id="pn" value="${esc(p.name)}"></label>
    <label class="f"><span>Kategoriya</span><select id="pc">${opts}</select></label>
    <label class="f"><span>Tavsif</span><textarea id="pd" rows="2">${esc(p.description)}</textarea></label>
    <label class="f"><span>Narx (so‘m)</span><input id="pp" type="number" min="0" value="${Number(p.price) || ''}"></label>
    <label class="check"><input type="checkbox" id="pfe" ${p.featured == 1 ? 'checked' : ''}> Ommabop (bosh sahifada)</label>
    <label class="check"><input type="checkbox" id="pa" ${p.available === undefined || p.available == 1 ? 'checked' : ''}> Menyuda mavjud</label>
    <div class="err" id="perr"></div>
    <div class="row"><button class="btn ghost" data-act="close">Bekor</button><button class="btn" data-act="prod-save" data-v="${p.id || 0}">Saqlash</button></div>`);
  $('#pf').addEventListener('change', async e => {
    const f = e.target.files[0]; if (!f) return;
    const fd = new FormData(); fd.append('file', f);
    try {
      const r = await aapi('admin_upload', null, fd);
      $('#pimg').value = r.path;
      $('#prev').style.backgroundImage = `url('${r.path}')`; $('#prev').textContent = '';
      toast('Rasm yuklandi ✓');
    } catch (err) { $('#perr').textContent = err.message; }
  });
}
function catForm(c = {}) {
  openModal(`<h3>${c.id ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya'}</h3>
    <label class="f"><span>Nomi</span><input id="cn" value="${esc(c.name)}"></label>
    <label class="f"><span>Icon (emoji)</span><input id="ci" value="${esc(c.icon || '🍽')}"></label>
    <label class="f"><span>Tartib raqami</span><input id="cs" type="number" value="${c.sort_order ?? 0}"></label>
    <div class="err" id="perr"></div>
    <div class="row"><button class="btn ghost" data-act="close">Bekor</button><button class="btn" data-act="cat-save" data-v="${c.id || 0}">Saqlash</button></div>`);
}
function extraForm(e = {}) {
  openModal(`<h3>${e.id ? 'Qo‘shimchani tahrirlash' : 'Yangi qo‘shimcha'}</h3>
    <label class="f"><span>Nomi</span><input id="en" value="${esc(e.name)}"></label>
    <label class="f"><span>Narx (so‘m, 0 = bepul)</span><input id="ep" type="number" min="0" value="${e.price ?? 0}"></label>
    <div class="err" id="perr"></div>
    <div class="row"><button class="btn ghost" data-act="close">Bekor</button><button class="btn" data-act="extra-save" data-v="${e.id || 0}">Saqlash</button></div>`);
}

/* ---------- XODIMLAR ---------- */
function staffHTML() {
  const active = A.staff.filter(s => s.active == 1).length;
  return `<div class="head"><h1>Xodimlar (${active} / ${A.maxStaff})</h1></div>
  <div class="card"><h3>Yangi xodim qo‘shish</h3>
    <p class="muted" style="margin-top:0">Xodimning Telegram ID raqamini kiriting (@userinfobot orqali bilib olinadi). Xodim buyurtmalarni faqat qabul qila oladi. Xodim botni bir marta /start qilgan bo‘lishi kerak.</p>
    <div class="row"><input id="sid" placeholder="Telegram ID (masalan 123456789)" inputmode="numeric" style="flex:1;min-width:200px">
      <input id="sname" placeholder="Ism (masalan Ali)" style="flex:1;min-width:160px">
      <button class="btn" data-act="staff-add" ${active >= A.maxStaff ? 'disabled' : ''}>+ Qo‘shish</button></div>
    ${active >= A.maxStaff ? '<div class="err">Xodimlar soni chegarasiga yetdi</div>' : ''}</div>
  <div class="card" style="overflow:auto"><h3>Ro‘yxat</h3>
    <table><tr><th>Ism</th><th>Telegram ID</th><th>Holat</th><th></th></tr>
    ${A.staff.map(s => `<tr>
      <td><input value="${esc(s.name)}" data-sname="${s.id}" style="width:160px"></td>
      <td>${esc(s.telegram_id)}</td>
      <td><span class="badge ${s.active == 1 ? 'accepted' : ''}">${s.active == 1 ? 'Faol' : 'Nofaol'}</span></td>
      <td style="text-align:right;white-space:nowrap">
        <button class="btn ghost sm" data-act="staff-toggle" data-v="${s.id}" data-a="${s.active == 1 ? 0 : 1}">${s.active == 1 ? 'Nofaol qilish' : 'Faollashtirish'}</button>
        <button class="btn sm" data-act="staff-rename" data-v="${s.id}">Saqlash</button>
        <button class="btn red sm" data-act="staff-del" data-v="${s.id}">O‘chirish</button></td></tr>`).join('') || '<tr><td colspan="4" class="muted">Xodimlar yo‘q</td></tr>'}
    </table></div>`;
}

/* ---------- SOZLAMALAR ---------- */
function settingsHTML() {
  const c = A.cfg;
  return `<div class="head"><h1>Sozlamalar</h1></div>
  <div class="card"><h3>Restoran</h3>
    <label class="f"><span>Nomi</span><input id="s_name" value="${esc(c.name)}" style="width:100%"></label>
    <label class="f"><span>Telefon</span><input id="s_phone" value="${esc(c.phone)}" style="width:100%"></label>
    <label class="f"><span>Bosh sahifa matni</span><input id="s_hero" value="${esc(c.hero_text)}" style="width:100%"></label>
    <label class="check"><input type="checkbox" id="s_acc" ${c.accepting ? 'checked' : ''}> Buyurtma qabul qilish yoqilgan</label>
  </div>
  <div class="card"><h3>Yetkazib berish va joylashuv</h3>
    <div class="row"><label class="f" style="flex:1;min-width:140px"><span>Yetkazib berish narxi (so‘m)</span><input id="s_fee" type="number" min="0" value="${c.delivery_fee}" style="width:100%"></label>
    <label class="f" style="flex:1;min-width:140px"><span>Restoran kenglik (lat)</span><input id="s_lat" value="${c.lat}" style="width:100%"></label>
    <label class="f" style="flex:1;min-width:140px"><span>Restoran uzunlik (lng)</span><input id="s_lng" value="${c.lng}" style="width:100%"></label></div>
    <p class="muted">Koordinatalarni Google Maps da restoran joyiga o‘ng tugma bosib olishingiz mumkin.</p>
  </div>
  <div class="card"><h3>Promo-kod</h3>
    <div class="row"><label class="f" style="flex:1;min-width:140px"><span>Promo-kod</span><input id="s_promo" value="${esc(c.promo_code)}" style="width:100%"></label>
    <label class="f" style="flex:1;min-width:140px"><span>Chegirma (%)</span><input id="s_pct" type="number" min="0" max="100" value="${c.promo_percent}" style="width:100%"></label></div>
  </div>
  <div class="row"><span class="err" id="serr"></span><button class="btn" data-act="settings-save">💾 Saqlash</button></div>`;
}

/* ---------- RENDER ---------- */
function render() {
  if (!A.ok) { $('#root').innerHTML = loginHTML(); return; }
  const H = {dash: dashHTML, orders: ordersHTML, menu: menuHTML, staff: staffHTML, settings: settingsHTML}[A.tab];
  $('#root').innerHTML = shell(H());
}
async function loadAll() {
  const [st, od, mn, sf, se] = await Promise.all([
    aapi('admin_stats'), aapi('admin_orders'), aapi('admin_menu'), aapi('admin_staff'), aapi('admin_settings')]);
  A.stats = st; A.orders = od.orders; A.statuses = od.statuses;
  A.cats = mn.categories; A.products = mn.products; A.extras = mn.extras;
  A.staff = sf.staff; A.maxStaff = sf.max; A.cfg = se.cfg;
}
async function enter() {
  try { await loadAll(); A.ok = true; A.msg = ''; }
  catch (e) { A.ok = false; A.msg = e.code === 401 ? '' : e.message; }
  render();
}
async function run(fn) {
  try { await fn(); } catch (e) { toast(e.message); }
}

/* ---------- HODISALAR ---------- */
document.addEventListener('click', async e => {
  const t = e.target.closest('[data-tab],[data-act]');
  if (!t) return;
  if (t.dataset.tab) { A.tab = t.dataset.tab; return render(); }
  const act = t.dataset.act, v = Number(t.dataset.v);
  if (act === 'login') {
    try { await aapi('admin_login', { username: $('#u').value, password: $('#p').value }); await enter(); }
    catch (err) { A.ok = false; A.msg = err.message; render(); }
    return;
  }
  if (act === 'logout') { await aapi('admin_logout', {}).catch(() => {}); A.ok = false; A.msg = ''; return render(); }
  if (act === 'o-accept') return run(async () => {
    await aapi('admin_order_accept', { id: v }); toast('Buyurtma qabul qilindi ✓'); await enter(); A.tab = 'orders'; render();
  });
  if (act === 'o-loc') return orderLocation(v);
  if (act === 'o-loc-save') return run(async () => {
    if (!LOC.geo) return toast('Xaritada joyni belgilang');
    await aapi('admin_order_location', { id: LOC.id, lat: LOC.geo.lat, lng: LOC.geo.lng });
    closeModal(); toast('Lokatsiya saqlandi va xodimlarga yuborildi ✓'); await enter(); A.tab = 'orders'; render();
  });
  if (act === 'o-loc-locate') return locateAdmin();
  if (act === 'close') return closeModal();

  if (act === 'prod-new') return productForm({});
  if (act === 'prod-edit') return productForm(A.products.find(p => p.id == v));
  if (act === 'prod-del') return run(async () => {
    if (!confirm('Taomni menyudan o‘chirasizmi?')) return;
    await aapi('admin_product_delete', { id: v }); toast('O‘chirildi'); await enter();
  });
  if (act === 'prod-save') {
    const body = {
      id: v, name: $('#pn').value.trim(), category_id: Number($('#pc').value), description: $('#pd').value,
      price: Number($('#pp').value), image: $('#pimg').value, featured: $('#pfe').checked, available: $('#pa').checked,
    };
    try { await aapi('admin_product_save', body); closeModal(); toast('Saqlandi ✓'); await enter(); }
    catch (err) { $('#perr').textContent = err.message; }
    return;
  }

  if (act === 'cat-new') return catForm({});
  if (act === 'cat-edit') return catForm(A.cats.find(c => c.id == v));
  if (act === 'cat-del') return run(async () => {
    if (!confirm('Kategoriyani o‘chirasizmi? Undagi taomlar menyudan yashiriladi.')) return;
    await aapi('admin_category_delete', { id: v }); toast('O‘chirildi'); await enter();
  });
  if (act === 'cat-save') {
    try {
      await aapi('admin_category_save', { id: v, name: $('#cn').value, icon: $('#ci').value, sort_order: Number($('#cs').value) });
      closeModal(); toast('Saqlandi ✓'); await enter();
    } catch (err) { $('#perr').textContent = err.message; }
    return;
  }

  if (act === 'extra-new') return extraForm({});
  if (act === 'extra-edit') return extraForm(A.extras.find(x => x.id == v));
  if (act === 'extra-del') return run(async () => {
    if (!confirm('Qo‘shimchani o‘chirasizmi?')) return;
    await aapi('admin_extra_delete', { id: v }); toast('O‘chirildi'); await enter();
  });
  if (act === 'extra-save') {
    try {
      await aapi('admin_extra_save', { id: v, name: $('#en').value, price: Number($('#ep').value) });
      closeModal(); toast('Saqlandi ✓'); await enter();
    } catch (err) { $('#perr').textContent = err.message; }
    return;
  }

  if (act === 'staff-add') return run(async () => {
    await aapi('admin_staff_add', { telegram_id: $('#sid').value, name: $('#sname').value });
    toast('Xodim qo‘shildi ✓'); await enter(); A.tab = 'staff'; render();
  });
  if (act === 'staff-rename') return run(async () => {
    const s = A.staff.find(x => x.id == v);
    const name = document.querySelector(`[data-sname="${v}"]`).value;
    await aapi('admin_staff_save', { id: v, name, active: s.active == 1 });
    toast('Saqlandi ✓'); await enter(); A.tab = 'staff'; render();
  });
  if (act === 'staff-toggle') return run(async () => {
    const s = A.staff.find(x => x.id == v);
    await aapi('admin_staff_save', { id: v, name: s.name, active: t.dataset.a === '1' });
    toast('Yangilandi ✓'); await enter(); A.tab = 'staff'; render();
  });
  if (act === 'staff-del') return run(async () => {
    if (!confirm('Xodimni ro‘yxatdan o‘chirasizmi?')) return;
    await aapi('admin_staff_delete', { id: v }); toast('O‘chirildi'); await enter(); A.tab = 'staff'; render();
  });

  if (act === 'settings-save') {
    const body = {
      name: $('#s_name').value, phone: $('#s_phone').value, hero_text: $('#s_hero').value,
      accepting: $('#s_acc').checked, delivery_fee: $('#s_fee').value, lat: $('#s_lat').value,
      lng: $('#s_lng').value, promo_code: $('#s_promo').value, promo_percent: $('#s_pct').value,
    };
    try { await aapi('admin_settings_save', body); toast('Sozlamalar saqlandi ✓'); await enter(); A.tab = 'settings'; render(); }
    catch (err) { $('#serr').textContent = err.message; }
  }
});
document.addEventListener('change', async e => {
  const t = e.target;
  if (t.dataset.status) {
    try {
      await aapi('admin_order_status', { id: Number(t.dataset.status), status: t.value });
      const o = A.orders.find(x => x.id == t.dataset.status); if (o) o.status = t.value;
      toast('Holat saqlandi ✓');
    } catch (err) { toast(err.message); }
  }
  if (t.dataset.avail) {
    const p = A.products.find(x => x.id == t.dataset.avail);
    try {
      await aapi('admin_product_save', { id: p.id, name: p.name, category_id: p.category_id, description: p.description,
        price: p.price, image: p.image || '', featured: p.featured == 1, available: t.checked });
      p.available = t.checked ? 1 : 0; toast('Saqlandi ✓');
    } catch (err) { toast(err.message); t.checked = !t.checked; }
  }
  if (t.id === 'flt') { A.filter = t.value; render(); }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Enter' && e.target.id === 'p') document.querySelector('[data-act="login"]')?.click();
});

enter();
</script>
</body>
</html>
HTML;
}
