<?php
declare(strict_types=1);

/**
 * Telegram Bot API bilan ishlash: initData tekshiruvi, xabar yuborish, bildirishnomalar navbati.
 */

/**
 * Telegram Mini App initData imzosini tekshiradi (WebAppData algoritmi).
 * Muvaffaqiyatli bo‘lsa Telegram "user" massivini, aks holda null qaytaradi.
 */
function tg_verify_init_data(string $initData, string $botToken, int $maxAge = 86400): ?array
{
    if ($botToken === '' || $initData === '' || strlen($initData) > 4096) {
        return null;
    }
    parse_str($initData, $params);
    if (!isset($params['hash'], $params['auth_date']) || !is_string($params['hash'])) {
        return null;
    }
    $hash = strtolower((string) $params['hash']);
    unset($params['hash']);

    ksort($params);
    $pairs = [];
    foreach ($params as $key => $value) {
        if (!is_string($value)) {
            return null;
        }
        $pairs[] = $key . '=' . $value;
    }
    $dataCheckString = implode("\n", $pairs);

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $calculated = hash_hmac('sha256', $dataCheckString, $secretKey);
    if (!hash_equals($calculated, $hash)) {
        return null;
    }

    $authDate = (int) $params['auth_date'];
    if ($authDate <= 0 || $authDate > time() + 300 || (time() - $authDate) > $maxAge) {
        return null;
    }

    $user = json_decode((string) ($params['user'] ?? ''), true);
    if (!is_array($user) || !isset($user['id']) || !is_numeric($user['id'])) {
        return null;
    }
    return $user;
}

/** Telegram Bot API chaqiruvi. Javob massivini qaytaradi (ok / result / description). */
function tg_api(string $method, array $params = []): array
{
    $token = tg_bot_token();
    if ($token === '') {
        return ['ok' => false, 'description' => 'Bot tokeni sozlanmagan'];
    }
    $ch = curl_init('https://api.telegram.org/bot' . $token . '/' . $method);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT => 12,
    ]);
    $raw = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        error_log('[restoran] Telegram API cURL xato: ' . $method . ' — ' . $error);
        return ['ok' => false, 'description' => 'Telegram bilan aloqa yo‘q'];
    }
    $data = json_decode((string) $raw, true);
    return is_array($data) ? $data : ['ok' => false, 'description' => 'Telegram javobi noto‘g‘ri'];
}

/* ---------- Bildirishnomalar navbati ---------- */

function notify_enqueue(string $recipientType, int $chatId, string $message, ?array $markup = null, ?int $orderId = null): int
{
    return db_insert(
        'INSERT INTO notifications (recipient_type, recipient_telegram_id, order_id, message, reply_markup)
         VALUES (:t, :c, :o, :m, :r)',
        [
            ':t' => $recipientType === 'admin' ? 'admin' : 'customer',
            ':c' => $chatId,
            ':o' => $orderId,
            ':m' => mb_substr($message, 0, 4000),
            ':r' => $markup ? json_encode($markup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]
    );
}

/** Navbatdagi xabarlarni yuboradi. ['sent' => n, 'failed' => n] qaytaradi. */
function notify_dispatch(int $limit = 30): array
{
    $sent = 0;
    $failed = 0;
    // Qayta urinish orasidagi kutish: 1, 4, 9, 16 daqiqa (urinishlar soni kvadratiga teng).
    // Shunda Telegram vaqtincha ishlamasa, har so‘rovda xabar "failed" bo‘lib qolmaydi.
    $rows = db_all(
        "SELECT * FROM notifications
         WHERE status = 'pending' AND attempts < 5
           AND updated_at <= NOW() - INTERVAL (attempts * attempts) MINUTE
         ORDER BY id ASC LIMIT " . max(1, $limit)
    );
    foreach ($rows as $row) {
        $params = [
            'chat_id' => (int) $row['recipient_telegram_id'],
            'text' => (string) $row['message'],
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if (!empty($row['reply_markup'])) {
            $params['reply_markup'] = json_decode((string) $row['reply_markup'], true);
        }
        $res = tg_api('sendMessage', $params);
        $attempts = (int) $row['attempts'] + 1;
        if (!empty($res['ok'])) {
            db_exec(
                "UPDATE notifications SET status = 'sent', attempts = :a, sent_at = NOW(), last_error = NULL WHERE id = :id",
                [':a' => $attempts, ':id' => $row['id']]
            );
            $sent++;
        } else {
            $status = $attempts >= 5 ? 'failed' : 'pending';
            db_exec(
                'UPDATE notifications SET status = :s, attempts = :a, last_error = :e WHERE id = :id',
                [
                    ':s' => $status,
                    ':a' => $attempts,
                    ':e' => mb_substr((string) ($res['description'] ?? 'Noma’lum xato'), 0, 250),
                    ':id' => $row['id'],
                ]
            );
            $failed++;
        }
    }
    return ['sent' => $sent, 'failed' => $failed];
}

/** Admin/oshpaz Telegram chat ID lari: sozlamalardagi ro‘yxat + faol admin hisoblari. */
function admin_chat_ids(): array
{
    $ids = [];
    foreach (explode(',', setting('admin_chat_ids', '')) as $part) {
        $part = trim($part);
        if (preg_match('/^-?\d{5,15}$/', $part)) {
            $ids[(int) $part] = true;
        }
    }
    foreach (db_all("SELECT telegram_id FROM admins WHERE role = 'admin' AND is_active = 1 AND telegram_id IS NOT NULL") as $row) {
        $ids[(int) $row['telegram_id']] = true;
    }
    return array_keys($ids);
}

/** Bot /start uchun yoki boshqa joyda ishlatiladigan Mini App tugmasi. */
function mini_app_keyboard(string $text = '🍽 Buyurtma berish'): array
{
    return ['inline_keyboard' => [[['text' => $text, 'web_app' => ['url' => base_url() . '/']]]]];
}
