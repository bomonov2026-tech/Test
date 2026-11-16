<?php
declare(strict_types=1);

/**
 * POST {items:[{product_id,qty,options[],note}], order_type, promo_code}
 * Savatni serverda qayta hisoblaydi: narx, qo‘shimcha, chegirma, yetkazib berish, jami.
 * Frontend yuborgan narxlar hisobga olinmaydi.
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_method('POST');
tg_customer();

$items = input_value('items', []);
if (!is_array($items)) {
    json_fail('Savat noto‘g‘ri', 422);
}
$type = (string) input_value('order_type', 'delivery');
if (!in_array($type, ['delivery', 'pickup'], true)) {
    $type = 'delivery';
}
$promo = v_str((string) input_value('promo_code', ''), 40) ?? '';

$calc = price_cart($items, $type, $promo);
$store = store_status();

json_ok([
    'valid' => $calc['valid'],
    'lines' => $calc['lines'],
    'subtotal' => $calc['subtotal'],
    'discount' => $calc['discount'],
    'promo' => $calc['promo'],
    'delivery_fee' => $calc['delivery_fee'],
    'total' => $calc['total'],
    'min_order' => $calc['min_order'],
    'errors' => $calc['errors'],
    'store' => ['is_open' => $store['is_open'], 'message' => $store['message']],
]);
