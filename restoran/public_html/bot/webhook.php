<?php
declare(strict_types=1);

/**
 * Telegram bot webhook. Telegram shu manzilga yangilanishlarni yuboradi.
 * Himoya: X-Telegram-Bot-Api-Secret-Token sarlavhasi config dagi webhook_secret bilan solishtiriladi.
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_method('POST');

$secret = (string) config('telegram.webhook_secret', '');
$sent = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
if ($secret === '' || str_starts_with($secret, 'CHANGE_ME') || !hash_equals($secret, $sent)) {
    json_fail('Ruxsat yo‘q', 403);
}

$update = request_data();
$message = $update['message'] ?? null;

if (is_array($message) && isset($message['from']['id'], $message['chat']['id'])
    && ($message['chat']['type'] ?? '') === 'private') {
    $from = $message['from'];
    $chatId = (int) $message['chat']['id'];
    $text = trim((string) ($message['text'] ?? ''));
    $command = strtolower(explode(' ', $text)[0] ?? '');
    $command = explode('@', $command)[0];

    tg_upsert_customer($from);
    $name = e((string) ($from['first_name'] ?? 'mehmon'));

    if ($command === '/start') {
        $restaurant = e(setting('restaurant_name', 'Dasturxon'));
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'parse_mode' => 'HTML',
            'text' => "Assalomu alaykum, <b>{$name}</b>! 👋\n\n"
                . "<b>{$restaurant}</b> restoraniga xush kelibsiz.\n"
                . "Quyidagi tugma orqali menyuni ko‘ring va buyurtma bering.",
            'reply_markup' => mini_app_keyboard(),
        ]);
    } elseif ($command === '/help') {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Buyurtma berish uchun «🍽 Buyurtma berish» tugmasini bosing.\n"
                . "Buyurtma holati o‘zgarganda sizga xabar yuboramiz.",
            'reply_markup' => mini_app_keyboard(),
        ]);
    } else {
        tg_api('sendMessage', [
            'chat_id' => $chatId,
            'text' => 'Buyurtma berish uchun quyidagi tugmani bosing.',
            'reply_markup' => mini_app_keyboard(),
        ]);
    }
}

json_ok();
