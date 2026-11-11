<?php
declare(strict_types=1);

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
