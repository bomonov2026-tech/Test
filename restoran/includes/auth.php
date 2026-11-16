<?php
declare(strict_types=1);

/**
 * Autentifikatsiya:
 *  - Mijozlar: Telegram Mini App initData (HMAC imzo tekshiriladi, sessiya ishlatilmaydi)
 *  - Xodimlar (admin / oshpaz): PHP sessiya + password_hash + CSRF
 */

/* =====================================================================
 *  MIJOZ (Telegram)
 * ===================================================================== */

function tg_bot_token(): string
{
    $token = trim((string) config('telegram.bot_token', ''));
    if ($token === '' || str_starts_with($token, 'CHANGE_ME')) {
        $token = trim(setting('bot_token', ''));
    }
    return $token;
}

/**
 * Joriy mijozni qaytaradi. X-Init-Data sarlavhasi imzosi tekshiriladi.
 * $required = true bo‘lsa, yaroqsiz bo‘lganda 401 qaytaradi.
 */
function tg_customer(bool $required = true): ?array
{
    static $resolved = false;
    static $user = null;

    if (!$resolved) {
        $resolved = true;
        $initData = (string) ($_SERVER['HTTP_X_INIT_DATA'] ?? '');
        $tg = $initData !== ''
            ? tg_verify_init_data($initData, tg_bot_token(), (int) config('telegram.init_data_max_age', 86400))
            : null;
        if ($tg !== null) {
            $user = tg_upsert_customer($tg);
            if ((int) $user['is_blocked'] === 1) {
                json_fail('Hisobingiz bloklangan. Qo‘llab-quvvatlash bilan bog‘laning.', 403);
            }
        }
    }
    if ($user === null && $required) {
        json_fail('Telegram orqali oching. Sessiya aniqlanmadi.', 401);
    }
    return $user;
}

function tg_upsert_customer(array $tg): array
{
    db_exec(
        'INSERT INTO users (telegram_id, first_name, last_name, username, language_code, last_seen_at)
         VALUES (:id, :fn, :ln, :un, :lc, NOW())
         ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name),
                                 username = VALUES(username), language_code = VALUES(language_code),
                                 last_seen_at = VALUES(last_seen_at)',
        [
            ':id' => (int) $tg['id'],
            ':fn' => mb_substr((string) ($tg['first_name'] ?? ''), 0, 120),
            ':ln' => isset($tg['last_name']) ? mb_substr((string) $tg['last_name'], 0, 120) : null,
            ':un' => isset($tg['username']) ? mb_substr((string) $tg['username'], 0, 120) : null,
            ':lc' => isset($tg['language_code']) ? mb_substr((string) $tg['language_code'], 0, 10) : null,
        ]
    );
    return db_one('SELECT * FROM users WHERE telegram_id = :id', [':id' => (int) $tg['id']]) ?? [];
}

/* =====================================================================
 *  XODIM (admin / kitchen) — sessiya
 * ===================================================================== */

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name((string) config('session.admin_name', 'RESTORAN_ADMIN'));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function admin_login(string $username, string $password): ?array
{
    admin_session_start();
    $admin = db_one('SELECT * FROM admins WHERE username = :u AND is_active = 1', [':u' => $username]);
    if ($admin === null || !password_verify($password, (string) $admin['password_hash'])) {
        usleep(800000); // brute-force ni sekinlashtirish
        return null;
    }
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['last_activity'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db_exec('UPDATE admins SET last_login_at = NOW() WHERE id = :id', [':id' => $admin['id']]);
    admin_log('login', 'admin', (string) $admin['id']);
    return $admin;
}

/** Joriy xodim (yo‘q bo‘lsa null). Bir so‘rov ichida keshlanadi. */
function admin_current(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    admin_session_start();
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) {
        return null;
    }
    $idle = (int) config('session.idle_timeout', 7200);
    if (time() - (int) ($_SESSION['last_activity'] ?? 0) > $idle) {
        admin_logout();
        return null;
    }
    $user = db_one('SELECT id, username, full_name, role, telegram_id FROM admins WHERE id = :id AND is_active = 1', [':id' => $id]);
    if ($user === null) {
        admin_logout();
        return null;
    }
    $_SESSION['last_activity'] = time();
    return $user;
}

/**
 * Sahifa/API uchun rol tekshiruvi.
 * @param string[] $roles  ruxsat etilgan rollar: 'admin', 'kitchen'
 */
function admin_require(array $roles = ['admin', 'kitchen']): array
{
    $user = admin_current();
    if ($user === null) {
        if (is_api_request()) {
            json_fail('Avval tizimga kiring', 401);
        }
        redirect('/admin/login.php');
    }
    if (!in_array($user['role'], $roles, true)) {
        if (is_api_request()) {
            json_fail('Bu amal uchun ruxsat yo‘q', 403);
        }
        if ($user['role'] === 'kitchen') {
            redirect('/admin/kitchen.php');
        }
        http_response_code(403);
        exit('Ruxsat yo‘q');
    }
    return $user;
}

function admin_logout(): void
{
    admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    }
    session_destroy();
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    admin_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf'];
}

function csrf_valid(): bool
{
    admin_session_start();
    $expected = (string) ($_SESSION['csrf'] ?? '');
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return $expected !== '' && $sent !== '' && hash_equals($expected, $sent);
}

function csrf_require(): void
{
    if (!csrf_valid()) {
        if (is_api_request()) {
            json_fail('Sessiya muddati tugagan. Sahifani yangilab qayta urinib ko‘ring.', 419);
        }
        http_response_code(419);
        exit('Sessiya muddati tugagan. Sahifani yangilab qayta urinib ko‘ring.');
    }
}

/* ---------- Flash xabarlar ---------- */

function flash_set(string $type, string $message): void
{
    admin_session_start();
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flash_pull(): array
{
    admin_session_start();
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------- Audit jurnali ---------- */

function admin_log(string $action, ?string $entity = null, ?string $entityId = null, array $details = []): void
{
    admin_session_start();
    $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    db_exec(
        'INSERT INTO admin_logs (admin_id, action, entity, entity_id, details, ip) VALUES (:a, :act, :ent, :eid, :det, :ip)',
        [
            ':a' => $adminId,
            ':act' => mb_substr($action, 0, 80),
            ':ent' => $entity,
            ':eid' => $entityId,
            ':det' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            ':ip' => client_ip(),
        ]
    );
}
