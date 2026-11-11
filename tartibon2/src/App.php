<?php
declare(strict_types=1);

/** Foydalanuvchi, statistika va hamyon — bitta joyda. */
final class App
{
    /** @var array<string,mixed>|null */
    private static $user = null;

    /**
     * Joriy foydalanuvchi. initData imzosi tekshiriladi;
     * namoyish rejimida soxta foydalanuvchi qaytadi.
     * @return array<string,mixed>|null
     */
    public static function user(string $initData): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        if (Config::isDemo()) {
            return self::$user = self::decorate(Telegram::demoUser(), ['balance' => 128000, 'hold' => 0, 'chats' => 0]);
        }
        $tgUser = Telegram::verify($initData);
        if ($tgUser === null) {
            return null;
        }
        $row = self::upsert($tgUser);
        return self::$user = self::decorate($tgUser, $row);
    }

    /**
     * @param array<string,mixed> $tg
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private static function decorate(array $tg, array $row): array
    {
        $admins = (array)Config::get('admin_ids', []);
        return [
            'id'         => (int)$tg['id'],
            'first_name' => (string)($tg['first_name'] ?? ''),
            'username'   => (string)($tg['username'] ?? ''),
            'admin'      => in_array((int)$tg['id'], array_map('intval', $admins), true),
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
        $row = Db::one(
            'SELECT u.balance, u.hold, (SELECT COUNT(*) FROM chats c WHERE c.owner_id = u.tg_id) AS chats
             FROM users u WHERE u.tg_id = ?',
            [(int)$tg['id']]
        );
        return $row ?? [];
    }

    /** Bosh sahifadagi umumiy sonlar. @return array<string,int> */
    public static function stats(): array
    {
        if (Config::isDemo()) {
            return ['channels' => 60, 'groups' => 1149, 'subscribers' => 11068];
        }
        $row = Db::one(
            "SELECT
               SUM(type = 'channel')            AS channels,
               SUM(type = 'group')              AS groups_,
               COALESCE(SUM(members), 0)        AS subscribers
             FROM chats WHERE active = 1"
        ) ?? [];
        return [
            'channels'    => (int)($row['channels'] ?? 0),
            'groups'      => (int)($row['groups_'] ?? 0),
            'subscribers' => (int)($row['subscribers'] ?? 0),
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

    /** Admin yashirgan xizmatlar. @return list<string> */
    public static function hiddenServices(): array
    {
        if (Config::isDemo()) {
            return [];
        }
        $row = Db::one("SELECT value FROM settings WHERE name = 'hidden_services'");
        $list = json_decode((string)($row['value'] ?? '[]'), true);
        return is_array($list) ? array_values(array_map('strval', $list)) : [];
    }
}
