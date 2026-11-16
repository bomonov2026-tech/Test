<?php
declare(strict_types=1);

/**
 * GET  ?action=list&scope=active|history&page=  — mijozning buyurtmalari (faqat o‘ziniki)
 * GET  ?action=detail&id=                       — bitta buyurtma (faqat o‘ziniki)
 * POST {action:'create', ...}                   — buyurtma yaratish (transaction)
 * POST {action:'cancel', id}                    — yangi buyurtmani bekor qilish
 * POST {action:'repeat', id}                    — takroriy buyurtma: savat elementlari
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$action = (string) ($_GET['action'] ?? input_value('action', ''));
$customer = tg_customer();

switch ($action) {
    case 'list':
        require_method('GET');
        $scope = ($_GET['scope'] ?? 'active') === 'history' ? 'history' : 'active';
        $statuses = $scope === 'history' ? ['completed', 'cancelled'] : ORDER_ACTIVE_STATUSES;
        $in = "'" . implode("','", $statuses) . "'";
        $total = (int) db_val("SELECT COUNT(*) FROM orders WHERE user_id = :u AND status IN ({$in})", [':u' => $customer['id']]);
        [$limit, $offset, $page, $pages] = paginate($total, 20, (int) ($_GET['page'] ?? 1));
        $rows = db_all(
            "SELECT * FROM orders WHERE user_id = :u AND status IN ({$in})
             ORDER BY created_at DESC, id DESC LIMIT {$limit} OFFSET {$offset}",
            [':u' => $customer['id']]
        );
        json_ok([
            'orders' => array_map(static fn(array $o): array => order_full($o), $rows),
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);

    case 'detail':
        require_method('GET');
        $id = v_int($_GET['id'] ?? null, 1, PHP_INT_MAX);
        $order = $id === null ? null : db_one('SELECT * FROM orders WHERE id = :id AND user_id = :u', [':id' => $id, ':u' => $customer['id']]);
        if ($order === null) {
            json_fail('Buyurtma topilmadi', 404);
        }
        json_ok(['order' => order_full($order)]);

    case 'create':
        require_method('POST');
        $result = create_order($customer, request_data());
        if (isset($result['error'])) {
            json_fail($result['error'], (int) ($result['status'] ?? 422), isset($result['details']) ? ['cart' => $result['details']] : []);
        }
        json_ok([
            'order_id' => $result['order_id'],
            'order_no' => $result['order_no'],
            'total' => $result['total'],
            'message' => 'Buyurtmangiz qabul qilindi. Raqam: #' . $result['order_no'],
        ]);

    case 'cancel':
        require_method('POST');
        $id = v_int(input_value('id'), 1, PHP_INT_MAX);
        $order = $id === null ? null : db_one('SELECT id FROM orders WHERE id = :id AND user_id = :u', [':id' => $id, ':u' => $customer['id']]);
        if ($order === null) {
            json_fail('Buyurtma topilmadi', 404);
        }
        $result = change_order_status((int) $id, 'cancelled', ['type' => 'customer', 'id' => (int) $customer['id']], 'Mijoz bekor qildi');
        if (isset($result['error'])) {
            json_fail($result['error'], (int) $result['status']);
        }
        json_ok(['message' => 'Buyurtma bekor qilindi']);

    case 'repeat':
        require_method('POST');
        $id = v_int(input_value('id'), 1, PHP_INT_MAX);
        $order = $id === null ? null : db_one('SELECT id FROM orders WHERE id = :id AND user_id = :u', [':id' => $id, ':u' => $customer['id']]);
        if ($order === null) {
            json_fail('Buyurtma topilmadi', 404);
        }
        $items = [];
        $skipped = [];
        foreach (order_items_map([(int) $id])[(int) $id] ?? [] as $it) {
            $pid = $it['product_id'] !== null ? (int) $it['product_id'] : null;
            $product = $pid === null ? null : db_one('SELECT id, name, is_active, is_available FROM products WHERE id = :id', [':id' => $pid]);
            if ($product === null || (int) $product['is_active'] !== 1 || (int) $product['is_available'] !== 1) {
                $skipped[] = (string) $it['product_name'];
                continue;
            }
            $optionIds = [];
            foreach ($it['options'] as $opt) {
                if ($opt['option_value_id'] !== null && db_one('SELECT id FROM product_option_values WHERE id = :id AND is_available = 1', [':id' => $opt['option_value_id']])) {
                    $optionIds[] = (int) $opt['option_value_id'];
                }
            }
            $items[] = [
                'product_id' => $pid,
                'qty' => (int) $it['quantity'],
                'options' => $optionIds,
                'note' => (string) ($it['note'] ?? ''),
            ];
        }
        if (!$items) {
            json_fail('Bu buyurtmadagi mahsulotlar hozir mavjud emas', 409);
        }
        json_ok(['items' => $items, 'skipped' => $skipped]);

    default:
        json_fail('Noto‘g‘ri action', 400);
}
