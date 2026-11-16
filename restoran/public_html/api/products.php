<?php
declare(strict_types=1);

/**
 * GET  ?action=home                      — bosh sahifa: bannerlar, kategoriyalar, bo‘limlar
 * GET  ?action=list[&category_id|category][&q]  — menyu (qidiruv va filtr)
 * GET  ?action=product&id=               — mahsulot tafsiloti + qo‘shimchalar
 * GET  ?action=favorites                 — sevimlilar (Telegram auth)
 * POST {action:'favorite', product_id, on} — sevimlilarga qo‘shish/olib tashlash (Telegram auth)
 */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';

const PRODUCT_VISIBLE_SQL = 'p.is_active = 1 AND (p.category_id IS NULL OR c.is_active = 1)';
const PRODUCT_BASE_SQL = 'SELECT p.*, c.slug AS category_slug,
    (SELECT COUNT(*) FROM product_options po WHERE po.product_id = p.id AND po.is_required = 1) AS required_count
    FROM products p LEFT JOIN categories c ON c.id = p.category_id';

function product_payload(array $p, array $favIds): array
{
    $base = (int) $p['price'];
    $disc = (int) $p['discount_percent'];
    return [
        'id' => (int) $p['id'],
        'category_id' => $p['category_id'] !== null ? (int) $p['category_id'] : null,
        'name' => (string) $p['name'],
        'description' => (string) ($p['description'] ?? ''),
        'price' => product_final_price($base, $disc),
        'base_price' => $base,
        'discount_percent' => $disc,
        'unit' => (string) $p['unit'],
        'image' => asset_url($p['image'] ?? null),
        'is_available' => (int) $p['is_available'] === 1,
        'is_featured' => (int) $p['is_featured'] === 1,
        'is_recommended' => (int) $p['is_recommended'] === 1,
        'is_new' => (int) $p['is_new'] === 1,
        'is_combo' => (int) $p['is_combo'] === 1,
        'required_options' => (int) ($p['required_count'] ?? 0) > 0,
        'is_favorite' => isset($favIds[(int) $p['id']]),
    ];
}

$action = (string) ($_GET['action'] ?? input_value('action', ''));
$customer = tg_customer(false);
$favIds = [];
if ($customer !== null) {
    foreach (db_all('SELECT product_id FROM favorites WHERE user_id = :u', [':u' => $customer['id']]) as $f) {
        $favIds[(int) $f['product_id']] = true;
    }
}

switch ($action) {
    case 'home':
        require_method('GET');
        $banners = db_all(
            'SELECT b.id, b.title, b.subtitle, b.image, b.product_id FROM banners b
             LEFT JOIN products p ON p.id = b.product_id
             WHERE b.is_active = 1 AND (b.product_id IS NULL OR p.is_active = 1)
             ORDER BY b.sort_order, b.id LIMIT 10'
        );
        $banners = array_map(static fn(array $b): array => [
            'id' => (int) $b['id'],
            'title' => (string) $b['title'],
            'subtitle' => (string) ($b['subtitle'] ?? ''),
            'image' => asset_url($b['image']),
            'product_id' => $b['product_id'] !== null ? (int) $b['product_id'] : null,
        ], $banners);

        $categories = array_map(static fn(array $c): array => [
            'id' => (int) $c['id'],
            'slug' => (string) $c['slug'],
            'name' => (string) $c['name'],
            'image' => asset_url($c['image']),
            'count' => (int) $c['total'],
        ], db_all(
            'SELECT c.id, c.slug, c.name, c.image, COUNT(p.id) AS total FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.is_available = 1
             WHERE c.is_active = 1 GROUP BY c.id ORDER BY c.sort_order, c.id'
        ));

        $section = static function (string $flag) use ($favIds): array {
            $rows = db_all(
                PRODUCT_BASE_SQL . ' WHERE ' . PRODUCT_VISIBLE_SQL . " AND p.{$flag} = 1
                 ORDER BY p.is_available DESC, p.sort_order, p.id LIMIT 12"
            );
            return array_map(static fn(array $p): array => product_payload($p, $favIds), $rows);
        };

        json_ok([
            'banners' => $banners,
            'categories' => $categories,
            'featured' => $section('is_featured'),
            'recommended' => $section('is_recommended'),
            'new' => $section('is_new'),
        ]);

    case 'list':
        require_method('GET');
        $where = [PRODUCT_VISIBLE_SQL];
        $params = [];
        $categoryId = v_int($_GET['category_id'] ?? null, 1, PHP_INT_MAX);
        $categorySlug = v_str((string) ($_GET['category'] ?? ''), 120) ?? '';
        if ($categoryId !== null) {
            $where[] = 'p.category_id = :cid';
            $params[':cid'] = $categoryId;
        } elseif ($categorySlug !== '') {
            $where[] = 'c.slug = :slug';
            $params[':slug'] = $categorySlug;
        }
        $q = v_str((string) ($_GET['q'] ?? ''), 100) ?? '';
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(p.name LIKE :q1 OR p.description LIKE :q2)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }
        $rows = db_all(
            PRODUCT_BASE_SQL . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.is_available DESC, p.sort_order, p.id LIMIT 300',
            $params
        );
        json_ok([
            'products' => array_map(static fn(array $p): array => product_payload($p, $favIds), $rows),
            'count' => count($rows),
        ]);

    case 'product':
        require_method('GET');
        $id = v_int($_GET['id'] ?? null, 1, PHP_INT_MAX);
        $p = $id === null ? null : db_one(PRODUCT_BASE_SQL . ' WHERE p.id = :id AND ' . PRODUCT_VISIBLE_SQL, [':id' => $id]);
        if ($p === null) {
            json_fail('Mahsulot topilmadi', 404);
        }
        $values = [];
        foreach (db_all(
            'SELECT v.id, v.option_id, v.name, v.price, v.is_available FROM product_option_values v
             JOIN product_options o ON o.id = v.option_id WHERE o.product_id = :id ORDER BY v.sort_order, v.id',
            [':id' => $id]
        ) as $v) {
            $values[(int) $v['option_id']][] = [
                'id' => (int) $v['id'],
                'name' => (string) $v['name'],
                'price' => (int) $v['price'],
                'is_available' => (int) $v['is_available'] === 1,
            ];
        }
        $groups = [];
        foreach (db_all('SELECT * FROM product_options WHERE product_id = :id ORDER BY sort_order, id', [':id' => $id]) as $g) {
            $groups[] = [
                'id' => (int) $g['id'],
                'name' => (string) $g['name'],
                'type' => (string) $g['option_type'],
                'required' => (int) $g['is_required'] === 1,
                'values' => $values[(int) $g['id']] ?? [],
            ];
        }
        json_ok(['product' => product_payload($p, $favIds) + ['groups' => $groups]]);

    case 'favorites':
        require_method('GET');
        $customer = tg_customer();
        $rows = db_all(
            PRODUCT_BASE_SQL . ' JOIN favorites f ON f.product_id = p.id AND f.user_id = :u
             WHERE ' . PRODUCT_VISIBLE_SQL . ' ORDER BY f.created_at DESC',
            [':u' => $customer['id']]
        );
        json_ok(['products' => array_map(static fn(array $p): array => product_payload($p, $favIds + [(int) $p['id'] => true]), $rows)]);

    case 'favorite':
        require_method('POST');
        $customer = tg_customer();
        $productId = v_int(input_value('product_id'), 1, PHP_INT_MAX);
        $on = v_bool(input_value('on', false));
        if ($productId === null || db_one('SELECT id FROM products WHERE id = :id AND is_active = 1', [':id' => $productId]) === null) {
            json_fail('Mahsulot topilmadi', 404);
        }
        if ($on) {
            db_exec('INSERT IGNORE INTO favorites (user_id, product_id) VALUES (:u, :p)', [':u' => $customer['id'], ':p' => $productId]);
        } else {
            db_exec('DELETE FROM favorites WHERE user_id = :u AND product_id = :p', [':u' => $customer['id'], ':p' => $productId]);
        }
        json_ok(['is_favorite' => $on]);

    default:
        json_fail('Noto‘g‘ri action', 400);
}
