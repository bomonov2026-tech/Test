<?php
// Nusxa oling: cp config.example.php config.php && chmod 640 config.php
// config.php gitga KIRMAYDI (.gitignore).
return [
    'bot_token'         => '',            // BotFather tokeni (Mini App ochiladigan bot)
    'api_id'            => 0,             // my.telegram.org -> API development tools
    'api_hash'          => '',
    'owner_telegram_id' => '',            // raqamli Telegram ID (username emas)
    'cron_key'          => '',            // URL-cron uchun (ixtiyoriy). Bo'sh qolsa faqat CLI cron ishlaydi
    'timezone'          => 'Asia/Tashkent',
    // TartibOn intro (app/splash.php)
    'intro'              => true,   // false — introni o'chirish
    'intro_cooldown_min' => 360,    // Mini App qayta ochilganda shu daqiqa ichida intro qayta chiqmaydi
    'intro_video'        => '',     // ixtiyoriy: '/intro/tartibon.mp4' (public/intro/ ga qo'ying, ≤3.4 s); bo'sh — CSS animatsiya
];
