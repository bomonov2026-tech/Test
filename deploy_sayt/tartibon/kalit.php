<?php
/* BIR MARTALIK yordamchi: mavjud (ko'chirilgan) bazada o'rnatishni tugatish uchun havola va cron buyruqlarini ko'rsatadi.
   Ishga tushgach O'ZINI O'CHIRADI. Ochish: https://SIZNING-DOMEN.uz/kalit.php */
header('Content-Type: text/plain; charset=utf-8');
if (!is_file(__DIR__ . '/config.php')) exit("config.php topilmadi — avval config.php.example dan config.php yarating.\n");
require __DIR__ . '/config.php';
$key = 'up_' . substr(md5(BOT_TOKEN . 'usdpremium'), 0, 20);
$dir = rtrim(__DIR__, '/');
echo "1) O'rnatishni yakunlash (bazani tekshiradi + Telegram webhook + menyu tugmasi) — brauzerda oching:\n" . APP_URL . '?setup=' . $key . "\n\n";
echo "2) Cron (ISPmanager → Планировщик / Cron):\n";
echo "   Har daqiqada  (hammasi *):        /usr/bin/php " . $dir . "/index.php prayer\n";
echo "   Har 5 daqiqada (Минуты */5):      /usr/bin/php " . $dir . "/index.php watch\n";
echo "   (php yo'li boshqacha bo'lsa panelda ko'rsatilganini yozing: masalan /opt/php83/bin/php)\n\n";
echo "Bu fayl endi o'chiriladi.\n";
@unlink(__FILE__);
