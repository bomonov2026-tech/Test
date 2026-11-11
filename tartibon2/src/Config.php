<?php
declare(strict_types=1);

/**
 * Sozlamalar. `tartibon2/config.php` bo'lsa o'sha o'qiladi,
 * bo'lmasa ilova namoyish (demo) rejimida ishlaydi.
 */
final class Config
{
    /** @var array<string,mixed>|null */
    private static $data = null;

    private const DEFAULTS = [
        'bot_token'    => '',
        'bot_username' => 'TartibOnBot',
        'support'      => 'tartibon_admin',
        'admin_ids'    => [],
        'db'           => ['host' => '127.0.0.1', 'port' => 3306, 'name' => '', 'user' => '', 'pass' => ''],
        'wallet'       => ['min' => 5000, 'max' => 3000000, 'wd_min' => 20000],
        'auth_ttl'     => 86400, // initData necha soniya amal qiladi
    ];

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }
        $file = dirname(__DIR__) . '/config.php';
        $user = is_file($file) ? require $file : [];
        if (!is_array($user)) {
            $user = [];
        }
        $cfg = self::DEFAULTS;
        foreach ($user as $k => $v) {
            $cfg[$k] = (is_array($v) && isset($cfg[$k]) && is_array($cfg[$k])) ? ($v + $cfg[$k]) : $v;
        }
        self::$data = $cfg;
        return $cfg;
    }

    /** @return mixed */
    public static function get(string $key, $fallback = null)
    {
        $cfg = self::all();
        return $cfg[$key] ?? $fallback;
    }

    /** Bot tokeni yoki baza sozlanmagan bo'lsa — namoyish rejimi. */
    public static function isDemo(): bool
    {
        $cfg = self::all();
        return $cfg['bot_token'] === '' || ($cfg['db']['name'] ?? '') === '';
    }
}
