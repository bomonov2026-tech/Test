<?php
declare(strict_types=1);

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\RPCErrorException;

/* ========== SOZLAMA (Hetzner VPS) ==========
 * Tuzilma:  /var/www/tgm/public/index.php   <- nginx root (faqat shu fayl ochiq)
 *           /var/www/tgm/app/config.php     <- maxfiy sozlamalar (config.example.php dan)
 *           /var/www/tgm/app/vendor/        <- composer (MadelineProto)
 *           /var/www/tgm/app/data/          <- sqlite + sessiyalar
 * Boshqa joy kerak bo'lsa: php-fpm pool'da env[TGM_APP_DIR] = /yo'l
 */
define('APP_DIR', rtrim((string)(getenv('TGM_APP_DIR') ?: dirname(__DIR__) . '/app'), '/'));
if (!is_file(APP_DIR . '/config.php')) { http_response_code(500); exit('config.php topilmadi: ' . APP_DIR . '/config.php (config.example.php dan nusxa oling).'); }
$CFG = (array)require APP_DIR . '/config.php';
/* ============================================================= */

if (PHP_VERSION_ID < 80200) { http_response_code(500); exit('PHP 8.2+ kerak (8.3 tavsiya etiladi).'); }
if (!is_file(APP_DIR . '/vendor/autoload.php')) {
    http_response_code(500); exit('vendor/autoload.php topilmadi: ' . APP_DIR . '/vendor/');
}
require APP_DIR . '/vendor/autoload.php';
// Hetzner serverlari odatda UTC da — tarix/jurnal vaqti mahalliy bo'lsin
date_default_timezone_set((string)($CFG['timezone'] ?? 'Asia/Tashkent'));
umask(0077);
@mkdir(APP_DIR . '/data', 0700, true);
@chmod(APP_DIR . '/data', 0700);

$IS_CRON = PHP_SAPI === 'cli' || isset($_GET['cron']);
if (!$IS_CRON) {
ini_set('session.use_strict_mode', '1');
session_name('tgm_sid');
// Telegram Web/Desktop Mini Appni iframe ichida ochadi: Strict cookie u yerda yuborilmaydi
// va login aylanib qoladi. CSRF himoyasi tokendan, shuning uchun None + Secure xavfsiz.
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'None']);
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; script-src 'unsafe-inline' https://telegram.org; frame-ancestors 'self' https://web.telegram.org https://*.telegram.org");
}

/* ---------- yordamchilar ---------- */
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string {
    return '<input type="hidden" name="_t" value="' . h(csrf()) . '">'
        . (isset($_SESSION['auth']) ? '<input type="hidden" name="_acc" value="' . acc_active() . '">' : '');
}
function csrf_ok(): bool { return isset($_POST['_t'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['_t']); }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function go(string $p): never { header('Location: ?p=' . urlencode($p)); exit; }

function db(): PDO {
    static $p = null;
    if ($p) return $p;
    $p = new PDO('sqlite:' . APP_DIR . '/data/panel.sqlite');
    $p->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $p->exec('PRAGMA busy_timeout = 5000');
    $p->exec("CREATE TABLE IF NOT EXISTS items(id INTEGER PRIMARY KEY AUTOINCREMENT, created_at TEXT, type TEXT, title TEXT, about TEXT, tg_id TEXT, invite_link TEXT, status TEXT, error TEXT, admin_target TEXT, admin_status TEXT, link_sent TEXT);
              CREATE TABLE IF NOT EXISTS logs(id INTEGER PRIMARY KEY AUTOINCREMENT, ts TEXT, level TEXT, msg TEXT);
              CREATE TABLE IF NOT EXISTS settings(k TEXT PRIMARY KEY, v TEXT);
              CREATE TABLE IF NOT EXISTS fails(ip TEXT, ts INTEGER);
              CREATE TABLE IF NOT EXISTS member_queue(job_id TEXT, user_id TEXT, peer TEXT, status TEXT, reason TEXT, PRIMARY KEY(job_id,user_id));
              CREATE TABLE IF NOT EXISTS dm_queue(job_id TEXT, user_id TEXT, peer TEXT, status TEXT, reason TEXT, random_id TEXT, PRIMARY KEY(job_id,user_id));");
    // Qaysi hisobdan yaratilgani (eski bazalarga ustun qo'shiladi)
    $cols = $p->query('PRAGMA table_info(items)')->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('acc', $cols, true)) $p->exec('ALTER TABLE items ADD COLUMN acc INTEGER');
    return $p;
}
function logx(string $level, string $msg): void {
    // MUHIM: bu yerga parol, kod, API HASH, sessiya yozilmaydi — faqat xato matni.
    db()->prepare('INSERT INTO logs(ts,level,msg) VALUES(?,?,?)')->execute([date('Y-m-d H:i:s'), $level, mb_substr($msg, 0, 500)]);
}
function setting(string $k, string $d = ''): string {
    $s = db()->prepare('SELECT v FROM settings WHERE k=?'); $s->execute([$k]); $v = $s->fetchColumn();
    return $v === false ? $d : (string)$v;
}
function set_setting(string $k, string $v): void {
    db()->prepare('INSERT INTO settings(k,v) VALUES(?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v')->execute([$k, $v]);
}

/* ---------- Bir nechta Telegram hisoblari (1..50), bittasi asosiy ---------- */
const MAX_ACCOUNTS = 50;
const ACC_ROTATE_SECONDS = 3600;
function acc_list(): array {
    $l = json_decode(setting('acc_list', '[1]'), true);
    $l = array_values(array_unique(array_filter(array_map('intval', is_array($l) ? $l : []), fn($n) => $n >= 1 && $n <= MAX_ACCOUNTS)));
    sort($l);
    return $l ?: [1];
}
function acc_save_list(array $l): void { sort($l); set_setting('acc_list', json_encode(array_values(array_unique($l)))); }
function acc_active(): int {
    // Bir so'rovning barcha Telegram amallari bir xil hisobdan bajariladi.
    if (isset($GLOBALS['TGM_ACTIVE_ACCOUNT'])) return $GLOBALS['TGM_ACTIVE_ACCOUNT'];
    $l = acc_list(); $a = (int)setting('acc_active', (string)$l[0]);
    return $GLOBALS['TGM_ACTIVE_ACCOUNT'] = in_array($a, $l, true) ? $a : $l[0];
}
function acc_rotation_on(): bool { return setting('acc_rotate_on', '1') === '1'; }
function acc_rotation_reset(): void {
    set_setting('acc_rotate_next', acc_rotation_on() ? (string)(time() + ACC_ROTATE_SECONDS) : '0');
}
function acc_set_active(int $n): void {
    set_setting('acc_active', (string)$n);
    $GLOBALS['TGM_ACTIVE_ACCOUNT'] = $n;
    acc_rotation_reset();
}
// Cron ham, hisobni o'zgartiradigan web-so'rovlar ham bir xil qulfdan foydalanadi.
function acc_control_lock(): void {
    static $lock = null;
    if (is_resource($lock)) return;
    $lock = fopen(APP_DIR . '/data/cron.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) fclose($lock);
        $lock = null;
        throw new RuntimeException('Boshqa amal bajarilmoqda. Birozdan keyin qayta urining.');
    }
    unset($GLOBALS['TGM_ACTIVE_ACCOUNT']);
}
function acc_require_current(): void {
    if ((int)($_POST['_acc'] ?? 0) !== acc_active()) {
        throw new RuntimeException('Asosiy hisob almashgan. Sahifani yangilab, qayta urining.');
    }
}
// Faqat cron.lock olingach chaqiriladi. Avto yaratish o'chiq bo'lsa ham ishlaydi.
function acc_rotation_tick(): string {
    if (!acc_rotation_on()) return 'hisob almashish o‘chiq';
    $now = time();
    $next = (int)setting('acc_rotate_next', '0');
    if ($next <= 0) {
        acc_rotation_reset();
        return 'hisob almashish: birinchi almashish 1 soatdan keyin';
    }
    if ($now < $next) return 'hisob almashish: hali erta';
    $list = acc_list();
    $current = acc_active();
    $pos = (int)array_search($current, $list, true);
    $new = $list[($pos + 1) % count($list)];
    // Cron kechiksa ham hisoblar tashlab ketilmaydi: keyingi hisobga to'liq 1 soat.
    acc_set_active($new);
    if ($new === $current) return 'hisob almashish: bitta hisob';
    $msg = "Asosiy hisob avtomatik almashdi: #{$current} → #{$new}";
    logx('INFO', $msg);
    return $msg;
}
function acc_name(int $n): string { return setting('acc_name_' . $n); }
function acc_label(int $n): string { $nm = acc_name($n); return '#' . $n . ($nm !== '' ? ' · ' . $nm : ' · (ulanmagan)'); }
function acc_remember_self(array $me): void {
    $nm = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? '')) . (isset($me['username']) ? ' @' . $me['username'] : '');
    set_setting('acc_name_' . acc_active(), mb_substr(trim($nm), 0, 80));
}

function session_dir(?int $slot = null): string {
    $uid = (string)($_SESSION['tg_user_id'] ?? '');
    if ($uid === '' || !ctype_digit($uid)) { throw new RuntimeException('Telegram foydalanuvchisi tasdiqlanmagan.'); }
    $slot ??= acc_active();
    // 1-hisob eski papkada qoladi (mavjud sessiya buzilmaydi), qolganlari: <uid>-<N>
    return APP_DIR . '/data/sessions/' . $uid . ($slot === 1 ? '' : '-' . $slot);
}

// Chiqilgan yoki buzilgan MadelineProto sessiyasini o'chiradi, keyin qaytadan kirish mumkin.
function tg_reset_session(?int $slot = null): void {
    $dir = session_dir($slot);
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($it as $f) { $f->isDir() && !$f->isLink() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
}

function tg(?int $slot = null): API {
    static $cache = [];
    global $CFG;
    $slot ??= acc_active();
    if (isset($cache[$slot])) return $cache[$slot];
    // Har bir hisobga alohida MadelineProto sessiyasi.
    $sessionDir = session_dir($slot);
    if (!is_dir($sessionDir) && !mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) {
        throw new RuntimeException('Xavfsiz sessiya papkasini yaratib bo‘lmadi.');
    }
    @chmod(APP_DIR . '/data/sessions', 0700);
    @chmod($sessionDir, 0700);
    $app = new AppInfo();
    $app->setApiId((int)$CFG['api_id']);
    $app->setApiHash((string)$CFG['api_hash']);
    $s = new Settings();
    $s->setAppInfo($app);
    // Ipc::setSlow() faqat eski (v7) versiyalarda bor; yangisida yo'q — tekshirib chaqiramiz
    if (class_exists(Settings\Ipc::class)) {
        $ipc = new Settings\Ipc();
        if (method_exists($ipc, 'setSlow')) { $ipc->setSlow(true); $s->setIpc($ipc); }
    }
    $lg = new Settings\Logger();
    if (method_exists($lg, 'setType')) $lg->setType(\danog\MadelineProto\Logger::FILE_LOGGER);
    if (method_exists($lg, 'setExtra')) $lg->setExtra($sessionDir . '/madeline.log');
    if (method_exists($lg, 'setLevel')) $lg->setLevel(\danog\MadelineProto\Logger::LEVEL_ERROR);
    $s->setLogger($lg);
    return $cache[$slot] = new API($sessionDir . '/session.madeline', $s);
}

function tg_error(Throwable $e): string {
    $m = $e->getMessage();
    if ($e instanceof RPCErrorException) $m = (string)$e->rpc;
    if (preg_match('/FLOOD_WAIT_(\d+)/', $m, $x)) return "FloodWait: Telegram {$x[1]} soniya kutishni talab qildi. Keyinroq urinib ko‘ring (aylanib o‘tilmaydi).";
    static $map = [
        'PEER_FLOOD' => 'Akkaunt vaqtincha cheklangan (PEER_FLOOD). Birozdan keyin urinib ko‘ring.',
        'CHANNELS_TOO_MUCH' => 'Akkaunt kanal/guruh limitiga yetgan.',
        'CHANNELS_ADMIN_PUBLIC_TOO_MUCH' => 'Ommaviy kanal limiti to‘lgan.',
        'USERNAME_NOT_OCCUPIED' => 'Bunday username mavjud emas.',
        'USERNAME_INVALID' => 'Username noto‘g‘ri.',
        'USER_ID_INVALID' => 'User ID noto‘g‘ri yoki akkauntga ma’lum emas.',
        'USER_NOT_MUTUAL_CONTACT' => 'Foydalanuvchini admin qilish uchun u bilan o‘zaro kontakt bo‘lish yoki u kanalga qo‘shilgan bo‘lishi kerak. Havolani yuborib, qo‘shilgach qayta urining.',
        'USER_PRIVACY_RESTRICTED' => 'Foydalanuvchi maxfiylik sozlamalari bunga ruxsat bermaydi.',
        'CHAT_ADMIN_REQUIRED' => 'Akkauntda bu amal uchun huquq yo‘q.',
        'USER_CREATOR' => 'Yaratuvchining huquqlarini o‘zgartirib bo‘lmaydi.',
        'RIGHT_FORBIDDEN' => 'Telegram bu huquqni berishga ruxsat bermadi.',
        'AUTH_KEY_UNREGISTERED' => 'Sessiya yaroqsiz. Telegramga qayta kiring.',
        'SESSION_REVOKED' => 'Sessiya bekor qilingan. Telegramga qayta kiring.',
        'PHONE_CODE_INVALID' => 'Tasdiqlash kodi noto‘g‘ri.',
        'PHONE_CODE_EXPIRED' => 'Kod muddati tugadi. Qaytadan so‘rang.',
        'PASSWORD_HASH_INVALID' => '2FA paroli noto‘g‘ri.',
        'PHONE_NUMBER_INVALID' => 'Telefon raqami noto‘g‘ri (+998... formatida).',
        'BOT_GROUPS_BLOCKED' => 'Botni guruhga qo‘shib bo‘lmaydi: BotFather → /mybots → bot → Bot Settings → Allow Groups → Turn on.',
        'BOT_CHANNELS_NA' => 'Botni bu kanalga qo‘shib bo‘lmadi (BOT_CHANNELS_NA).',
        'BOTS_TOO_MUCH' => 'Bu chatda botlar limiti to‘lgan.',
        'ADMIN_RIGHTS_EMPTY' => 'Admin uchun kamida bitta huquq belgilang ([@] Admin bo‘limi).',
        'USER_BLOCKED' => 'Foydalanuvchi/bot bu akkauntni bloklagan.',
        'PEER_ID_INVALID' => 'Bot/foydalanuvchi topilmadi (username’ni tekshiring).',
    ];
    foreach ($map as $k => $v) if (str_contains($m, $k)) return $v;
    return 'Telegram xatosi: ' . mb_substr($m, 0, 200);
}

function tg_state(): int|string {
    static $cache = [];
    $slot = acc_active();
    if (isset($cache[$slot])) return $cache[$slot];
    try { return $cache[$slot] = tg($slot)->getAuthorization(); }
    catch (Throwable $e) {
        $GLOBALS['TG_LAST_ERR'] = get_class($e) . ': ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')';
        logx('ERROR', 'getAuthorization: ' . $GLOBALS['TG_LAST_ERR']);
        return $cache[$slot] = -99;
    }
}
function logged_in(): bool { return tg_state() === API::LOGGED_IN; }

function gen_names(int $n = 10): array {
    $a = ['Oltin','Kumush','Yulduz','Sharq','Bahor','Zumrad','Nur','Qalam','Tong','Dengiz','Cho‘qqi','Orzu'];
    $b = ['Maskani','Olami','Xazinasi','Markazi','Bog‘i','Yo‘li','Davrasi','Makoni','Hamjamiyati','Maydoni'];
    $all = [];
    foreach ($a as $x) foreach ($b as $y) $all[] = "$x $y"; // 120 ta kombinatsiya
    shuffle($all);
    return array_slice($all, 0, $n);
}

function gen_unique_name(): string {
    $a = ['Oltin','Kumush','Yulduz','Sharq','Bahor','Zumrad','Nur','Qalam','Tong','Dengiz','Cho‘qqi','Orzu'];
    $b = ['Maskani','Olami','Xazinasi','Markazi','Bog‘i','Yo‘li','Davrasi','Makoni','Hamjamiyati','Maydoni'];
    $c = ['Plus','Pro','Club','Hub','Zone','Land','Team','Life','Star','Net'];
    $q = db()->prepare('SELECT COUNT(*) FROM items WHERE title=?');
    for ($i = 0; $i < 50; $i++) {
        $t = $a[array_rand($a)] . ' ' . $b[array_rand($b)] . ' ' . $c[array_rand($c)];
        $q->execute([$t]);
        if ((int)$q->fetchColumn() === 0) return $t;
    }
    return $t . ' ' . random_int(100, 999);
}

function valid_target(string $t): bool {
    return (bool)preg_match('/^@?[A-Za-z][A-Za-z0-9_]{4,31}$/', $t) || (bool)preg_match('/^\d{5,15}$/', $t);
}
function target_peer(string $t): string|int { return ctype_digit($t) ? (int)$t : '@' . ltrim($t, '@'); }

const RIGHTS = [
    'change_info' => 'Ma’lumotni o‘zgartirish', 'post_messages' => 'Post yozish (kanal)',
    'edit_messages' => 'Postni tahrirlash (kanal)', 'delete_messages' => 'Xabarni o‘chirish',
    'invite_users' => 'Taklif qilish', 'pin_messages' => 'Pin qilish',
    'ban_users' => 'Bloklash (guruh)', 'manage_call' => 'Ovozli chat',
];
// add_admins HECH QACHON berilmaydi.

// Bosh sahifadagi tugmalar (ikonka, nom)
const MENU = [
    'create' => ['[+]', 'Yaratish'], 'auto' => ['>_', 'Avto'],
    'history' => ['[#]', 'Tarix'],   'links' => ['://', 'Havolalar'],
    'names' => ['{?}', 'Nomlar'],    'admin' => ['[@]', 'Admin'],
    'tg' => ['((o))', 'Telegram'],   'logs' => ['[!]', 'Xatolar'],
    'members' => ['[>>]', 'A’zo taklif'],
    'dm' => ['[@>]', 'Shaxsiy xabar'],
];

/* Telegram profilni ulash formasi (bosh sahifada ham, 📡 sahifada ham ishlatiladi) */
function tg_login_card(int|string $st, string $ret): string {
    $r = '<input type="hidden" name="ret" value="' . h($ret) . '">';
    $steps = '<div class="steps"><span class="' . ($st === API::NOT_LOGGED_IN ? 'on' : 'done') . '">1. Raqam</span><span class="' . ($st === API::WAITING_CODE ? 'on' : ($st === API::WAITING_PASSWORD ? 'done' : '')) . '">2. Kod</span><span class="' . ($st === API::WAITING_PASSWORD ? 'on' : '') . '">3. 2FA</span></div>';
    if ($st === API::NOT_LOGGED_IN) {
        return $steps . '<p><small>Kanal/guruhlar shu akkaunt nomidan yaratiladi. Telefon raqamingizni kiriting — Telegram ilovangizga kod keladi.</small></p><form method="post">' . csrf_field() . $r . '<input type="hidden" name="action" value="tg_phone"><input name="phone" placeholder="+998901234567" inputmode="tel" autocomplete="tel" required><button>Kod yuborish</button></form>';
    }
    if ($st === API::WAITING_CODE) {
        return $steps . '<p><small>Telegram ilovangizdagi «Telegram» rasmiy chatiga kelgan kodni kiriting.</small></p><form method="post">' . csrf_field() . $r . '<input type="hidden" name="action" value="tg_code"><input name="code" placeholder="12345" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required><button>Tasdiqlash</button></form>';
    }
    if ($st === API::WAITING_PASSWORD) {
        return $steps . '<p><small>Akkauntda ikki bosqichli himoya bor. Bulutli parolni kiriting.</small></p><form method="post">' . csrf_field() . $r . '<input type="hidden" name="action" value="2fa"><input type="password" name="pw" placeholder="2FA paroli" autocomplete="current-password" required><button>Kirish</button></form>';
    }
    $reset = '<form method="post" onsubmit="return confirm(\'Sessiya o‘chirilib, qaytadan kiriladimi?\')">' . csrf_field() . $r . '<input type="hidden" name="action" value="tg_reset"><button class="sec">Sessiyani tozalab, qayta kirish</button></form>';
    if ($st === API::WAITING_SIGNUP) {
        return '<div class="msg err">Bu raqam Telegramda ro‘yxatdan o‘tmagan. Panel ro‘yxatdan o‘tkazmaydi.</div>' . $reset;
    }
    if ($st === API::LOGGED_OUT) {
        return '<div class="msg err">Telegram sessiyasi tugagan.</div>' . $reset;
    }
    $err = (string)($GLOBALS['TG_LAST_ERR'] ?? '');
    $ext = [];
    foreach (['json', 'xml', 'dom', 'mbstring', 'openssl', 'fileinfo', 'pdo_sqlite', 'gmp', 'ffi'] as $x) $ext[] = (extension_loaded($x) ? '[+] ' : '[-] ') . $x;
    $fn = [];
    foreach (['proc_open', 'exec', 'shell_exec', 'pcntl_fork'] as $f) $fn[] = (function_exists($f) && !in_array($f, array_map('trim', explode(',', (string)ini_get('disable_functions'))), true) ? '[+] ' : '[-] ') . $f;
    return '<div class="msg err">MadelineProto ishga tushmadi.' . ($err !== '' ? '<br><small style="color:inherit;word-break:break-all">' . h(mb_substr($err, 0, 400)) . '</small>' : '') . '</div>'
        . '<p><small>PHP ' . h(PHP_VERSION) . ' · ' . (PHP_INT_SIZE === 8 ? '64-bit' : '32-bit [-]') . '<br>Kengaytmalar: ' . h(implode(' · ', $ext)) . '<br>Funksiyalar: ' . h(implode(' · ', $fn)) . '<br>Sessiya papkasi yoziladimi: ' . (is_writable(APP_DIR . '/data') ? '[+]' : '[-]') . '</small></p>'
        . '<p><small>Shu kartochkaning skrinshotini yuboring.</small></p>' . $reset;
}

/* ---------- Telegram'da yaratish ---------- */
function create_object(string $type, string $title, string $about): int {
    if (time() < (int)setting('tg_pause_until_' . acc_active(), '0')) throw new RuntimeException('Telegram kutish muddati hali tugamagan.');
    $db = db();
    $db->prepare('INSERT INTO items(created_at,type,title,about,status,acc) VALUES(?,?,?,?,?,?)')
       ->execute([date('Y-m-d H:i:s'), $type, $title, $about, 'jarayonda', acc_active()]);
    $id = (int)$db->lastInsertId();
    $upd = fn(array $f) => $db->prepare('UPDATE items SET ' . implode(',', array_map(fn($k) => "$k=?", array_keys($f))) . ' WHERE id=?')->execute([...array_values($f), $id]);

    try {
        $res = tg()->channels->createChannel([
            'broadcast' => $type === 'channel',
            'megagroup' => $type === 'group',
            'title' => $title,
            'about' => $about,
        ]);
        $chat = $res['chats'][0] ?? null;
        if (!$chat) throw new RuntimeException('Telegram javobida chat topilmadi');
        $peerId = tg()->getId($chat); // -100... ko'rinishidagi to'liq ID
        $upd(['tg_id' => (string)$peerId, 'status' => 'yaratildi']);
    } catch (Throwable $e) {
        $upd(['status' => 'xato', 'error' => tg_error($e)]);
        logx('ERROR', "create: " . tg_error($e));
        return $id;
    }

    // Taklif havolasi
    $link = '';
    try {
        $inv = tg()->messages->exportChatInvite(['peer' => $peerId, 'title' => 'Panel']);
        $link = (string)($inv['link'] ?? '');
        if ($link === '') throw new RuntimeException('Havola bo‘sh qaytdi');
        $upd(['invite_link' => $link]);
    } catch (Throwable $e) {
        $upd(['error' => 'Havola: ' . tg_error($e)]);
        logx('ERROR', 'invite: ' . tg_error($e));
    }

    // Administrator
    $target = setting('admin_target');
    if ($target !== '' && valid_target($target)) {
        $rights = json_decode(setting('admin_rights', '[]'), true) ?: [];
        $ar = ['_' => 'chatAdminRights'];
        foreach (array_keys(RIGHTS) as $r) {
            if ($type === 'group' && in_array($r, ['post_messages', 'edit_messages'], true)) continue;
            if ($type === 'channel' && in_array($r, ['ban_users'], true)) continue;
            $ar[$r] = in_array($r, $rights, true);
        }
        $ar['add_admins'] = false;
        // Superguruhda avval a'zo qilamiz; ruxsat bo'lmasa ham editAdmin o'zi urinib ko'radi
        if ($type === 'group') {
            try { tg()->channels->inviteToChannel(['channel' => $peerId, 'users' => [target_peer($target)]]); }
            catch (Throwable $e) { logx('WARN', 'invite: ' . tg_error($e)); }
        }
        try {
            tg()->channels->editAdmin(['channel' => $peerId, 'user_id' => target_peer($target), 'admin_rights' => $ar, 'rank' => '']);
            $upd(['admin_target' => $target, 'admin_status' => 'tayinlandi']);
        } catch (Throwable $e) {
            $upd(['admin_target' => $target, 'admin_status' => 'xato: ' . tg_error($e)]);
            logx('ERROR', 'editAdmin: ' . tg_error($e));
        }
        // Havolani yuborish
        if ($link !== '' && setting('send_link') === '1') {
            try {
                tg()->messages->sendMessage(['peer' => target_peer($target), 'message' => "«{$title}» uchun taklif havolasi:\n{$link}"]);
                $upd(['link_sent' => 'yuborildi']);
            } catch (Throwable $e) {
                $upd(['link_sent' => 'xato: ' . tg_error($e)]);
                logx('ERROR', 'sendLink: ' . tg_error($e));
            }
        }
    }
    return $id;
}


/* ---------- Guruh a'zolarini taklif qilish (faqat Telegram ruxsatlari bilan) ---------- */
function members_job(): array {
    $job = json_decode(setting('members_job', '{}'), true);
    return is_array($job) ? $job : [];
}
function members_save(array $job): void {
    set_setting('members_job', json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
}
function members_counts(string $id): array {
    $counts = array_fill_keys(['pending', 'working', 'invited', 'skipped', 'failed', 'unknown'], 0);
    $q = db()->prepare('SELECT status,COUNT(*) AS n FROM member_queue WHERE job_id=? GROUP BY status');
    $q->execute([$id]);
    foreach ($q as $row) $counts[$row['status']] = (int)$row['n'];
    return $counts;
}
function members_peer(string $value): string|int {
    $value = trim($value);
    if (preg_match('~^(?:https?://)?t\.me/([A-Za-z][A-Za-z0-9_]{3,31})/?$~i', $value, $m)) $value = '@' . $m[1];
    if (preg_match('/^-\d{1,16}$/', $value)) return (int)$value;
    if (preg_match('/^@?[A-Za-z][A-Za-z0-9_]{3,31}$/', $value)) return '@' . ltrim($value, '@');
    throw new InvalidArgumentException('Guruhning @username, https://t.me/username yoki manfiy ID raqamini yozing. Yopiq guruh uchun ID kerak.');
}
function members_group(API $api, string|int $peer, bool $target = false): array {
    $info = $api->getInfo($peer);
    if (!in_array($info['type'] ?? '', ['chat', 'supergroup'], true)) {
        throw new RuntimeException('Manba ham, qabul qiluvchi ham guruh bo‘lishi kerak. Kanal qo‘llanmaydi.');
    }
    $chat = $info['Chat'] ?? [];
    if (!isset($info['bot_api_id']) || !empty($chat['left']) || !empty($chat['kicked']) || !empty($chat['deactivated']) || isset($chat['migrated_to']) || str_contains((string)($chat['_'] ?? ''), 'Forbidden')) {
        throw new RuntimeException('Hisob ushbu guruhga a’zo emas yoki guruh mavjud emas.');
    }
    if ($target && empty($chat['creator']) && empty($chat['admin_rights']['invite_users'])) {
        throw new RuntimeException('Qabul qiluvchi guruhda yaratuvchi yoki a’zo taklif qilish huquqiga ega admin bo‘lishingiz kerak.');
    }
    return $info;
}
function members_rpc_error(Throwable $e): string {
    return $e instanceof RPCErrorException ? (string)$e->rpc : $e->getMessage();
}
function members_wait(Throwable $e): int {
    foreach (['getWaitTimeLeft', 'getWaitTime'] as $method) {
        if (method_exists($e, $method)) return max(1, (int)$e->$method());
    }
    return preg_match('/(?:FLOOD_WAIT|FLOOD_PREMIUM_WAIT|SLOWMODE_WAIT)_(\d+)/', members_rpc_error($e), $m) ? max(1, (int)$m[1]) : 0;
}
function members_create(string $source, string $target, int $limit, int $interval = 60): void {
    $old = members_job();
    if (in_array($old['state'] ?? '', ['running', 'paused', 'paused_account'], true)) {
        throw new RuntimeException('Avval mavjud taklif vazifasini to‘xtating yoki davom ettiring.');
    }
    $acc = acc_active();
    $dm = dm_job();
    if ((int)($dm['acc'] ?? 0) === $acc && in_array($dm['state'] ?? '', ['running', 'paused', 'paused_account'], true)) {
        throw new RuntimeException('Shu hisobda shaxsiy xabar vazifasi bor. Avval uni yakunlang yoki to‘xtating.');
    }
    if (time() < (int)setting('tg_pause_until_' . $acc, '0')) throw new RuntimeException('Telegram kutish muddati hali tugamagan.');
    if (!logged_in()) throw new RuntimeException('Avval asosiy hisob bilan Telegramga kiring.');
    $api = tg();
    $from = members_group($api, members_peer($source));
    $to = members_group($api, members_peer($target), true);
    if ((string)$from['bot_api_id'] === (string)$to['bot_api_id']) throw new InvalidArgumentException('Manba va qabul qiluvchi guruh bir xil bo‘lishi mumkin emas.');
    $me = $api->getSelf();
    $job = [
        'id' => bin2hex(random_bytes(12)), 'acc' => $acc, 'state' => 'running',
        'source' => (string)$from['bot_api_id'], 'source_type' => $from['type'], 'source_title' => (string)($from['Chat']['title'] ?? $source),
        'target' => (string)$to['bot_api_id'], 'target_type' => $to['type'], 'target_title' => (string)($to['Chat']['title'] ?? $target),
        'self_id' => (string)$me['id'], 'limit' => max(1, min(10000, $limit)), 'interval' => max(60, min(3600, $interval)),
        'offset' => 0, 'scanned' => 0, 'source_done' => false, 'next_at' => time(),
        'message' => 'Navbat tayyor. Cron ko‘rinadigan a’zolarni tekshirib, bittadan taklif qiladi.', 'created_at' => date('Y-m-d H:i:s'),
    ];
    // Vazifalar tarixi o'rniga faqat joriy navbat saqlanadi.
    $db = db(); $db->beginTransaction();
    try { $db->exec('DELETE FROM member_queue'); members_save($job); $db->commit(); }
    catch (Throwable $e) { $db->rollBack(); throw $e; }
}
function members_fetch(array &$job, API $api, string $table = 'member_queue'): void {
    if (!in_array($table, ['member_queue', 'dm_queue'], true)) throw new InvalidArgumentException('Noto‘g‘ri navbat jadvali.');
    $remaining = $job['limit'] - $job['scanned'];
    if ($remaining <= 0) { $job['source_done'] = true; return; }
    if ($job['source_type'] === 'supergroup') {
        $page = $api->channels->getParticipants([
            'channel' => (int)$job['source'], 'filter' => ['_' => 'channelParticipantsRecent'],
            'offset' => $job['offset'], 'limit' => min(200, $remaining), 'hash' => 0,
        ]);
        if (!isset($page['participants'], $page['users'])) throw new RuntimeException('Telegram a’zolar ro‘yxatini qaytarmadi.');
        $participants = $page['participants']; $users = $page['users'];
        $job['offset'] += count($participants);
        if (!$participants || (isset($page['count']) && $job['offset'] >= (int)$page['count'])) $job['source_done'] = true;
    } else {
        // getFullChat to'g'ridan-to'g'ri MadelineProto v8 da taqiqlangan.
        $page = $api->getFullInfo((int)$job['source']);
        $list = $page['full']['participants'] ?? [];
        if (($list['_'] ?? '') !== 'chatParticipants') throw new RuntimeException('Bu guruh a’zolari ro‘yxatini ko‘rishga ruxsat yo‘q.');
        $participants = $list['participants'] ?? []; $users = [];
        foreach (array_slice($participants, 0, $remaining) as $participant) {
            $id = (int)($participant['user_id'] ?? 0);
            if (!$id || (string)$id === $job['self_id']) continue;
            $userInfo = $api->getInfo($id);
            if (isset($userInfo['User'])) $users[] = $userInfo['User'];
        }
        // Faqat barcha ma'lumot olingach yakunlanadi: FloodWait bo'lsa sahifa yo'qolmaydi.
        $job['source_done'] = true;
    }
    $usersById = [];
    foreach ($users as $user) $usersById[(string)$user['id']] = $user;
    $insert = db()->prepare('INSERT OR IGNORE INTO ' . $table . '(job_id,user_id,peer,status,reason) VALUES(?,?,?,?,?)');
    foreach (array_slice($participants, 0, $remaining) as $participant) {
        $job['scanned']++;
        $id = (string)($participant['user_id'] ?? $participant['peer']['user_id'] ?? '');
        if ($id === '' || $id === $job['self_id']) continue;
        $user = $usersById[$id] ?? [];
        if (!$user || !empty($user['self']) || !empty($user['bot']) || !empty($user['deleted']) || ($user['_'] ?? '') === 'userEmpty') continue;
        // Shu hisobning MadelineProto peer keshi access_hash va min foydalanuvchilarini yechadi.
        $insert->execute([$job['id'], $id, json_encode((int)$id, JSON_THROW_ON_ERROR), 'pending', '']);
    }
    if ($job['scanned'] >= $job['limit']) $job['source_done'] = true;
}
function members_tick(): string {
    $job = members_job();
    if (!in_array($job['state'] ?? '', ['running', 'paused_account'], true)) return 'a’zo taklif: faol vazifa yo‘q';
    if (!in_array((int)$job['acc'], acc_list(), true)) {
        $job['state'] = 'error'; $job['message'] = 'Vazifa boshlangan hisob o‘chirilgan.'; members_save($job); return $job['message'];
    }
    if ((int)$job['acc'] !== acc_active()) {
        $job['state'] = 'paused_account'; $job['message'] = 'Asosiy hisob almashgan. #' . $job['acc'] . ' yana asosiy bo‘lganda davom etadi.';
        members_save($job); return $job['message'];
    }
    if ($job['state'] === 'paused_account') { $job['state'] = 'running'; members_save($job); }
    $pause = max((int)$job['next_at'], (int)setting('tg_pause_until_' . $job['acc'], '0'), (int)setting('auto_pause_until', '0'));
    if (time() < $pause) return 'a’zo taklif: kutish muddati';
    $row = null;
    try {
        if (!logged_in()) throw new RuntimeException('Telegram sessiyasi mavjud emas. Hisobga qayta kiring.');
        $api = tg();
        if ((string)($api->getSelf()['id'] ?? '') !== (string)$job['self_id']) throw new RuntimeException('Bu joyga boshqa Telegram hisobi ulangan. Vazifa to‘xtatildi.');
        // Oldingi jarayon API javobini yozishdan oldin uzilgan bo'lsa, taklif takrorlanmaydi.
        db()->prepare("UPDATE member_queue SET status='unknown',reason=? WHERE job_id=? AND status='working'")
            ->execute(['Oldingi so‘rov uzilgan; natijani guruhda tekshiring.', $job['id']]);
        $select = db()->prepare("SELECT * FROM member_queue WHERE job_id=? AND status='pending' ORDER BY rowid LIMIT 1");
        $select->execute([$job['id']]); $row = $select->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row && !$job['source_done']) {
            members_fetch($job, $api); members_save($job);
            $select->execute([$job['id']]); $row = $select->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$row) {
            if ($job['source_done']) {
                $job['state'] = 'done'; $job['message'] = 'Ko‘rinadigan ro‘yxat bo‘yicha takliflar tugadi.';
            } else $job['message'] = 'Keyingi a’zolar sahifasi tekshirilmoqda.';
        } else {
            db()->prepare("UPDATE member_queue SET status='working' WHERE job_id=? AND user_id=?")->execute([$job['id'], $row['user_id']]);
            $user = json_decode($row['peer'], true, 512, JSON_THROW_ON_ERROR);
            $result = $job['target_type'] === 'supergroup'
                ? $api->channels->inviteToChannel(['channel' => (int)$job['target'], 'users' => [$user]])
                : $api->messages->addChatUser(['chat_id' => (int)$job['target'], 'user_id' => $user, 'fwd_limit' => 0]);
            if (!is_array($result) || !isset($result['_']) || (!array_key_exists('updates', $result) && !in_array($result['_'], ['updatesTooLong', 'updateShort', 'updateShortChatMessage', 'updateShortMessage'], true))) {
                throw new RuntimeException('Taklif javobi tushunarsiz. Natijani qabul qiluvchi guruhda tekshiring.');
            }
            $missing = !empty($result['missing_invitees']);
            db()->prepare('UPDATE member_queue SET status=?,reason=? WHERE job_id=? AND user_id=?')
                ->execute([$missing ? 'skipped' : 'invited', $missing ? 'Telegram bu foydalanuvchini qo‘shishga ruxsat bermadi.' : '', $job['id'], $row['user_id']]);
            $job['message'] = $missing ? 'Maxfiylik yoki Telegram ruxsati sabab bitta a’zo tashlab ketildi.' : 'Bitta a’zo uchun taklif muvaffaqiyatli bajarildi.';
        }
        $job['next_at'] = time() + $job['interval'];
    } catch (Throwable $e) {
        $wait = members_wait($e); $rpc = members_rpc_error($e);
        if ($wait > 0) {
            $job['next_at'] = time() + $wait + 5;
            set_setting('tg_pause_until_' . $job['acc'], (string)$job['next_at']);
            $job['message'] = 'Telegram ' . $wait . ' soniya kutishni talab qildi. Shu muddatdan keyin davom etadi.';
            if ($row) db()->prepare("UPDATE member_queue SET status='pending',reason=? WHERE job_id=? AND user_id=?")->execute(['FloodWait: kutilmoqda', $job['id'], $row['user_id']]);
        } elseif ($row && in_array($rpc, ['USER_PRIVACY_RESTRICTED', 'USER_NOT_MUTUAL_CONTACT', 'USER_ALREADY_PARTICIPANT', 'USER_CHANNELS_TOO_MUCH', 'INPUT_USER_DEACTIVATED', 'USER_KICKED'], true)) {
            db()->prepare("UPDATE member_queue SET status='skipped',reason=? WHERE job_id=? AND user_id=?")->execute([mb_substr($rpc, 0, 180), $job['id'], $row['user_id']]);
            $job['next_at'] = time() + $job['interval']; $job['message'] = 'Taklifga ruxsat yo‘q yoki a’zo allaqachon guruhda: tashlab ketildi.';
        } else {
            if ($row) db()->prepare('UPDATE member_queue SET status=?,reason=? WHERE job_id=? AND user_id=?')
                ->execute([$e instanceof RPCErrorException ? 'failed' : 'unknown', mb_substr($rpc, 0, 180), $job['id'], $row['user_id']]);
            $job['state'] = 'error'; $job['message'] = 'To‘xtatildi: ' . tg_error($e);
            if (str_contains($rpc, 'PEER_FLOOD')) tg_stop_account_jobs((int)$job['acc']);
            logx('WARN', 'A’zo taklif: ' . $job['message']);
        }
    }
    members_save($job);
    return 'a’zo taklif: ' . $job['message'];
}

/* ---------- Shaxsiy xabarlar navbati ---------- */
function dm_job(): array {
    $job = json_decode(setting('dm_job', '{}'), true);
    return is_array($job) ? $job : [];
}
function dm_save(array $job): void { set_setting('dm_job', json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)); }
function dm_counts(string $id): array {
    $counts = array_fill_keys(['pending', 'working', 'sent', 'skipped', 'failed', 'unknown'], 0);
    $q = db()->prepare('SELECT status,COUNT(*) AS n FROM dm_queue WHERE job_id=? GROUP BY status');
    $q->execute([$id]);
    foreach ($q as $row) $counts[$row['status']] = (int)$row['n'];
    return $counts;
}
function dm_text(string $text): string {
    if (!mb_check_encoding($text, 'UTF-8')) throw new InvalidArgumentException('Xabar matni UTF-8 formatida bo‘lsin.');
    $text = str_replace("\r\n", "\n", $text);
    if (trim($text) === '') throw new InvalidArgumentException('Xabar matnini kiriting.');
    if (strlen(mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')) > 8192) throw new InvalidArgumentException('Xabar 4096 belgidan oshmasin; emoji ikki birlik hisoblanishi mumkin.');
    return $text;
}
function tg_stop_account_jobs(int $acc): void {
    set_setting('auto_on', '0'); auto_status('Telegram PEER_FLOOD cheklovi sabab to‘xtatildi.');
    foreach (['members_job', 'dm_job'] as $key) {
        $job = json_decode(setting($key, '{}'), true);
        if (is_array($job) && (int)($job['acc'] ?? 0) === $acc && in_array($job['state'] ?? '', ['running', 'paused', 'paused_account'], true)) {
            $job['state'] = 'error'; $job['message'] = 'Telegram PEER_FLOOD cheklovi sabab to‘xtatildi.';
            set_setting($key, json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }
    }
}
function dm_create(string $source, string $text, int $limit, int $interval = 60, ?string $expectedSelf = null): void {
    $text = dm_text($text);
    $old = dm_job();
    if (in_array($old['state'] ?? '', ['running', 'paused', 'paused_account'], true)) throw new RuntimeException('Avval mavjud shaxsiy xabar vazifasini to‘xtating yoki davom ettiring.');
    $acc = acc_active(); $memberJob = members_job();
    if ((int)($memberJob['acc'] ?? 0) === $acc && in_array($memberJob['state'] ?? '', ['running', 'paused', 'paused_account'], true)) {
        throw new RuntimeException('Shu hisobda a’zo taklif vazifasi bor. Avval uni yakunlang yoki to‘xtating.');
    }
    if (time() < max((int)setting('tg_pause_until_' . $acc, '0'), (int)setting('auto_pause_until', '0'))) throw new RuntimeException('Telegram kutish muddati hali tugamagan.');
    if (!logged_in()) throw new RuntimeException('Avval asosiy hisob bilan Telegramga kiring.');
    $api = tg(); $from = members_group($api, members_peer($source)); $me = $api->getSelf();
    if ($expectedSelf !== null && (string)($me['id'] ?? '') !== $expectedSelf) throw new RuntimeException('Tasdiqlashdan keyin Telegram hisobi almashgan. Xabarni qayta ko‘rib chiqing.');
    $job = [
        'id' => bin2hex(random_bytes(12)), 'acc' => $acc, 'state' => 'running',
        'source' => (string)$from['bot_api_id'], 'source_type' => $from['type'], 'source_title' => (string)($from['Chat']['title'] ?? $source),
        'self_id' => (string)$me['id'], 'text' => $text, 'limit' => max(1, min(10000, $limit)), 'interval' => max(60, min(3600, $interval)),
        'offset' => 0, 'scanned' => 0, 'source_done' => false, 'next_at' => time(),
        'message' => 'Xabar navbati tayyor. Cron ko‘rinadigan a’zolarga bittadan yuboradi.', 'created_at' => date('Y-m-d H:i:s'),
    ];
    $db = db(); $db->beginTransaction();
    try { $db->exec('DELETE FROM dm_queue'); dm_save($job); $db->commit(); }
    catch (Throwable $e) { $db->rollBack(); throw $e; }
}
function dm_sent_response(mixed $result, string $randomId): bool {
    if (!is_array($result)) return false;
    if (($result['_'] ?? '') === 'updateShortSentMessage') return (int)($result['id'] ?? 0) > 0;
    $updates = $result['updates'] ?? (isset($result['update']) ? [$result['update']] : []);
    foreach ($updates as $update) {
        if (($update['_'] ?? '') === 'updateMessageID' && (string)($update['random_id'] ?? '') === $randomId && (int)($update['id'] ?? 0) > 0) return true;
    }
    return false;
}
function dm_skip_error(string $rpc): bool {
    return in_array($rpc, ['USER_IS_BLOCKED', 'YOU_BLOCKED_USER', 'USER_BLOCKED', 'INPUT_USER_DEACTIVATED', 'USER_PRIVACY_RESTRICTED', 'PRIVACY_PREMIUM_REQUIRED', 'PREMIUM_ACCOUNT_REQUIRED', 'CHAT_WRITE_FORBIDDEN', 'USER_NOT_MUTUAL_CONTACT', 'PEER_ID_INVALID', 'USER_ID_INVALID'], true)
        || (bool)preg_match('/^ALLOW_PAYMENT_REQUIRED(?:_\d+)?$/', $rpc);
}
function dm_tick(): string {
    $job = dm_job();
    if (!in_array($job['state'] ?? '', ['running', 'paused_account'], true)) return 'shaxsiy xabar: faol vazifa yo‘q';
    if (!in_array((int)$job['acc'], acc_list(), true)) {
        $job['state'] = 'error'; $job['message'] = 'Vazifa boshlangan hisob o‘chirilgan.'; dm_save($job); return $job['message'];
    }
    if ((int)$job['acc'] !== acc_active()) {
        $job['state'] = 'paused_account'; $job['message'] = 'Asosiy hisob almashgan. #' . $job['acc'] . ' yana asosiy bo‘lganda davom etadi.'; dm_save($job); return $job['message'];
    }
    if ($job['state'] === 'paused_account') { $job['state'] = 'running'; dm_save($job); }
    if (time() < max((int)$job['next_at'], (int)setting('tg_pause_until_' . $job['acc'], '0'), (int)setting('auto_pause_until', '0'))) return 'shaxsiy xabar: kutish muddati';
    $row = null;
    try {
        if (!logged_in()) throw new RuntimeException('Telegram sessiyasi mavjud emas. Hisobga qayta kiring.');
        $api = tg();
        if ((string)($api->getSelf()['id'] ?? '') !== (string)$job['self_id']) throw new RuntimeException('Bu joyga boshqa Telegram hisobi ulangan. Vazifa to‘xtatildi.');
        db()->prepare("UPDATE dm_queue SET status='unknown',reason=? WHERE job_id=? AND status='working'")
            ->execute(['Oldingi so‘rov uzilgan; natijani chatda tekshiring.', $job['id']]);
        $select = db()->prepare("SELECT * FROM dm_queue WHERE job_id=? AND status='pending' ORDER BY rowid LIMIT 1");
        $select->execute([$job['id']]); $row = $select->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$row && !$job['source_done']) {
            members_fetch($job, $api, 'dm_queue'); dm_save($job);
            $select->execute([$job['id']]); $row = $select->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$row) {
            if ($job['source_done']) { $job['state'] = 'done'; $job['message'] = 'Ko‘rinadigan ro‘yxat bo‘yicha xabar navbati tugadi.'; }
            else $job['message'] = 'Keyingi a’zolar sahifasi tekshirilmoqda.';
        } else {
            $randomId = $row['random_id'] ?: (string)random_int(1, PHP_INT_MAX);
            db()->prepare("UPDATE dm_queue SET status='working',random_id=? WHERE job_id=? AND user_id=?")->execute([$randomId, $job['id'], $row['user_id']]);
            $result = $api->messages->sendMessage([
                'peer' => (int)$row['user_id'], 'message' => $job['text'], 'random_id' => (int)$randomId,
                'parse_mode' => 'text', 'entities' => [], 'no_webpage' => true,
            ]);
            if (!dm_sent_response($result, $randomId)) throw new RuntimeException('Xabar javobi tasdiqlanmadi. Natijani chatda tekshiring.');
            db()->prepare("UPDATE dm_queue SET status='sent',reason='' WHERE job_id=? AND user_id=?")->execute([$job['id'], $row['user_id']]);
            $job['message'] = 'Bitta shaxsiy xabar Telegram tomonidan qabul qilindi.';
        }
        $job['next_at'] = time() + $job['interval'];
    } catch (Throwable $e) {
        $wait = members_wait($e); $rpc = members_rpc_error($e);
        if ($wait > 0) {
            $job['next_at'] = time() + $wait + 5; set_setting('tg_pause_until_' . $job['acc'], (string)$job['next_at']);
            $job['message'] = 'Telegram ' . $wait . ' soniya kutishni talab qildi. Muddat tugagach davom etadi.';
            if ($row) db()->prepare("UPDATE dm_queue SET status='pending',reason=? WHERE job_id=? AND user_id=?")->execute(['Telegram kutish muddati', $job['id'], $row['user_id']]);
        } elseif ($row && dm_skip_error($rpc)) {
            db()->prepare("UPDATE dm_queue SET status='skipped',reason=? WHERE job_id=? AND user_id=?")->execute([mb_substr($rpc, 0, 180), $job['id'], $row['user_id']]);
            $job['next_at'] = time() + $job['interval']; $job['message'] = 'Qabul qiluvchiga yozib bo‘lmadi; navbatdagi a’zoga o‘tiladi.';
        } else {
            if ($row) db()->prepare('UPDATE dm_queue SET status=?,reason=? WHERE job_id=? AND user_id=?')
                ->execute([($e instanceof RPCErrorException && $rpc !== 'RANDOM_ID_DUPLICATE') ? 'failed' : 'unknown', mb_substr($rpc, 0, 180), $job['id'], $row['user_id']]);
            $job['state'] = 'error'; $job['message'] = 'To‘xtatildi: ' . tg_error($e);
            if (str_contains($rpc, 'PEER_FLOOD')) tg_stop_account_jobs((int)$job['acc']);
            logx('WARN', 'Shaxsiy xabar: ' . $job['message']);
        }
    }
    dm_save($job);
    return 'shaxsiy xabar: ' . $job['message'];
}

/* ---------- AVTO YARATISH (har 1 daqiqada cron chaqiradi) ---------- */
function auto_status(string $m): void { set_setting('auto_status', date('H:i:s') . ' — ' . $m); }

function auto_tick(): string {
    if (setting('auto_on') !== '1') return 'avto o‘chiq';
    if (time() < (int)setting('tg_pause_until_' . acc_active(), '0')) return 'Telegram kutish muddati';
    if (time() < (int)setting('auto_pause_until', '0')) { return 'FloodWait pauza'; }
    if (time() - (int)setting('last_create', '0') < 55) return 'hali erta';
    if (!logged_in()) { auto_status('Telegram ulanmagan'); return 'Telegram ulanmagan'; }

    $chT = (int)setting('auto_ch_target', '200'); $grT = (int)setting('auto_gr_target', '200');
    $chD = (int)setting('auto_ch_done', '0');     $grD = (int)setting('auto_gr_done', '0');
    $needCh = $chD < $chT; $needGr = $grD < $grT;
    if (!$needCh && !$needGr) { set_setting('auto_on', '0'); auto_status('Tugadi'); return 'tugadi'; }
    $type = ($needCh && (!$needGr || $chD <= $grD)) ? 'channel' : 'group';

    set_setting('last_create', (string)time());
    $id = create_object($type, gen_unique_name(), '');
    $st = db()->prepare('SELECT status,error FROM items WHERE id=?'); $st->execute([$id]); $r = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    if (($r['status'] ?? '') === 'yaratildi') {
        set_setting($type === 'channel' ? 'auto_ch_done' : 'auto_gr_done', (string)(($type === 'channel' ? $chD : $grD) + 1));
        set_setting('auto_fail', '0');
        auto_status('yaratildi: ' . ($type === 'channel' ? 'kanal' : 'guruh'));
        return 'ok ' . $type;
    }
    $err = (string)($r['error'] ?? '');
    if (preg_match('/FloodWait: Telegram (\d+)/u', $err, $x)) {
        set_setting('auto_pause_until', (string)(time() + (int)$x[1] + 5));
        set_setting('tg_pause_until_' . acc_active(), (string)(time() + (int)$x[1] + 5));
        auto_status('FloodWait: ' . $x[1] . ' soniya kutiladi');
        return 'flood';
    }
    if (preg_match('/limitiga yetgan|PEER_FLOOD|cheklangan|Sessiya yaroqsiz|to‘lgan/u', $err)) {
        set_setting('auto_on', '0'); auto_status('To‘xtatildi: ' . $err);
        return 'toxtadi';
    }
    $f = (int)setting('auto_fail', '0') + 1; set_setting('auto_fail', (string)$f);
    if ($f >= 3) { set_setting('auto_on', '0'); auto_status('3 marta ketma-ket xato, to‘xtatildi: ' . $err); }
    else auto_status('xato: ' . $err);
    return 'xato';
}

$__argv = $argv ?? [];
if ($IS_CRON) {
    $okCli = PHP_SAPI === 'cli' && (($__argv[1] ?? '') === 'cron');
    $key = (string)($CFG['cron_key'] ?? '');
    $okWeb = PHP_SAPI !== 'cli' && strlen($key) >= 16 && hash_equals($key, (string)($_GET['cron'] ?? ''));
    if (!$okCli && !$okWeb) { http_response_code(403); exit("cron: ruxsat yo'q\n"); }
    @ignore_user_abort(true); @set_time_limit(120);
    $_SESSION = ['tg_user_id' => (string)$CFG['owner_telegram_id']];
    $lock = fopen(APP_DIR . '/data/cron.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) exit("band\n");
    try {
        echo acc_rotation_tick() . "\n";
        echo members_tick() . "\n";
        echo dm_tick() . "\n";
        echo auto_tick() . "\n";
    }
    catch (Throwable $e) { logx('ERROR', 'cron: ' . tg_error($e)); echo "xato\n"; }
    exit;
}

/* ---------- Telegram Mini App autentifikatsiyasi ---------- */
function verify_webapp_init_data(string $initData): array {
    global $CFG;
    $botToken = (string)($CFG['bot_token'] ?? '');
    if ($botToken === '' || !str_contains($botToken, ':')) {
        throw new RuntimeException('Bot token sozlanmagan. ' . APP_DIR . '/config.php da bot_token ni yozing (Web App ochiladigan botning BotFather tokeni).');
    }
    if ($initData === '' || strlen($initData) > 16384) {
        throw new RuntimeException('Telegram initData topilmadi. Web Appni bot ichidan oching.');
    }
    parse_str($initData, $fields);
    $receivedHash = (string)($fields['hash'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/i', $receivedHash)) {
        throw new RuntimeException('Telegram autentifikatsiya imzosi topilmadi.');
    }
    unset($fields['hash']);
    ksort($fields, SORT_STRING);
    $pairs = [];
    foreach ($fields as $key => $value) {
        if (is_scalar($value)) $pairs[] = $key . '=' . (string)$value;
    }
    $checkString = implode("\n", $pairs);
    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $expectedHash = hash_hmac('sha256', $checkString, $secretKey);
    if (!hash_equals($expectedHash, strtolower($receivedHash))) {
        throw new RuntimeException('Telegram imzosi tekshiruvdan o‘tmadi. Bot tokenini tekshiring.');
    }
    $authDate = (int)($fields['auth_date'] ?? 0);
    if ($authDate < 1 || $authDate > time() + 60 || time() - $authDate > 86400) {
        throw new RuntimeException('Telegram autentifikatsiyasi eskirgan. Web Appni qayta oching.');
    }
    $userJson = (string)($fields['user'] ?? '');
    $user = json_decode($userJson, true);
    if (!is_array($user) || empty($user['id'])) {
        throw new RuntimeException('Telegram foydalanuvchi ma’lumoti olinmadi.');
    }
    $id = (string)$user['id'];
    $ownerId = (string)($CFG['owner_telegram_id'] ?? '');
    if ($ownerId === '' || !ctype_digit($ownerId) || !hash_equals($ownerId, $id)) {
        throw new RuntimeException('Kirish yopiq: sizning ID ' . $id . '. config.php dagi owner_telegram_id ga aynan shu raqamni yozing.');
    }
    return ['id' => $id, 'first_name' => (string)($user['first_name'] ?? ''), 'username' => (string)($user['username'] ?? '')];
}

// initData faqat Telegram Web App ichidan kelganida serverda tekshiriladi.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['action'] ?? '') === 'mini_auth') {
    try {
        $user = verify_webapp_init_data((string)($_POST['init_data'] ?? ''));
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['tg_user_id'] = $user['id'];
        $_SESSION['tg_first_name'] = $user['first_name'];
        $_SESSION['tg_username'] = $user['username'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: ?p=home'); exit;
    } catch (Throwable $e) {
        $_SESSION['mini_error'] = $e->getMessage();
        logx('WARN', 'Mini App auth: ' . $e->getMessage());
        header('Location: ?p=auth'); exit;
    }
}

/* ---------- MINI APP KIRISH ---------- */
if (!isset($_SESSION['auth'])) {
    $miniError = (string)($_SESSION['mini_error'] ?? ''); unset($_SESSION['mini_error']);
    page_head('Telegram Mini App orqali kirish');
    echo '<div class="card"><h2>ACCESS_CONTROL</h2><pre class="boot">$ ./tgm --auth
[ok] secure channel
[..] verifying telegram signature</pre><p>Bu boshqaruv paneli faqat Telegram bot orqali ochiladi. Telegram Mini App ma’lumoti serverda tekshiriladi.</p>';
    if ($miniError !== '') echo '<div class="msg err">' . h($miniError) . '</div>';
    $auto = $miniError === '' ? 'true' : 'false'; // xato bo'lsa qayta-qayta yubormaydi
    echo '<div id="mini-status" class="msg"' . ($miniError !== '' ? ' style="display:none"' : '') . '>Telegram bilan xavfsiz ulanish tekshirilmoqda…</div>';
    echo '<form id="mini-auth-form" method="post" action=""><input type="hidden" name="action" value="mini_auth"><input type="hidden" name="init_data" id="init-data" value="">' . ($miniError !== '' ? '<button>Qayta urinish</button>' : '') . '</form>';
    echo '<p><small>Agar bu oynani Telegramdan tashqarida ochgan bo‘lsangiz, botga qayting va “TG Menejer” tugmasini bosing.</small></p></div>';
    echo '<script src="https://telegram.org/js/telegram-web-app.js"></script><script>(function(){var s=document.getElementById("mini-status");var d="";try{if(window.Telegram&&Telegram.WebApp){Telegram.WebApp.ready();Telegram.WebApp.expand();try{Telegram.WebApp.setHeaderColor("#000000");Telegram.WebApp.setBackgroundColor("#030603");}catch(e){}d=Telegram.WebApp.initData||"";}}catch(e){} if(d){document.getElementById("init-data").value=d;if(' . $auto . ')document.getElementById("mini-auth-form").submit();}else{s.style.display="";s.textContent="Telegram initData olinmadi. Bot ichidagi Web App tugmasidan oching.";s.className="msg err";}})();</script>';
    page_foot(); exit;
}

/* ---------- Routing ---------- */
$p = (string)($_GET['p'] ?? 'home');
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($post && !csrf_ok()) { flash('err', 'CSRF tokeni noto‘g‘ri.'); go($p); }

if ($post) {
    $act = (string)($_POST['action'] ?? '');
    try {
        if ($act === 'logout') { $_SESSION = []; session_destroy(); header('Location: ?'); exit; }
        if (in_array($act, ['tg_phone', 'tg_code', '2fa', 'tg_logout', 'tg_reset', 'acc_add', 'acc_use', 'acc_del', 'acc_rotation_save', 'auto_save', 'create', 'members_start', 'members_pause', 'members_resume', 'members_stop', 'dm_preview', 'dm_start', 'dm_pause', 'dm_resume', 'dm_stop'], true)) {
            acc_control_lock();
        }
        if (in_array($act, ['tg_phone', 'tg_code', '2fa', 'tg_logout', 'tg_reset', 'create', 'members_start', 'members_resume', 'dm_preview', 'dm_start', 'dm_resume'], true)) {
            acc_require_current();
        }

        if ($act === 'tg_phone') {
            $ph = preg_replace('/[^\d+]/', '', (string)$_POST['phone']);
            tg()->phoneLogin($ph);
            flash('ok', 'Kod Telegram ilovangizga yuborildi.');
        } elseif ($act === 'tg_code') {
            $r = tg()->completePhoneLogin(preg_replace('/\D/', '', (string)$_POST['code']));
            if (($r['_'] ?? '') === 'account.password') flash('ok', '2FA paroli kerak.');
            else { try { acc_remember_self(tg()->getSelf()); } catch (Throwable $e) {} flash('ok', 'Telegramga kirildi.'); }
        } elseif ($act === '2fa') {
            tg()->complete2faLogin((string)$_POST['pw']);
            try { acc_remember_self(tg()->getSelf()); } catch (Throwable $e) {}
            flash('ok', 'Telegramga kirildi.');
        } elseif ($act === 'tg_logout') {
            try { tg()->logout(); } finally { tg_reset_session(); set_setting('acc_name_' . acc_active(), ''); }
            flash('ok', 'Telegram sessiyasi tugatildi.');
        } elseif ($act === 'acc_add') {
            $l = acc_list();
            if (count($l) >= MAX_ACCOUNTS) throw new RuntimeException('Ko‘pi bilan ' . MAX_ACCOUNTS . ' ta hisob qo‘shish mumkin.');
            $n = 1; while (in_array($n, $l, true)) $n++;
            $l[] = $n; acc_save_list($l);
            set_setting('acc_name_' . $n, '');
            acc_set_active($n);
            flash('ok', "#{$n}-hisob qo‘shildi va asosiy qilindi. Telefon raqamini kiriting.");
        } elseif ($act === 'acc_use') {
            $n = (int)($_POST['slot'] ?? 0);
            if (!in_array($n, acc_list(), true)) throw new RuntimeException('Bunday hisob yo‘q.');
            acc_set_active($n);
            flash('ok', 'Asosiy hisob: ' . acc_label($n));
        } elseif ($act === 'acc_rotation_save') {
            $on = (string)($_POST['enabled'] ?? '') === '1';
            set_setting('acc_rotate_on', $on ? '1' : '0');
            acc_rotation_reset();
            flash('ok', $on ? 'Har 1 soatda navbat bilan asosiy hisobni almashtirish yoqildi.' : 'Hisoblarni avtomatik almashtirish to‘xtatildi.');
        } elseif ($act === 'acc_del') {
            $n = (int)($_POST['slot'] ?? 0); $l = acc_list();
            if (!in_array($n, $l, true)) throw new RuntimeException('Bunday hisob yo‘q.');
            if (count($l) <= 1) throw new RuntimeException('Oxirgi hisobni o‘chirib bo‘lmaydi — «Telegramdan chiqish»dan foydalaning.');
            if ($n === acc_active() && setting('auto_on') === '1') throw new RuntimeException('Avto yaratish shu hisobda ishlayapti. Avval to‘xtating.');
            $label = acc_label($n);
            try { if (is_file(session_dir($n) . '/session.madeline') && tg($n)->getAuthorization() === API::LOGGED_IN) tg($n)->logout(); }
            catch (Throwable $e) { logx('WARN', 'acc_del logout: ' . tg_error($e)); }
            finally { tg_reset_session($n); if ($n !== 1) @rmdir(session_dir($n)); }
            $l = array_values(array_diff($l, [$n])); acc_save_list($l);
            set_setting('acc_name_' . $n, '');
            if ((int)setting('acc_active', '1') === $n) acc_set_active($l[0]);
            flash('ok', "Hisob o‘chirildi: {$label}");
        } elseif ($act === 'tg_reset') {
            tg_reset_session(); flash('ok', 'Sessiya tozalandi. Telefon raqamingizni qayta kiriting.');
        } elseif ($act === 'admin_save') {
            $t = trim((string)$_POST['target']);
            if ($t !== '' && !valid_target($t)) throw new InvalidArgumentException('Username (@nom) yoki raqamli ID noto‘g‘ri.');
            set_setting('admin_target', $t);
            $sel = array_values(array_intersect(array_keys(RIGHTS), (array)($_POST['rights'] ?? [])));
            if ($t !== '' && !$sel) throw new InvalidArgumentException('Kamida bitta huquqni belgilang — huquqsiz admin tayinlanmaydi.');
            set_setting('admin_rights', json_encode($sel));
            set_setting('send_link', isset($_POST['send_link']) ? '1' : '0');
            flash('ok', 'Saqlandi.');
        } elseif ($act === 'auto_save') {
            set_setting('auto_ch_target', (string)max(0, min(500, (int)($_POST['ch_target'] ?? 200))));
            set_setting('auto_gr_target', (string)max(0, min(500, (int)($_POST['gr_target'] ?? 200))));
            if (isset($_POST['reset'])) { foreach (['auto_ch_done', 'auto_gr_done', 'auto_fail', 'auto_pause_until'] as $k) set_setting($k, '0'); }
            if (isset($_POST['start'])) { set_setting('auto_on', '1'); set_setting('auto_fail', '0'); set_setting('auto_pause_until', '0'); auto_status('yoqildi'); flash('ok', 'Avto yaratish yoqildi. Cron har daqiqada ishlaydi.'); }
            elseif (isset($_POST['stop'])) { set_setting('auto_on', '0'); auto_status('to‘xtatildi'); flash('ok', 'To‘xtatildi.'); }
            else flash('ok', 'Saqlandi.');
        } elseif ($act === 'members_start') {
            try {
                members_create((string)($_POST['source'] ?? ''), (string)($_POST['target'] ?? ''), (int)($_POST['limit'] ?? 10000), (int)($_POST['interval'] ?? 60));
            } catch (Throwable $e) {
                $wait = members_wait($e);
                if ($wait > 0) set_setting('tg_pause_until_' . acc_active(), (string)(time() + $wait + 5));
                throw $e;
            }
            flash('ok', 'A’zolarni taklif qilish vazifasi yaratildi. Cron har daqiqada tekshiradi.');
        } elseif (in_array($act, ['members_pause', 'members_resume', 'members_stop'], true)) {
            $job = members_job();
            if (!$job || !hash_equals((string)$job['id'], (string)($_POST['job_id'] ?? ''))) throw new RuntimeException('Vazifa o‘zgargan. Sahifani yangilang.');
            if (!in_array($job['state'], ['running', 'paused', 'paused_account'], true)) throw new RuntimeException('Bu vazifa tugagan yoki xato sabab to‘xtagan.');
            if ($act === 'members_resume' && (int)$job['acc'] !== acc_active()) throw new RuntimeException('Davom ettirish uchun avval #' . $job['acc'] . ' hisobni asosiy qiling.');
            $job['state'] = $act === 'members_stop' ? 'stopped' : ($act === 'members_pause' ? 'paused' : 'running');
            $job['message'] = $act === 'members_stop' ? 'Foydalanuvchi vazifani to‘xtatdi.' : ($act === 'members_pause' ? 'Foydalanuvchi pauza qildi.' : 'Vazifa davom ettiriladi; Telegram kutish muddatlari saqlanadi.');
            members_save($job); flash('ok', $job['message']);
        } elseif ($act === 'dm_preview') {
            $current = dm_job();
            if (in_array($current['state'] ?? '', ['running', 'paused', 'paused_account'], true)) throw new RuntimeException('Avval mavjud xabar vazifasini to‘xtating yoki davom ettiring.');
            $source = trim((string)($_POST['source'] ?? ''));
            members_peer($source);
            $text = dm_text((string)($_POST['text'] ?? ''));
            try {
                if (!logged_in()) throw new RuntimeException('Avval asosiy hisob bilan Telegramga kiring.');
                $senderId = (string)(tg()->getSelf()['id'] ?? '');
                if ($senderId === '') throw new RuntimeException('Telegram hisobi aniqlanmadi.');
            } catch (Throwable $e) {
                $wait = members_wait($e);
                if ($wait > 0) set_setting('tg_pause_until_' . acc_active(), (string)(time() + $wait + 5));
                throw $e;
            }
            $_SESSION['dm_pending'] = [
                'id' => bin2hex(random_bytes(16)), 'acc' => acc_active(), 'self_id' => $senderId, 'source' => $source, 'text' => $text,
                'limit' => max(1, min(10000, (int)($_POST['limit'] ?? 10000))),
                'interval' => max(60, min(3600, (int)($_POST['interval'] ?? 60))),
            ];
            go('dm_confirm');
        } elseif ($act === 'dm_start') {
            $draft = $_SESSION['dm_pending'] ?? null;
            if (!is_array($draft) || !isset($draft['id'], $draft['self_id']) || !hash_equals((string)$draft['id'], (string)($_POST['pending_id'] ?? ''))) throw new RuntimeException('Tasdiqlanadigan xabar o‘zgargan. Matnni qayta ko‘rib chiqing.');
            if ((int)$draft['acc'] !== acc_active()) throw new RuntimeException('Asosiy hisob almashgan. Xabarni yangi hisob bilan qayta ko‘rib chiqing.');
            try {
                dm_create((string)$draft['source'], (string)$draft['text'], (int)$draft['limit'], (int)$draft['interval'], (string)$draft['self_id']);
            } catch (Throwable $e) {
                $wait = members_wait($e);
                if ($wait > 0) set_setting('tg_pause_until_' . acc_active(), (string)(time() + $wait + 5));
                throw $e;
            }
            unset($_SESSION['dm_pending']);
            flash('ok', 'Shaxsiy xabarlar vazifasi yaratildi. Cron navbatni bittadan bajaradi.');
        } elseif (in_array($act, ['dm_pause', 'dm_resume', 'dm_stop'], true)) {
            $job = dm_job();
            if (!$job || !hash_equals((string)$job['id'], (string)($_POST['job_id'] ?? ''))) throw new RuntimeException('Vazifa o‘zgargan. Sahifani yangilang.');
            if (!in_array($job['state'], ['running', 'paused', 'paused_account'], true)) throw new RuntimeException('Bu vazifa tugagan yoki xato sabab to‘xtagan.');
            if ($act === 'dm_resume' && (int)$job['acc'] !== acc_active()) throw new RuntimeException('Davom ettirish uchun avval #' . $job['acc'] . ' hisobni asosiy qiling.');
            $job['state'] = $act === 'dm_stop' ? 'stopped' : ($act === 'dm_pause' ? 'paused' : 'running');
            $job['message'] = $act === 'dm_stop' ? 'Foydalanuvchi xabar vazifasini to‘xtatdi.' : ($act === 'dm_pause' ? 'Foydalanuvchi xabar vazifasini pauza qildi.' : 'Xabar vazifasi davom ettiriladi; Telegram kutish muddatlari saqlanadi.');
            dm_save($job); flash('ok', $job['message']);
        } elseif ($act === 'preview') {
            $type = in_array($_POST['type'] ?? '', ['channel', 'group'], true) ? $_POST['type'] : 'channel';
            $title = trim((string)($_POST['title'] ?? '')); $about = trim((string)($_POST['about'] ?? ''));
            if (isset($_POST['random'])) { $title = gen_names(1)[0]; $_SESSION['draft'] = compact('type', 'title', 'about'); go('create'); }
            if ($title === '' || mb_strlen($title) > 128) throw new InvalidArgumentException('Nom 1–128 belgi bo‘lishi kerak.');
            if (mb_strlen($about) > 255) throw new InvalidArgumentException('Tavsif 255 belgidan oshmasin.');
            $_SESSION['pending'] = compact('type', 'title', 'about');
            go('confirm');
        } elseif ($act === 'create') {
            $d = $_SESSION['pending'] ?? null; unset($_SESSION['pending']);
            if (!$d) throw new RuntimeException('Tasdiqlanadigan ma’lumot yo‘q.');
            if (!logged_in()) throw new RuntimeException('Telegram sessiyasi mavjud emas yoki login tasdiqlanmagan.');
            if (time() - (int)setting('last_create', '0') < 30) throw new RuntimeException('Telegram limitlari uchun yaratishlar orasida kamida 30 soniya kuting.');
            set_setting('last_create', (string)time());
            $id = create_object($d['type'], $d['title'], $d['about']);
            flash('ok', 'Amal bajarildi, natijani tarixdan ko‘ring.');
            header('Location: ?p=history'); exit;
        }
    } catch (Throwable $e) {
        $m = $e instanceof InvalidArgumentException || $e instanceof RuntimeException ? $e->getMessage() : tg_error($e);
        logx('ERROR', "{$act}: {$m}");
        flash('err', $m);
    }
    if (str_starts_with($act, 'members_')) go('members');
    if (str_starts_with($act, 'dm_')) go('dm');
    go(in_array($act, ['tg_phone', 'tg_code', '2fa', 'tg_logout', 'tg_reset', 'acc_add', 'acc_use', 'acc_del', 'acc_rotation_save']) ? ((($_POST['ret'] ?? '') === 'home') ? 'home' : 'tg') : ($act === 'admin_save' ? 'admin' : ($act === 'auto_save' ? 'auto' : ($act === 'preview' ? 'create' : 'home'))));
}

/* ---------- Sahifalar ---------- */
function page_head(string $t): void {
    $cur = (string)($GLOBALS['p'] ?? 'home');
    $nav = '';
    if (isset($_SESSION['auth'])) {
        if ($cur === 'home' || !isset(MENU[$cur]) && $cur !== 'confirm' && $cur !== 'dm_confirm') {
            $nm = trim((string)($_SESSION['tg_first_name'] ?? ''));
            $user = strtolower((string)($_SESSION['tg_username'] ?? '')) ?: 'root';
            $nav = '<div class="hdr"><div class="ava">&gt;_</div><div><b class="glitch" data-t="TG_MENEJER">TG_MENEJER</b><br><small>' . h($user) . '@tgm:~$ id=' . h((string)($_SESSION['tg_user_id'] ?? '')) . ' · ' . h($nm) . '</small></div></div>';
        } else {
            [$ic, $nmx] = MENU[$cur] ?? ['[✓]', 'Tasdiqlash'];
            $nav = '<div class="hdr"><a class="back" href="?p=home">&lt;</a><b>' . h($ic) . ' ' . h($nmx) . '</b><small class="path">~/' . h($cur) . '</small></div>';
        }
    }
    echo '<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex"><title>' . h($t) . '</title><style>
:root{--bg:#030603;--c:rgba(0,20,6,.78);--c2:#000;--t:#b6ffc8;--m:#3fae5c;--a:#00ff41;--ah:#5cff85;--b:rgba(0,255,65,.18);--bd:rgba(0,255,65,.35);--ok:rgba(0,255,65,.08);--okt:#00ff41;--er:rgba(255,40,60,.08);--ert:#ff3b4e;--wr:#ffcc00;--glow:0 0 6px rgba(0,255,65,.55);color-scheme:dark}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html{background:var(--bg)}
body{margin:0 auto;font:14px/1.5 ui-monospace,"JetBrains Mono","Fira Code","SF Mono",Menlo,Consolas,"Liberation Mono","Courier New",monospace;background:var(--bg);color:var(--t);padding:12px 12px 28px;max-width:560px;min-height:100vh;
 background-image:radial-gradient(ellipse at top,rgba(0,255,65,.07),transparent 60%),linear-gradient(rgba(0,255,65,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(0,255,65,.035) 1px,transparent 1px);background-size:100% 100%,22px 22px,22px 22px;background-attachment:fixed}
body::after{content:"";position:fixed;inset:0;pointer-events:none;z-index:99;background:repeating-linear-gradient(0deg,rgba(0,0,0,.22) 0 1px,transparent 1px 3px);animation:flick 6s infinite}
@keyframes flick{0%,97%,100%{opacity:1}98%{opacity:.75}}
@keyframes blink{50%{opacity:0}}
@keyframes boot{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
::selection{background:var(--a);color:#000}
a{color:var(--a)}
.hdr{display:flex;align-items:center;gap:12px;min-height:46px;margin:0 0 12px;padding:6px 10px;border:1px solid var(--bd);background:rgba(0,0,0,.6);box-shadow:inset 0 0 18px rgba(0,255,65,.08)}
.hdr b{font-size:15px;color:var(--a);text-shadow:var(--glow);letter-spacing:1px}.hdr small{color:var(--m)}
.hdr small::after{content:"█";margin-left:3px;color:var(--a);animation:blink 1s steps(1) infinite}
.hdr .path{margin-left:auto}
.ava{width:40px;height:40px;border:1px solid var(--a);color:var(--a);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;flex:none;text-shadow:var(--glow);box-shadow:var(--glow)}
.back{width:34px;height:34px;border:1px solid var(--bd);color:var(--a);text-decoration:none;display:flex;align-items:center;justify-content:center;font-size:18px;flex:none}
.back:active{background:var(--a);color:#000}
.glitch{position:relative;display:inline-block}
.glitch::before,.glitch::after{content:attr(data-t);position:absolute;left:0;top:0;opacity:.7;clip-path:inset(0 0 0 0)}
.glitch::before{color:#ff2a6d;animation:g1 3.2s infinite steps(1)}.glitch::after{color:#05d9e8;animation:g2 2.7s infinite steps(1)}
@keyframes g1{0%,90%,100%{transform:none;clip-path:inset(0 0 100% 0)}92%{transform:translate(-2px,0);clip-path:inset(20% 0 50% 0)}95%{transform:translate(2px,0);clip-path:inset(60% 0 10% 0)}}
@keyframes g2{0%,88%,100%{transform:none;clip-path:inset(0 0 100% 0)}90%{transform:translate(2px,0);clip-path:inset(10% 0 70% 0)}93%{transform:translate(-2px,0);clip-path:inset(50% 0 20% 0)}}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:0 0 10px}
.tile{background:var(--c);border:1px solid var(--b);padding:14px 4px 11px;text-align:center;text-decoration:none;color:var(--t);font-size:11px;text-transform:uppercase;letter-spacing:.5px;display:flex;flex-direction:column;align-items:center;gap:8px;transition:all .12s;min-width:0;animation:boot .3s both}
.tile span{font-size:18px;line-height:1;color:var(--a);text-shadow:var(--glow);font-weight:700;white-space:nowrap}
.tile:hover,.tile:active{border-color:var(--a);box-shadow:var(--glow),inset 0 0 14px rgba(0,255,65,.15)}.tile:active{transform:scale(.96)}
@media(max-width:330px){.grid{grid-template-columns:repeat(3,1fr)}}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:0 0 10px}
.stat{background:var(--c);border:1px solid var(--b);padding:10px;text-align:center}.stat b{display:block;font-size:22px;color:var(--a);text-shadow:var(--glow)}.stat small{font-size:10px;text-transform:uppercase;letter-spacing:1px}
.steps{display:flex;gap:6px;margin:0 0 4px}.steps span{flex:1;text-align:center;font-size:11px;padding:5px 0;border:1px solid var(--b);color:var(--m)}
.steps span.on{border-color:var(--a);color:#000;background:var(--a);box-shadow:var(--glow)}.steps span.done{color:var(--okt);border-color:var(--bd)}.steps span.done::before{content:"✓ "}
.card p{margin:8px 0}
.card{position:relative;background:var(--c);border:1px solid var(--bd);padding:16px;margin:0 0 10px;box-shadow:inset 0 0 24px rgba(0,255,65,.05);animation:boot .25s both}
.card::before,.card::after{content:"";position:absolute;width:10px;height:10px;border:2px solid var(--a)}
.card::before{top:-2px;left:-2px;border-right:0;border-bottom:0}.card::after{bottom:-2px;right:-2px;border-left:0;border-top:0}
h2{margin:0 0 12px;font-size:14px;font-weight:700;color:var(--a);text-transform:uppercase;letter-spacing:1px;text-shadow:var(--glow)}
h2::before{content:"# ";color:var(--m)}
form{display:flex;flex-wrap:wrap;gap:8px;margin:0}
label{flex:1 1 100%;font-size:11px;color:var(--m);margin:6px 0 -2px;text-transform:uppercase;letter-spacing:.5px}label::before{content:"> "}
input,select,textarea{flex:1 1 100%;width:100%;height:42px;padding:0 12px;border-radius:0;border:1px solid var(--bd);background:var(--c2);color:var(--a);font:inherit;outline:0;caret-color:var(--a);transition:border-color .15s,box-shadow .15s}
textarea{height:auto;min-height:84px;padding:10px 12px}
select option{background:#000;color:var(--a)}
input:focus,select:focus,textarea:focus{border-color:var(--a);box-shadow:var(--glow)}
input::placeholder,textarea::placeholder{color:#1f6b33}
button{flex:1 1 auto;min-width:112px;height:42px;padding:0 14px;border:1px solid var(--a);border-radius:0;background:transparent;color:var(--a);font:inherit;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;cursor:pointer;text-shadow:var(--glow);transition:all .12s}
button::before{content:"[ "}button::after{content:" ]"}
button:hover{background:var(--a);color:#000;text-shadow:none;box-shadow:var(--glow)}button:active{transform:scale(.97)}
button.sec{border-color:var(--bd);color:var(--t);text-shadow:none}button.sec:hover{background:rgba(0,255,65,.12);color:var(--a)}
button.red{border-color:var(--ert);color:var(--ert);text-shadow:0 0 6px rgba(255,59,78,.5)}button.red:hover{background:var(--ert);color:#000}
a>button{width:100%}
label.ck{display:flex;gap:10px;align-items:center;padding:5px 0;font-size:13px;color:var(--t);margin:0;text-transform:none;letter-spacing:0}label.ck::before{content:none}
label.ck input{width:18px;height:18px;flex:none;margin:0;accent-color:var(--a)}
.msg{padding:10px 12px;border:1px solid;border-left-width:4px;margin:0 0 10px;font-size:13px}
.msg::before{content:"[*] ";font-weight:700}
.ok{background:var(--ok);color:var(--okt);border-color:var(--okt)}.ok::before{content:"[+] "}
.err{background:var(--er);color:var(--ert);border-color:var(--ert)}.err::before{content:"[x] "}
.msg:not(.ok):not(.err){color:var(--wr);border-color:var(--wr);background:rgba(255,204,0,.06)}
.errt{color:var(--ert)}
.row{border-top:1px dashed var(--b);padding:10px 0;font-size:13px;word-break:break-word}.row:first-of-type{border:0;padding-top:0}.row input{margin:6px 0 0}.row button{margin-top:6px}
.row b{color:var(--a)}
.on{color:var(--a);text-shadow:var(--glow);animation:blink 1.2s steps(1) infinite}
.boot{margin:0 0 10px;padding:10px;background:#000;border:1px solid var(--b);color:var(--a);font:inherit;font-size:12px;white-space:pre-wrap;text-shadow:var(--glow)}
small{color:var(--m);font-size:11px}b{font-weight:700}
</style></head><body>' . $nav;
    foreach ($_SESSION['flash'] ?? [] as [$ty, $m]) echo '<div class="msg ' . ($ty === 'ok' ? 'ok' : 'err') . '">' . h($m) . '</div>';
    unset($_SESSION['flash']);
}
function page_foot(): void {
    $home = (($GLOBALS['p'] ?? 'home') === 'home') ? 'true' : 'false';
    if (isset($_SESSION['auth'])) echo '<script src="https://telegram.org/js/telegram-web-app.js"></script><script>try{var w=Telegram.WebApp;w.ready();w.expand();try{w.setHeaderColor("#000000");w.setBackgroundColor("#030603");}catch(e){}if(!' . $home . '){var bb=document.querySelector(".hdr .back");if(bb&&w.isVersionAtLeast&&w.isVersionAtLeast("6.1"))bb.style.display="none";w.BackButton.show();w.BackButton.onClick(function(){location.href="?p=home";});}else{w.BackButton.hide();}}catch(e){}</script>';
    echo '</body></html>';
}

page_head('TG Menejer');
$db = db();

switch ($p) {
case 'create':
    $d = $_SESSION['draft'] ?? ['type' => 'channel', 'title' => '', 'about' => ''];
    unset($_SESSION['draft']);
    echo '<div class="card"><h2>Yaratish</h2><form method="post">' . csrf_field() . '<input type="hidden" name="action" value="preview">
    <select name="type"><option value="channel"' . ($d['type'] === 'channel' ? ' selected' : '') . '>Yopiq kanal</option><option value="group"' . ($d['type'] === 'group' ? ' selected' : '') . '>Yopiq superguruh</option></select>
    <input name="title" maxlength="128" placeholder="Nomi" value="' . h($d['title']) . '">
    <textarea name="about" maxlength="255" rows="3" placeholder="Tavsif (ixtiyoriy)">' . h($d['about']) . '</textarea>
    <button name="random" value="1" class="sec" formnovalidate>Tasodifiy nom</button><button>Ko‘rib chiqish →</button></form></div>';
    break;

case 'confirm':
    $d = $_SESSION['pending'] ?? null;
    if (!$d) go('create');
    echo '<div class="card"><h2>Tasdiqlang</h2><div class="row">Turi: <b>' . ($d['type'] === 'channel' ? 'Yopiq kanal' : 'Yopiq superguruh') . '</b></div><div class="row">Nomi: <b>' . h($d['title']) . '</b></div><div class="row">Tavsif: ' . h($d['about'] ?: '—') . '</div>
    <div class="row">Hisob: <b>' . h(acc_label(acc_active())) . '</b></div>
    <div class="row">Admin: ' . h(setting('admin_target') ?: 'belgilanmagan') . '</div>
    <form method="post">' . csrf_field() . '<input type="hidden" name="action" value="create"><button>Telegramda yaratish</button></form>
    <a href="?p=create"><button class="sec" type="button">Bekor qilish</button></a></div>';
    break;

case 'names':
    echo '<div class="card"><h2>Nom namunalari (120 ta kombinatsiya)</h2>';
    foreach (gen_names(10) as $n) echo '<form method="post" class="row">' . csrf_field() . '<input type="hidden" name="action" value="preview"><input type="hidden" name="type" value="channel"><input type="hidden" name="about" value=""><input type="hidden" name="title" value="' . h($n) . '"><b>' . h($n) . '</b><button class="sec">Tanlash</button></form>';
    echo '<a href="?p=names"><button type="button">Qayta generatsiya</button></a></div>';
    break;

case 'admin':
    $sel = json_decode(setting('admin_rights', '[]'), true) ?: [];
    echo '<div class="card"><h2>Administrator sozlamalari</h2><form method="post">' . csrf_field() . '<input type="hidden" name="action" value="admin_save">
    <input name="target" placeholder="@username yoki ID" value="' . h(setting('admin_target')) . '">';
    foreach (RIGHTS as $k => $v) echo '<label class="ck"><input type="checkbox" name="rights[]" value="' . $k . '"' . (in_array($k, $sel, true) ? ' checked' : '') . '> ' . h($v) . '</label>';
    echo '<label class="ck"><input type="checkbox" name="send_link" ' . (setting('send_link') === '1' ? 'checked' : '') . '> Havolani adminga Telegram orqali yuborish</label>
    <small>«Admin tayinlash huquqi» (add_admins) hech qachon berilmaydi.</small><button>Saqlash</button></form></div>';
    break;

case 'history':
    echo '<div class="card"><h2>Tarix</h2>';
    foreach ($db->query('SELECT * FROM items ORDER BY id DESC LIMIT 100') as $r)
        echo '<div class="row"><b>' . h($r['title']) . '</b> · ' . ($r['type'] === 'channel' ? 'kanal' : 'guruh') . '<br><small>' . h($r['created_at']) . ' · ID: ' . h($r['tg_id']) . (!empty($r['acc']) ? ' · hisob #' . (int)$r['acc'] : '') . '</small><br>Holat: ' . h($r['status']) .
            ($r['admin_status'] ? '<br>Admin: ' . h($r['admin_status']) : '') . ($r['link_sent'] ? '<br>Havola: ' . h($r['link_sent']) : '') . ($r['error'] ? '<br><span class="errt">' . h($r['error']) . '</span>' : '') . '</div>';
    echo '</div>';
    break;

case 'links':
    echo '<div class="card"><h2>Havolalar</h2>';
    foreach ($db->query("SELECT title,invite_link FROM items WHERE invite_link<>'' ORDER BY id DESC LIMIT 100") as $r)
        echo '<div class="row"><b>' . h($r['title']) . '</b><br><input readonly value="' . h($r['invite_link']) . '" onclick="this.select()"><button class="sec" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent=\'Nusxalandi \'">Nusxalash</button></div>';
    echo '</div>';
    break;

case 'logs':
    echo '<div class="card"><h2>Xatolar jurnali</h2>';
    foreach ($db->query('SELECT * FROM logs ORDER BY id DESC LIMIT 100') as $r) echo '<div class="row"><small>' . h($r['ts']) . '</small><br>' . h($r['msg']) . '</div>';
    echo '</div>';
    break;

case 'auto':
    $on = setting('auto_on') === '1';
    $pu = (int)setting('auto_pause_until', '0');
    echo '<div class="card"><h2>Avto yaratish (har 1 daqiqada)</h2>';
    echo '<div class="row">Holat: <b>' . ($on ? '<span class="on">[ON]</span> ishlayapti' : '[OFF] o‘chiq') . '</b></div>';
    echo '<div class="row">Kanal: <b>' . (int)setting('auto_ch_done', '0') . ' / ' . (int)setting('auto_ch_target', '200') . '</b> · Guruh: <b>' . (int)setting('auto_gr_done', '0') . ' / ' . (int)setting('auto_gr_target', '200') . '</b></div>';
    if ($pu > time()) echo '<div class="row">FloodWait pauza: <b>' . ($pu - time()) . ' soniya</b></div>';
    echo '<div class="row"><small>Oxirgi: ' . h(setting('auto_status', '—')) . '</small></div>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="auto_save">
    <label>Nechta kanal yaratilsin</label><input type="number" name="ch_target" min="0" max="500" value="' . (int)setting('auto_ch_target', '200') . '">
    <label>Nechta guruh yaratilsin</label><input type="number" name="gr_target" min="0" max="500" value="' . (int)setting('auto_gr_target', '200') . '">
    <button name="start" value="1">Boshlash</button><button class="red" name="stop" value="1">To‘xtatish</button>
    <button class="sec" name="save" value="1">Faqat saqlash</button>
    <button class="sec" name="reset" value="1" onclick="return confirm(\'Hisoblagichlar nolga tushsinmi?\')">Hisobni nolga tushirish</button></form></div>';
    echo '<div class="card"><h2>Cron (bir marta sozlanadi)</h2><small>Serverda <b>www-data</b> nomidan har daqiqaga (setup.sh buni /etc/cron.d/tgm ga o‘zi yozadi):</small><div class="row"><input readonly onclick="this.select()" value="' . h('* * * * * www-data /usr/bin/php ' . __FILE__ . ' cron') . '"></div></div>';
    break;

case 'dm':
    $job = dm_job();
    $busy = in_array($job['state'] ?? '', ['running', 'paused', 'paused_account'], true);
    $draft = $_SESSION['dm_pending'] ?? [];
    if (!is_array($draft) || (int)($draft['acc'] ?? 0) !== acc_active()) $draft = [];
    echo '<div class="card"><h2>Shaxsiy xabarlar</h2><p>Asosiy hisob: <b>' . h(acc_label(acc_active())) . '</b></p>';
    echo '<p><small>Manba guruhning shu hisobga ko‘rinadigan a’zolariga bittadan shaxsiy xabar yuboriladi. Botlar, o‘zingiz va o‘chirilgan akkauntlar navbatga olinmaydi. Barcha a’zolarga xabar yetib borishi kafolatlanmaydi.</small></p>';
    echo '<p><small>Eng qisqa oraliq 60 soniya. Telegram kutish talabi chiqsa kutiladi; spam cheklovida vazifa to‘xtaydi. Mini App yopiq paytda <a href="?p=auto">cron</a> navbatni bajaradi.</small></p>';
    echo '<p><small>Vazifa boshlangan hisobga bog‘lanadi. Asosiy hisob almashsa pauza qiladi; shu hisob yana asosiy bo‘lganda davom etadi. Soatlik almashtirishni <a href="?p=tg">Telegram bo‘limida</a> boshqarishingiz mumkin.</small></p>';
    if (!$busy) {
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="dm_preview">
        <label>Manba guruh</label><input name="source" placeholder="@manba_guruh yoki -1001234567890" value="' . h((string)($draft['source'] ?? '')) . '" required>
        <label>Xabar matni (4096 belgigacha)</label><textarea name="text" maxlength="4096" rows="6" placeholder="Yuboriladigan xabar matni" required>' . h((string)($draft['text'] ?? '')) . '</textarea>
        <label>Tekshiriladigan a’zolar soni (1–10000)</label><input type="number" name="limit" min="1" max="10000" value="' . (int)($draft['limit'] ?? 10000) . '" required>
        <label>Xabarlar oralig‘i (soniya)</label><input type="number" name="interval" min="60" max="3600" step="60" value="' . (int)($draft['interval'] ?? 60) . '" required>
        <button>Xabarni ko‘rib chiqish</button></form>';
    }
    echo '</div>';
    if ($job) {
        $states = ['running' => 'Ishlayapti', 'paused' => 'Pauza', 'paused_account' => 'Asosiy hisob kutilmoqda', 'done' => 'Tugadi', 'stopped' => 'To‘xtatilgan', 'error' => 'Xato sabab to‘xtagan'];
        $counts = dm_counts($job['id']);
        echo '<div class="card"><h2>Xabar vazifasi</h2><div class="row">Manba: <b>' . h($job['source_title']) . '</b></div>';
        echo '<div class="row">Hisob: <b>#' . (int)$job['acc'] . '</b> · Holat: <b>' . h($states[$job['state']] ?? $job['state']) . '</b></div>';
        echo '<div class="row">Tekshirilgan: <b>' . (int)$job['scanned'] . ' / ' . (int)$job['limit'] . '</b> · Navbatda: <b>' . ($counts['pending'] + $counts['working']) . '</b></div>';
        echo '<div class="row">Yuborilgan: <b>' . $counts['sent'] . '</b> · Tashlab ketilgan: <b>' . $counts['skipped'] . '</b><br>Xato: ' . $counts['failed'] . ' · Natijasi noma’lum: ' . $counts['unknown'] . '</div>';
        echo '<div class="row"><small>' . h($job['message']) . '</small></div><pre class="boot">' . h($job['text']) . '</pre>';
        if ($busy) {
            $next = max((int)$job['next_at'], (int)setting('tg_pause_until_' . $job['acc'], '0'), (int)setting('auto_pause_until', '0'));
            echo '<div class="row">Keyingi tekshiruv: <b>' . h(date('d.m.Y H:i:s', $next)) . '</b></div>';
            echo '<form method="post">' . csrf_field() . '<input type="hidden" name="job_id" value="' . h($job['id']) . '">';
            echo $job['state'] === 'running' ? '<button class="sec" name="action" value="dm_pause">Pauza</button>' : '<button class="sec" name="action" value="dm_resume">Davom ettirish</button>';
            echo '<button class="red" name="action" value="dm_stop">Vazifani to‘xtatish</button></form>';
        }
        if ($counts['unknown'] > 0) echo '<p><small>Natijasi noma’lum bo‘lgan xabarlar avtomatik qayta yuborilmaydi. Ularni Telegramda tekshiring.</small></p>';
        echo '<p><a href="?p=dm">Holatni yangilash</a></p></div>';
        $q = $db->prepare("SELECT user_id,status,reason FROM dm_queue WHERE job_id=? AND status IN ('skipped','failed','unknown') ORDER BY rowid DESC LIMIT 15");
        $q->execute([$job['id']]); $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo '<div class="card"><h2>Oxirgi natijalar</h2>';
            foreach ($rows as $row) echo '<div class="row"><small>ID ' . h($row['user_id']) . '</small><br>' . h($row['reason']) . '</div>';
            echo '</div>';
        }
    }
    break;

case 'dm_confirm':
    $draft = $_SESSION['dm_pending'] ?? null;
    if (!is_array($draft) || !isset($draft['id'], $draft['self_id']) || (int)($draft['acc'] ?? 0) !== acc_active()) {
        echo '<div class="card"><h2>Xabarni tasdiqlash</h2><div class="msg err">Tasdiqlanadigan xabar topilmadi yoki asosiy hisob almashgan.</div><p><a href="?p=dm">Xabarni qayta ko‘rib chiqish</a></p></div>';
        break;
    }
    echo '<div class="card"><h2>Xabarni tasdiqlang</h2><div class="row">Manba guruh: <b>' . h((string)$draft['source']) . '</b></div>';
    echo '<div class="row">Hisob: <b>' . h(acc_label((int)$draft['acc'])) . '</b></div>';
    echo '<div class="row">Yuboruvchi Telegram ID: <b>' . h((string)$draft['self_id']) . '</b></div>';
    echo '<div class="row">Tekshiriladigan a’zolar: <b>' . (int)$draft['limit'] . ' tagacha</b> · Oraliq: <b>' . (int)$draft['interval'] . ' soniya</b></div>';
    echo '<pre class="boot">' . h((string)$draft['text']) . '</pre><p><small>Matn oddiy matn sifatida yuboriladi. Navbat boshlangach, xabar matni shu vazifa uchun saqlanadi.</small></p>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="dm_start"><input type="hidden" name="pending_id" value="' . h((string)$draft['id']) . '"><button>Xabar yuborishni boshlash</button></form>';
    echo '<p><a href="?p=dm">Matnni tahrirlashga qaytish</a></p></div>';
    break;

case 'members':
    $job = members_job();
    $busy = in_array($job['state'] ?? '', ['running', 'paused', 'paused_account'], true);
    echo '<div class="card"><h2>Guruhga a’zo taklif qilish</h2><p>Asosiy hisob: <b>' . h(acc_label(acc_active())) . '</b></p>';
    echo '<p><small>Manba guruhda oddiy a’zo bo‘lish yetishi mumkin, lekin a’zolar ro‘yxati sizga ko‘rinishi kerak. Qabul qiluvchi guruhda yaratuvchi yoki taklif huquqi bor admin bo‘ling. Takliflar Telegram maxfiylik ruxsatlariga qarab bajariladi; barcha a’zolar qo‘shilishi kafolatlanmaydi.</small></p>';
    echo '<p><small>Bitta taklif kamida 60 soniya oralig‘ida yuboriladi. FloodWait bo‘lsa kutiladi, cheklov yoki huquq xatosida vazifa to‘xtaydi. Mini App yopiq paytda ham <a href="?p=auto">cron</a> ishlaydi.</small></p>';
    echo '<p><small>Vazifa boshlangan hisobga bog‘lanadi. Asosiy hisob almashsa pauza bo‘ladi; shu hisob yana asosiy bo‘lganda davom etadi. Uzoq vazifa davomida soatlik almashtirishni <a href="?p=tg">Telegram bo‘limida</a> vaqtincha to‘xtatishingiz mumkin.</small></p>';
    if (!$busy) {
        echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="members_start">
        <label>Manba guruh</label><input name="source" placeholder="@manba_guruh yoki -1001234567890" value="' . h((string)($job['source'] ?? '')) . '" required>
        <label>O‘zingizning guruhingiz</label><input name="target" placeholder="@mening_guruhim yoki -1001234567890" value="' . h((string)($job['target'] ?? '')) . '" required>
        <label>Tekshiriladigan a’zolar soni (1–10000)</label><input type="number" name="limit" min="1" max="10000" value="10000" required>
        <label>Takliflar oralig‘i (soniya)</label><input type="number" name="interval" min="60" max="3600" step="60" value="60" required>
        <button>Taklif qilishni boshlash</button></form>';
    }
    echo '</div>';
    if ($job) {
        $states = ['running' => 'Ishlayapti', 'paused' => 'Pauza', 'paused_account' => 'Asosiy hisob kutilmoqda', 'done' => 'Tugadi', 'stopped' => 'To‘xtatilgan', 'error' => 'Xato sabab to‘xtagan'];
        $counts = members_counts($job['id']);
        echo '<div class="card"><h2>Vazifa holati</h2><div class="row">' . h($job['source_title']) . ' → <b>' . h($job['target_title']) . '</b></div>';
        echo '<div class="row">Hisob: <b>#' . (int)$job['acc'] . '</b> · Holat: <b>' . h($states[$job['state']] ?? $job['state']) . '</b></div>';
        echo '<div class="row">Tekshirilgan: <b>' . (int)$job['scanned'] . ' / ' . (int)$job['limit'] . '</b> · Navbatda: <b>' . ($counts['pending'] + $counts['working']) . '</b></div>';
        echo '<div class="row">Muvaffaqiyatli taklif: <b>' . $counts['invited'] . '</b> · Tashlab ketilgan: <b>' . $counts['skipped'] . '</b><br>Xato: ' . $counts['failed'] . ' · Natijasi noma’lum: ' . $counts['unknown'] . '</div>';
        echo '<div class="row"><small>' . h($job['message']) . '</small></div>';
        if ($busy) {
            $next = max((int)$job['next_at'], (int)setting('tg_pause_until_' . $job['acc'], '0'), (int)setting('auto_pause_until', '0'));
            echo '<div class="row">Keyingi tekshiruv: <b>' . h(date('d.m.Y H:i:s', $next)) . '</b></div>';
            echo '<form method="post">' . csrf_field() . '<input type="hidden" name="job_id" value="' . h($job['id']) . '">';
            echo $job['state'] === 'running' ? '<button class="sec" name="action" value="members_pause">Pauza</button>' : '<button class="sec" name="action" value="members_resume">Davom ettirish</button>';
            echo '<button class="red" name="action" value="members_stop">Vazifani to‘xtatish</button></form>';
        }
        echo '<p><a href="?p=members">Holatni yangilash</a></p></div>';
        $q = $db->prepare("SELECT user_id,status,reason FROM member_queue WHERE job_id=? AND status IN ('skipped','failed','unknown') ORDER BY rowid DESC LIMIT 15");
        $q->execute([$job['id']]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            echo '<div class="card"><h2>Oxirgi natijalar</h2>';
            foreach ($rows as $row) echo '<div class="row"><small>ID ' . h($row['user_id']) . '</small><br>' . h($row['reason']) . '</div>';
            echo '</div>';
        }
    }
    break;

case 'tg':
    $st = tg_state();
    $accs = acc_list(); $act = acc_active();
    $rotateOn = acc_rotation_on();
    $rotateNext = (int)setting('acc_rotate_next', '0');
    echo '<div class="card"><h2>Hisoblarni avto almashtirish</h2>';
    echo '<div class="row">Holat: <b>' . ($rotateOn ? '[ON] har 1 soatda' : '[OFF] o‘chiq') . '</b></div>';
    echo '<p><small>Hisoblar raqami bo‘yicha navbat bilan asosiy qilinadi. Oxirgi hisobdan so‘ng birinchisiga qaytadi. Barcha hisoblarni oldindan Telegramga ulang. Qo‘lda asosiy hisob tanlansa, bir soat qaytadan sanaladi.</small></p>';
    if ($rotateOn) {
        echo '<div class="row">Keyingi almashish: <b>' . ($rotateNext > 0 ? h(date('d.m.Y H:i:s', $rotateNext)) : 'cron ishga tushganidan 1 soat keyin') . '</b></div>';
        if ($rotateNext > 0 && $rotateNext < time() - 120) echo '<div class="msg err">Almashish vaqti o‘tgan. Serverdagi cron ishlayotganini tekshiring.</div>';
    }
    echo '<p><small>Mini App yopiq bo‘lsa ham ishlashi uchun <a href="?p=auto">cron</a> har daqiqada ishlashi kerak. Hisob almashishi avto yaratishdan mustaqil.</small></p>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="acc_rotation_save"><button class="' . ($rotateOn ? 'red' : 'sec') . '" name="enabled" value="' . ($rotateOn ? '0' : '1') . '">' . ($rotateOn ? 'Almashtirishni to‘xtatish' : 'Har soatda almashtirishni yoqish') . '</button></form></div>';
    echo '<div class="card"><h2>Hisoblar (' . count($accs) . '/' . MAX_ACCOUNTS . ')</h2><small>Barcha amallar va avto yaratish faqat <b>asosiy</b> hisobdan bajariladi.</small>';
    foreach ($accs as $n) {
        $isA = $n === $act;
        echo '<div class="row">' . ($isA ? '<b>[*] ' . h(acc_label($n)) . '</b> <small>asosiy</small>' : h(acc_label($n)))
            . '<form method="post">' . csrf_field() . '<input type="hidden" name="slot" value="' . $n . '">'
            . (!$isA ? '<button class="sec" name="action" value="acc_use">Asosiy qilish</button>' : '')
            . (count($accs) > 1 ? '<button class="red" name="action" value="acc_del" onclick="return confirm(\'' . h('#' . $n) . ' hisobi o‘chirilsinmi? Sessiyasi tugatiladi.\')">O‘chirish</button>' : '')
            . '</form></div>';
    }
    if (count($accs) < MAX_ACCOUNTS) echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="acc_add"><button>+ Hisob qo‘shish</button></form>';
    echo '</div>';
    echo '<div class="card"><h2>Asosiy hisob #' . $act . '</h2>';
    if ($st === API::LOGGED_IN) {
        try { $me = tg()->getSelf(); acc_remember_self($me); echo '<div class="msg ok">Ulangan: ' . h(trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? ''))) . ' ' . (isset($me['username']) ? '@' . h($me['username']) : '') . '</div>'; }
        catch (Throwable $e) { echo '<div class="msg err">' . h(tg_error($e)) . '</div>'; }
        echo '<form method="post" onsubmit="return confirm(\'Telegram sessiyasi tugatilsinmi?\')">' . csrf_field() . '<input type="hidden" name="action" value="tg_logout"><button class="red">Telegramdan chiqish</button></form>';
    } else {
        echo tg_login_card($st, 'tg');
    }
    echo '</div>';
    break;

default:
    $n = (int)$db->query('SELECT COUNT(*) FROM items')->fetchColumn();
    $e = (int)$db->query("SELECT COUNT(*) FROM items WHERE status='xato'")->fetchColumn();
    $s = tg_state();
    // 1) Telegram profil holati — ulanmagan bo'lsa, ulash formasi shu yerning o'zida
    if ($s === API::LOGGED_IN) {
        $who = '';
        try { $me = tg()->getSelf(); acc_remember_self($me); $who = trim(($me['first_name'] ?? '') . ' ' . ($me['last_name'] ?? '')) . (isset($me['username']) ? ' · @' . $me['username'] : ''); } catch (Throwable $ex) {}
        $cnt = count(acc_list());
        echo '<a href="?p=tg" style="text-decoration:none"><div class="msg ok">Telegram ulangan #' . acc_active() . ($who !== '' ? ': <b>' . h($who) . '</b>' : '') . '<br><small style="color:inherit">Hisoblar: ' . $cnt . '/' . MAX_ACCOUNTS . ' · almashtirish / qo‘shish →</small></div></a>';
    } else {
        echo '<div class="card"><h2>Telegram profilni ulang (#' . acc_active() . ')</h2>' . tg_login_card($s, 'home') . (count(acc_list()) > 1 ? '<p><a href="?p=tg"><small>Boshqa hisobga o‘tish →</small></a></p>' : '') . '</div>';
    }
    // 2) Statistika
    $on = setting('auto_on') === '1';
    echo '<div class="stats"><div class="stat"><b>' . $n . '</b><small>Yaratilgan</small></div><div class="stat"><b>' . $e . '</b><small>Xatolik</small></div><div class="stat"><b>' . ($on ? '<span class="on">ON</span>' : 'OFF') . '</b><small>Avto</small></div></div>';
    // 3) Tugmalar to'ri
    echo '<div class="grid">';
    foreach (MENU as $k => [$ic, $nm]) echo '<a class="tile" href="?p=' . $k . '"><span>' . h($ic) . '</span>' . h($nm) . '</a>';
    echo '</div>';
    echo '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="logout"><button class="sec">Paneldan chiqish</button></form>';
}
page_foot();
