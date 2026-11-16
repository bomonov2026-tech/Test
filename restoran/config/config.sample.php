<?php
/**
 * Maxfiy sozlamalar namunasi.
 * Nusxa oling:  cp config/config.sample.php config/config.php
 * config/config.php ni HECH QACHON gitga yoki ochiq joyga yubormang.
 */
return [
    'app' => [
        'name'       => 'Dasturxon',
        // HTTPS manzil, oxirida "/" bo‘lmasin
        'base_url'   => 'https://restoran.example.uz',
        'timezone'   => 'Asia/Tashkent',
        'debug'      => false, // production da false
    ],

    'db' => [
        'host'    => 'localhost',
        'name'    => 'cpuser_restoran',   // cPanel da yaratilgan baza nomi
        'user'    => 'cpuser_resto',      // cPanel da yaratilgan foydalanuvchi
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],

    'telegram' => [
        // BotFather dan olingan token. Bo‘sh qoldirsangiz, admin Sozlamalar sahifasidagi token ishlatiladi.
        'bot_token'         => 'CHANGE_ME:123456789',
        'bot_username'      => 'your_restaurant_bot',   // @ belgisisiz
        // Webhook himoyasi uchun tasodifiy 32-64 belgili satr (tools/setup_telegram.php ishlatadi)
        'webhook_secret'    => 'CHANGE_ME_RANDOM_STRING_32_PLUS_CHARS',
        'init_data_max_age' => 86400, // initData eskirish muddati (soniya)
    ],

    'session' => [
        'admin_name'   => 'RESTORAN_ADMIN',
        'idle_timeout' => 7200, // admin paneldan harakatsizlik (soniya)
    ],

    'uploads' => [
        'max_bytes'     => 5 * 1024 * 1024, // 5 MB
        'max_dimension' => 1200,            // px, GD mavjud bo‘lsa avtomatik kichraytiriladi
    ],
];
