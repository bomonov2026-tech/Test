<?php
declare(strict_types=1);

/**
 * TartibOn — yagona kirish nuqtasi.
 * GET  /            -> HTML qobig'i
 * POST /?api=<amal> -> JSON API
 */

if (extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ob_start('ob_gzhandler');
}

spl_autoload_register(static function (string $class): void {
    $file = dirname(__DIR__) . '/src/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

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
