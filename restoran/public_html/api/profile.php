<?php
declare(strict_types=1);

/**
 * GET                                  — profil, statistika, manzillar
 * POST {action:'phone', phone}         — telefon raqamini saqlash
 * POST {action:'address_save', id?, title, address, landmark}
 * POST {action:'address_delete', id}
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';

$customer = tg_customer();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'GET' ? 'get' : (string) input_value('action', '');

function profile_addresses(int $userId): array
{
    return array_map(static fn(array $a): array => [
        'id' => (int) $a['id'],
        'title' => (string) $a['title'],
        'address' => (string) $a['address'],
        'landmark' => (string) ($a['landmark'] ?? ''),
    ], db_all('SELECT * FROM addresses WHERE user_id = :u ORDER BY id DESC', [':u' => $userId]));
}

switch ($action) {
    case 'get':
        $stats = db_one(
            "SELECT COUNT(*) AS orders_count,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END), 0) AS spent
             FROM orders WHERE user_id = :u",
            [':u' => $customer['id']]
        );
        json_ok([
            'user' => [
                'telegram_id' => (int) $customer['telegram_id'],
                'first_name' => (string) $customer['first_name'],
                'last_name' => (string) ($customer['last_name'] ?? ''),
                'username' => (string) ($customer['username'] ?? ''),
                'phone' => (string) ($customer['phone'] ?? ''),
            ],
            'stats' => [
                'orders_count' => (int) ($stats['orders_count'] ?? 0),
                'spent' => (int) ($stats['spent'] ?? 0),
            ],
            'addresses' => profile_addresses((int) $customer['id']),
            'restaurant' => [
                'name' => setting('restaurant_name', 'Dasturxon'),
                'phone' => setting('restaurant_phone', ''),
                'address' => setting('restaurant_address', ''),
                'about' => setting('restaurant_about', ''),
                'contact_telegram' => setting('contact_telegram', ''),
                'working_open' => setting('working_open', '09:00'),
                'working_close' => setting('working_close', '23:00'),
            ],
        ]);

    case 'phone':
        require_method('POST');
        $phone = v_phone(input_value('phone'));
        if ($phone === null) {
            json_fail('Telefon raqamini to‘g‘ri kiriting (+998 XX XXX XX XX)', 422);
        }
        db_exec('UPDATE users SET phone = :p WHERE id = :id', [':p' => $phone, ':id' => $customer['id']]);
        json_ok(['phone' => $phone]);

    case 'address_save':
        require_method('POST');
        $address = v_str((string) input_value('address', ''), 255, true);
        $landmark = v_str((string) input_value('landmark', ''), 255) ?? '';
        $title = v_str((string) input_value('title', ''), 60) ?? '';
        if ($address === null || $address === '') {
            json_fail('Manzilni kiriting', 422);
        }
        if ($landmark === null) {
            json_fail('Mo‘ljal juda uzun', 422);
        }
        $title = $title !== '' ? $title : 'Manzil';
        $id = v_int(input_value('id'), 1, PHP_INT_MAX);
        if ($id !== null) {
            $updated = db_exec(
                'UPDATE addresses SET title = :t, address = :a, landmark = :l WHERE id = :id AND user_id = :u',
                [':t' => $title, ':a' => $address, ':l' => $landmark ?: null, ':id' => $id, ':u' => $customer['id']]
            );
            if ($updated === 0 && db_one('SELECT id FROM addresses WHERE id = :id AND user_id = :u', [':id' => $id, ':u' => $customer['id']]) === null) {
                json_fail('Manzil topilmadi', 404);
            }
        } else {
            $count = (int) db_val('SELECT COUNT(*) FROM addresses WHERE user_id = :u', [':u' => $customer['id']]);
            if ($count >= 10) {
                json_fail('Ko‘pi bilan 10 ta manzil saqlash mumkin', 409);
            }
            db_insert(
                'INSERT INTO addresses (user_id, title, address, landmark) VALUES (:u, :t, :a, :l)',
                [':u' => $customer['id'], ':t' => $title, ':a' => $address, ':l' => $landmark ?: null]
            );
        }
        json_ok(['addresses' => profile_addresses((int) $customer['id'])]);

    case 'address_delete':
        require_method('POST');
        $id = v_int(input_value('id'), 1, PHP_INT_MAX);
        if ($id === null) {
            json_fail('Manzil topilmadi', 404);
        }
        db_exec('DELETE FROM addresses WHERE id = :id AND user_id = :u', [':id' => $id, ':u' => $customer['id']]);
        json_ok(['addresses' => profile_addresses((int) $customer['id'])]);

    default:
        json_fail('Noto‘g‘ri action', 400);
}
