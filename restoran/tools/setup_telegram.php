<?php
declare(strict_types=1);

/**
 * Telegram botni sozlash (faqat CLI):
 *   php tools/setup_telegram.php           — webhook, buyruqlar va menu tugmasini o‘rnatadi
 *   php tools/setup_telegram.php --info    — joriy webhook holatini ko‘rsatadi
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Faqat CLI');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

if (in_array('--info', $argv, true)) {
    print_r(tg_api('getWebhookInfo'));
    exit;
}

$base = base_url();
if (!str_starts_with($base, 'https://')) {
    exit("Xato: app.base_url HTTPS bo‘lishi kerak (hozir: {$base}).\n");
}

$secret = (string) config('telegram.webhook_secret', '');
if ($secret === '' || str_starts_with($secret, 'CHANGE_ME') || strlen($secret) < 32) {
    exit("Xato: config.php dagi telegram.webhook_secret kamida 32 belgili tasodifiy satr bo‘lishi kerak.\n");
}
if (tg_bot_token() === '') {
    exit("Xato: bot tokeni topilmadi (config.php yoki admin sozlamalari).\n");
}

$steps = [
    'setWebhook' => [
        'url' => $base . '/bot/webhook.php',
        'secret_token' => $secret,
        'allowed_updates' => ['message'],
        'drop_pending_updates' => true,
    ],
    'setMyCommands' => [
        'commands' => [
            ['command' => 'start', 'description' => 'Botni ishga tushirish'],
            ['command' => 'help', 'description' => 'Yordam'],
        ],
    ],
    'setChatMenuButton' => [
        'menu_button' => [
            'type' => 'web_app',
            'text' => 'Menyu',
            'web_app' => ['url' => $base . '/'],
        ],
    ],
];

foreach ($steps as $method => $params) {
    $res = tg_api($method, $params);
    echo str_pad($method, 20) . ': ' . (!empty($res['ok']) ? 'OK' : 'XATO — ' . ($res['description'] ?? '?')) . "\n";
}
echo "\nTekshirish: php tools/setup_telegram.php --info\n";
