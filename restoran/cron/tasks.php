<?php
declare(strict_types=1);

/**
 * Cron vazifalari (faqat CLI). Har 2 daqiqada ishga tushiriladi:
 *   php /home/CPUSER/restoran/cron/tasks.php >> /home/CPUSER/restoran/cron/cron.log 2>&1
 *
 *  1) Navbatdagi Telegram xabarlarini yuboradi (buyurtma holati, admin xabarlari)
 *  2) Eski yuborilgan xabarlar va jurnallarni tozalaydi
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Faqat CLI');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

$started = microtime(true);
$lock = APP_ROOT . '/cron/tasks.lock';
$fp = fopen($lock, 'c');
if ($fp === false || !flock($fp, LOCK_EX | LOCK_NB)) {
    echo '[' . date('Y-m-d H:i:s') . "] Oldingi ishlash hali tugamagan, o‘tkazib yuborildi\n";
    exit(0);
}

$dispatch = notify_dispatch(100);
$purged = db_exec("DELETE FROM notifications WHERE status = 'sent' AND sent_at < (NOW() - INTERVAL 90 DAY)");
$logsPurged = db_exec('DELETE FROM admin_logs WHERE created_at < (NOW() - INTERVAL 365 DAY)');

printf(
    "[%s] yuborildi=%d xato=%d tozalandi(bildirishnoma)=%d tozalandi(jurnal)=%d vaqt=%.2fs\n",
    date('Y-m-d H:i:s'),
    $dispatch['sent'],
    $dispatch['failed'],
    $purged,
    $logsPurged,
    microtime(true) - $started
);

flock($fp, LOCK_UN);
fclose($fp);
