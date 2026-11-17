<?php
declare(strict_types=1);

/* ==========================================================================
 *  PO’LATHON SHASHLIK SOMSA — bitta fayllik Telegram Mini App + Admin panel
 *  Talab: PHP 8.1+ (pdo_sqlite, curl, json, session)
 *  Ma’lumotlar bazasi: data/restaurant.sqlite (avtomatik yaratiladi)
 * ========================================================================== */

/* ============== SOZLAMALAR — ishga tushirishdan oldin o‘zgartiring ============== */
const BOT_TOKEN      = 'BOT_TOKEN';            // @BotFather bergan token
const ADMIN_CHAT_ID  = 'ADMIN_CHAT_ID';        // yangi buyurtma xabari boradigan chat ID
const ADMIN_USER     = 'admin';                // admin panel login
const ADMIN_PASSWORD = 'ChangeThisPassword123!'; // DARHOL o‘zgartiring
const DEBUG          = false;                  // production’da false bo‘lsin
const RESTAURANT     = 'Po’lathon Shashlik Somsa';
const CURRENCY       = 'so‘m';
const DELIVERY_FEE   = 10000;
const PROMOS         = ['POLAT5' => 5];        // promo-kod => chegirma foizi
const STATUSES       = [
  'new' => 'Qabul qilindi', 'cooking' => 'Tayyorlanmoqda', 'ready' => 'Tayyor',
  'delivering' => 'Yo‘lda', 'done' => 'Yakunlandi', 'cancelled' => 'Bekor qilindi',
];
const DB_DIR = __DIR__ . '/data';

/* ============== YORDAMCHI FUNKSIYALAR ============== */
function json_out(array $data, int $status = 200): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function fail(string $msg, int $code = 400): void { json_out(['ok' => false, 'error' => $msg], $code); }
function json_input(): array { return json_decode((string)file_get_contents('php://input'), true) ?: []; }

function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;
  if (!is_dir(DB_DIR)) {
    mkdir(DB_DIR, 0750, true);
    file_put_contents(DB_DIR . '/.htaccess', "Require all denied\nDeny from all\n"); // DB brauzerdan ochilmasin
  }
  $pdo = new PDO('sqlite:' . DB_DIR . '/restaurant.sqlite', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  $pdo->exec('PRAGMA foreign_keys = ON');
  $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT, telegram_id INTEGER UNIQUE NOT NULL,
  name TEXT, username TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, icon TEXT,
  sort_order INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
CREATE TABLE IF NOT EXISTS products (
  id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER REFERENCES categories(id),
  name TEXT NOT NULL, description TEXT, price INTEGER NOT NULL, image TEXT,
  featured INTEGER DEFAULT 0, available INTEGER DEFAULT 1, active INTEGER DEFAULT 1);
CREATE TABLE IF NOT EXISTS extras (
  id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, price INTEGER DEFAULT 0, active INTEGER DEFAULT 1);
CREATE TABLE IF NOT EXISTS orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER REFERENCES users(id),
  customer_name TEXT, phone TEXT, order_type TEXT, address TEXT, note TEXT,
  payment TEXT, subtotal INTEGER, discount INTEGER DEFAULT 0, delivery_fee INTEGER DEFAULT 0,
  total INTEGER, status TEXT DEFAULT 'new', created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
  product_id INTEGER, product_name TEXT, price INTEGER, qty INTEGER, extras TEXT, line_total INTEGER);
SQL);
  if (!(int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn()) seed($pdo);
  return $pdo;
}

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

/* ============== TELEGRAM AUTH ============== */
function valid_init_data(string $init): bool {
  if (BOT_TOKEN === 'BOT_TOKEN' || $init === '') return false;
  parse_str($init, $d);
  if (!isset($d['hash'])) return false;
  $hash = $d['hash'];
  unset($d['hash']);
  if (empty($d['auth_date']) || abs(time() - (int)$d['auth_date']) > 86400) return false; // 24 soat
  ksort($d);
  $parts = [];
  foreach ($d as $k => $v) $parts[] = "$k=$v";
  $secret = hash_hmac('sha256', BOT_TOKEN, 'WebAppData', true);
  return hash_equals($hash, hash_hmac('sha256', implode("\n", $parts), $secret));
}

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

function upsert_user(array $tg): array {
  $pdo = db();
  $pdo->prepare('INSERT INTO users(telegram_id, name, username) VALUES(?,?,?)
                 ON CONFLICT(telegram_id) DO UPDATE SET name = excluded.name, username = excluded.username')
      ->execute([(int)$tg['id'], trim(($tg['first_name'] ?? '') . ' ' . ($tg['last_name'] ?? '')), $tg['username'] ?? null]);
  $s = $pdo->prepare('SELECT * FROM users WHERE telegram_id = ?');
  $s->execute([(int)$tg['id']]);
  return $s->fetch();
}

function tg_send($chat, string $text): void {
  if (BOT_TOKEN === 'BOT_TOKEN' || !$chat) return;
  $ch = curl_init('https://api.telegram.org/bot' . BOT_TOKEN . '/sendMessage');
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['chat_id' => $chat, 'text' => $text], // oddiy matn: HTML belgilar yo‘q
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
  ]);
  curl_exec($ch);
  curl_close($ch);
}

/* ============== AVTOMATIK BOT SOZLASH ============== */
function bot_configured(): bool { return BOT_TOKEN !== 'BOT_TOKEN' && BOT_TOKEN !== ''; }

function self_url(): string {
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
}
function app_url(): string { return rtrim(dirname(self_url()), '/') . '/'; }

function tg_api(string $method, array $params): array {
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

/* Birinchi so‘rovda webhook va menyu tugmasini o‘rnatadi. Muvaffaqiyatsiz bo‘lsa 10 daqiqada qayta urinadi. */
function ensure_bot_installed(): void {
  if (!bot_configured() || !is_dir(DB_DIR) && !@mkdir(DB_DIR, 0750, true)) return;
  $flag = DB_DIR . '/install.json';
  $st = is_file($flag) ? (json_decode((string)file_get_contents($flag), true) ?: []) : [];
  if (!empty($st['done']) || (time() - (int)($st['last'] ?? 0)) < 600) return;
  $ok1 = tg_api('setWebhook', ['url' => self_url(), 'drop_pending_updates' => true]);
  $ok2 = tg_api('setChatMenuButton', ['menu_button' => ['type' => 'web_app', 'text' => 'Menyu', 'web_app' => ['url' => app_url()]]]);
  $done = !empty($ok1['ok']) && !empty($ok2['ok']);
  file_put_contents($flag, json_encode(['done' => $done, 'last' => time()]));
}

/* Telegram webhook: /start va /menu ga Mini App tugmasi bilan javob beradi */
function handle_bot_update(array $upd): void {
  $msg = $upd['message'] ?? null;
  if (!$msg || !isset($msg['text']) || !bot_configured()) return;
  $cmd = strtolower(explode(' ', trim((string)$msg['text']))[0] ?? '');
  $cmd = explode('@', $cmd)[0];
  if ($cmd !== '/start' && $cmd !== '/menu') return;
  tg_api('sendMessage', [
    'chat_id' => $msg['chat']['id'],
    'text' => "Assalomu alaykum! " . RESTAURANT . " ga xush kelibsiz.\nMenyuni ochib buyurtma bering 👇",
    'reply_markup' => ['inline_keyboard' => [[['text' => '🍽 Menyuni ochish', 'web_app' => ['url' => app_url()]]]]],
  ]);
}

/* ============== ADMIN ============== */
function admin_guard(): void {
  if (empty($_SESSION['admin'])) fail('Avval tizimga kiring', 401);
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_SERVER['HTTP_X_ADMIN'])) fail('Ruxsat yo‘q', 403); // CSRF himoyasi
}

/* ============== API ============== */
function handle_api(string $a): void {
  $pdo = db();
  $method = $_SERVER['REQUEST_METHOD'];

  switch ($a) {
    case 'menu':
      json_out([
        'ok' => true,
        'categories' => $pdo->query('SELECT id, name, icon FROM categories WHERE active = 1 ORDER BY sort_order, id')->fetchAll(),
        'products' => $pdo->query('SELECT id, category_id, name, description, price, image, featured
                                   FROM products WHERE active = 1 AND available = 1 ORDER BY featured DESC, id')->fetchAll(),
        'extras' => $pdo->query('SELECT id, name, price FROM extras WHERE active = 1 ORDER BY id')->fetchAll(),
        'delivery_fee' => DELIVERY_FEE,
        'promos' => PROMOS,
      ]);

    case 'orders':
      $tg = tg_user();
      if (!$tg) fail('Telegram sessiyasi tasdiqlanmadi. Botdan oching.', 401);
      $u = upsert_user($tg);
      $st = $pdo->prepare('SELECT id, order_type, address, subtotal, discount, delivery_fee, total, status, created_at
                           FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 30');
      $st->execute([$u['id']]);
      $orders = $st->fetchAll();
      $it = $pdo->prepare('SELECT product_name, price, qty, extras FROM order_items WHERE order_id = ? ORDER BY id');
      foreach ($orders as &$o) { $it->execute([$o['id']]); $o['items'] = $it->fetchAll(); }
      unset($o);
      json_out(['ok' => true, 'user' => ['name' => $u['name'], 'username' => $u['username']], 'orders' => $orders]);

    case 'order':
      if ($method !== 'POST') fail('Noto‘g‘ri so‘rov', 405);
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
      if ($name === '') fail('Ismingizni kiriting.', 422);
      if (!preg_match('/^\+?[0-9 ()\-]{7,25}$/', $phone)) fail('Telefon raqamini to‘g‘ri kiriting.', 422);
      if ($type === 'delivery' && $addr === '') fail('Yetkazib berish manzilini kiriting.', 422);

      $pdo->beginTransaction();
      try {
        $ids = array_values(array_unique(array_map(fn($x) => (int)($x['id'] ?? 0), $items)));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id, name, price FROM products WHERE active = 1 AND available = 1 AND id IN ($ph)");
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
        $fee = $type === 'delivery' ? DELIVERY_FEE : 0;
        $pct = PROMOS[strtoupper(trim((string)($b['promo'] ?? '')))] ?? 0;
        $discount = (int)round($subtotal * $pct / 100);
        $total = $subtotal - $discount + $fee;

        $pdo->prepare('INSERT INTO orders(user_id, customer_name, phone, order_type, address, note, payment,
                       subtotal, discount, delivery_fee, total, status) VALUES(?,?,?,?,?,?,?,?,?,?,?,\'new\')')
            ->execute([$u['id'], $name, $phone, $type, $addr, $note, $pay, $subtotal, $discount, $fee, $total]);
        $oid = (int)$pdo->lastInsertId();
        $si = $pdo->prepare('INSERT INTO order_items(order_id, product_id, product_name, price, qty, extras, line_total)
                             VALUES(?,?,?,?,?,?,?)');
        foreach ($lines as $l) $si->execute([$oid, $l[0], $l[1], $l[2], $l[3], $l[4], $l[2] * $l[3]]);
        $pdo->commit();

        $txt = "🆕 Yangi buyurtma #$oid\n"
             . "Mijoz: $name\nTelefon: $phone\n"
             . ($type === 'delivery' ? "Yetkazib berish: $addr\n" : "Olib ketish\n")
             . ($note !== '' ? "Izoh: $note\n" : '')
             . "To‘lov: " . ($pay === 'card' ? 'karta' : 'naqd') . "\nTaomlar:\n";
        foreach ($lines as $l) $txt .= "- {$l[3]}× {$l[1]}" . ($l[4] !== '' ? " ({$l[4]})" : '') . "\n";
        $txt .= 'Jami: ' . number_format($total, 0, '.', ' ') . ' ' . CURRENCY;
        tg_send(ADMIN_CHAT_ID, $txt);
        json_out(['ok' => true, 'order_id' => $oid, 'total' => $total]);
      } catch (Throwable $e) {
        $pdo->rollBack();
        fail(DEBUG ? $e->getMessage() : ($e instanceof RuntimeException ? $e->getMessage() : 'Buyurtmani saqlashda xatolik.'), 422);
      }

    case 'admin_login':
      if ($method !== 'POST') fail('Noto‘g‘ri so‘rov', 405);
      if (empty($_SERVER['HTTP_X_ADMIN'])) fail('Ruxsat yo‘q', 403);
      $b = json_input();
      $ok = hash_equals(ADMIN_USER, (string)($b['username'] ?? ''))
         && hash_equals(ADMIN_PASSWORD, (string)($b['password'] ?? ''));
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
      $today = $pdo->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) s FROM orders
                            WHERE date(created_at) = date('now') AND status != 'cancelled'")->fetch();
      $active = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('new','cooking','ready','delivering')")->fetchColumn();
      $done = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'done'")->fetchColumn();
      $customers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
      json_out(['ok' => true, 'today_orders' => (int)$today['c'], 'today_revenue' => (int)$today['s'],
                'active' => $active, 'done' => $done, 'customers' => $customers]);

    case 'admin_orders':
      admin_guard();
      $orders = $pdo->query('SELECT id, customer_name, phone, order_type, address, note, payment, total, status, created_at
                             FROM orders ORDER BY id DESC LIMIT 100')->fetchAll();
      $it = $pdo->prepare('SELECT product_name, qty, extras FROM order_items WHERE order_id = ? ORDER BY id');
      foreach ($orders as &$o) { $it->execute([$o['id']]); $o['items'] = $it->fetchAll(); }
      unset($o);
      json_out(['ok' => true, 'orders' => $orders, 'statuses' => STATUSES]);

    case 'admin_order_status':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $st = (string)($b['status'] ?? '');
      if (!isset(STATUSES[$st])) fail('Noto‘g‘ri holat.', 422);
      $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$st, $id]);
      $q = $pdo->prepare('SELECT u.telegram_id FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?');
      $q->execute([$id]);
      $tid = (int)($q->fetchColumn() ?: 0);
      if ($tid > 1000) tg_send($tid, "Buyurtma #$id holati: " . STATUSES[$st]);
      json_out(['ok' => true]);

    case 'admin_products':
      admin_guard();
      json_out([
        'ok' => true,
        'categories' => $pdo->query('SELECT id, name, icon FROM categories WHERE active = 1 ORDER BY sort_order, id')->fetchAll(),
        'products' => $pdo->query('SELECT p.*, c.name AS category FROM products p
                                   LEFT JOIN categories c ON c.id = p.category_id
                                   WHERE p.active = 1 ORDER BY p.id DESC')->fetchAll(),
      ]);

    case 'admin_product_save':
      admin_guard();
      $b = json_input();
      $id = (int)($b['id'] ?? 0);
      $name = mb_substr(trim((string)($b['name'] ?? '')), 0, 160);
      $price = (int)($b['price'] ?? 0);
      $img = trim((string)($b['image'] ?? ''));
      if ($name === '' || $price < 0) fail('Nomi va narxini to‘g‘ri kiriting.', 422);
      if ($img !== '' && !preg_match('#^https?://#i', $img)) fail('Rasm manzili http(s) bilan boshlanishi kerak.', 422);
      $vals = [(int)($b['category_id'] ?? 0), $name, mb_substr(trim((string)($b['description'] ?? '')), 0, 500),
               $price, $img !== '' ? $img : null, !empty($b['featured']) ? 1 : 0, !empty($b['available']) ? 1 : 0];
      if ($id > 0) {
        $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, price=?, image=?, featured=?, available=? WHERE id=?')
            ->execute([...$vals, $id]);
      } else {
        $pdo->prepare('INSERT INTO products(category_id, name, description, price, image, featured, available) VALUES(?,?,?,?,?,?,?)')
            ->execute($vals);
      }
      json_out(['ok' => true]);

    case 'admin_product_delete':
      admin_guard();
      $b = json_input();
      $pdo->prepare('UPDATE products SET active = 0 WHERE id = ?')->execute([(int)($b['id'] ?? 0)]);
      json_out(['ok' => true]);
  }
  fail('Noma’lum so‘rov', 404);
}

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

/* Telegram webhook so‘rovi (POST, update_id bilan keladi) */
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
if (isset($_GET['admin'])) { render_admin(); }
else { render_app(); }

/* ============== SAHIFA: MINI APP ============== */
function render_app(): void {
  header('Content-Type: text/html; charset=utf-8');
  echo <<<'HTML'
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=1,user-scalable=no">
<meta name="theme-color" content="#1f6fe8">
<title>Po’lathon Shashlik Somsa</title>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<style>
:root{--p:#1f6fe8;--p2:#4c8dff;--bg:#eef2f8;--card:#fff;--tx:#1b2333;--mut:#8a93a6;--line:#e6ebf3;--ok:#22a06b;--warn:#e8a317;--red:#e5484d;--r:18px}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;background:var(--bg);color:var(--tx);font:15px/1.4 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
button,input,textarea,select{font:inherit;color:inherit}
button{cursor:pointer;border:0;background:none;padding:0}
.app{max-width:480px;margin:0 auto;min-height:100vh;padding-bottom:96px;background:var(--bg)}
.hidden{display:none!important}
.hero{position:relative;height:250px;border-radius:0 0 28px 28px;overflow:hidden;color:#fff;
  background:radial-gradient(circle at 75% 35%,#ff9b4d 0,transparent 35%),linear-gradient(160deg,#5a2412 0%,#2a120a 60%,#120806 100%)}
.hero .food{position:absolute;right:-10px;top:40px;font-size:150px;opacity:.95;filter:drop-shadow(0 12px 18px rgba(0,0,0,.4))}
.hero .txt{position:absolute;left:20px;bottom:86px;max-width:70%}
.hero h1{margin:0;font-size:26px;line-height:1.15;font-weight:800}
.hero p{margin:6px 0 0;opacity:.85;font-size:14px}
.hbar{position:absolute;top:14px;left:16px;right:16px;display:flex;align-items:center;gap:10px}
.logo{width:40px;height:40px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:22px}
.hbar b{font-size:16px;color:#fff}
.search{margin:-30px 16px 0;position:relative;z-index:2;background:#fff;border-radius:16px;box-shadow:0 8px 24px rgba(20,40,80,.12);display:flex;align-items:center;padding:0 14px;height:52px;gap:10px}
.search input{flex:1;border:0;outline:0;background:none;font-size:15px}
.section{background:#fff;margin-top:18px;border-radius:26px 26px 0 0;padding:18px 16px 4px;min-height:200px}
.sh{display:flex;justify-content:space-between;align-items:center;margin:4px 0 12px}
.sh h2{font-size:18px;margin:0}
.sh a,.link{color:var(--p);font-size:14px;font-weight:600;cursor:pointer}
.cats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.cat{background:#f6f8fc;border-radius:16px;padding:12px 4px 10px;text-align:center;font-size:12px;font-weight:600}
.cat span{display:block;font-size:30px;margin-bottom:4px}
.promo{margin:16px 16px 0;border-radius:18px;padding:16px 18px;color:#fff;background:linear-gradient(120deg,var(--p),var(--p2));display:flex;align-items:center;justify-content:space-between;gap:10px}
.promo b{font-size:16px;display:block}
.promo small{opacity:.85}
.promo .go{width:38px;height:38px;border-radius:50%;background:#fff;color:var(--p);display:grid;place-items:center;font-size:18px;flex:none}
.item{display:flex;gap:12px;background:#fff;border-radius:var(--r);padding:10px;margin-bottom:12px;box-shadow:0 2px 10px rgba(20,40,80,.06);cursor:pointer;align-items:center}
.thumb{width:92px;height:92px;border-radius:14px;flex:none;display:grid;place-items:center;font-size:44px;overflow:hidden;background:linear-gradient(135deg,#ffd8b0,#f19a4d)}
.thumb img{width:100%;height:100%;object-fit:cover}
.item .info{flex:1;min-width:0}
.item h3{margin:0 0 4px;font-size:15px;line-height:1.25}
.item p{margin:0;color:var(--mut);font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.price{color:var(--p);font-weight:800;margin-top:8px;font-size:15px}
.addb{width:36px;height:36px;border-radius:50%;background:var(--p);color:#fff;font-size:22px;display:grid;place-items:center;flex:none;box-shadow:0 6px 14px rgba(31,111,232,.35)}
.tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:12px;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.chip{padding:9px 16px;border-radius:999px;background:#f1f4fa;font-weight:600;font-size:14px;white-space:nowrap;cursor:pointer}
.chip.on{background:var(--p);color:#fff}
.bar{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--bg);position:sticky;top:0;z-index:5}
.bar h2{font-size:17px;margin:0;text-align:center;flex:1}
.ib{width:40px;height:40px;border-radius:12px;background:#fff;display:grid;place-items:center;font-size:20px;box-shadow:0 2px 8px rgba(20,40,80,.08)}
.pad{padding:0 16px}
.card{background:#fff;border-radius:var(--r);padding:14px;box-shadow:0 2px 10px rgba(20,40,80,.06);margin-bottom:12px}
.ci{display:flex;gap:12px;align-items:center}
.ci .thumb{width:76px;height:76px;font-size:36px;border-radius:12px}
.ci .info{flex:1;min-width:0}
.ci h3{margin:0 0 6px;font-size:15px}
.qty{display:inline-flex;align-items:center;gap:0;background:#f1f4fa;border-radius:12px;overflow:hidden}
.qty button{width:34px;height:34px;font-size:18px;font-weight:700;color:var(--p)}
.qty span{min-width:30px;text-align:center;font-weight:700}
.trash{width:36px;height:36px;border-radius:10px;color:var(--red);font-size:18px;display:grid;place-items:center}
.row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;color:var(--mut)}
.row b{color:var(--tx)}
.total{display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:6px;border-top:1px dashed var(--line);font-weight:800;font-size:17px}
.total b{font-size:20px;color:var(--tx)}
.promoin{display:flex;gap:8px;align-items:center;background:#fff;border-radius:14px;padding:6px 6px 6px 14px;box-shadow:0 2px 10px rgba(20,40,80,.06);margin-bottom:12px}
.promoin input{flex:1;border:0;outline:0;background:none;text-transform:uppercase;padding:8px 0;font-size:15px}
.gobtn{width:40px;height:40px;border-radius:12px;background:var(--p);color:#fff;font-size:18px;flex:none}
.seg{display:grid;grid-template-columns:1fr 1fr;gap:8px;background:#e9eef7;border-radius:14px;padding:4px;margin-bottom:14px}
.seg button{padding:11px 8px;border-radius:11px;font-weight:700;color:#5b6578;font-size:14px}
.seg button.on{background:#fff;color:var(--p);box-shadow:0 2px 8px rgba(20,40,80,.1)}
.field{display:block;margin-bottom:12px}
.field span{display:block;font-size:13px;font-weight:700;margin-bottom:6px;color:#414b60}
.field input,.field textarea,.field select{width:100%;padding:13px 14px;border:1.5px solid var(--line);border-radius:14px;background:#fff;outline:0;font-size:15px}
.field input:focus,.field textarea:focus{border-color:var(--p2)}
.primary{display:block;width:100%;padding:16px;border-radius:16px;background:linear-gradient(120deg,var(--p),var(--p2));color:#fff;font-weight:800;font-size:16px;box-shadow:0 10px 22px rgba(31,111,232,.3)}
.primary:disabled{opacity:.6}
.badge{display:inline-block;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;background:#eef2f8;color:#48536a}
.badge.new,.badge.cooking{background:#fff4d6;color:#8a5b00}
.badge.ready,.badge.delivering{background:#e3f7ee;color:#137a50}
.badge.done{background:#e6eeff;color:var(--p)}
.badge.cancelled{background:#fde7e8;color:#b42328}
.ot{font-size:13px;color:var(--mut);margin-top:6px}
.oc h3{margin:0;font-size:16px;display:flex;justify-content:space-between;align-items:center}
.oc .items{margin:10px 0;font-size:14px;color:#414b60;line-height:1.6}
.steps{margin:10px 0 0;padding:0;list-style:none}
.steps li{display:flex;gap:10px;align-items:center;padding:4px 0;font-size:13px;color:var(--mut)}
.steps li i{width:12px;height:12px;border-radius:50%;background:#d7dee9;flex:none}
.steps li.on{color:var(--tx);font-weight:600}
.steps li.on i{background:var(--ok)}
.steps li.cur i{background:var(--p);box-shadow:0 0 0 4px rgba(31,111,232,.18)}
.empty{text-align:center;color:var(--mut);padding:40px 16px}
.empty span{font-size:52px;display:block;margin-bottom:8px}
.prof{text-align:center;padding:20px 0 8px}
.avatar{width:84px;height:84px;border-radius:50%;margin:0 auto 10px;background:linear-gradient(135deg,var(--p),var(--p2));color:#fff;display:grid;place-items:center;font-size:34px;font-weight:800}
.stats{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0}
.stat{background:#fff;border-radius:16px;padding:14px;text-align:center}
.stat b{display:block;font-size:20px}
.stat small{color:var(--mut)}
.mrow{display:flex;justify-content:space-between;align-items:center;padding:15px 4px;border-bottom:1px solid var(--line);cursor:pointer;font-weight:600}
.mrow:last-child{border:0}
.tabbar{position:fixed;left:50%;transform:translateX(-50%);bottom:0;width:100%;max-width:480px;background:#fff;display:grid;grid-template-columns:repeat(5,1fr);padding:8px 4px calc(8px + env(safe-area-inset-bottom));box-shadow:0 -6px 22px rgba(20,40,80,.08);z-index:20}
.tabbar button{display:flex;flex-direction:column;align-items:center;gap:2px;font-size:11px;color:#8a93a6;font-weight:600;position:relative;padding:4px 0}
.tabbar button .ic{font-size:21px;line-height:1}
.tabbar button.on{color:var(--p)}
.tabbar .cnt{position:absolute;top:0;left:calc(50% + 6px);background:var(--red);color:#fff;border-radius:999px;font-size:10px;min-width:17px;height:17px;display:grid;place-items:center;padding:0 4px}
.sheetwrap{position:fixed;inset:0;background:rgba(15,22,38,.45);display:flex;align-items:flex-end;justify-content:center;z-index:40}
.sheet{width:100%;max-width:480px;background:#fff;border-radius:26px 26px 0 0;max-height:92vh;overflow:auto;animation:up .25s ease-out}
@keyframes up{from{transform:translateY(40px);opacity:.4}to{transform:none;opacity:1}}
.sheet .hero2{height:220px;display:grid;place-items:center;font-size:120px;background:linear-gradient(135deg,#ffd8b0,#f19a4d);position:relative}
.sheet .hero2 img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.sheet .close{position:absolute;top:14px;right:14px;width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.92);font-size:16px;z-index:2}
.sheet .body{padding:18px 18px 22px}
.sheet h2{margin:0 0 6px;font-size:21px}
.sheet .desc{color:var(--mut);margin:0 0 14px}
.sheet .big{color:var(--p);font-size:22px;font-weight:800}
.sheet h4{margin:18px 0 8px;font-size:15px}
.chk{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid var(--line);cursor:pointer}
.chk input{width:20px;height:20px;accent-color:var(--p)}
.chk span{flex:1}
.chk em{font-style:normal;color:var(--mut);font-size:13px}
.sheet .row2{display:flex;align-items:center;justify-content:space-between;margin:16px 0}
.toast{position:fixed;left:50%;bottom:110px;transform:translateX(-50%);background:#1b2333;color:#fff;padding:12px 18px;border-radius:14px;font-size:14px;z-index:60;opacity:0;transition:.25s;pointer-events:none;max-width:90%}
.toast.show{opacity:1}
.admin{max-width:1100px;margin:0 auto;padding:16px}
.adm-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
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
const load = (k, d) => { try { return JSON.parse(localStorage.getItem('pl_' + k)) ?? d; } catch (e) { return d; } };
const saveLS = (k, v) => { try { localStorage.setItem('pl_' + k, JSON.stringify(v)); } catch (e) {} };
const STATUS_STEPS = ['new', 'cooking', 'ready', 'delivering', 'done'];
const STATUS_LABEL = {new:'Qabul qilindi', cooking:'Tayyorlanmoqda', ready:'Tayyor', delivering:'Yo‘lda', done:'Yakunlandi', cancelled:'Bekor qilindi'};
const DEFAULT_ICON = '🍽';

const S = {
  tab: 'home', cats: [], products: [], extras: [], fee: 10000, promos: {},
  cart: load('cart', []), catId: 0, q: '', orderType: 'delivery', payment: 'cash',
  promo: '', promoOk: false, orders: null, user: null, ordersTab: 'active',
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
const discountAmt = () => S.promoOk ? Math.round(subtotal() * (S.promos[S.promo] || 0) / 100) : 0;
const deliveryAmt = () => S.orderType === 'delivery' && S.cart.length ? S.fee : 0;
const totalAmt = () => subtotal() - discountAmt() + deliveryAmt();
const cartCount = () => S.cart.reduce((s, i) => s + i.qty, 0);
const iconOf = catId => S.cats.find(c => c.id == catId)?.icon || DEFAULT_ICON;

function toast(msg) {
  const t = $('#toast'); t.textContent = msg; t.classList.add('show');
  clearTimeout(toast._t); toast._t = setTimeout(() => t.classList.remove('show'), 1800);
}

function thumb(p, size) {
  const inner = p.image ? `<img src="${esc(p.image)}" alt="">` : esc(iconOf(p.category_id));
  return `<div class="thumb" ${size ? `style="${size}"` : ''}>${inner}</div>`;
}

/* ---------- VIEWS ---------- */
function homeHTML() {
  const feat = S.products.filter(p => p.featured == 1).slice(0, 6);
  const list = feat.length ? feat : S.products.slice(0, 6);
  return `
  <section class="hero">
    <div class="hbar"><div class="logo">👨‍🍳</div><b>Mening Restoran</b></div>
    <div class="food">🍢</div>
    <div class="txt"><h1>Mazali taomlar<br>sizni kutmoqda!</h1><p>Eng yaxshi taomlar bir joyda</p></div>
  </section>
  <div class="search" data-act="gomenu" style="cursor:pointer"><span>🔍</span><input placeholder="Taom yoki kategoriya qidirish..." readonly></div>
  <div class="promo" data-act="copypromo">
    <div><b>Birinchi buyurtmaga 5% chegirma!</b><small>Promo-kod: POLAT5</small></div>
    <div class="go">›</div>
  </div>
  <div class="section">
    <div class="sh"><h2>Kategoriyalar</h2><a data-act="cat" data-v="0">Barchasi</a></div>
    <div class="cats">${S.cats.map(c => `<div class="cat" data-act="cat" data-v="${c.id}"><span>${esc(c.icon)}</span>${esc(c.name)}</div>`).join('')}</div>
    <div class="sh" style="margin-top:22px"><h2>Ommabop taomlar</h2><a data-act="cat" data-v="0">Barchasi</a></div>
    ${list.map(p => itemHTML(p)).join('') || emptyHTML('🍽', 'Hozircha taomlar yo‘q')}
  </div>`;
}

function itemHTML(p) {
  return `<div class="item" data-act="open" data-v="${p.id}">
    ${thumb(p)}
    <div class="info"><h3>${esc(p.name)}</h3><p>${esc(p.description)}</p>
      <div class="price">${money(p.price)}</div></div>
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
  return `<div class="bar" style="background:#fff;border-bottom:0"><span style="width:40px"></span><h2>Menyu</h2><span style="width:40px"></span></div>
  <div class="pad">
    <div class="search" style="margin:0 0 12px;box-shadow:none;border:1.5px solid var(--line)"><span>🔍</span>
      <input id="q" placeholder="Qidirish..." value="${esc(S.q)}" autocomplete="off"></div>
    <div class="tabs">
      <span class="chip ${!S.catId ? 'on' : ''}" data-act="cat" data-v="0">Barchasi</span>
      ${S.cats.map(c => `<span class="chip ${S.catId == c.id ? 'on' : ''}" data-act="cat" data-v="${c.id}">${esc(c.icon)} ${esc(c.name)}</span>`).join('')}
    </div>
    <div id="list">${menuListHTML()}</div>
  </div>`;
}

function emptyHTML(icon, text) { return `<div class="empty"><span>${icon}</span>${esc(text)}</div>`; }

function cartHTML() {
  if (!S.cart.length) return `<div class="bar"><span></span><h2>Savat</h2><span></span></div>
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
  return `<div class="bar"><span style="width:40px"></span><h2>Savat</h2><span style="width:40px"></span></div>
  <div class="pad">
    ${items}
    <div class="seg" style="margin-top:6px">
      <button class="${S.orderType === 'delivery' ? 'on' : ''}" data-act="otype" data-v="delivery">🛵 Yetkazib berish</button>
      <button class="${S.orderType === 'pickup' ? 'on' : ''}" data-act="otype" data-v="pickup">🏃 Olib ketish</button>
    </div>
    <div class="promoin"><span>🏷</span>
      <input id="promo" placeholder="Promo-kod kiriting" value="${esc(S.promo)}" autocomplete="off">
      <button class="gobtn" data-act="promo">→</button></div>
    ${S.promoOk ? `<div class="ot" style="color:var(--ok);margin:-6px 4px 10px">Promo-kod qo‘llandi: −${S.promos[S.promo]}%</div>` : ''}
    <div class="card">
      <div class="row">Mahsulotlar <b>${money(subtotal())}</b></div>
      <div class="row">Yetkazib berish <b>${money(deliveryAmt())}</b></div>
      ${discountAmt() ? `<div class="row">Chegirma <b style="color:var(--ok)">−${money(discountAmt())}</b></div>` : ''}
      <div class="total"><span>Jami</span><b>${money(totalAmt())}</b></div>
    </div>
    <button class="primary" data-act="checkout">Buyurtma berish →</button>
  </div>`;
}

function checkoutHTML() {
  return `<div class="bar"><button class="ib" data-act="tab" data-v="cart">‹</button><h2>Rasmiylashtirish</h2><span style="width:40px"></span></div>
  <div class="pad">
    <div class="card">
      <div class="row">Buyurtma turi <b>${S.orderType === 'delivery' ? 'Yetkazib berish' : 'Olib ketish'}</b></div>
      <div class="row">Taomlar soni <b>${cartCount()} ta</b></div>
      <div class="total"><span>To‘lov jami</span><b>${money(totalAmt())}</b></div>
    </div>
    <label class="field"><span>Ism</span><input id="f_name" value="${esc(S.user?.name || '')}" autocomplete="name"></label>
    <label class="field"><span>Telefon</span><input id="f_phone" placeholder="+998 90 123 45 67" inputmode="tel" autocomplete="tel"></label>
    ${S.orderType === 'delivery' ? `<label class="field"><span>Yetkazib berish manzili</span><textarea id="f_addr" rows="2" placeholder="Ko‘cha, uy, xonadon"></textarea></label>` : ''}
    <label class="field"><span>Izoh (ixtiyoriy)</span><input id="f_note" placeholder="Masalan: eshik oldida"></label>
    <div class="seg">
      <button class="${S.payment === 'cash' ? 'on' : ''}" data-act="pay" data-v="cash">💵 Naqd</button>
      <button class="${S.payment === 'card' ? 'on' : ''}" data-act="pay" data-v="card">💳 Karta</button>
    </div>
    <button class="primary" id="submitBtn" data-act="submit">Buyurtmani tasdiqlash</button>
  </div>`;
}

function ordersHTML() {
  if (!S.orders) return `<div class="bar"><span></span><h2>Buyurtmalarim</h2><span></span></div><div class="pad">${emptyHTML('⏳', 'Yuklanmoqda...')}</div>`;
  const active = S.orders.filter(o => !['done', 'cancelled'].includes(o.status));
  const hist = S.orders.filter(o => ['done', 'cancelled'].includes(o.status));
  const list = S.ordersTab === 'active' ? active : hist;
  return `<div class="bar"><span></span><h2>Buyurtmalarim</h2><span></span></div>
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
  const idx = STATUS_STEPS.indexOf(o.status);
  const steps = o.status === 'cancelled' ? '' : `<ul class="steps">${STATUS_STEPS.map((s, i) =>
    `<li class="${i <= idx ? 'on' : ''} ${i === idx ? 'cur' : ''}"><i></i>${STATUS_LABEL[s]}</li>`).join('')}</ul>`;
  return `<div class="card oc">
    <h3>#${o.id} <span class="badge ${esc(o.status)}">${esc(STATUS_LABEL[o.status] || o.status)}</span></h3>
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
  return `<div class="bar"><span></span><h2>Profil</h2><span></span></div>
  <div class="pad">
    <div class="prof"><div class="avatar">${esc(name.charAt(0).toUpperCase())}</div>
      <b style="font-size:18px">${esc(name)}</b>
      <div class="ot">${S.user?.username ? '@' + esc(S.user.username) : 'Telegram foydalanuvchisi'}</div></div>
    <div class="stats">
      <div class="stat"><b>${o.length}</b><small>Buyurtmalar</small></div>
      <div class="stat"><b>${money(spent)}</b><small>Jami xarid</small></div>
    </div>
    <div class="card" style="padding:4px 14px">
      <div class="mrow" data-act="ordtab" data-v="history">Buyurtmalar tarixi <span>›</span></div>
      <div class="mrow" data-act="tab" data-v="orders">Faol buyurtmalar <span>›</span></div>
      <div class="mrow" data-act="tab" data-v="menu">Menyu <span>›</span></div>
    </div>
    <div class="ot" style="text-align:center;margin-top:18px">Po’lathon Shashlik Somsa</div>
  </div>`;
}

/* ---------- ROUTER / RENDER ---------- */
const TABS = [['home','🏠','Bosh sahifa'],['menu','🍽','Menyu'],['cart','🛒','Savat'],['orders','🧾','Buyurtmalar'],['profile','👤','Profil']];

function renderTabbar() {
  const bar = $('#tabbar');
  bar.classList.toggle('hidden', S.tab === 'checkout');
  bar.innerHTML = TABS.map(([k, ic, lb]) => `<button class="${S.tab === k ? 'on' : ''}" data-act="tab" data-v="${k}">
    <span class="ic">${ic}</span>${lb}${k === 'cart' && cartCount() ? `<span class="cnt">${cartCount()}</span>` : ''}</button>`).join('');
}

function render() {
  const h = {home: homeHTML, menu: menuHTML, cart: cartHTML, checkout: checkoutHTML, orders: ordersHTML, profile: profileHTML}[S.tab];
  const scroll = window.scrollY;
  $('#app').innerHTML = S.tab === 'home' ? h() : `<div style="padding-top:8px">${h()}</div>`;
  renderTabbar();
  window.scrollTo(0, S.tab === 'home' ? scroll : 0);
}

function go(tab) {
  S.tab = tab;
  if (tab === 'orders' || tab === 'profile') loadOrders();
  render();
}

async function loadOrders() {
  try {
    const r = await api('orders');
    S.orders = r.orders; S.user = r.user;
    if (S.tab === 'orders' || S.tab === 'profile') render();
  } catch (e) {
    S.orders = S.orders || [];
    if (S.tab === 'orders') $('#app').innerHTML = emptyHTML('🔒', e.message);
  }
}

/* ---------- CART ACTIONS ---------- */
function addToCart(id, extras = [], qty = 1) {
  const key = keyOf(id, extras);
  const ex = S.cart.find(i => i.key === key);
  if (ex) ex.qty = Math.min(50, ex.qty + qty);
  else S.cart.push({ key, id: Number(id), extras: extras.map(Number).sort((a, b) => a - b), qty });
  saveLS('cart', S.cart);
  toast('Savatga qo‘shildi ✓');
  if (S.tab !== 'checkout') renderTabbar();
}

function changeQty(key, d) {
  const it = S.cart.find(i => i.key === key);
  if (!it) return;
  it.qty += d;
  if (it.qty < 1) S.cart = S.cart.filter(i => i !== it);
  saveLS('cart', S.cart);
  render();
}

/* ---------- PRODUCT SHEET ---------- */
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
      ${S.extras.length ? `<h4>Qo‘shimchalar</h4>` + S.extras.map(e => `
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

/* ---------- ORDER SUBMIT ---------- */
async function submitOrder() {
  const val = id => ($('#' + id)?.value || '').trim();
  const body = {
    items: S.cart.map(i => ({ id: i.id, qty: i.qty, extras: i.extras })),
    order_type: S.orderType, name: val('f_name'), phone: val('f_phone'), address: val('f_addr'),
    note: val('f_note'), payment: S.payment, promo: S.promoOk ? S.promo : '',
  };
  if (!body.name) return toast('Ismingizni kiriting');
  if (!/^\+?[0-9 ()\-]{7,25}$/.test(body.phone)) return toast('Telefon raqamini to‘g‘ri kiriting');
  if (S.orderType === 'delivery' && !body.address) return toast('Manzilni kiriting');
  const btn = $('#submitBtn'); btn.disabled = true; btn.textContent = 'Yuborilmoqda...';
  try {
    const r = await api('order', body);
    S.cart = []; saveLS('cart', S.cart);
    S.promo = ''; S.promoOk = false; S.orders = null;
    toast('Buyurtma #' + r.order_id + ' qabul qilindi 🎉');
    go('orders');
  } catch (e) {
    toast(e.message);
    btn.disabled = false; btn.textContent = 'Buyurtmani tasdiqlash';
  }
}

/* ---------- EVENTS ---------- */
document.addEventListener('click', e => {
  if (e.target.id === 'sheet') return closeSheet();
  const el = e.target.closest('[data-act]');
  if (!el) return;
  const a = el.dataset.act, v = el.dataset.v;
  switch (a) {
    case 'tab': go(v); break;
    case 'gomenu': S.catId = 0; go('menu'); break;
    case 'copypromo': toast('Promo-kod: POLAT5 — savatda kiriting'); break;
    case 'cat': S.catId = Number(v); if (S.tab !== 'menu') go('menu'); else render(); break;
    case 'open': openProduct(Number(v)); break;
    case 'quick': addToCart(Number(v)); break;
    case 'qty': changeQty(v, Number(el.dataset.d)); break;
    case 'del': S.cart = S.cart.filter(i => i.key !== v); saveLS('cart', S.cart); render(); break;
    case 'otype': S.orderType = v; render(); break;
    case 'promo': {
      const code = ($('#promo')?.value || '').trim().toUpperCase();
      S.promo = code;
      S.promoOk = !!S.promos[code];
      toast(S.promoOk ? 'Promo-kod qo‘llandi ✓' : 'Bunday promo-kod yo‘q');
      render(); break;
    }
    case 'checkout':
      if (!S.cart.length) return toast('Savat bo‘sh');
      go('checkout'); break;
    case 'pay': S.payment = v; render(); break;
    case 'submit': submitOrder(); break;
    case 'ordtab': S.ordersTab = v; if (S.tab !== 'orders') go('orders'); else render(); break;
    case 'closesheet': closeSheet(); break;
    case 'pdqty': SH.qty = Math.max(1, Math.min(50, SH.qty + Number(el.dataset.d))); renderSheet(); break;
    case 'pdadd': addToCart(SH.id, [...SH.extras], SH.qty); closeSheet(); renderTabbar(); break;
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
    S.cats = r.categories; S.products = r.products; S.extras = r.extras;
    S.fee = Number(r.delivery_fee); S.promos = r.promos;
  } catch (e) {
    $('#app').innerHTML = emptyHTML('⚠️', e.message);
    return;
  }
  render();
  loadOrders();
}
window.addEventListener('DOMContentLoaded', boot);
</script>
</body>
</html>
HTML;
}

/* ============== SAHIFA: ADMIN PANEL ============== */
function render_admin(): void {
  header('Content-Type: text/html; charset=utf-8');
  echo <<<'HTML'
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Po’lathon</title>
<style>
:root{--p:#1f6fe8;--bg:#eef2f8;--tx:#1b2333;--mut:#7a8498;--line:#e6ebf3;--red:#e5484d;--ok:#22a06b}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--tx);font:14px/1.45 -apple-system,Segoe UI,Roboto,sans-serif}
button,input,select,textarea{font:inherit;color:inherit}
.wrap{max-width:1100px;margin:0 auto;padding:16px}
.top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#1b2333;color:#fff;padding:14px 18px;border-radius:18px;margin-bottom:16px}
.top b{font-size:17px;margin-right:auto}
.top button{background:transparent;color:#fff;border:1px solid #3a4560;border-radius:12px;padding:8px 12px;cursor:pointer}
.top button.on{background:var(--p);border-color:var(--p)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}
.kpi{background:#fff;border-radius:16px;padding:16px;box-shadow:0 2px 10px rgba(20,40,80,.06)}
.kpi small{color:var(--mut)}
.kpi b{display:block;font-size:22px;margin-top:4px}
.card{background:#fff;border-radius:16px;padding:16px;box-shadow:0 2px 10px rgba(20,40,80,.06);margin-bottom:12px}
.card h3{margin:0 0 10px;font-size:16px;display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}
.muted{color:var(--mut)}
.items{margin:8px 0;line-height:1.6}
.row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:space-between}
select,input,textarea{padding:9px 11px;border:1.5px solid var(--line);border-radius:10px;background:#fff;outline:0}
.btn{padding:9px 14px;border-radius:10px;border:0;background:var(--p);color:#fff;font-weight:700;cursor:pointer}
.btn.ghost{background:#f1f4fa;color:var(--tx)}
.btn.red{background:#fde7e8;color:#b42328}
.login{max-width:380px;margin:12vh auto;background:#fff;padding:26px;border-radius:20px;box-shadow:0 10px 30px rgba(20,40,80,.1)}
.login h2{margin:0 0 14px}
.login input{width:100%;margin-bottom:10px}
.login .btn{width:100%;padding:12px}
.err{color:var(--red);min-height:18px;margin:6px 0}
.modal{position:fixed;inset:0;background:rgba(15,22,38,.5);display:grid;place-items:center;padding:16px;z-index:50}
.modal .box{background:#fff;border-radius:18px;padding:20px;width:100%;max-width:480px;max-height:92vh;overflow:auto}
.f{display:block;margin-bottom:10px}
.f span{display:block;font-size:12px;font-weight:700;color:#414b60;margin-bottom:4px}
.f input,.f textarea,.f select{width:100%}
.hidden{display:none!important}
.badge{padding:3px 9px;border-radius:999px;background:#eef2f8;font-size:12px;font-weight:700}
table{width:100%;border-collapse:collapse}
@media (max-width:640px){.hide-m{display:none}}
</style>
</head>
<body>
<div class="wrap" id="root"></div>
<script>
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const money = n => String(Math.round(Number(n) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' so‘m';
const A = { tab: 'dash', stats: null, orders: [], statuses: {}, products: [], cats: [], filter: 'all' };

async function aapi(name, body) {
  const opt = { method: body ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json', 'X-Admin': '1' }, credentials: 'same-origin' };
  if (body) opt.body = JSON.stringify(body);
  const r = await fetch('index.php?admin=1&api=' + encodeURIComponent(name), opt);
  let j; try { j = await r.json(); } catch (e) { throw new Error('Server xatosi'); }
  if (!j.ok) { const err = new Error(j.error || 'Xatolik'); err.code = r.status; throw err; }
  return j;
}

function loginHTML(msg = '') {
  return `<div class="login"><h2>Po’lathon Admin</h2>
    <input id="u" placeholder="Login" autocomplete="username">
    <input id="p" type="password" placeholder="Parol" autocomplete="current-password">
    <div class="err">${esc(msg)}</div>
    <button class="btn" id="loginBtn">Kirish</button></div>`;
}

function shell(body) {
  return `<div class="top"><b>🍢 Po’lathon — Admin</b>
    <button class="${A.tab === 'dash' ? 'on' : ''}" data-tab="dash">Bosh sahifa</button>
    <button class="${A.tab === 'orders' ? 'on' : ''}" data-tab="orders">Buyurtmalar</button>
    <button class="${A.tab === 'menu' ? 'on' : ''}" data-tab="menu">Menyu</button>
    <button data-tab="logout">Chiqish</button></div>${body}`;
}

function dashHTML() {
  const s = A.stats || {};
  return `<div class="grid">
    <div class="kpi"><small>Bugungi buyurtmalar</small><b>${s.today_orders ?? '—'}</b></div>
    <div class="kpi"><small>Bugungi savdo</small><b>${money(s.today_revenue)}</b></div>
    <div class="kpi"><small>Faol buyurtmalar</small><b>${s.active ?? '—'}</b></div>
    <div class="kpi"><small>Mijozlar</small><b>${s.customers ?? '—'}</b></div>
  </div>
  <div class="card"><h3>So‘nggi buyurtmalar <a href="#" data-tab="orders" class="muted">Barchasi →</a></h3>${ordersListHTML(A.orders.slice(0, 8))}</div>`;
}

function statusSelect(o) {
  return `<select data-status="${o.id}">${Object.entries(A.statuses).map(([k, v]) =>
    `<option value="${k}" ${k === o.status ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select>`;
}

function ordersListHTML(list) {
  if (!list.length) return '<p class="muted">Buyurtmalar yo‘q</p>';
  return list.map(o => `<div class="card" style="margin:0 0 10px">
    <h3><span>#${o.id} — ${esc(o.customer_name)}</span><span class="muted" style="font-weight:400">${esc(o.created_at)}</span></h3>
    <div class="muted">${esc(o.phone)} · ${o.order_type === 'delivery' ? '🛵 ' + esc(o.address) : '🏃 Olib ketish'} · ${o.payment === 'card' ? 'Karta' : 'Naqd'}</div>
    <div class="items">${(o.items || []).map(i => `${i.qty} × ${esc(i.product_name)}${i.extras ? ` <span class="muted">(${esc(i.extras)})</span>` : ''}`).join('<br>')}</div>
    ${o.note ? `<div class="muted">Izoh: ${esc(o.note)}</div>` : ''}
    <div class="row"><b>${money(o.total)}</b>${statusSelect(o)}</div>
  </div>`).join('');
}

function ordersHTML() {
  const list = A.filter === 'all' ? A.orders : A.orders.filter(o => o.status === A.filter);
  return `<div class="row" style="margin-bottom:12px"><b>Buyurtmalar (${list.length})</b>
    <select id="flt"><option value="all">Hammasi</option>${Object.entries(A.statuses).map(([k, v]) =>
      `<option value="${k}" ${A.filter === k ? 'selected' : ''}>${esc(v)}</option>`).join('')}</select></div>
    ${ordersListHTML(list)}`;
}

function menuHTML() {
  return `<div class="row" style="margin-bottom:12px"><b>Taomlar (${A.products.length})</b>
    <button class="btn" data-act="new">+ Taom qo‘shish</button></div>
    <div class="card" style="overflow:auto"><table>
      <tr class="muted"><th align="left">Nomi</th><th align="left" class="hide-m">Kategoriya</th><th align="right">Narx</th><th>Mavjud</th><th></th></tr>
      ${A.products.map(p => `<tr style="border-top:1px solid var(--line)">
        <td style="padding:8px 4px"><b>${esc(p.name)}</b>${p.featured == 1 ? ' ⭐' : ''}<div class="muted">${esc(p.description)}</div></td>
        <td class="hide-m">${esc(p.category)}</td>
        <td align="right" style="white-space:nowrap">${money(p.price)}</td>
        <td align="center"><input type="checkbox" data-avail="${p.id}" ${p.available == 1 ? 'checked' : ''}></td>
        <td align="right" style="white-space:nowrap"><button class="btn ghost" data-act="edit" data-v="${p.id}">Tahrirlash</button>
          <button class="btn red" data-act="del" data-v="${p.id}">O‘chirish</button></td></tr>`).join('')}
    </table></div>`;
}

function productModal(p = {}) {
  const catOpts = A.cats.map(c => `<option value="${c.id}" ${c.id == p.category_id ? 'selected' : ''}>${esc(c.icon)} ${esc(c.name)}</option>`).join('');
  const wrap = document.createElement('div');
  wrap.className = 'modal'; wrap.id = 'pm';
  wrap.innerHTML = `<div class="box"><h3 style="margin-top:0">${p.id ? 'Taomni tahrirlash' : 'Yangi taom'}</h3>
    <label class="f"><span>Nomi</span><input id="pn" value="${esc(p.name)}"></label>
    <label class="f"><span>Kategoriya</span><select id="pc">${catOpts}</select></label>
    <label class="f"><span>Tavsif</span><textarea id="pd" rows="2">${esc(p.description)}</textarea></label>
    <label class="f"><span>Narx (so‘m)</span><input id="pp" type="number" min="0" value="${Number(p.price) || ''}"></label>
    <label class="f"><span>Rasm URL (ixtiyoriy, https://...)</span><input id="pi" value="${esc(p.image)}"></label>
    <label class="f"><input type="checkbox" id="pf" ${p.featured == 1 ? 'checked' : ''}> Ommabop (bosh sahifada)</label>
    <label class="f"><input type="checkbox" id="pa" ${p.available == undefined || p.available == 1 ? 'checked' : ''}> Mavjud</label>
    <div class="err" id="perr"></div>
    <div class="row"><button class="btn ghost" data-act="cancel">Bekor</button><button class="btn" data-act="save" data-v="${p.id || 0}">Saqlash</button></div>
  </div>`;
  document.body.appendChild(wrap);
}

function render() {
  const root = $('#root');
  if (!A.ok) { root.innerHTML = loginHTML(A.msg || ''); return; }
  const body = A.tab === 'dash' ? dashHTML() : A.tab === 'orders' ? ordersHTML() : menuHTML();
  root.innerHTML = shell(body);
}

async function loadAll() {
  const [st, od, pr] = await Promise.all([aapi('admin_stats'), aapi('admin_orders'), aapi('admin_products')]);
  A.stats = st; A.orders = od.orders; A.statuses = od.statuses; A.products = pr.products; A.cats = pr.categories;
}

async function enter() {
  try { await loadAll(); A.ok = true; A.msg = ''; }
  catch (e) { A.ok = false; A.msg = e.code === 401 ? '' : e.message; }
  render();
}

document.addEventListener('click', async e => {
  const t = e.target;
  if (t.id === 'loginBtn') {
    try {
      await aapi('admin_login', { username: $('#u').value, password: $('#p').value });
      await enter();
    } catch (err) { A.ok = false; A.msg = err.message; render(); }
    return;
  }
  if (t.dataset.tab) {
    e.preventDefault();
    if (t.dataset.tab === 'logout') { await aapi('admin_logout', {}).catch(() => {}); A.ok = false; A.msg = ''; return render(); }
    A.tab = t.dataset.tab; return render();
  }
  const act = t.dataset.act;
  if (act === 'new') return productModal({});
  if (act === 'edit') return productModal(A.products.find(p => p.id == t.dataset.v));
  if (act === 'cancel') return $('#pm')?.remove();
  if (act === 'del') {
    if (!confirm('Taomni o‘chirasizmi?')) return;
    await aapi('admin_product_delete', { id: Number(t.dataset.v) });
    return enter();
  }
  if (act === 'save') {
    const body = {
      id: Number(t.dataset.v), name: $('#pn').value.trim(), category_id: Number($('#pc').value),
      description: $('#pd').value, price: Number($('#pp').value), image: $('#pi').value.trim(),
      featured: $('#pf').checked, available: $('#pa').checked,
    };
    try { await aapi('admin_product_save', body); $('#pm')?.remove(); return enter(); }
    catch (err) { $('#perr').textContent = err.message; }
  }
});
document.addEventListener('change', async e => {
  const t = e.target;
  if (t.dataset.status) {
    try {
      await aapi('admin_order_status', { id: Number(t.dataset.status), status: t.value });
      const o = A.orders.find(x => x.id == t.dataset.status); if (o) o.status = t.value;
      toast('Holat saqlandi');
    } catch (err) { alert(err.message); }
  }
  if (t.dataset.avail) {
    await aapi('admin_product_save', Object.assign({}, A.products.find(p => p.id == t.dataset.avail),
      { id: Number(t.dataset.avail), available: t.checked, featured: A.products.find(p => p.id == t.dataset.avail).featured == 1,
        category_id: A.products.find(p => p.id == t.dataset.avail).category_id, image: A.products.find(p => p.id == t.dataset.avail).image || '' }))
      .catch(err => alert(err.message));
    return enter();
  }
  if (t.id === 'flt') { A.filter = t.value; render(); }
});
document.addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.id === 'p') $('#loginBtn')?.click(); });

function toast(m) { const d = document.createElement('div'); d.textContent = m; d.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1b2333;color:#fff;padding:10px 16px;border-radius:12px;z-index:60'; document.body.appendChild(d); setTimeout(() => d.remove(), 1500); }

enter();
</script>
</body>
</html>
HTML;
}
