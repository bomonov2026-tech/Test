<?php
declare(strict_types=1);

/**
 * Umumiy yuklash fayli: konfiguratsiya, xatoliklar, yordamchi funksiyalar.
 * Barcha public sahifalar va API lar shu faylni yuklaydi.
 */

define('APP_ROOT', dirname(__DIR__));                 // restoran/
define('PUBLIC_ROOT', APP_ROOT . '/public_html');     // web hujjatlar ildizi
define('UPLOAD_ROOT', PUBLIC_ROOT . '/uploads');

$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config/config.php topilmadi. config/config.sample.php dan nusxa oling.');
}
$GLOBALS['APP_CONFIG'] = require $configFile;

date_default_timezone_set((string) config('app.timezone', 'Asia/Tashkent'));
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['APP_CONFIG'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function is_api_request(): bool
{
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    return str_contains($script, '/api/') || str_contains($script, '/bot/') || str_ends_with($script, '/admin/api.php');
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[restoran] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $debug = (bool) config('app.debug', false);
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'Serverda xatolik yuz berdi. Keyinroq urinib ko‘ring.',
            'detail' => $debug ? $e->getMessage() : null,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo '<!doctype html><meta charset="utf-8"><title>Xatolik</title>';
        echo '<p style="font-family:sans-serif;padding:24px">Serverda xatolik yuz berdi. Keyinroq urinib ko‘ring.</p>';
        if ($debug) {
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }
    exit;
});

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/telegram.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/layout.php';

/* ---------- JSON javoblar ---------- */

function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function json_ok(array $data = []): never
{
    json_out(['ok' => true] + $data, 200);
}

function json_fail(string $message, int $status = 400, array $extra = []): never
{
    json_out(['ok' => false, 'error' => $message] + $extra, $status);
}

function require_method(string ...$methods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $methods, true)) {
        json_fail('Ruxsat etilmagan so‘rov usuli', 405);
    }
}

/** JSON body (application/json) yoki form-data ni massiv sifatida qaytaradi. */
function request_data(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $raw = file_get_contents('php://input') ?: '';
    $decoded = $raw !== '' ? json_decode($raw, true) : null;
    $data = is_array($decoded) ? $decoded : $_POST;
    return $data;
}

function input_value(string $key, mixed $default = null): mixed
{
    $data = request_data();
    return array_key_exists($key, $data) ? $data[$key] : $default;
}

/* ---------- Chiqish va yordamchi funksiyalar ---------- */

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int|float|string $amount): string
{
    $currency = setting('currency', 'so‘m');
    return number_format((float) $amount, 0, '.', ' ') . ' ' . $currency;
}

function money_plain(int|float|string $amount): string
{
    return number_format((float) $amount, 0, '.', ' ');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function base_url(): string
{
    return rtrim((string) config('app.base_url', ''), '/');
}

function asset_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }
    return '/' . ltrim($path, '/');
}

/**
 * Xavfsiz HTTP sarlavhalari. $context = 'mini' | 'admin'.
 * $nonce berilsa, shu nonce bilan inline <script> ruxsat etiladi (bitta faylli sahifalar uchun).
 */
function send_security_headers(string $context = 'mini', ?string $nonce = null): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    $nonceSrc = $nonce !== null ? " 'nonce-{$nonce}'" : '';
    $scripts = "script-src 'self'{$nonceSrc} https://telegram.org";
    $common = "default-src 'self'; {$scripts}; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'";
    if ($context === 'admin') {
        header('X-Frame-Options: DENY');
        header("Content-Security-Policy: {$common}; frame-ancestors 'none'");
    } else {
        // Telegram Web mini appni iframe ichida ochadi
        header("Content-Security-Policy: {$common}; frame-ancestors 'self' https://web.telegram.org https://*.telegram.org");
    }
}

/* ---------- Sozlamalar (settings jadvali) ---------- */

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return array_key_exists($key, $all) && $all[$key] !== null ? (string) $all[$key] : $default;
}

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($cache === null || $refresh) {
        $cache = [];
        foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache;
}

function set_setting(string $key, string $value): void
{
    db_exec(
        'INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [':k' => $key, ':v' => $value]
    );
    settings_all(true);
}
