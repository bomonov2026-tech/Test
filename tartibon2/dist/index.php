<?php
declare(strict_types=1);
/* TartibOn — build/bundle.php tomonidan yig'ilgan. Tahrirlamang: src/ ni tahrirlang. */
/* Yig'ilgan vaqt: 2026-11-12 03:57 */

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

/** Yagona PDO ulanishi. */
final class Db
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $c = Config::get('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'], (int)$c['port'], $c['name']);
        self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return self::$pdo;
    }

    /** @param array<int|string,mixed> $args */
    public static function run(string $sql, array $args = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($args);
        return $st;
    }

    /** @param array<int|string,mixed> $args @return array<string,mixed>|null */
    public static function one(string $sql, array $args = []): ?array
    {
        $row = self::run($sql, $args)->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<int|string,mixed> $args @return list<array<string,mixed>> */
    public static function all(string $sql, array $args = []): array
    {
        return self::run($sql, $args)->fetchAll();
    }
}

/**
 * Telegram Mini App `initData` imzosini tekshirish.
 * Hujjat: https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
final class Telegram
{
    /**
     * Imzo to'g'ri bo'lsa foydalanuvchi massivini, aks holda null qaytaradi.
     * @return array<string,mixed>|null
     */
    public static function verify(string $initData): ?array
    {
        if ($initData === '') {
            return null;
        }
        $token = (string)Config::get('bot_token');
        if ($token === '') {
            return null;
        }

        parse_str($initData, $fields);
        if (!isset($fields['hash']) || !is_string($fields['hash'])) {
            return null;
        }
        $hash = $fields['hash'];
        unset($fields['hash'], $fields['signature']);

        ksort($fields);
        $pairs = [];
        foreach ($fields as $k => $v) {
            $pairs[] = $k . '=' . (is_string($v) ? $v : json_encode($v));
        }
        $checkString = implode("\n", $pairs);

        $secret = hash_hmac('sha256', $token, 'WebAppData', true);
        $calc   = hash_hmac('sha256', $checkString, $secret);
        if (!hash_equals($calc, $hash)) {
            return null;
        }

        $ttl = (int)Config::get('auth_ttl', 86400);
        if ($ttl > 0 && (time() - (int)($fields['auth_date'] ?? 0)) > $ttl) {
            return null; // eskirgan
        }

        $user = json_decode((string)($fields['user'] ?? ''), true);
        return is_array($user) && isset($user['id']) ? $user : null;
    }

    /** Namoyish rejimidagi soxta foydalanuvchi. @return array<string,mixed> */
    public static function demoUser(): array
    {
        return ['id' => 1, 'first_name' => 'Mehmon', 'username' => 'demo', 'language_code' => 'uz'];
    }
}

/**
 * Bo'limlar va xizmatlar — ilovaning yagona manbasi.
 *
 * kind   — rang va ma'no:
 *          'manage'  ko'k    — siz boshqarasiz
 *          'auto'    yashil  — ilova o'zi bajaradi (tumbler bor)
 *          'digital' binafsha — sotib olinadi
 *          'help'    ko'k    — yordam
 * toggle — true bo'lsa xizmat ro'yxatda tumbler bilan chiqadi
 * icon   — assets/icons.js dagi kalit
 */
final class Catalog
{
    private const DATA = [
        [
            'id' => 'manage', 'title' => 'Boshqaruv', 'subtitle' => 'Kanal va guruh boshqaruvi',
            'icon' => 'manage', 'kind' => 'manage',
            'items' => [
                ['id' => 'my-channel', 'title' => 'Kanallarim',     'icon' => 'channel',  'toggle' => false, 'sub' => 'Ulangan kanallar'],
                ['id' => 'my-group',   'title' => 'Guruhlarim',     'icon' => 'group',    'toggle' => false, 'sub' => 'Ulangan guruhlar'],
                ['id' => 'shield',     'title' => 'Guruh himoyasi', 'icon' => 'shield',   'toggle' => true,  'sub' => 'Spam va reklamaga qarshi'],
                ['id' => 'schedule',   'title' => 'Avto Post',      'icon' => 'calendar', 'toggle' => true,  'sub' => 'Rejalashtirilgan postlar'],
                ['id' => 'stats',      'title' => 'Statistika',     'icon' => 'chart',    'toggle' => false, 'sub' => 'Obunachilar dinamikasi'],
                ['id' => 'tests',      'title' => 'Testlar',        'icon' => 'test',     'toggle' => false, 'sub' => 'Guruhdagi test o\'yinlari'],
                ['id' => 'paid',       'title' => 'Pullik kanallar','icon' => 'paid',     'toggle' => false, 'sub' => 'Obuna asosidagi kanal'],
                ['id' => 'contest',    'title' => 'Konkurs',        'icon' => 'trophy',   'toggle' => false, 'sub' => 'Sovrinli tanlovlar'],
            ],
        ],
        [
            'id' => 'daily', 'title' => 'Avtomatik xabarlar', 'subtitle' => 'Har kunlik xabar va eslatmalar',
            'icon' => 'daily', 'kind' => 'auto',
            'items' => [
                ['id' => 'prayer',   'title' => 'Namoz vaqtlari',    'icon' => 'prayer',   'toggle' => true, 'sub' => 'Kunlik namoz jadvali'],
                ['id' => 'currency', 'title' => 'Dollar kursi',      'icon' => 'currency', 'toggle' => true, 'sub' => 'Markaziy bank kursi'],
                ['id' => 'weather',  'title' => 'Ob-havo',           'icon' => 'weather',  'toggle' => true, 'sub' => 'Viloyatlar bo\'yicha'],
                ['id' => 'morning',  'title' => 'Tongi xabar',       'icon' => 'morning',  'toggle' => true, 'sub' => 'Ertalabki avtomatik post'],
                ['id' => 'night',    'title' => 'Tungi xabar',       'icon' => 'night',    'toggle' => true, 'sub' => 'Kechki avtomatik post'],
                ['id' => 'gift',     'title' => 'Bayram tabriklari', 'icon' => 'gift',     'toggle' => true, 'sub' => 'Bayramlarda avtomatik'],
                ['id' => 'ramadan',  'title' => 'Ramazon taqvimi',   'icon' => 'ramadan',  'toggle' => true, 'sub' => 'Saharlik va iftorlik'],
                ['id' => 'rates',    'title' => 'Valyutalar',        'icon' => 'currency', 'toggle' => true, 'sub' => 'Banklar kursi'],
            ],
        ],
        [
            'id' => 'digital', 'title' => 'Raqamli xizmatlar', 'subtitle' => 'Nakrutka, eSIM, Premium va Stars',
            'icon' => 'digital', 'kind' => 'digital',
            'items' => [
                ['id' => 'growth',  'title' => 'Nakrutka',         'icon' => 'digital', 'toggle' => false, 'sub' => 'Obunachi va ko\'rish'],
                ['id' => 'esim',    'title' => 'eSIM',             'icon' => 'esim',    'toggle' => false, 'sub' => 'Xorijiy raqamlar'],
                ['id' => 'premium', 'title' => 'Telegram Premium', 'icon' => 'premium', 'toggle' => false, 'sub' => 'Rasmiy obuna'],
                ['id' => 'stars',   'title' => 'Stars',            'icon' => 'stars',   'toggle' => false, 'sub' => 'Telegram yulduzlari'],
            ],
        ],
        [
            'id' => 'help', 'title' => 'Xizmatlar va yordam', 'subtitle' => 'Hamkorlik, yordam va qo\'shimcha xizmatlar',
            'icon' => 'help', 'kind' => 'help',
            'items' => [
                ['id' => 'bot',      'title' => 'Bot yaratish',      'icon' => 'help',      'toggle' => false, 'sub' => 'O\'z botingizni oching'],
                ['id' => 'referral', 'title' => 'Taklif havolalari', 'icon' => 'group',     'toggle' => false, 'sub' => 'Do\'st taklif qilish'],
                ['id' => 'partner',  'title' => 'Hamkor dasturi',    'icon' => 'job',       'toggle' => false, 'sub' => 'Daromad ulushi'],
                ['id' => 'prava',    'title' => 'Prava testlari',    'icon' => 'education', 'toggle' => false, 'sub' => 'Yo\'l harakati qoidalari'],
                ['id' => 'donate',   'title' => 'Qo\'llab-quvvatlash','icon' => 'gift',     'toggle' => false, 'sub' => 'Loyihaga hissa'],
                ['id' => 'botdev',   'title' => 'Bot rivoji',        'icon' => 'brand',     'toggle' => false, 'sub' => 'Yangiliklar'],
                ['id' => 'support',  'title' => 'Savol va takliflar','icon' => 'question',  'toggle' => false, 'sub' => 'Adminga yozish'],
                ['id' => 'about',    'title' => 'Ilova haqida',      'icon' => 'info',      'toggle' => false, 'sub' => 'Versiya va shartlar'],
            ],
        ],
    ];

    /**
     * Bo'limlar, yashirilganlari olib tashlangan holda.
     * $state — xizmat id => ['on' => bool, 'value' => string]
     *
     * @param list<string>              $hidden
     * @param array<string,array<string,mixed>> $state
     * @return list<array<string,mixed>>
     */
    public static function build(array $hidden = [], array $state = []): array
    {
        $out = [];
        foreach (self::DATA as $cat) {
            $items = [];
            foreach ($cat['items'] as $item) {
                if (in_array($item['id'], $hidden, true)) {
                    continue;
                }
                $st = $state[$item['id']] ?? [];
                $item['on']    = (bool)($st['on'] ?? false);
                $item['value'] = (string)($st['value'] ?? '');
                $items[] = $item;
            }
            if ($items !== []) {
                $cat['items'] = $items;
                $out[] = $cat;
            }
        }
        return $out;
    }

    /** Tumbler bilan boshqariladigan xizmat id'lari. @return list<string> */
    public static function toggleable(): array
    {
        $ids = [];
        foreach (self::DATA as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['toggle']) {
                    $ids[] = $item['id'];
                }
            }
        }
        return $ids;
    }

    /** @return array<string,mixed>|null */
    public static function service(string $id): ?array
    {
        foreach (self::DATA as $cat) {
            foreach ($cat['items'] as $item) {
                if ($item['id'] === $id) {
                    return $item + ['category' => $cat['id'], 'kind' => $cat['kind']];
                }
            }
        }
        return null;
    }
}

/** Foydalanuvchi, statistika, hamyon va xizmatlar holati. */
final class App
{
    /** @var array<string,mixed>|null */
    private static $user = null;

    /** Namoyish rejimidagi xizmat holatlari. */
    private const DEMO_STATE = [
        'my-channel' => ['on' => false, 'value' => '60'],
        'my-group'   => ['on' => false, 'value' => '1 149'],
        'shield'     => ['on' => true,  'value' => 'Yoniq'],
        'schedule'   => ['on' => true,  'value' => '3 ta'],
        'prayer'     => ['on' => true,  'value' => '05:30'],
        'currency'   => ['on' => true,  'value' => '12 018'],
        'morning'    => ['on' => true,  'value' => '07:00'],
        'growth'     => ['on' => false, 'value' => '2 buyurtma'],
    ];

    /** @return array<string,mixed>|null */
    public static function user(string $initData): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (Config::isDemo()) {
            return self::$user = self::decorate(Telegram::demoUser(),
                ['balance' => 128000, 'hold' => 0, 'chats' => 0]);
        }
        $tgUser = Telegram::verify($initData);
        if ($tgUser === null) {
            return null;
        }
        return self::$user = self::decorate($tgUser, self::upsert($tgUser));
    }

    /**
     * @param array<string,mixed> $tg
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function decorate(array $tg, array $row): array
    {
        $admins = array_map('intval', (array)Config::get('admin_ids', []));
        return [
            'id'         => (int)$tg['id'],
            'first_name' => (string)($tg['first_name'] ?? ''),
            'username'   => (string)($tg['username'] ?? ''),
            'admin'      => in_array((int)$tg['id'], $admins, true),
            'balance'    => (int)($row['balance'] ?? 0),
            'hold'       => (int)($row['hold'] ?? 0),
            'chats'      => (int)($row['chats'] ?? 0),
        ];
    }

    /** @param array<string,mixed> $tg @return array<string,mixed> */
    private static function upsert(array $tg): array
    {
        Db::run(
            'INSERT INTO users (tg_id, first_name, username, created_at, seen_at)
             VALUES (:id, :fn, :un, NOW(), NOW())
             ON DUPLICATE KEY UPDATE first_name = :fn, username = :un, seen_at = NOW()',
            [':id' => (int)$tg['id'], ':fn' => (string)($tg['first_name'] ?? ''), ':un' => (string)($tg['username'] ?? '')]
        );
        return Db::one(
            'SELECT u.balance, u.hold, (SELECT COUNT(*) FROM chats c WHERE c.owner_id = u.tg_id) AS chats
             FROM users u WHERE u.tg_id = ?',
            [(int)$tg['id']]
        ) ?? [];
    }

    /** @return array<string,int> */
    public static function stats(): array
    {
        if (Config::isDemo()) {
            return ['channels' => 60, 'groups' => 1149, 'subscribers' => 11068, 'growth' => 248];
        }
        $row = Db::one(
            "SELECT SUM(type = 'channel') AS channels, SUM(type = 'group') AS groups_,
                    COALESCE(SUM(members), 0) AS subscribers
             FROM chats WHERE active = 1"
        ) ?? [];
        $growth = Db::one(
            'SELECT COALESCE(SUM(members), 0) - COALESCE(SUM(members_7d), 0) AS g
             FROM chats WHERE active = 1'
        );
        return [
            'channels'    => (int)($row['channels'] ?? 0),
            'groups'      => (int)($row['groups_'] ?? 0),
            'subscribers' => (int)($row['subscribers'] ?? 0),
            'growth'      => (int)($growth['g'] ?? 0),
        ];
    }

    /** @param array<string,mixed> $user @return array<string,mixed> */
    public static function wallet(array $user): array
    {
        $w = (array)Config::get('wallet', []);
        return [
            'balance' => (int)$user['balance'],
            'hold'    => (int)$user['hold'],
            'min'     => (int)($w['min'] ?? 5000),
            'max'     => (int)($w['max'] ?? 3000000),
            'wd_min'  => (int)($w['wd_min'] ?? 20000),
        ];
    }

    /**
     * Har bir xizmatning yoniq/o'chiq holati va o'ng tomonda ko'rsatiladigan qiymati.
     * @return array<string,array<string,mixed>>
     */
    public static function serviceState(int $tgId): array
    {
        if (Config::isDemo()) {
            return self::DEMO_STATE;
        }
        $state = [];
        foreach (Db::all('SELECT service_id, enabled, value FROM user_services WHERE tg_id = ?', [$tgId]) as $r) {
            $state[(string)$r['service_id']] = [
                'on'    => (bool)$r['enabled'],
                'value' => (string)$r['value'],
            ];
        }
        return $state;
    }

    /** Nechta xizmat yoniq. @param array<string,array<string,mixed>> $state */
    public static function runningCount(array $state): int
    {
        $n = 0;
        foreach ($state as $s) {
            if (!empty($s['on'])) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Bugungi keyingi avtomatik amal — "Keyingisi 12:28 · Peshin".
     * @return array{time:string,title:string}|null
     */
    public static function nextEvent(int $tgId): ?array
    {
        if (Config::isDemo()) {
            return ['time' => '12:28', 'title' => 'Peshin'];
        }
        $row = Db::one(
            "SELECT DATE_FORMAT(run_at, '%H:%i') AS t, title
             FROM schedule WHERE tg_id = ? AND run_at > NOW() ORDER BY run_at LIMIT 1",
            [$tgId]
        );
        return $row ? ['time' => (string)$row['t'], 'title' => (string)$row['title']] : null;
    }

    /** Xizmatni yoqish/o'chirish. */
    public static function toggleService(int $tgId, string $serviceId, bool $on): void
    {
        if (!in_array($serviceId, Catalog::toggleable(), true)) {
            throw new RuntimeException('Bu xizmatni yoqib bo\'lmaydi');
        }
        if (Config::isDemo()) {
            return;
        }
        Db::run(
            'INSERT INTO user_services (tg_id, service_id, enabled, updated_at)
             VALUES (:u, :s, :e, NOW())
             ON DUPLICATE KEY UPDATE enabled = :e, updated_at = NOW()',
            [':u' => $tgId, ':s' => $serviceId, ':e' => $on ? 1 : 0]
        );
    }

    /** @return list<string> */
    public static function hiddenServices(): array
    {
        if (Config::isDemo()) {
            return [];
        }
        $row  = Db::one("SELECT value FROM settings WHERE name = 'hidden_services'");
        $list = json_decode((string)($row['value'] ?? '[]'), true);
        return is_array($list) ? array_values(array_map('strval', $list)) : [];
    }

    /** Oylik kirim va chiqim. @return array{in:int,out:int} */
    public static function summary(int $tgId): array
    {
        if (Config::isDemo()) {
            return ['in' => 95000, 'out' => 42000];
        }
        $row = Db::one(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount END), 0) AS inc,
                    COALESCE(SUM(CASE WHEN amount < 0 THEN -amount END), 0) AS exp
             FROM ledger WHERE tg_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
            [$tgId]
        ) ?? [];
        return ['in' => (int)($row['inc'] ?? 0), 'out' => (int)($row['exp'] ?? 0)];
    }

    /** Bo'lim bo'yicha nechta yoniq. @return array<string,array{on:int,all:int}> */
    public static function perCategory(array $catalog): array
    {
        $out = [];
        foreach ($catalog as $c) {
            $on = 0;
            foreach ($c['items'] as $i) {
                if (!empty($i['on'])) { $on++; }
            }
            $out[$c['id']] = ['on' => $on, 'all' => count($c['items'])];
        }
        return $out;
    }

    /** Balans sahifasidagi so'nggi amallar. @return list<array<string,mixed>> */
    public static function recent(int $tgId): array
    {
        if (Config::isDemo()) {
            return [
                ['title' => "To'ldirish",        'sub' => 'Click · 09-noy',   'amount' =>  50000],
                ['title' => 'Nakrutka buyurtma', 'sub' => "1 000 ta · 07-noy", 'amount' => -9900],
                ['title' => 'Hamkorlik ulushi',  'sub' => 'Taklif · 05-noy',  'amount' =>  12000],
            ];
        }
        $rows = Db::all(
            "SELECT 'To''ldirish' AS title, DATE_FORMAT(created_at, '%d.%m') AS sub, amount
             FROM topups WHERE tg_id = ? AND status = 'paid'
             ORDER BY created_at DESC LIMIT 10",
            [$tgId]
        );
        return array_map(static fn(array $r): array => [
            'title'  => (string)$r['title'],
            'sub'    => (string)$r['sub'],
            'amount' => (int)$r['amount'],
        ], $rows);
    }
}

/** JSON API. Har bir so'rovda initData imzosi qaytadan tekshiriladi. */
final class Api
{
    public static function handle(string $action): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $body = json_decode((string)file_get_contents('php://input'), true);
        $body = is_array($body) ? $body : [];
        $data = is_array($body['data'] ?? null) ? $body['data'] : [];

        try {
            $user = App::user((string)($body['initData'] ?? ''));
            if ($user === null) {
                self::fail('Avtorizatsiya amalga oshmadi', 401);
                return;
            }
            switch ($action) {
                case 'me':             self::ok(self::me($user)); return;
                case 'wallet':         self::ok(['wallet' => App::wallet($user)]); return;
                case 'topup_create':   self::ok(self::topupCreate($user, $data)); return;
                case 'service_toggle': self::ok(self::serviceToggle($user, $data)); return;
                default:               self::fail('Noma\'lum amal: ' . $action, 404);
            }
        } catch (RuntimeException $e) {
            self::fail($e->getMessage(), 400);          // foydalanuvchiga ko'rsatiladi
        } catch (Throwable $e) {
            error_log('[tartibon] ' . $e->getMessage());
            self::fail('Server xatosi', 500);
        }
    }

    /** @param array<string,mixed> $user @return array<string,mixed> */
    private static function me(array $user): array
    {
        $state   = App::serviceState((int)$user['id']);
        $catalog = Catalog::build(App::hiddenServices(), $state);
        return [
            'user'    => $user,
            'stats'   => App::stats(),
            'wallet'  => App::wallet($user),
            'catalog' => $catalog,
            'counts'  => App::perCategory($catalog),
            'running' => App::runningCount($state),
            'next'    => App::nextEvent((int)$user['id']),
            'recent'  => App::recent((int)$user['id']),
            'summary' => App::summary((int)$user['id']),
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function serviceToggle(array $user, array $data): array
    {
        $id = (string)($data['id'] ?? '');
        $on = (bool)($data['on'] ?? false);
        App::toggleService((int)$user['id'], $id, $on);

        $state = App::serviceState((int)$user['id']);
        if (Config::isDemo()) {
            $state[$id] = ['on' => $on, 'value' => $on ? 'Yoniq' : "O'chiq"];
        }
        return [
            'id'      => $id,
            'on'      => $on,
            'running' => App::runningCount($state),
            'message' => $on ? 'Yoqildi' : "O'chirildi",
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function topupCreate(array $user, array $data): array
    {
        $w      = App::wallet($user);
        $amount = (int)($data['amount'] ?? 0);

        if ($amount < $w['min'] || $amount > $w['max']) {
            throw new RuntimeException(sprintf(
                "Summa %s – %s so'm oralig'ida bo'lishi kerak",
                number_format($w['min'], 0, '.', ' '),
                number_format($w['max'], 0, '.', ' ')
            ));
        }
        if (!Config::isDemo()) {
            Db::run(
                "INSERT INTO topups (tg_id, amount, status, created_at) VALUES (?, ?, 'pending', NOW())",
                [(int)$user['id'], $amount]
            );
        }
        return [
            'message' => 'Ariza qabul qilindi. To\'lov tasdiqlangach balans yangilanadi.',
            'wallet'  => $w,
        ];
    }

    /** @param array<string,mixed> $payload */
    private static function ok(array $payload): void
    {
        echo json_encode(['ok' => true] + $payload, JSON_UNESCAPED_UNICODE);
    }

    private static function fail(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    }
}

/** HTML qobig'i. Butun sahifa shu yerda chiqadi. */
final class View
{
    /** @param array<string,mixed> $boot */
    public static function shell(array $boot): void
    {
        $json = json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $v    = self::assetVersion();
        header('Content-Type: text/html; charset=utf-8');
        ?><!doctype html>
<html lang="uz" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#F2F3F7">
<title>TartibOn</title>
<style>/* ==========================================================================
   TartibOn — dizayn tizimi
   Ma'no ranglari:  KO'K = siz boshqarasiz · YASHIL = ilova o'zi bajaradi
                    BINAFSHA = sotib olinadi
   Bitta qatlam, !important yo'q, soya va blur minimal.
   ========================================================================== */

/* --- 1. Tokenlar --------------------------------------------------------- */
:root{
  --blue:#1C7DF3;  --blue-d:#1468D4;  --blue-s:#EAF2FE;
  --green:#22C55E; --green-d:#15803D; --green-s:#E6F7EC;
  --violet:#8B5CF6;--violet-s:#F0EAFE;
  --grey:#AAB0BB;
  --bg:#F2F3F7;
  --card:#FFFFFF;
  --card-a:#F1F2F7;
  --text:#14171C;
  --muted:#99A0AC;
  --line:#E9EAEF;
  --hair:#F0F1F4;
  --red:#E5484D;
  --sh:0 1px 2px rgba(28,34,66,.04), 0 6px 16px rgba(28,34,66,.06);
  --sh-nav:0 6px 22px rgba(56,45,98,.12);
  --font:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
  --round:Nunito,-apple-system,"SF Pro Rounded",ui-rounded,Roboto,Arial,sans-serif;
  --pad:16px;
  --nav-h:96px;
}
html[data-theme="dark"]{
  --blue-s:#17283F; --green-s:#14301F; --violet-s:#241B3D;
  --bg:#16171B; --card:#21232A; --card-a:#2A2D36;
  --text:#F4F5F7; --muted:#8C8FA0; --line:#2C2F38; --hair:#262930;
  --sh:0 1px 2px rgba(0,0,0,.3), 0 6px 16px rgba(0,0,0,.38);
  --sh-nav:0 6px 22px rgba(0,0,0,.45);
}

/* --- 2. Asos ------------------------------------------------------------- */
*,*::before,*::after{box-sizing:border-box}
html,body{margin:0;padding:0}
html{background:var(--bg);-webkit-text-size-adjust:100%;-webkit-tap-highlight-color:transparent}
body{
  min-height:100vh; min-height:100dvh;
  background:var(--bg);
  color:var(--text);
  font:400 15px/1.45 var(--font);
  overflow-x:hidden;
}
button{font:inherit;color:inherit;border:0;background:none;padding:0;cursor:pointer}
svg{display:block}
b,strong{font-weight:700}
[hidden]{display:none}
.sr{position:absolute;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
b,em,span,u{font-variant-numeric:tabular-nums}

/* --- 3. Sarlavha --------------------------------------------------------- */
.hdr{position:relative;display:flex;align-items:center;gap:12px;max-width:600px;margin:0 auto;
     padding:calc(10px + env(safe-area-inset-top)) 18px 6px}
.hdr__brand{position:absolute;left:50%;transform:translateX(-50%);text-align:center;pointer-events:none}
.hdr__brand b{display:block;font-size:25px;line-height:1.2;font-weight:750;letter-spacing:-.8px}
.hdr__brand span{display:block;color:var(--muted);font-size:15px;line-height:1.4}
.hdr__btn{display:grid;place-items:center;width:40px;height:40px;border-radius:50%;
          background:var(--card-a);color:var(--text)}
.hdr__btn svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:1.9;
              stroke-linecap:round;stroke-linejoin:round}
.hdr__btn--end{margin-left:auto;background:none;color:var(--blue)}
.is-home .hdr__brand{visibility:hidden}

/* --- 4. Sahifa ----------------------------------------------------------- */
.wrap{max-width:600px;margin:0 auto;padding:0 var(--pad) calc(var(--nav-h) + env(safe-area-inset-bottom))}
.page{display:none}
.page.is-on{display:block;animation:fade .18s ease-out}
@keyframes fade{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
.h1{margin:4px 2px 16px;font-size:27px;line-height:1.15;font-weight:800;letter-spacing:-.8px}
.gh{margin:20px 0 8px 14px;font:700 11px/1 var(--font);letter-spacing:.1em;color:var(--muted)}
.gh:first-child{margin-top:0}

/* --- 5. Logotip ---------------------------------------------------------- */
.logo{display:flex;align-items:center;justify-content:center;padding:2px 8px 14px}
.logo__w{display:flex;align-items:center;white-space:nowrap;color:#0E1A33;
         font:900 clamp(34px,10.5vw,46px)/1 var(--round);letter-spacing:-.021em}
html[data-theme="dark"] .logo__w{color:#fff}
.logo__w>span{display:inline-block}
.logo__o{position:relative;display:inline-block;width:.92em;height:.92em;margin:0 .02em 0 .04em;
         transform:translateY(.02em);flex:none}
.logo__o svg{width:100%;height:100%;fill:none;stroke-width:3.8;stroke-linecap:round;overflow:visible}
.logo__o .r{stroke:var(--blue)}
.logo__o .b{stroke:#16A34A}

/* --- 6. Statistika + tezkor amallar -------------------------------------- */
.stat{display:flex;align-items:stretch;padding:13px 8px;border-radius:22px;background:var(--card);box-shadow:var(--sh)}
.stat>div{flex:1;min-width:0;text-align:center}
.stat b{display:block;font-size:19px;font-weight:800;letter-spacing:-.4px;line-height:1.25}
.stat span{font-size:11px;color:var(--muted)}
.stat em{display:inline-block;margin-top:3px;padding:2px 7px;border-radius:999px;background:var(--green-s);
         color:var(--green-d);font:700 10px/1.4 var(--font);font-style:normal}
.stat i{width:1px;align-self:center;height:30px;background:var(--line);flex:none}
.quick{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:10px}
.quick button{display:flex;align-items:center;justify-content:center;padding:11px 4px;border-radius:14px;
              background:var(--card);color:var(--blue);box-shadow:var(--sh);
              font:650 13px/1 var(--font);white-space:nowrap}
.quick svg{width:16px;height:16px;margin-right:5px;fill:none;stroke:currentColor;stroke-width:2.6;stroke-linecap:round}

/* --- 7. "Hozir ishlab turibdi" ------------------------------------------- */
.now{display:flex;align-items:center;gap:11px;margin-top:10px;padding:12px 14px;border-radius:18px;
     background:var(--green-s)}
.now__dot{position:relative;width:9px;height:9px;border-radius:50%;background:var(--green);flex:none;
          box-shadow:0 0 0 4px rgba(34,197,94,.17)}
.now__dot::after{content:"";position:absolute;inset:-4px;border-radius:50%;border:1.5px solid var(--green);
          opacity:0;animation:pulse 2.4s ease-out infinite}
@keyframes pulse{0%{transform:scale(.7);opacity:.7}70%,100%{transform:scale(1.9);opacity:0}}
.now__t{flex:1;min-width:0}
.now__t b{display:block;font:700 13.5px/1.25 var(--font);letter-spacing:-.2px;color:#14532D}
.now__t span{display:block;font:500 11.5px/1.35 var(--font);color:#3E7A54;
             white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.now__next{display:flex;align-items:center;gap:6px;flex:none;padding:6px 11px;border-radius:999px;
           background:rgba(255,255,255,.72);color:#14532D;font:700 11.5px/1 var(--font)}
.now__next svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.1;
               stroke-linecap:round;stroke-linejoin:round}
html[data-theme="dark"] .now__t b{color:#8FE3AE}
html[data-theme="dark"] .now__t span{color:#6DBF8C}
html[data-theme="dark"] .now__next{background:rgba(255,255,255,.1);color:#8FE3AE}
html[data-theme="dark"] .now b{color:#8FE3AE}
html[data-theme="dark"] .now em{color:#6DBF8C}

/* --- 8. Bo'lim kartasi (bosh sahifa) ------------------------------------- */
.sec{margin-top:12px;padding:14px 13px;border-radius:22px;background:var(--card);box-shadow:var(--sh);min-width:0}
.sec__h{display:flex;align-items:baseline;gap:8px;margin:0 3px 13px;min-width:0}
.sec__h b{flex:none;white-space:nowrap;font-size:17px;font-weight:800;letter-spacing:-.35px}
.sec__h span{flex:1;min-width:0;text-align:right;font-size:11px;color:var(--muted);
             white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sec__n{flex:none;padding:3px 9px;border-radius:999px;background:var(--card-a);color:var(--muted);
        font:700 10.5px/1.5 var(--font)}
.sec__n--on{background:var(--green-s);color:var(--green-d)}
.sec__g{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px 4px;min-width:0}
.tile{display:flex;flex-direction:column;align-items:center;gap:7px;min-width:0;
      font:650 12px/1.25 var(--font);text-align:center;color:var(--text)}
.tile b{font-weight:650;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.chip{position:relative;display:grid;place-items:center;width:46px;height:46px;flex:none;border-radius:16px;
      background:var(--blue-s);color:var(--blue);transition:background .12s}
.chip svg{width:25px;height:25px;fill:none;stroke:currentColor;stroke-width:1.85;
          stroke-linecap:round;stroke-linejoin:round;overflow:visible}
.chip--on{background:var(--green-s);color:var(--green-d)}
.chip__dot{position:absolute;top:-2px;right:-2px;width:11px;height:11px;border-radius:50%;
           background:var(--green);border:2.5px solid var(--card)}
.tile:active .chip{transform:scale(.93)}
.chip{transition:background .14s, transform .12s}

/* --- 9. Guruhlangan ro'yxat (Xizmatlar, Balans) -------------------------- */
.card{border-radius:16px;overflow:hidden;background:var(--card)}
.row{display:flex;align-items:center;gap:13px;width:100%;padding:9px 14px;position:relative;text-align:left}
.row+.row::before{content:"";position:absolute;left:58px;right:0;top:0;height:1px;background:var(--hair)}
.row:active{background:var(--card-a)}
.ic{display:grid;place-items:center;width:31px;height:31px;flex:none;border-radius:9px;
    background:var(--grey);color:#fff;font:800 17px/1 var(--font)}
.ic svg{width:19px;height:19px;fill:none;stroke:currentColor;stroke-width:1.9;
        stroke-linecap:round;stroke-linejoin:round;overflow:visible}
.ic--blue{background:var(--blue)}
.ic--green{background:var(--green)}
.ic--violet{background:var(--violet)}
.ic--grey{background:var(--grey)}
.row b{flex:1;min-width:0;font:500 16px/1.3 var(--font);letter-spacing:-.2px}
.row em{font:400 15px/1 var(--font);font-style:normal;color:var(--muted);flex:none}
.row em.pos{color:var(--green-d);font-weight:600}
.row .cv{width:15px;height:15px;flex:none;margin-left:4px;color:#C7CAD2;
         fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

/* tumbler */
.sw{position:relative;width:48px;height:29px;flex:none;border-radius:999px;background:var(--line);
    transition:background .16s}
.sw::after{content:"";position:absolute;top:2.5px;left:2.5px;width:24px;height:24px;border-radius:50%;
           background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .16s}
.sw[aria-checked="true"]{background:var(--green)}
.sw[aria-checked="true"]::after{left:21.5px}
.sw[disabled]{opacity:.5}

/* --- 10. Balans ---------------------------------------------------------- */
.bal{padding:20px;border-radius:22px;background:var(--card);margin-bottom:4px}
.bal span{font:600 12.5px/1 var(--font);color:var(--muted)}
.bal b{display:flex;align-items:baseline;gap:7px;font:800 36px/1.25 var(--font);letter-spacing:-1.4px}
.bal b em{font:700 15px/1 var(--font);font-style:normal;color:var(--muted)}
.bal i{display:inline-block;margin-top:6px;padding:4px 12px;border-radius:999px;background:var(--green-s);
       color:var(--green-d);font-style:normal;font:650 12.5px/1 var(--font)}

/* --- 11. Profil ---------------------------------------------------------- */
.prof{display:flex;align-items:center;gap:16px;padding:16px;margin-bottom:4px;border-radius:22px;background:var(--card)}
.prof__av{display:grid;place-items:center;width:62px;height:62px;flex:none;border-radius:50%;
          background:linear-gradient(145deg,#4FA3FF,var(--blue));color:#fff;font:800 26px/1 var(--round)}
.prof__t{min-width:0}
.prof__t b{display:block;font-size:21px;font-weight:800;letter-spacing:-.5px}
.prof__t small{display:block;color:var(--muted);font-size:14px}
.tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:7px}
.tag{padding:4px 11px;border-radius:999px;background:var(--line);color:var(--muted);font-size:12px;font-weight:700}
.tag--blue{background:var(--blue);color:#fff}

/* --- 12. Pastki navigatsiya ---------------------------------------------- */
.nav{position:fixed;z-index:45;left:16px;right:16px;bottom:calc(12px + env(safe-area-inset-bottom));
     max-width:568px;margin:0 auto;padding:5px;border-radius:40px;
     display:grid;grid-template-columns:repeat(4,minmax(0,1fr));
     background:var(--card);box-shadow:var(--sh-nav)}
.nav button{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
            min-height:62px;padding:7px 2px;border-radius:32px;font:500 12px/1.2 var(--font)}
.nav svg{width:25px;height:25px;fill:none;stroke:currentColor;stroke-width:1.8;
         stroke-linecap:round;stroke-linejoin:round}
.nav button[aria-current="page"]{color:var(--blue);background:var(--card-a);font-weight:650}
.nav button[aria-current="page"][data-tab="home"] svg{fill:var(--blue)}

/* --- 13. Pastki oyna ----------------------------------------------------- */
.ov{position:fixed;z-index:80;inset:0;display:flex;align-items:flex-end;justify-content:center;
    background:rgba(22,22,33,.42);animation:ovIn .18s ease-out}
.sheet{width:100%;max-width:600px;max-height:calc(100dvh - 28px - env(safe-area-inset-top));
       padding:0 16px calc(20px + env(safe-area-inset-bottom));border-radius:30px 30px 0 0;
       background:var(--card);overflow-y:auto;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;
       animation:shIn .23s cubic-bezier(.2,.7,.3,1)}
.sheet__grip{display:flex;align-items:center;justify-content:center;height:22px}
.sheet__grip::before{content:"";width:42px;height:5px;border-radius:5px;background:#DCDBE5}
.sheet__h{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 16px}
.sheet__h h2{margin:0;font-size:23px;line-height:1.2;font-weight:750;letter-spacing:-.6px}
.sheet__x{display:grid;place-items:center;width:36px;height:36px;flex:none;border-radius:50%;
          background:var(--card-a);color:var(--text)}
.sheet__x svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round}
@keyframes ovIn{from{background:rgba(22,22,33,0)}to{background:rgba(22,22,33,.42)}}
@keyframes shIn{from{transform:translateY(32px);opacity:0}to{transform:none;opacity:1}}
html.is-locked{overflow:hidden}

/* --- 14. Forma ----------------------------------------------------------- */
.fld{display:block;margin-bottom:12px}
.fld span{display:block;margin:0 0 6px 4px;font-size:13px;font-weight:650;color:var(--muted)}
.fld input{width:100%;padding:13px 15px;border-radius:16px;border:1.5px solid var(--line);
           background:var(--bg);color:var(--text);font:650 17px/1.2 var(--font)}
.fld input:focus{outline:none;border-color:var(--blue);background:var(--card)}
.chips{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-bottom:14px}
.chips button{padding:10px 4px;border-radius:14px;background:var(--blue-s);color:var(--blue);
              font:700 14px/1 var(--font)}
.btn{display:block;width:100%;padding:16px;border-radius:18px;background:var(--blue);color:#fff;
     font:700 16px/1 var(--font)}
.btn:active{background:var(--blue-d)}
.btn[disabled]{opacity:.5}
.note{margin:12px 2px 0;font-size:13px;line-height:1.5;color:var(--muted)}

/* --- 15. Toast ----------------------------------------------------------- */
.toast{position:fixed;z-index:120;left:50%;bottom:calc(var(--nav-h) + 16px);transform:translateX(-50%);
       max-width:calc(100% - 40px);padding:12px 20px;border-radius:999px;background:#14171C;color:#fff;
       font:650 14px/1.3 var(--font);text-align:center;box-shadow:0 8px 26px rgba(0,0,0,.25);
       animation:fade .18s ease-out}

/* --- 16. Moslashuv ------------------------------------------------------- */
@media(max-width:360px){
  :root{--pad:12px}
  .sec__g{gap:12px 3px}
  .chip{width:42px;height:42px;border-radius:14px}
  .chip svg{width:23px;height:23px}
  .tile{font-size:11px}
  .bal b{font-size:30px}
  .row b{font-size:15px}
}
@media(min-width:430px){
  .chip{width:50px;height:50px}
  .chip svg{width:27px;height:27px}
  .tile{font-size:13px}
}
@media(prefers-reduced-motion:reduce){
  .page.is-on,.ov,.sheet,.toast{animation:none}
  .sw,.sw::after{transition:none}
}

/* ==========================================================================
   Kuchaytirish: filtr, qator izohi, kirim/chiqim, yuklanish holati
   ========================================================================== */

/* --- filtr tasmasi (Xizmatlar sahifasi) --------------------------------- */
.seg{display:flex;gap:6px;margin:0 0 14px}
.seg button{display:flex;align-items:center;gap:6px;padding:9px 13px;border-radius:12px;
            background:var(--card);color:var(--muted);font:650 13px/1 var(--font);box-shadow:var(--sh)}
.seg em{font:700 11px/1 var(--font);font-style:normal;opacity:.75}
.seg button[aria-pressed="true"]{background:var(--text);color:var(--card)}
.seg button[aria-pressed="true"] em{opacity:.6}

/* --- ro'yxat qatoriga ikkinchi qator ------------------------------------ */
.row__t{flex:1;min-width:0}
.row__t b{display:block;font:500 16px/1.3 var(--font);letter-spacing:-.2px}
.row__t small{display:block;font:400 12.5px/1.35 var(--font);color:var(--muted);margin-top:1px;
              white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.row--tall{padding-top:11px;padding-bottom:11px}
.card__h{display:flex;align-items:baseline;gap:8px;margin:20px 14px 8px}
.card__h b{font:700 11px/1 var(--font);letter-spacing:.1em;color:var(--muted)}
.card__h em{margin-left:auto;font:700 11px/1 var(--font);font-style:normal;color:var(--muted)}
.card__h em.on{color:var(--green-d)}

/* --- kirim / chiqim ------------------------------------------------------ */
.mini{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-top:10px}
.mini div{padding:13px 15px;border-radius:18px;background:var(--card)}
.mini b{display:block;font:800 19px/1.2 var(--font);letter-spacing:-.5px}
.mini div:first-child b{color:var(--green-d)}
.mini span{font:500 11px/1.5 var(--font);color:var(--muted)}

/* --- yuklanish (skeleton) ------------------------------------------------ */
.skel{display:inline-block;min-width:44px;height:.72em;border-radius:5px;vertical-align:-.04em;
      background:linear-gradient(90deg,var(--line) 25%,var(--card-a) 37%,var(--line) 63%);
      background-size:400% 100%;animation:shim 1.3s ease-in-out infinite}
@keyframes shim{0%{background-position:100% 0}100%{background-position:0 0}}
.skel--wide{min-width:120px}

/* --- bo'sh holat --------------------------------------------------------- */
.empty{padding:34px 20px;text-align:center;color:var(--muted);font:500 14.5px/1.5 var(--font)}

/* --- bosilish sezilsin --------------------------------------------------- */
.quick button:active,.seg button:active,.btn:active{transform:scale(.98)}
.quick button,.seg button,.btn,.row{transition:transform .12s, background .14s}
@media(prefers-reduced-motion:reduce){
  .now__dot::after,.skel{animation:none}
  .tile:active .chip,.quick button:active,.seg button:active,.btn:active{transform:none}
}
</style>
<!-- Shrift sahifani to'sib qo'ymasligi uchun keyin yuklanadi -->
<link rel="stylesheet" media="print" onload="this.media='all';this.onload=null"
      href="https://fonts.googleapis.com/css2?family=Nunito:wght@800;900&amp;display=swap">
<!-- SDK yuklanmasa ham ilova ishlaydi (app.js ichida zaxira obyekt bor) -->
<script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>

<header class="hdr">
  <button type="button" class="hdr__btn" data-close-app aria-label="Ilovani yopish">
    <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
  </button>
  <div class="hdr__brand"><b>TartibOn</b><span>mini-ilova</span></div>
  <span class="hdr__btn hdr__btn--end" aria-hidden="true">
    <svg viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 4.5-3.2 8.3-8 9-4.8-.7-8-4.5-8-9V6z"/><path d="m9 12 2 2 4-4"/></svg>
  </span>
</header>

<main class="wrap" id="view"><!-- sahifalar app.js tomonidan chiziladi --></main>

<nav class="nav" id="nav" aria-label="Asosiy navigatsiya">
  <button type="button" data-tab="home">
    <svg viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg><span>Asosiy</span></button>
  <button type="button" data-tab="tools">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.7"/><rect x="14" y="3" width="7" height="7" rx="1.7"/><rect x="3" y="14" width="7" height="7" rx="1.7"/><rect x="14" y="14" width="7" height="7" rx="1.7"/></svg><span>Xizmatlar</span></button>
  <button type="button" data-tab="bal">
    <svg viewBox="0 0 24 24"><path d="M4 7V5a2 2 0 0 1 2-2h11v4"/><rect x="3" y="7" width="18" height="14" rx="3"/><path d="M21 12h-6a2 2 0 0 0 0 4h6M16 14h.01"/></svg><span>Balans</span></button>
  <button type="button" data-tab="me">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 6 0 0 1 16 0v2z"/></svg><span>Profil</span></button>
</nav>

<script>window.TO_BOOT = <?= $json ?>;</script>
<script>/* TartibOn ikonkalari — chiziqli (outline) uslub, 24x24 viewBox. */
window.TO_ICONS = {
"manage":"<path d=\"M12 3 20 6v6c0 4.5-3.2 8.3-8 9-4.8-.7-8-4.5-8-9V6z\"/><circle cx=\"12\" cy=\"10.5\" r=\"2.1\"/><path d=\"M8.4 16.4c.5-1.6 1.9-2.6 3.6-2.6s3.1 1 3.6 2.6\"/>",
"daily":"<path d=\"M18 8.5a6 6 0 1 0-12 0c0 5-2.2 6.5-2.2 6.5h16.4S18 13.5 18 8.5z\"/><path d=\"M13.7 19a2 2 0 0 1-3.4 0\"/>",
"digital":"<path d=\"M4 18c3-9 9-13 16-13 0 7-4 13-13 16\"/><path d=\"m4 18 4 2M7 15c1.5.4 2.6 1.5 3 3\"/>",
"posts":"<rect x=\"3.5\" y=\"4.5\" width=\"17\" height=\"15\" rx=\"3\"/><path d=\"M7.5 9.5h5M7.5 13.5h9\"/>",
"help":"<rect x=\"4\" y=\"7\" width=\"16\" height=\"12\" rx=\"4\"/><path d=\"M12 3v4M8.5 12.5h.01M15.5 12.5h.01M9.5 16h5\"/>",
"channel":"<path d=\"M21 3 10 14M21 3l-7 18-4-7-7-4z\"/>",
"group":"<circle cx=\"9\" cy=\"8\" r=\"3.2\"/><circle cx=\"17\" cy=\"9.5\" r=\"2.6\"/><path d=\"M3.5 19c0-3 2.5-5.4 5.5-5.4s5.5 2.4 5.5 5.4M15.5 19c0-1.9-.6-3.5-1.7-4.6.7-.3 1.4-.5 2.2-.5 2.7 0 4.5 1.9 4.5 4.4\"/>",
"shield":"<path d=\"M12 3 20 6v6c0 4.5-3.2 8.3-8 9-4.8-.7-8-4.5-8-9V6z\"/><path d=\"m9 12 2 2 4-4\"/>",
"calendar":"<rect x=\"3.5\" y=\"5\" width=\"17\" height=\"15.5\" rx=\"3\"/><path d=\"M8 3v4M16 3v4M3.5 10h17\"/><path d=\"m10 15 4-1.5L12.5 17z\"/>",
"chart":"<path d=\"M4 20V10M10 20V4M16 20v-7M21 20H3\"/>",
"test":"<rect x=\"5\" y=\"3.5\" width=\"14\" height=\"17\" rx=\"3\"/><path d=\"M9 8.5h6M9 12.5h6M9 16.5h3\"/>",
"paid":"<rect x=\"4.5\" y=\"10\" width=\"15\" height=\"10\" rx=\"3\"/><path d=\"M8 10V7a4 4 0 0 1 8 0v3\"/><path d=\"M12 14v2.5\"/>",
"trophy":"<path d=\"M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3\"/>",
"prayer":"<path d=\"M4 20.5v-7a8 8 0 0 1 16 0v7z\"/><path d=\"M12 2.5v2.4M3 20.5h18\"/><path d=\"M9.5 20.5v-3.5a2.5 2.5 0 0 1 5 0v3.5\"/>",
"currency":"<circle cx=\"12\" cy=\"12\" r=\"8.5\"/><path d=\"M12 7v10M14.5 9.4c-.6-.8-1.6-1.2-2.7-1.2-1.5 0-2.6.8-2.6 1.9s1 1.7 2.6 2.1c1.8.4 2.8 1 2.8 2.2 0 1.3-1.2 2.1-2.8 2.1-1.2 0-2.2-.5-2.8-1.4\"/>",
"weather":"<path d=\"M7.5 18.5h9a4 4 0 0 0 .5-8 6 6 0 0 0-11.4 1.6A3.5 3.5 0 0 0 7.5 18.5z\"/>",
"morning":"<circle cx=\"12\" cy=\"12.5\" r=\"3.6\"/><path d=\"M12 4v2.2M12 18.8V21M4.6 12.5H2.4M21.6 12.5h-2.2M6.4 6.9 4.8 5.3M19.2 19.7l-1.6-1.6M17.6 6.9l1.6-1.6M4.8 19.7l1.6-1.6\"/>",
"night":"<path d=\"M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z\"/>",
"gift":"<rect x=\"3.5\" y=\"9\" width=\"17\" height=\"11.5\" rx=\"2.5\"/><path d=\"M3.5 13.5h17M12 9v11.5M12 9S9.2 3.6 7 5.3 9 9 12 9zM12 9s2.8-5.4 5-3.7S15 9 12 9z\"/>",
"ramadan":"<rect x=\"3.5\" y=\"5\" width=\"17\" height=\"15.5\" rx=\"3\"/><path d=\"M8 3v4M16 3v4\"/><path d=\"M15.4 15a3.2 3.2 0 1 1-2.9-4.6 3.8 3.8 0 0 0 2.9 4.6z\"/>",
"esim":"<path d=\"M5 7.5 9.5 3H17a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z\"/><rect x=\"8.5\" y=\"9.5\" width=\"7\" height=\"6\" rx=\"1.6\"/>",
"premium":"<path d=\"m12 3 2.3 6.3H21l-5.4 4 2 6.7-5.6-4.1-5.6 4.1 2-6.7-5.4-4h6.7z\"/>",
"stars":"<path d=\"m12 3.6 2.5 5.3 5.8.8-4.2 4 1 5.8-5.1-2.8-5.1 2.8 1-5.8-4.2-4 5.8-.8z\"/>",
"education":"<path d=\"M12 4 2.5 8.8 12 13.6l9.5-4.8z\"/><path d=\"M6.5 11v4.6c0 1.6 2.5 2.9 5.5 2.9s5.5-1.3 5.5-2.9V11M21.5 8.8v5\"/>",
"job":"<rect x=\"3\" y=\"7.5\" width=\"18\" height=\"12.5\" rx=\"3\"/><path d=\"M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5M3 12.5h18\"/>",
"brand":"<path d=\"M3.5 7.5a2 2 0 0 1 2-2h3.6l2 2.4h7.4a2 2 0 0 1 2 2v8.6a2 2 0 0 1-2 2h-13a2 2 0 0 1-2-2z\"/><path d=\"M9.5 14.5h5\"/>",
"question":"<circle cx=\"12\" cy=\"12\" r=\"8.6\"/><path d=\"M9.6 9.6a2.5 2.5 0 0 1 4.8.9c0 1.7-2.4 2-2.4 3.5M12 17h.01\"/>",
"info":"<circle cx=\"12\" cy=\"12\" r=\"8.6\"/><path d=\"M12 11v5.5M12 7.8h.01\"/>",
"taxi":"<rect x=\"3\" y=\"10\" width=\"18\" height=\"7.5\" rx=\"2.5\"/><path d=\"M5.5 10 7 6h10l1.5 4M7 17.5V19M17 17.5V19M9.5 4h5\"/>",
"car":"<rect x=\"3\" y=\"10\" width=\"18\" height=\"7.5\" rx=\"2.5\"/><path d=\"M5.5 10 7 6h10l1.5 4M7.5 14h.01M16.5 14h.01\"/>",
"shop":"<path d=\"M4.5 9h15l-1 11h-13z\"/><path d=\"M8.5 9V6.5a3.5 3.5 0 0 1 7 0V9\"/>",
"travel":"<rect x=\"4\" y=\"7.5\" width=\"16\" height=\"12.5\" rx=\"2.5\"/><path d=\"M9 7.5V5.5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M9 11v5M15 11v5\"/>",
"house":"<path d=\"m3.5 10.5 8.5-6.5 8.5 6.5V20a1 1 0 0 1-1 1H4.5a1 1 0 0 1-1-1z\"/><path d=\"M9.5 21v-6h5v6\"/>",
"restaurant":"<path d=\"M6 3v8a2 2 0 0 0 4 0V3M8 11v10M17 3c-1.7 0-3 2.2-3 5s1.3 4 3 4v9\"/>",
"clinic":"<rect x=\"3.5\" y=\"7\" width=\"17\" height=\"13\" rx=\"3\"/><path d=\"M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M12 11v5M9.5 13.5h5\"/>",
"ballot":"<path d=\"M4 10.5 12 7l8 3.5v8a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5z\"/><path d=\"M8.5 10.5V4h7v6.5M11 7.5h2\"/>"
};
</script>
<script>/* ==========================================================================
   TartibOn — mini-ilova qobig'i
   Tashqi kutubxonasiz. Ma'lumot PHP'dan window.TO_BOOT va ?api=me orqali.
   ========================================================================== */
(function (window, document) {
  'use strict';

  var BOOT  = window.TO_BOOT || {};
  var ICONS = window.TO_ICONS || {};

  /* ---- 1. Telegram SDK (yo'q bo'lsa ham ishlaydi) ---------------------- */
  var TG_OK = !!(window.Telegram && window.Telegram.WebApp &&
                 window.Telegram.WebApp.initData !== undefined);
  var noop = function () {};
  var tg = TG_OK ? window.Telegram.WebApp : {
    initData: '', initDataUnsafe: {}, colorScheme: 'light', themeParams: {},
    ready: noop, expand: noop, close: noop, onEvent: noop, offEvent: noop,
    disableVerticalSwipes: noop, setHeaderColor: noop, setBackgroundColor: noop,
    openLink: function (u) { window.open(u, '_blank'); },
    openTelegramLink: function (u) { window.open(u, '_blank'); },
    BackButton: { show: noop, hide: noop, onClick: noop },
    HapticFeedback: { impactOccurred: noop, notificationOccurred: noop },
    showAlert: function (m) { window.alert(m); }
  };
  ['ready', 'expand', 'disableVerticalSwipes'].forEach(function (m) {
    try { tg[m](); } catch (e) {}
  });
  function haptic(k) { try { tg.HapticFeedback.impactOccurred(k || 'light'); } catch (e) {} }

  function applyTheme() {
    var dark = tg.colorScheme === 'dark';
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    try {
      tg.setHeaderColor(dark ? '#16171B' : '#F2F3F7');
      tg.setBackgroundColor(dark ? '#16171B' : '#F2F3F7');
    } catch (e) {}
  }
  applyTheme();
  try { tg.onEvent('themeChanged', applyTheme); } catch (e) {}

  /* ---- 2. Yordamchilar -------------------------------------------------- */
  function $(s, r) { return (r || document).querySelector(s); }
  function el(tag, cls, html) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function money(n) {
    n = Math.round(Number(n) || 0);
    var sign = n < 0 ? '−' : '';
    return sign + String(Math.abs(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }
  function svg(key) {
    return '<svg viewBox="0 0 24 24">' + (ICONS[key] || ICONS.info || '') + '</svg>';
  }
  var CHEVRON = '<svg class="cv" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>';
  /* ma'lumot kelmaguncha kulrang chiziqcha ko'rsatiladi */
  function val(text, wide) {
    return state.ready ? text : '<span class="skel' + (wide ? ' skel--wide' : '') + '"></span>';
  }

  /* ---- 3. API ----------------------------------------------------------- */
  function api(action, payload) {
    return window.fetch((BOOT.base || '') + '?api=' + encodeURIComponent(action), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ initData: tg.initData || '', data: payload || {} })
    }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j || j.ok !== true) throw new Error((j && j.error) || 'Xatolik yuz berdi');
      return j;
    });
  }

  /* ---- 4. Toast --------------------------------------------------------- */
  var toastTimer = null;
  function toast(text) {
    var old = $('.toast');
    if (old) old.remove();
    var n = el('div', 'toast', esc(text));
    n.setAttribute('role', 'status');
    document.body.appendChild(n);
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { n.remove(); }, 2600);
  }

  /* ---- 5. Pastki oyna --------------------------------------------------- */
  var sheetEl = null, lastFocus = null;
  function sheetOpen(title, bodyHTML, opts) {
    sheetClose();
    opts = opts || {};
    lastFocus = document.activeElement;
    var ov = el('div', 'ov');
    ov.innerHTML =
      '<section class="sheet" role="dialog" aria-modal="true" aria-label="' + esc(title) + '" tabindex="-1">' +
        '<div class="sheet__grip"></div>' +
        '<div class="sheet__h"><h2>' + esc(title) + '</h2>' +
          '<button type="button" class="sheet__x" data-close aria-label="Yopish">' +
          '<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>' +
        '<div class="sheet__b">' + bodyHTML + '</div></section>';
    ov.addEventListener('click', function (e) {
      if (e.target === ov || e.target.closest('[data-close]')) sheetClose();
    });
    document.body.appendChild(ov);
    document.documentElement.classList.add('is-locked');
    sheetEl = ov;
    var panel = $('.sheet', ov);
    panel.focus();
    if (opts.onOpen) opts.onOpen(panel);
    return panel;
  }
  function sheetClose() {
    if (!sheetEl) return;
    sheetEl.remove();
    sheetEl = null;
    document.documentElement.classList.remove('is-locked');
    if (lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sheetEl) sheetClose();
  });

  /* ---- 6. Holat --------------------------------------------------------- */
  var state = {
    page: 'home',
    ready: false,
    user: { first_name: '—' },
    stats: BOOT.stats || { channels: 0, groups: 0, subscribers: 0 },
    wallet: { balance: 0, hold: 0, min: 5000, max: 3000000 },
    catalog: BOOT.catalog || [],
    running: 0,
    next: null,
    recent: [],
    counts: {},
    summary: { in: 0, out: 0 },
    filter: 'all',                      /* Xizmatlar sahifasidagi filtr */
    support: BOOT.support || ''
  };
  function category(id) {
    for (var i = 0; i < state.catalog.length; i++) {
      if (state.catalog[i].id === id) return state.catalog[i];
    }
    return null;
  }
  function service(id) {
    for (var i = 0; i < state.catalog.length; i++) {
      var it = state.catalog[i].items;
      for (var j = 0; j < it.length; j++) if (it[j].id === id) return it[j];
    }
    return null;
  }
  /* ro'yxatdagi ikonka rangi: yoqiladigan xizmat yashil/kulrang,
     qolgani bo'lim ma'nosiga qarab ko'k yoki binafsha */
  function iconClass(cat, item) {
    if (item.toggle) return item.on ? 'ic--green' : 'ic--grey';
    if (cat.kind === 'digital') return 'ic--violet';
    return 'ic--blue';
  }

  /* ---- 7. Bo'laklar ----------------------------------------------------- */
  function tilesHTML(cat) {
    var c  = state.counts[cat.id] || { on: 0, all: cat.items.length };
    var tag = c.on
      ? '<span class="sec__n sec__n--on">' + c.on + '/' + c.all + ' yoniq</span>'
      : '<span class="sec__n">' + c.all + ' ta</span>';
    /* izoh o'rniga sanoq — kesilib qolgan matndan ko'ra foydaliroq */
    return '<section class="sec">' +
      '<div class="sec__h"><b>' + esc(cat.title) + '</b><span></span>' + tag + '</div>' +
      '<div class="sec__g">' + cat.items.map(function (s) {
        return '<button type="button" class="tile" data-service="' + esc(s.id) + '">' +
          '<span class="chip' + (s.on ? ' chip--on' : '') + '">' + svg(s.icon) +
          (s.on ? '<i class="chip__dot"></i>' : '') + '</span>' +
          '<b>' + esc(s.title) + '</b></button>';
      }).join('') + '</div></section>';
  }
  function listHTML(cat) {
    var items = cat.items.filter(function (s) {
      if (state.filter === 'on')  return !!s.on;
      if (state.filter === 'off') return s.toggle && !s.on;
      return true;
    });
    if (!items.length) return '';
    var c   = state.counts[cat.id] || { on: 0, all: cat.items.length };
    var tag = c.on ? '<em class="on">' + c.on + ' yoniq</em>' : '<em>' + c.all + ' ta</em>';
    return '<div class="card__h"><b>' + esc(cat.title.toUpperCase()) + '</b>' + tag + '</div>' +
      '<div class="card">' + items.map(function (s) {
        var ic   = '<span class="ic ' + iconClass(cat, s) + '">' + svg(s.icon) + '</span>';
        var text = '<span class="row__t"><b>' + esc(s.title) + '</b>' +
                   (s.sub ? '<small>' + esc(s.sub) + '</small>' : '') + '</span>';
        if (s.toggle) {
          return '<div class="row row--tall">' + ic + text +
            '<button type="button" class="sw" role="switch" data-toggle="' + esc(s.id) + '" ' +
            'aria-checked="' + (s.on ? 'true' : 'false') + '" ' +
            'aria-label="' + esc(s.title) + '"></button></div>';
        }
        return '<button type="button" class="row row--tall" data-service="' + esc(s.id) + '">' + ic + text +
          (s.value ? '<em>' + esc(s.value) + '</em>' : '') + CHEVRON + '</button>';
      }).join('') + '</div>';
  }

  /* ---- 8. Sahifalar ----------------------------------------------------- */
  var PAGES = {};

  PAGES.home = function () {
    var s = state.stats;
    return '' +
      '<div class="logo" role="img" aria-label="TartibOn"><span class="logo__w">' +
        '<span>T</span><span style="margin-left:-.079em">a</span><span>r</span>' +
        '<span style="margin-left:.029em">t</span><span style="margin-left:.018em">i</span><span>b</span>' +
        '<i class="logo__o" aria-hidden="true"><svg viewBox="0 0 24 24">' +
          '<path class="r" d="M7.2 6.6a8 8 0 1 0 9.6 0"/><path class="b" d="M12 2.6v8"/>' +
        '</svg></i><span>n</span></span></div>' +
      '<div class="stat">' +
        '<div><b>' + val(money(s.channels)) + '</b><span>Kanal</span></div><i></i>' +
        '<div><b>' + val(money(s.groups)) + '</b><span>Guruh</span></div><i></i>' +
        '<div><b>' + val(money(s.subscribers)) + '</b><span>Obunachi</span>' +
          (s.growth > 0 ? '<em>+' + money(s.growth) + '</em>' : '') +
        '</div>' +
      '</div>' +
      '<div class="quick">' +
        quickBtn('addChannel', 'Kanal') + quickBtn('addGroup', 'Guruh') + quickBtn('topup', "To'ldirish") +
      '</div>' +
      nowHTML() +
      ['manage', 'daily', 'digital'].map(function (id) {
        var c = category(id);
        return c ? tilesHTML(c) : '';
      }).join('');
  };
  /* "Hozir ishlab turibdi" — ishlayotgan xizmatlar nomi va keyingi amal vaqti */
  function nowHTML() {
    if (!state.running) return '';
    var names = [];
    state.catalog.forEach(function (c) {
      c.items.forEach(function (i) { if (i.on) names.push(i.title); });
    });
    var list = names.slice(0, 3).join(', ') +
               (names.length > 3 ? ' va yana ' + (names.length - 3) + ' ta' : '');
    var clock = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
    return '<div class="now"><span class="now__dot"></span>' +
      '<span class="now__t"><b>' + state.running + ' ta xizmat ishlab turibdi</b>' +
      '<span>' + esc(list) + '</span></span>' +
      (state.next ? '<span class="now__next">' + clock + esc(state.next.time) + '</span>' : '') +
      '</div>';
  }

  function quickBtn(action, label) {
    return '<button type="button" data-action="' + action + '">' +
      '<svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>' + esc(label) + '</button>';
  }

  PAGES.tools = function () {
    var all = 0, on = 0, off = 0;
    state.catalog.forEach(function (c) {
      c.items.forEach(function (i) {
        all++;
        if (i.on) on++;
        else if (i.toggle) off++;
      });
    });
    var body = ['manage', 'daily', 'digital', 'help'].map(function (id) {
      var c = category(id);
      return c ? listHTML(c) : '';
    }).join('');
    return '<h1 class="h1">Xizmatlar</h1>' +
      '<div class="seg">' +
        segBtn('all', 'Hammasi', all) + segBtn('on', 'Yoniq', on) + segBtn('off', "O'chiq", off) +
      '</div>' +
      (body || '<div class="empty">Bu filtrda xizmat yo‘q.</div>');
  };
  function segBtn(key, label, n) {
    return '<button type="button" data-filter="' + key + '" aria-pressed="' +
      (state.filter === key ? 'true' : 'false') + '">' + label + '<em>' + n + '</em></button>';
  }

  PAGES.bal = function () {
    var w = state.wallet;
    var rows = state.recent.map(function (r) {
      var pos = r.amount >= 0;
      return '<div class="row"><span class="ic ' + (pos ? 'ic--green' : 'ic--grey') + '">' +
        (pos ? '+' : '−') + '</span><b>' + esc(r.title) + '</b>' +
        '<em' + (pos ? ' class="pos"' : '') + '>' + (pos ? '+' : '') + money(r.amount) + '</em></div>';
    }).join('');
    return '' +
      '<h1 class="h1">Balans</h1>' +
      '<div class="bal"><span>Mavjud mablag‘</span>' +
        '<b>' + val(money(w.balance), true) + "<em>so'm</em></b>" +
        (w.hold ? '<i>' + money(w.hold) + " so'm muzlatilgan</i>" : '') +
      '</div>' +
      (state.summary && (state.summary['in'] || state.summary.out)
        ? '<div class="mini">' +
          '<div><b>+' + money(state.summary['in']) + '</b><span>Kirim · shu oy</span></div>' +
          '<div><b>−' + money(state.summary.out) + '</b><span>Chiqim · shu oy</span></div>' +
          '</div>'
        : '') +
      '<div class="card__h"><b>AMALLAR</b></div><div class="card">' +
        walletRow('topup', 'currency', "Balansni to'ldirish", 'ic--blue') +
        walletRow('withdraw', 'digital', 'Pul yechish', 'ic--blue') +
        walletRow('transfer', 'group', "Do'stga o'tkazish", 'ic--blue') +
        walletRow('donate', 'gift', "Qo'llab-quvvatlash", 'ic--violet') +
      '</div>' +
      (rows ? '<div class="card__h"><b>SO‘NGGI AMALLAR</b></div><div class="card">' + rows + '</div>' : '');
  };
  function walletRow(action, icon, label, cls) {
    return '<button type="button" class="row" data-action="' + action + '">' +
      '<span class="ic ' + cls + '">' + svg(icon) + '</span><b>' + esc(label) + '</b>' + CHEVRON + '</button>';
  }

  PAGES.me = function () {
    var u = state.user;
    var initial = String(u.first_name || u.username || 'T').trim().charAt(0).toUpperCase();
    return '' +
      '<h1 class="h1">Profil</h1>' +
      '<div class="prof"><div class="prof__av" aria-hidden="true">' + esc(initial) + '</div>' +
        '<div class="prof__t"><b>' + esc(u.first_name || 'Foydalanuvchi') + '</b>' +
          (u.username ? '<small>@' + esc(u.username) + '</small>' : '') +
          '<div class="tags"><span class="tag">ID: ' + esc(u.id || '—') + '</span>' +
          (u.admin ? '<span class="tag tag--blue">Admin</span>' : '') + '</div></div></div>' +
      '<div class="card__h"><b>HISOBIM</b></div><div class="card">' +
        walletRow2('my-channel', 'channel', 'Kanal va guruhlarim', (u.chats || 0) + ' ta') +
        walletRow2('referral', 'group', 'Taklif havolalari', '') +
        walletRow2('about', 'info', 'Ilova haqida', '') +
      '</div>';
  };
  function walletRow2(id, icon, label, value) {
    return '<button type="button" class="row" data-service="' + esc(id) + '">' +
      '<span class="ic ic--blue">' + svg(icon) + '</span><b>' + esc(label) + '</b>' +
      (value ? '<em>' + esc(value) + '</em>' : '') + CHEVRON + '</button>';
  }

  /* ---- 9. Router -------------------------------------------------------- */
  var view = null, nav = null;
  function go(page, push) {
    if (!PAGES[page]) page = 'home';
    sheetClose();
    state.page = page;
    document.documentElement.classList.toggle('is-home', page === 'home');
    view.innerHTML = '<div class="page is-on">' + PAGES[page]() + '</div>';
    window.scrollTo(0, 0);
    Array.prototype.forEach.call(nav.querySelectorAll('[data-tab]'), function (b) {
      if (b.dataset.tab === page) b.setAttribute('aria-current', 'page');
      else b.removeAttribute('aria-current');
    });
    if (push !== false && location.hash !== '#' + page) {
      try { history.pushState({ page: page }, '', '#' + page); } catch (e) {}
    }
    try { page === 'home' ? tg.BackButton.hide() : tg.BackButton.show(); } catch (e) {}
  }
  window.addEventListener('popstate', function () {
    go((location.hash || '#home').slice(1), false);
  });
  try { tg.BackButton.onClick(function () { go('home'); }); } catch (e) {}

  /* ---- 10. Amallar ------------------------------------------------------ */
  var ACTIONS = {
    addChannel: function () { openAddChat('channel'); },
    addGroup:   function () { openAddChat('group'); },
    topup:      openTopup,
    withdraw:   function () { stub('Pul yechish', 'Pul yechish arizasi'); },
    transfer:   function () { stub("Do'stga o'tkazish", 'Boshqa foydalanuvchiga pul o‘tkazish'); },
    donate:     function () { stub("Qo'llab-quvvatlash", 'Bot rivoji uchun hissa'); }
  };

  function openAddChat(kind) {
    var word = kind === 'channel' ? 'Kanal' : 'Guruh';
    sheetOpen(word + ' qo‘shish',
      '<p class="note" style="margin-top:0">Botni ' + word.toLowerCase() +
      'ga administrator qilib qo‘shing, so‘ng quyidagi tugmani bosing — ' +
      'ro‘yxat avtomatik yangilanadi.</p><div style="height:14px"></div>' +
      '<button type="button" class="btn" data-link="https://t.me/' + esc(BOOT.bot || '') +
      '?start' + (kind === 'channel' ? 'channel' : 'group') + '">Telegramda ochish</button>');
  }

  function openTopup() {
    var w = state.wallet;
    var sums = [10000, 25000, 50000, 100000, 250000, 500000];
    sheetOpen("Balansni to'ldirish",
      '<label class="fld"><span>Summa (so‘m)</span>' +
      '<input type="tel" inputmode="numeric" id="tp-sum" placeholder="' + money(w.min) + '"></label>' +
      '<div class="chips">' + sums.map(function (s) {
        return '<button type="button" data-sum="' + s + '">' + money(s) + '</button>';
      }).join('') + '</div>' +
      '<button type="button" class="btn" id="tp-go">Davom etish</button>' +
      '<p class="note">Eng kam: ' + money(w.min) + " so'm · eng ko'p: " + money(w.max) + " so'm.</p>",
      { onOpen: function (p) {
          var input = $('#tp-sum', p);
          p.addEventListener('click', function (e) {
            var chip = e.target.closest('[data-sum]');
            if (chip) { input.value = money(chip.dataset.sum); haptic(); }
          });
          input.addEventListener('input', function () {
            var raw = input.value.replace(/\D/g, '');
            input.value = raw ? money(raw) : '';
          });
          $('#tp-go', p).addEventListener('click', function () {
            var amount = Number(input.value.replace(/\D/g, ''));
            if (!amount) return toast('Summani kiriting');
            if (amount < w.min) return toast('Eng kam ' + money(w.min) + " so'm");
            if (amount > w.max) return toast('Eng ko‘p ' + money(w.max) + " so'm");
            var btn = this;
            btn.disabled = true;
            api('topup_create', { amount: amount }).then(function (r) {
              sheetClose();
              toast(r.message || 'Ariza yuborildi');
              if (r.wallet) { state.wallet = r.wallet; if (state.page === 'bal') go('bal', false); }
            })['catch'](function (err) { btn.disabled = false; toast(err.message); });
          });
        } });
  }

  function stub(title, text) {
    sheetOpen(title, '<p class="note" style="margin-top:0">' + esc(text) +
      '. Bu bo‘lim hali ulanmagan — server tomonidagi amal qo‘shilgach ishga tushadi.</p>');
  }

  var HANDLERS = {};
  function openService(id) {
    if (HANDLERS[id]) return HANDLERS[id]();
    var s = service(id);
    if (!s) return;
    sheetOpen(s.title,
      '<div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">' +
      '<span class="chip">' + svg(s.icon) + '</span><div><b style="font-size:17px">' + esc(s.title) + '</b>' +
      '<div style="color:var(--muted);font-size:13.5px">' + esc(s.sub || '') + '</div></div></div>' +
      '<p class="note" style="margin-top:0">Bu xizmat sahifasi hali ulanmagan.</p>');
  }

  /* xizmatni yoqish/o'chirish — joyida, sahifaga kirmasdan */
  function toggleService(btn, id) {
    var on = btn.getAttribute('aria-checked') !== 'true';
    btn.setAttribute('aria-checked', on ? 'true' : 'false');   /* darhol ko'rinsin */
    btn.disabled = true;
    var svc = service(id);
    if (svc) svc.on = on;
    var icon = btn.parentNode.querySelector('.ic');
    if (icon) { icon.classList.toggle('ic--green', on); icon.classList.toggle('ic--grey', !on); }

    api('service_toggle', { id: id, on: on }).then(function (r) {
      btn.disabled = false;
      state.running = r.running;
      state.counts  = recount();
      haptic();
    })['catch'](function (err) {
      btn.disabled = false;
      btn.setAttribute('aria-checked', on ? 'false' : 'true');  /* orqaga qaytarish */
      if (svc) svc.on = !on;
      if (icon) { icon.classList.toggle('ic--green', !on); icon.classList.toggle('ic--grey', on); }
      toast(err.message);
    });
  }

  /* bo'limlar bo'yicha sanoqni qayta hisoblash */
  function recount() {
    var out = {};
    state.catalog.forEach(function (c) {
      var on = 0;
      c.items.forEach(function (i) { if (i.on) on++; });
      out[c.id] = { on: on, all: c.items.length };
    });
    return out;
  }

  /* ---- 11. Bitta global tinglovchi (passive) ---------------------------- */
  function onTap(e) {
    var t = e.target, n;
    if ((n = t.closest('[data-toggle]'))) { return toggleService(n, n.dataset.toggle); }
    if ((n = t.closest('[data-filter]'))) {
      haptic(); state.filter = n.dataset.filter; return go('tools', false);
    }
    if ((n = t.closest('[data-tab]')))    { haptic(); return go(n.dataset.tab); }
    if ((n = t.closest('[data-action]'))) {
      haptic();
      var fn = ACTIONS[n.dataset.action];
      return fn ? fn() : toast('Amal topilmadi');
    }
    if ((n = t.closest('[data-service]'))) { haptic(); return openService(n.dataset.service); }
    if ((n = t.closest('[data-link]'))) {
      haptic();
      var url = n.dataset.link;
      try { /t\.me\//.test(url) ? tg.openTelegramLink(url) : tg.openLink(url); }
      catch (err) { window.open(url, '_blank'); }
      return;
    }
    if (t.closest('[data-close-app]')) { try { tg.close(); } catch (err) {} }
  }

  /* ---- 12. Ishga tushirish ---------------------------------------------- */
  function loadMe() {
    return api('me').then(function (r) {
      state.ready   = true;
      state.user    = r.user || state.user;
      state.stats   = r.stats || state.stats;
      state.wallet  = r.wallet || state.wallet;
      state.running = r.running || 0;
      state.next    = r.next || null;
      state.recent  = r.recent || [];
      state.counts  = r.counts || {};
      state.summary = r.summary || { in: 0, out: 0 };
      if (r.catalog && r.catalog.length) state.catalog = r.catalog;
      go(state.page, false);
    })['catch'](function (err) {
      state.ready = true;
      toast(err.message || 'Ma‘lumot yuklanmadi');
    });
  }
  function boot() {
    view = $('#view');
    nav  = $('#nav');
    document.addEventListener('click', onTap, { passive: true });
    go((location.hash || '#home').slice(1), false);
    loadMe();
    if (!TG_OK && !BOOT.demo) toast('Telegram ichida ochilmadi — namoyish rejimi');
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else { boot(); }

  /* ---- 13. Tashqi API --------------------------------------------------- */
  window.TartibOn = {
    go: go, toast: toast, sheet: sheetOpen, closeSheet: sheetClose,
    api: api, state: state, money: money,
    /* yangi xizmat sahifasini ulash: TartibOn.service('stats', fn) */
    service: function (id, fn) { HANDLERS[id] = fn; }
  };
})(window, document);
</script>
</body>
</html>
<?php
    }

    /** Fayl o'zgarsa brauzer keshi yangilansin. */
    private static function assetVersion(): string
    {
        $js = __DIR__ . '/../public/assets/app.js';
        return is_file($js) ? (string)filemtime($js) : '1';
    }

    public static function error(string $title, string $text): void
    {
        header('Content-Type: text/html; charset=utf-8');
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $x = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<style>body{margin:0;display:flex;flex-direction:column;align-items:center;justify-content:center;'
           . 'min-height:100vh;gap:8px;padding:24px;text-align:center;background:#F2F3F7;color:#10131B;'
           . "font:600 15px/1.45 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif}"
           . 'b{font-size:19px}p{margin:0;max-width:320px;color:#77758C;font-weight:500}</style>'
           . '<b>' . $t . '</b><p>' . $x . '</p>';
    }
}

/**
 * TartibOn — yagona kirish nuqtasi.
 * GET  /            -> HTML qobig'i
 * POST /?api=<amal> -> JSON API
 */

if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}


header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: ALLOWALL');          // Telegram ilovani iframe'da ochadi

$action = isset($_GET['api']) ? (string)$_GET['api'] : '';

if ($action !== '') {
    Api::handle($action);
    exit;
}

try {
    // Birinchi yuklash: faqat ochiq ma'lumot. Foydalanuvchi va hamyon
    // initData bilan `?api=me` orqali keladi (imzo tekshirilgandan keyin).
    View::shell([
        'base'    => '',
        'bot'     => (string)Config::get('bot_username'),
        'support' => (string)Config::get('support'),
        'demo'    => Config::isDemo(),
        'catalog' => Catalog::build(App::hiddenServices()),
        'stats'   => App::stats(),
    ]);
} catch (Throwable $e) {
    error_log('[tartibon] ' . $e->getMessage());
    http_response_code(500);
    View::error('Ilova ochilmadi', 'Server sozlamalarida xatolik bor. Birozdan keyin urinib ko\'ring.');
}
