<?php
declare(strict_types=1);

/**
 * Biznes mantiqi: narx hisoblash (faqat serverda), promo, ish vaqti,
 * buyurtma yaratish (transaction), status o‘zgartirish va bildirishnomalar.
 */

const ORDER_STATUS_LABELS = [
    'new' => 'Yangi',
    'accepted' => 'Qabul qilindi',
    'preparing' => 'Tayyorlanmoqda',
    'ready' => 'Tayyor',
    'delivering' => 'Yo‘lda',
    'completed' => 'Yakunlandi',
    'cancelled' => 'Bekor qilindi',
];

const ORDER_ACTIVE_STATUSES = ['new', 'accepted', 'preparing', 'ready', 'delivering'];

function order_status_label(string $status): string
{
    return ORDER_STATUS_LABELS[$status] ?? $status;
}

/**
 * Qaysi statuslarga o‘tish mumkin.
 * Yetkazib berish: ready -> delivering -> completed. Olib ketish: ready -> completed.
 */
function allowed_next_statuses(string $from, string $orderType): array
{
    $map = [
        'new' => ['accepted', 'preparing', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => $orderType === 'delivery' ? ['delivering', 'cancelled'] : ['completed', 'cancelled'],
        'delivering' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];
    return $map[$from] ?? [];
}

/* =====================================================================
 *  Restoran holati (ish vaqti, dam olish kunlari, yetkazib berish)
 * ===================================================================== */

function store_status(): array
{
    $tz = new DateTimeZone((string) config('app.timezone', 'Asia/Tashkent'));
    $now = new DateTimeImmutable('now', $tz);
    $open = v_time(setting('working_open', '09:00')) ?? '09:00';
    $close = v_time(setting('working_close', '23:00')) ?? '23:00';
    $daysOff = v_weekdays_csv(setting('days_off', ''));
    $accepting = setting('order_enabled', '1') === '1';
    $cur = $now->format('H:i');
    $inHours = $open <= $close
        ? ($cur >= $open && $cur < $close)
        : ($cur >= $open || $cur < $close); // tungi smena (masalan 20:00 – 02:00)
    $isDayOff = in_array((int) $now->format('N'), $daysOff, true);

    if (!$accepting) {
        $message = 'Hozir buyurtma qabul qilinmayapti.';
    } elseif ($isDayOff) {
        $message = 'Bugun dam olish kuni. Restoran yopiq.';
    } elseif (!$inHours) {
        $message = "Restoran hozir yopiq. Ish vaqti: {$open} – {$close}";
    } else {
        $message = 'Restoran ochiq';
    }

    return [
        'is_open' => $accepting && !$isDayOff && $inHours,
        'accepting' => $accepting,
        'message' => $message,
        'working_open' => $open,
        'working_close' => $close,
        'days_off' => $daysOff,
        'delivery_enabled' => setting('delivery_enabled', '1') === '1',
        'pickup_enabled' => setting('pickup_enabled', '1') === '1',
        'delivery_fee' => (int) setting('delivery_fee', '0'),
        'min_order_amount' => (int) setting('min_order_amount', '0'),
    ];
}

/* =====================================================================
 *  Narx hisoblash — FAQAT serverda. Frontend yuborgan narxlarga ishonilmaydi.
 * ===================================================================== */

/** Mahsulot narxi chegirma bilan (100 so‘mga yaxlitlangan). */
function product_final_price(int $price, int $discountPercent): int
{
    if ($discountPercent <= 0) {
        return $price;
    }
    return (int) (round($price * (100 - $discountPercent) / 100 / 100) * 100);
}

function product_image_url(?string $image): ?string
{
    return asset_url($image);
}

/**
 * Savatni (items) MySQL dan qayta hisoblaydi.
 * items: [{product_id, qty, options:[option_value_id...], note}]
 * @return array{valid: bool, lines: array, subtotal: int, discount: int, promo: ?array, promo_code: ?string,
 *               delivery_fee: int, total: int, errors: array, min_order: int}
 */
function price_cart(array $rawItems, string $orderType, string $promoCode = ''): array
{
    $errors = [];
    $normalized = [];
    $productIds = [];
    $optionIds = [];

    $rawItems = array_slice(array_values($rawItems), 0, 50);
    foreach ($rawItems as $i => $item) {
        if (!is_array($item)) {
            $errors[] = ['index' => $i, 'message' => 'Noto‘g‘ri savat elementi'];
            continue;
        }
        $pid = v_int($item['product_id'] ?? null, 1, PHP_INT_MAX);
        $qty = v_int($item['qty'] ?? 1, 1, 99);
        $note = v_str((string) ($item['note'] ?? ''), 255);
        $opts = [];
        foreach ((array) ($item['options'] ?? []) as $opt) {
            $id = v_int($opt, 1, PHP_INT_MAX);
            if ($id !== null) {
                $opts[$id] = $id;
            }
        }
        if ($pid === null || $qty === null || $note === null) {
            $errors[] = ['index' => $i, 'message' => 'Mahsulot ma’lumotlari noto‘g‘ri'];
            continue;
        }
        $normalized[$i] = ['product_id' => $pid, 'qty' => $qty, 'options' => array_values($opts), 'note' => $note];
        $productIds[$pid] = true;
        foreach ($opts as $o) {
            $optionIds[$o] = true;
        }
    }

    if (!$normalized && !$errors) {
        $errors[] = ['index' => null, 'message' => 'Savat bo‘sh'];
    }

    /** @var array<int, array> $products */
    $products = [];
    $groups = [];
    $optionValues = [];
    if ($productIds) {
        $inP = implode(',', array_map('intval', array_keys($productIds)));
        foreach (db_all(
            "SELECT p.*, c.is_active AS cat_active FROM products p
             LEFT JOIN categories c ON c.id = p.category_id WHERE p.id IN ({$inP})"
        ) as $p) {
            $products[(int) $p['id']] = $p;
        }
        foreach (db_all("SELECT id, product_id, name, option_type, is_required FROM product_options WHERE product_id IN ({$inP})") as $g) {
            $groups[(int) $g['product_id']][] = $g;
        }
    }
    if ($optionIds) {
        $inO = implode(',', array_map('intval', array_keys($optionIds)));
        foreach (db_all(
            "SELECT v.id, v.name, v.price, v.is_available, o.id AS option_id, o.name AS option_name,
                    o.option_type, o.product_id
             FROM product_option_values v JOIN product_options o ON o.id = v.option_id
             WHERE v.id IN ({$inO})"
        ) as $v) {
            $optionValues[(int) $v['id']] = $v;
        }
    }

    $lines = [];
    $subtotal = 0;
    foreach ($normalized as $i => $it) {
        $p = $products[$it['product_id']] ?? null;
        if ($p === null || (int) $p['is_active'] !== 1 || (int) $p['is_available'] !== 1
            || ($p['cat_active'] !== null && (int) $p['cat_active'] !== 1)) {
            $errors[] = ['index' => $i, 'message' => 'Mahsulot mavjud emas yoki sotuvdan olingan'];
            continue;
        }

        $chosen = [];
        $optionsTotal = 0;
        $countByGroup = [];
        $optError = null;
        foreach ($it['options'] as $ov) {
            $v = $optionValues[$ov] ?? null;
            if ($v === null || (int) $v['product_id'] !== (int) $p['id'] || (int) $v['is_available'] !== 1) {
                $optError = 'Tanlangan qo‘shimcha mavjud emas';
                break;
            }
            $gid = (int) $v['option_id'];
            $countByGroup[$gid] = ($countByGroup[$gid] ?? 0) + 1;
            if ($v['option_type'] === 'single' && $countByGroup[$gid] > 1) {
                $optError = '"' . $v['option_name'] . '" guruhidan faqat bittasini tanlang';
                break;
            }
            $chosen[] = [
                'id' => (int) $v['id'],
                'option_name' => (string) $v['option_name'],
                'name' => (string) $v['name'],
                'price' => (int) $v['price'],
            ];
            $optionsTotal += (int) $v['price'];
        }
        if ($optError === null) {
            foreach ($groups[(int) $p['id']] ?? [] as $g) {
                if ((int) $g['is_required'] === 1 && empty($countByGroup[(int) $g['id']])) {
                    $optError = '"' . $g['name'] . '" bo‘yicha tanlov qiling';
                    break;
                }
            }
        }
        if ($optError !== null) {
            $errors[] = ['index' => $i, 'message' => $p['name'] . ': ' . $optError];
            continue;
        }

        $base = (int) $p['price'];
        $discount = (int) $p['discount_percent'];
        $unit = product_final_price($base, $discount);
        $lineTotal = ($unit + $optionsTotal) * $it['qty'];
        $subtotal += $lineTotal;
        $lines[] = [
            'index' => $i,
            'product_id' => (int) $p['id'],
            'name' => (string) $p['name'],
            'unit' => (string) $p['unit'],
            'image' => product_image_url($p['image']),
            'base_price' => $base,
            'discount_percent' => $discount,
            'unit_price' => $unit,
            'options' => $chosen,
            'options_total' => $optionsTotal,
            'qty' => $it['qty'],
            'note' => $it['note'],
            'line_total' => $lineTotal,
        ];
    }

    $minOrder = (int) setting('min_order_amount', '0');
    if ($normalized && $subtotal < $minOrder) {
        $errors[] = ['index' => null, 'message' => 'Minimal buyurtma summasi: ' . money($minOrder)];
    }

    // Promo-kod
    $promoInfo = null;
    $promoApplied = null;
    $discountTotal = 0;
    $code = strtoupper(trim($promoCode));
    if ($code !== '') {
        $promo = db_one('SELECT * FROM promo_codes WHERE code = :c', [':c' => $code]);
        $now = time();
        $valid = $promo !== null
            && (int) $promo['is_active'] === 1
            && (empty($promo['starts_at']) || strtotime((string) $promo['starts_at']) <= $now)
            && (empty($promo['ends_at']) || strtotime((string) $promo['ends_at']) >= $now)
            && ($promo['max_uses'] === null || (int) $promo['used_count'] < (int) $promo['max_uses']);
        if (!$valid) {
            $promoInfo = ['valid' => false, 'message' => 'Promo-kod noto‘g‘ri yoki muddati tugagan'];
        } elseif ($subtotal < (int) $promo['min_order']) {
            $promoInfo = ['valid' => false, 'message' => 'Bu promo-kod uchun minimal summa: ' . money((int) $promo['min_order'])];
        } else {
            $discountTotal = $promo['discount_type'] === 'percent'
                ? intdiv($subtotal * (int) $promo['discount_value'], 100)
                : (int) $promo['discount_value'];
            $discountTotal = min($discountTotal, $subtotal);
            $promoApplied = ['id' => (int) $promo['id'], 'code' => (string) $promo['code']];
            $promoInfo = ['valid' => true, 'message' => 'Chegirma qo‘llandi: ' . money($discountTotal), 'code' => $promo['code']];
        }
    }

    $deliveryFee = $orderType === 'delivery' ? (int) setting('delivery_fee', '0') : 0;
    $total = max(0, $subtotal - $discountTotal + $deliveryFee);

    return [
        'valid' => !$errors && ($code === '' || ($promoInfo['valid'] ?? false)),
        'lines' => $lines,
        'subtotal' => $subtotal,
        'discount' => $discountTotal,
        'promo' => $promoInfo,
        'promo_code' => $promoApplied['code'] ?? null,
        'promo_id' => $promoApplied['id'] ?? null,
        'delivery_fee' => $deliveryFee,
        'total' => $total,
        'errors' => $errors,
        'min_order' => $minOrder,
    ];
}

/* =====================================================================
 *  Buyurtma yaratish (MySQL transaction)
 * ===================================================================== */

/**
 * @return array{order_id:int, order_no:int, total:int}|array{error:string, status:int, details?:array}
 */
function create_order(array $customer, array $data): array
{
    $store = store_status();
    if (!$store['accepting']) {
        return ['error' => 'Hozir buyurtma qabul qilinmayapti', 'status' => 409];
    }
    if (!$store['is_open']) {
        return ['error' => $store['message'], 'status' => 409];
    }

    $type = (string) ($data['order_type'] ?? '');
    if (!in_array($type, ['delivery', 'pickup'], true)) {
        return ['error' => 'Buyurtma turini tanlang', 'status' => 422];
    }
    if ($type === 'delivery' && !$store['delivery_enabled']) {
        return ['error' => 'Yetkazib berish vaqtincha o‘chirilgan', 'status' => 409];
    }
    if ($type === 'pickup' && !$store['pickup_enabled']) {
        return ['error' => 'Olib ketish vaqtincha o‘chirilgan', 'status' => 409];
    }

    $name = v_str((string) ($data['customer_name'] ?? ''), 120, true);
    $phone = v_phone($data['customer_phone'] ?? null);
    $comment = v_str((string) ($data['comment'] ?? ''), 500);
    $promoCode = v_str((string) ($data['promo_code'] ?? ''), 40) ?? '';
    if ($name === null || $name === '') {
        return ['error' => 'Ismingizni kiriting', 'status' => 422];
    }
    if ($phone === null) {
        return ['error' => 'Telefon raqamini to‘g‘ri kiriting (+998 XX XXX XX XX)', 'status' => 422];
    }
    if ($comment === null) {
        return ['error' => 'Izoh juda uzun', 'status' => 422];
    }
    $items = $data['items'] ?? [];
    if (!is_array($items) || !$items) {
        return ['error' => 'Savat bo‘sh', 'status' => 422];
    }

    $address = null;
    $landmark = null;
    $addressId = null;
    $saveAddress = false;
    if ($type === 'delivery') {
        $addressId = v_int($data['address_id'] ?? null, 1, PHP_INT_MAX);
        if ($addressId !== null) {
            $saved = db_one('SELECT * FROM addresses WHERE id = :id AND user_id = :u', [':id' => $addressId, ':u' => $customer['id']]);
            if ($saved === null) {
                return ['error' => 'Manzil topilmadi', 'status' => 422];
            }
            $address = (string) $saved['address'];
            $landmark = $saved['landmark'] !== null ? (string) $saved['landmark'] : null;
        } else {
            $address = v_str((string) ($data['address'] ?? ''), 255, true);
            $landmark = v_str((string) ($data['landmark'] ?? ''), 255) ?: null;
            $saveAddress = !empty($data['save_address']);
            if ($address === null || $address === '') {
                return ['error' => 'Yetkazib berish manzilini kiriting', 'status' => 422];
            }
        }
    }

    $result = db_tx(function (PDO $pdo) use ($customer, $items, $type, $promoCode, $name, $phone, $comment, $address, $landmark, $saveAddress) {
        $calc = price_cart($items, $type, $promoCode);
        if (!$calc['valid']) {
            return ['error' => $calc['errors'][0]['message'] ?? ($calc['promo']['message'] ?? 'Savatda xatolik bor'), 'status' => 422, 'details' => $calc];
        }

        if ($calc['promo_id'] !== null) {
            $stmt = $pdo->prepare('UPDATE promo_codes SET used_count = used_count + 1
                                   WHERE id = :id AND (max_uses IS NULL OR used_count < max_uses)');
            $stmt->execute([':id' => $calc['promo_id']]);
            if ($stmt->rowCount() !== 1) {
                return ['error' => 'Promo-kod limiti tugagan', 'status' => 422];
            }
        }

        // Buyurtma raqami: sozlamalardagi hisoblagich qulflanib oshiriladi (takrorlanmaydi)
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('order_counter', '1000')")->execute();
        $row = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'order_counter' FOR UPDATE")->fetch();
        $orderNo = (int) ($row['setting_value'] ?? 1000) + 1;
        $pdo->prepare("UPDATE settings SET setting_value = :v WHERE setting_key = 'order_counter'")->execute([':v' => (string) $orderNo]);

        $pdo->prepare('UPDATE users SET phone = :p WHERE id = :id')
            ->execute([':p' => $phone, ':id' => $customer['id']]);

        $stmt = $pdo->prepare(
            'INSERT INTO orders (order_no, user_id, order_type, status, payment_type, payment_status,
                customer_name, customer_phone, address, landmark, comment,
                subtotal, discount_amount, promo_code, delivery_fee, total)
             VALUES (:no, :uid, :type, :status, :pt, :ps, :cn, :cp, :addr, :lm, :cm, :sub, :disc, :promo, :fee, :total)'
        );
        $stmt->execute([
            ':no' => $orderNo,
            ':uid' => $customer['id'],
            ':type' => $type,
            ':status' => 'new',
            ':pt' => 'cash',
            ':ps' => 'unpaid',
            ':cn' => $name,
            ':cp' => $phone,
            ':addr' => $address,
            ':lm' => $landmark,
            ':cm' => $comment !== '' ? $comment : null,
            ':sub' => $calc['subtotal'],
            ':disc' => $calc['discount'],
            ':promo' => $calc['promo_code'],
            ':fee' => $calc['delivery_fee'],
            ':total' => $calc['total'],
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit, unit_price, options_total, quantity, line_total, note)
             VALUES (:oid, :pid, :name, :unit, :up, :ot, :qty, :lt, :note)'
        );
        $optStmt = $pdo->prepare(
            'INSERT INTO order_item_options (order_item_id, option_value_id, option_name, value_name, price)
             VALUES (:iid, :vid, :on, :vn, :pr)'
        );
        foreach ($calc['lines'] as $line) {
            $itemStmt->execute([
                ':oid' => $orderId,
                ':pid' => $line['product_id'],
                ':name' => $line['name'],
                ':unit' => $line['unit'],
                ':up' => $line['unit_price'],
                ':ot' => $line['options_total'],
                ':qty' => $line['qty'],
                ':lt' => $line['line_total'],
                ':note' => $line['note'] !== '' ? $line['note'] : null,
            ]);
            $itemId = (int) $pdo->lastInsertId();
            foreach ($line['options'] as $opt) {
                $optStmt->execute([
                    ':iid' => $itemId,
                    ':vid' => $opt['id'],
                    ':on' => $opt['option_name'],
                    ':vn' => $opt['name'],
                    ':pr' => $opt['price'],
                ]);
            }
        }

        $pdo->prepare(
            "INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, actor_id, note)
             VALUES (:oid, NULL, 'new', 'customer', :aid, NULL)"
        )->execute([':oid' => $orderId, ':aid' => $customer['id']]);

        if ($saveAddress && $address !== null) {
            $count = (int) $pdo->query('SELECT COUNT(*) FROM addresses WHERE user_id = ' . (int) $customer['id'])->fetchColumn();
            if ($count < 10) {
                $pdo->prepare('INSERT INTO addresses (user_id, title, address, landmark) VALUES (:u, :t, :a, :l)')
                    ->execute([':u' => $customer['id'], ':t' => 'Manzil ' . ($count + 1), ':a' => $address, ':l' => $landmark]);
            }
        }

        return ['order_id' => $orderId, 'order_no' => $orderNo, 'total' => $calc['total']];
    });

    if (isset($result['error'])) {
        return $result;
    }

    // Tranzaksiyadan keyin xabarlar (xato bo‘lsa ham buyurtma saqlangan)
    notify_enqueue('customer', (int) $customer['telegram_id'], order_customer_text(order_row($result['order_id']) ?? [], 'new'), null, $result['order_id']);
    notify_order_to_admins($result['order_id']);
    notify_dispatch(10);

    return $result;
}

/* =====================================================================
 *  Statusni o‘zgartirish
 * ===================================================================== */

/**
 * @param array{type: 'admin'|'kitchen'|'customer'|'system', id: ?int} $actor
 * @return array{ok:true, from:string, to:string}|array{error:string, status:int}
 */
function change_order_status(int $orderId, string $to, array $actor, ?string $note = null): array
{
    if (!isset(ORDER_STATUS_LABELS[$to])) {
        return ['error' => 'Noto‘g‘ri status', 'status' => 422];
    }
    $note = $note !== null && $note !== '' ? mb_substr($note, 0, 250) : null;

    $result = db_tx(function (PDO $pdo) use ($orderId, $to, $actor, $note) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $orderId]);
        $order = $stmt->fetch();
        if (!$order) {
            return ['error' => 'Buyurtma topilmadi', 'status' => 404];
        }
        $from = (string) $order['status'];
        $type = (string) $actor['type'];

        if ($type === 'kitchen' && !in_array($to, ['preparing', 'ready'], true)) {
            return ['error' => 'Oshpaz bu statusni o‘zgartira olmaydi', 'status' => 403];
        }
        if ($type === 'customer' && !($from === 'new' && $to === 'cancelled')) {
            return ['error' => 'Buyurtmani faqat yangi holatida bekor qilish mumkin', 'status' => 403];
        }
        if (!in_array($to, allowed_next_statuses($from, (string) $order['order_type']), true)) {
            return [
                'error' => 'Statusni “' . order_status_label($from) . '” dan “' . order_status_label($to) . '” ga o‘tkazib bo‘lmaydi',
                'status' => 409,
            ];
        }

        $sets = ['status = :st'];
        $params = [':st' => $to, ':id' => $orderId];
        if ($to === 'accepted') {
            $sets[] = 'accepted_at = NOW()';
        }
        if ($to === 'completed') {
            $sets[] = 'completed_at = NOW()';
        }
        if ($to === 'cancelled') {
            $sets[] = 'cancelled_at = NOW()';
            $sets[] = 'cancel_reason = :reason';
            $params[':reason'] = $note;
        }
        $pdo->prepare('UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($params);

        // Bekor qilingan buyurtmaning promo-kod ishlatilishini qaytaramiz
        if ($to === 'cancelled' && !empty($order['promo_code'])) {
            $pdo->prepare('UPDATE promo_codes SET used_count = GREATEST(used_count - 1, 0) WHERE code = :c')
                ->execute([':c' => $order['promo_code']]);
        }

        $pdo->prepare(
            'INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, actor_id, note)
             VALUES (:oid, :from, :to, :by, :aid, :note)'
        )->execute([
            ':oid' => $orderId,
            ':from' => $from,
            ':to' => $to,
            ':by' => in_array($type, ['admin', 'kitchen', 'customer'], true) ? $type : 'system',
            ':aid' => $actor['id'] ?? null,
            ':note' => $note,
        ]);

        return ['ok' => true, 'from' => $from, 'to' => $to];
    });

    if (isset($result['error']) || empty($result['ok'])) {
        return $result;
    }

    $order = order_row($orderId);
    if ($order !== null && in_array($to, ['accepted', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'], true)) {
        notify_enqueue('customer', (int) $order['telegram_id'], order_customer_text($order, $to, $note), null, $orderId);
        notify_dispatch(10);
    }
    if ($order !== null && $actor['type'] !== 'customer') {
        admin_log_status($orderId, $result['from'], $to);
    }
    return $result;
}

function admin_log_status(int $orderId, string $from, string $to): void
{
    admin_log('order_status', 'order', (string) $orderId, ['from' => $from, 'to' => $to]);
}

/* =====================================================================
 *  O‘qish yordamchilari
 * ===================================================================== */

function order_row(int $orderId): ?array
{
    return db_one(
        'SELECT o.*, u.telegram_id FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = :id',
        [':id' => $orderId]
    );
}

/** Buyurtma pozitsiyalari va qo‘shimchalarini bir necha so‘rovda oladi. */
function order_items_map(array $orderIds): array
{
    $map = [];
    if (!$orderIds) {
        return $map;
    }
    $in = implode(',', array_map('intval', $orderIds));
    $items = db_all("SELECT * FROM order_items WHERE order_id IN ({$in}) ORDER BY id ASC");
    $itemOrder = [];
    foreach ($items as $it) {
        $it['options'] = [];
        $map[(int) $it['order_id']][(int) $it['id']] = $it;
        $itemOrder[(int) $it['id']] = (int) $it['order_id'];
    }
    if ($itemOrder) {
        $inI = implode(',', array_map('intval', array_keys($itemOrder)));
        foreach (db_all("SELECT * FROM order_item_options WHERE order_item_id IN ({$inI})") as $opt) {
            $oid = $itemOrder[(int) $opt['order_item_id']];
            $map[$oid][(int) $opt['order_item_id']]['options'][] = $opt;
        }
    }
    foreach ($map as $oid => $list) {
        $map[$oid] = array_values($list);
    }
    return $map;
}

/** Mijoz/admin uchun to‘liq buyurtma (pozitsiyalar + tarix). */
function order_full(array $order, bool $withTelegram = false): array
{
    $items = order_items_map([(int) $order['id']])[(int) $order['id']] ?? [];
    $history = db_all(
        'SELECT from_status, to_status, changed_by, note, created_at FROM order_status_history
         WHERE order_id = :id ORDER BY id ASC',
        [':id' => $order['id']]
    );
    $payload = [
        'id' => (int) $order['id'],
        'order_no' => (int) $order['order_no'],
        'order_type' => $order['order_type'],
        'status' => $order['status'],
        'status_label' => order_status_label((string) $order['status']),
        'payment_type' => $order['payment_type'],
        'payment_status' => $order['payment_status'],
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'address' => $order['address'],
        'landmark' => $order['landmark'],
        'comment' => $order['comment'],
        'subtotal' => (int) $order['subtotal'],
        'discount_amount' => (int) $order['discount_amount'],
        'promo_code' => $order['promo_code'],
        'delivery_fee' => (int) $order['delivery_fee'],
        'total' => (int) $order['total'],
        'cancel_reason' => $order['cancel_reason'],
        'can_cancel' => $order['status'] === 'new',
        'created_at' => $order['created_at'],
        'accepted_at' => $order['accepted_at'],
        'completed_at' => $order['completed_at'],
        'items' => array_map(static fn(array $it): array => [
            'product_id' => $it['product_id'] !== null ? (int) $it['product_id'] : null,
            'name' => $it['product_name'],
            'unit' => $it['unit'],
            'unit_price' => (int) $it['unit_price'],
            'options_total' => (int) $it['options_total'],
            'quantity' => (int) $it['quantity'],
            'line_total' => (int) $it['line_total'],
            'note' => $it['note'],
            'options' => array_map(static fn(array $o): array => [
                'name' => $o['option_name'],
                'value' => $o['value_name'],
                'price' => (int) $o['price'],
            ], $it['options']),
        ], $items),
        'history' => array_map(static fn(array $h): array => [
            'from' => $h['from_status'],
            'to' => $h['to_status'],
            'to_label' => order_status_label((string) $h['to_status']),
            'by' => $h['changed_by'],
            'note' => $h['note'],
            'at' => $h['created_at'],
        ], $history),
    ];
    if ($withTelegram) {
        $payload['telegram_id'] = (int) $order['telegram_id'];
    }
    return $payload;
}

/* =====================================================================
 *  Bildirishnoma matnlari
 * ===================================================================== */

function order_customer_text(array $order, string $status, ?string $reason = null): string
{
    $texts = [
        'new' => 'Buyurtmangiz qabul qilindi. Tasdiqlanishini kuting.',
        'accepted' => '✅ Buyurtma qabul qilindi.',
        'preparing' => '👨‍🍳 Buyurtma tayyorlanmoqda.',
        'ready' => ($order['order_type'] ?? '') === 'pickup'
            ? '🥡 Buyurtma tayyor. Olib ketishingiz mumkin.'
            : '🥡 Buyurtma tayyor.',
        'delivering' => '🛵 Kuryer yo‘lga chiqdi.',
        'completed' => '🎉 Buyurtma yakunlandi. Xaridingiz uchun rahmat!',
        'cancelled' => '❌ Buyurtma bekor qilindi.' . ($reason ? "\nSabab: " . e($reason) : ''),
    ];
    $no = (int) ($order['order_no'] ?? 0);
    $total = isset($order['total']) ? money((int) $order['total']) : '';
    return '<b>Buyurtma #' . $no . '</b>' . "\n" . ($texts[$status] ?? '') . ($total !== '' ? "\n\nJami: " . $total : '');
}

/** Admin/oshpaz uchun yangi buyurtma xabari (namunadagi formatda). */
function order_admin_text(int $orderId): string
{
    $order = order_row($orderId);
    if ($order === null) {
        return 'Buyurtma topilmadi';
    }
    $items = order_items_map([$orderId])[$orderId] ?? [];
    $lines = [];
    foreach ($items as $it) {
        $line = '• ' . (int) $it['quantity'] . ' × ' . e((string) $it['product_name']);
        $opts = array_map(static fn(array $o): string => (string) $o['value_name'], $it['options']);
        if ($opts) {
            $line .= ' (' . e(implode(', ', $opts)) . ')';
        }
        $lines[] = $line;
    }
    $text = "🛎 <b>YANGI BUYURTMA</b>\n\n"
        . 'Buyurtma: #' . (int) $order['order_no'] . "\n\n"
        . 'Mijoz: ' . e((string) $order['customer_name']) . "\n"
        . 'Telefon: ' . e((string) $order['customer_phone']) . "\n\n"
        . "Mahsulotlar:\n" . implode("\n", $lines) . "\n\n"
        . 'Jami: ' . money((int) $order['total']) . "\n\n"
        . 'To‘lov: Naqd pul' . "\n"
        . 'Yetkazish: ' . ($order['order_type'] === 'delivery' ? 'Yetkazib berish' : 'Olib ketish');
    if ($order['order_type'] === 'delivery') {
        $text .= "\nManzil: " . e((string) $order['address']);
        if (!empty($order['landmark'])) {
            $text .= "\nMo‘ljal: " . e((string) $order['landmark']);
        }
    }
    if (!empty($order['comment'])) {
        $text .= "\nIzoh: " . e((string) $order['comment']);
    }
    return $text;
}

function notify_order_to_admins(int $orderId): void
{
    $text = order_admin_text($orderId);
    $markup = ['inline_keyboard' => [[[
        'text' => '📋 Buyurtmani ochish',
        'url' => base_url() . '/admin/order.php?id=' . $orderId,
    ]]]];
    foreach (admin_chat_ids() as $chatId) {
        notify_enqueue('admin', $chatId, $text, $markup, $orderId);
    }
}
