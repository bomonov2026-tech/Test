<?php
declare(strict_types=1);

/** GET — faol kategoriyalar ro‘yxati (mahsulot soni bilan). */

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_method('GET');

$rows = db_all(
    'SELECT c.id, c.slug, c.name, c.image, COUNT(p.id) AS total
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1 AND p.is_available = 1
     WHERE c.is_active = 1
     GROUP BY c.id ORDER BY c.sort_order, c.id'
);

json_ok([
    'categories' => array_map(static fn(array $c): array => [
        'id' => (int) $c['id'],
        'slug' => (string) $c['slug'],
        'name' => (string) $c['name'],
        'image' => asset_url($c['image']),
        'count' => (int) $c['total'],
    ], $rows),
]);
