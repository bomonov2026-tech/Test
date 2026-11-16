<?php
declare(strict_types=1);

/**
 * Admin / oshpaz AJAX API (sessiya + CSRF talab qilinadi).
 *   GET  ?action=kitchen_orders          — oshxona: faol buyurtmalar (admin, kitchen)
 *   POST {action:'set_status', id, status, note}  — status (admin, kitchen; oshpaz cheklangan)
 *   GET  ?action=new_orders_since&after=ID        — yangi buyurtmalar (admin)
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
send_security_headers('admin');
admin_session_start();

$user = admin_require(['admin', 'kitchen']);
$action = (string) ($_GET['action'] ?? input_value('action', ''));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
}

switch ($action) {
    case 'kitchen_orders':
        require_method('GET');
        $rows = db_all(
            "SELECT * FROM orders WHERE status IN ('new','accepted','preparing','ready')
             ORDER BY created_at ASC, id ASC LIMIT 100"
        );
        $items = order_items_map(array_map(static fn(array $r): int => (int) $r['id'], $rows));
        $now = time();
        json_ok([
            'orders' => array_map(static function (array $o) use ($items, $now): array {
                $created = strtotime((string) $o['created_at']) ?: $now;
                return [
                    'id' => (int) $o['id'],
                    'order_no' => (int) $o['order_no'],
                    'order_type' => $o['order_type'],
                    'status' => $o['status'],
                    'status_label' => order_status_label((string) $o['status']),
                    'comment' => $o['comment'],
                    'created_at' => $o['created_at'],
                    'minutes' => max(0, (int) floor(($now - $created) / 60)),
                    'items' => array_map(static fn(array $it): array => [
                        'name' => $it['product_name'],
                        'quantity' => (int) $it['quantity'],
                        'note' => $it['note'],
                        'options' => array_map(static fn(array $op): string => (string) $op['value_name'], $it['options']),
                    ], $items[(int) $o['id']] ?? []),
                    'next' => allowed_next_statuses((string) $o['status'], (string) $o['order_type']),
                ];
            }, $rows),
        ]);

    case 'set_status':
        require_method('POST');
        $id = v_int(input_value('id'), 1, PHP_INT_MAX);
        $status = (string) input_value('status', '');
        $note = v_str((string) input_value('note', ''), 250) ?? '';
        if ($id === null) {
            json_fail('Buyurtma topilmadi', 404);
        }
        $result = change_order_status($id, $status, ['type' => $user['role'], 'id' => (int) $user['id']], $note !== '' ? $note : null);
        if (isset($result['error'])) {
            json_fail($result['error'], (int) $result['status']);
        }
        json_ok(['from' => $result['from'], 'to' => $result['to'], 'label' => order_status_label($result['to'])]);

    case 'new_orders_since':
        require_method('GET');
        $after = v_int($_GET['after'] ?? 0, 0, PHP_INT_MAX, 0);
        $rows = db_all(
            "SELECT id, order_no, customer_name, total FROM orders WHERE id > :a AND status = 'new' ORDER BY id ASC LIMIT 20",
            [':a' => $after]
        );
        json_ok([
            'orders' => array_map(static fn(array $o): array => [
                'id' => (int) $o['id'],
                'order_no' => (int) $o['order_no'],
                'customer_name' => (string) $o['customer_name'],
                'total' => (int) $o['total'],
                'total_formatted' => money((int) $o['total']),
            ], $rows),
            'active_new' => (int) db_val("SELECT COUNT(*) FROM orders WHERE status = 'new'"),
        ]);

    default:
        json_fail('Noto‘g‘ri action', 400);
}
