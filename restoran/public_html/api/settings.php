<?php
declare(strict_types=1);

/** GET — ochiq ma’lumotlar: restoran ma’lumoti, ish holati, yetkazib berish sozlamalari. */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_method('GET');

$store = store_status();

json_ok([
    'restaurant' => [
        'name' => setting('restaurant_name', 'Dasturxon'),
        'slogan' => setting('restaurant_slogan', ''),
        'phone' => setting('restaurant_phone', ''),
        'address' => setting('restaurant_address', ''),
        'about' => setting('restaurant_about', ''),
        'contact_telegram' => setting('contact_telegram', ''),
        'logo' => asset_url(setting('logo', '')),
        'currency' => setting('currency', 'so‘m'),
    ],
    'store' => [
        'is_open' => $store['is_open'],
        'message' => $store['message'],
        'working_open' => $store['working_open'],
        'working_close' => $store['working_close'],
        'days_off' => $store['days_off'],
        'delivery_enabled' => $store['delivery_enabled'],
        'pickup_enabled' => $store['pickup_enabled'],
        'delivery_fee' => $store['delivery_fee'],
        'min_order_amount' => $store['min_order_amount'],
    ],
    'bot_username' => (string) config('telegram.bot_username', ''),
]);
