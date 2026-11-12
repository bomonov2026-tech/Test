<?php
declare(strict_types=1);

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
