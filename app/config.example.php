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
];
