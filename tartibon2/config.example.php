<?php
/**
 * Nusxa oling: cp config.example.php config.php
 * config.php git'ga tushmaydi va veb orqali ochilmaydi (public/ dan tashqarida).
 * Bu fayl bo'lmasa ilova namoyish (demo) rejimida ishlaydi.
 */
return [
    'bot_token'    => '',                 // BotFather bergan token
    'bot_username' => 'TartibOnBot',      // @ belgisisiz
    'support'      => 'tartibon_admin',   // adminning useri, @ belgisisiz
    'admin_ids'    => [],                 // [123456789]

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tartibon',
        'user' => 'tartibon',
        'pass' => '',
    ],

    'wallet' => [
        'min'    => 5000,                 // eng kam to'ldirish
        'max'    => 3000000,              // eng ko'p to'ldirish
        'wd_min' => 20000,                // eng kam yechish
    ],

    'auth_ttl' => 86400,                  // initData necha soniya amal qiladi
];
