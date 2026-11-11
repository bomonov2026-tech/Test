<?php
declare(strict_types=1);

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
