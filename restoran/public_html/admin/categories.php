<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

/* ---------- POST ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);

    switch ($action) {
        case 'save':
            $name = v_str((string) ($_POST['name'] ?? ''), 120, true);
            $slugInput = v_str((string) ($_POST['slug'] ?? ''), 120) ?? '';
            $sort = v_int($_POST['sort_order'] ?? 0, -9999, 9999, 0);
            $active = v_bool($_POST['is_active'] ?? false) ? 1 : 0;
            $back = $id ? '/admin/categories.php?edit=' . $id : '/admin/categories.php?new=1';
            if ($name === null || $name === '') {
                flash_set('error', 'Kategoriya nomini kiriting');
                redirect($back);
            }
            $slug = v_slug($slugInput !== '' ? $slugInput : $name);
            $clash = db_one('SELECT id FROM categories WHERE slug = :s AND id <> :id', [':s' => $slug, ':id' => $id ?? 0]);
            if ($clash !== null) {
                flash_set('error', 'Bu slug band: ' . $slug . '. Boshqa slug kiriting.');
                redirect($back);
            }

            $newImage = null;
            if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
                $saved = save_uploaded_image($_FILES['image'], 'categories');
                if (!$saved['ok']) {
                    flash_set('error', $saved['error']);
                    redirect($back);
                }
                $newImage = $saved['path'];
            }

            if ($id !== null) {
                $old = db_one('SELECT image FROM categories WHERE id = :id', [':id' => $id]);
                if ($old === null) {
                    redirect('/admin/categories.php');
                }
                db_exec(
                    'UPDATE categories SET name = :n, slug = :s, sort_order = :o, is_active = :a' . ($newImage ? ', image = :img' : '') . ' WHERE id = :id',
                    array_filter([':n' => $name, ':s' => $slug, ':o' => $sort, ':a' => $active, ':img' => $newImage, ':id' => $id], static fn($v) => $v !== null)
                );
                if ($newImage) {
                    delete_upload($old['image']);
                }
                admin_log('update_category', 'category', (string) $id, ['name' => $name]);
                flash_set('ok', 'Kategoriya saqlandi');
            } else {
                $newId = db_insert(
                    'INSERT INTO categories (slug, name, image, sort_order, is_active) VALUES (:s, :n, :img, :o, :a)',
                    [':s' => $slug, ':n' => $name, ':img' => $newImage, ':o' => $sort, ':a' => $active]
                );
                admin_log('create_category', 'category', (string) $newId, ['name' => $name]);
                flash_set('ok', 'Kategoriya yaratildi');
            }
            redirect('/admin/categories.php');

        case 'toggle':
            if ($id !== null) {
                db_exec('UPDATE categories SET is_active = 1 - is_active WHERE id = :id', [':id' => $id]);
                admin_log('toggle_category', 'category', (string) $id);
            }
            redirect('/admin/categories.php');

        case 'move':
            $dir = ($_POST['dir'] ?? '') === 'up' ? 'up' : 'down';
            $all = db_all('SELECT id, sort_order FROM categories ORDER BY sort_order, id');
            foreach ($all as $i => $row) {
                if ((int) $row['id'] !== $id) {
                    continue;
                }
                $j = $dir === 'up' ? $i - 1 : $i + 1;
                if (isset($all[$j])) {
                    // Tartib raqamlarini qayta yozamiz (teng bo‘lsa ham to‘g‘ri ishlaydi)
                    $order = $all;
                    [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
                    foreach ($order as $k => $r) {
                        db_exec('UPDATE categories SET sort_order = :o WHERE id = :id', [':o' => ($k + 1) * 10, ':id' => $r['id']]);
                    }
                    admin_log('reorder_category', 'category', (string) $id, ['dir' => $dir]);
                }
                break;
            }
            redirect('/admin/categories.php');

        case 'delete':
            $row = $id === null ? null : db_one('SELECT name, image FROM categories WHERE id = :id', [':id' => $id]);
            if ($row !== null) {
                $count = (int) db_val('SELECT COUNT(*) FROM products WHERE category_id = :id', [':id' => $id]);
                if ($count > 0) {
                    flash_set('error', '“' . $row['name'] . '” kategoriyasida ' . $count . ' ta mahsulot bor. Avval ularni boshqa kategoriyaga o‘tkazing.');
                } else {
                    db_exec('DELETE FROM categories WHERE id = :id', [':id' => $id]);
                    delete_upload($row['image']);
                    admin_log('delete_category', 'category', (string) $id, ['name' => $row['name']]);
                    flash_set('ok', 'Kategoriya o‘chirildi');
                }
            }
            redirect('/admin/categories.php');

        default:
            redirect('/admin/categories.php');
    }
}

/* ---------- GET ---------- */
$editId = v_int($_GET['edit'] ?? null, 1, PHP_INT_MAX);
$isNew = isset($_GET['new']);
$edit = $editId ? db_one('SELECT * FROM categories WHERE id = :id', [':id' => $editId]) : null;
$cats = db_all(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS products_count
     FROM categories c ORDER BY c.sort_order, c.id'
);

admin_head('Kategoriyalar', 'categories', $user);
?>
<div class="toolbar">
  <span class="muted">Mijozlar menyusidagi kategoriyalar. Tartibni ▲▼ tugmalari bilan o‘zgartiring.</span>
  <a class="btn" href="/admin/categories.php?new=1"><i data-lucide="plus"></i> Yangi kategoriya</a>
</div>

<?php if ($edit || $isNew): ?>
<section class="card">
  <h2><?= $edit ? 'Kategoriyani tahrirlash' : 'Yangi kategoriya' ?></h2>
  <form method="post" action="/admin/categories.php" enctype="multipart/form-data" class="form" style="margin-top:12px">
    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <label class="field"><span>Nomi *</span><input name="name" required maxlength="120" value="<?= e($edit['name'] ?? '') ?>"></label>
    <label class="field"><span>Slug (lotin, bo‘sh bo‘lsa avtomatik)</span><input name="slug" maxlength="120" value="<?= e($edit['slug'] ?? '') ?>" placeholder="shashlik"></label>
    <label class="field"><span>Tartib raqami</span><input name="sort_order" type="number" min="-9999" max="9999" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></label>
    <label class="field"><span>Rasm (ixtiyoriy)</span>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
      <?php if (!empty($edit['image'])): ?><img src="/<?= e($edit['image']) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:12px;margin-top:6px"><?php endif; ?>
    </label>
    <label class="check"><input type="checkbox" name="is_active" value="1" <?= !$edit || (int) $edit['is_active'] ? 'checked' : '' ?>> Faol (mijozlarga ko‘rinsin)</label>
    <div class="row-actions" style="grid-column:1/-1">
      <button class="btn" type="submit">Saqlash</button>
      <a class="btn ghost" href="/admin/categories.php">Bekor qilish</a>
    </div>
  </form>
</section>
<?php endif; ?>

<div class="table-wrap" style="margin-top:14px">
  <table>
    <thead><tr><th>Tartib</th><th>Nomi</th><th>Slug</th><th class="num">Mahsulot</th><th>Holat</th><th></th></tr></thead>
    <tbody>
    <?php if (!$cats): ?><tr><td colspan="6" class="empty">Kategoriyalar yo‘q</td></tr><?php endif; ?>
    <?php foreach ($cats as $i => $c): ?>
      <tr>
        <td>
          <div class="row-actions">
            <form method="post" action="/admin/categories.php" class="inline"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="dir" value="up"><button class="icon-btn" style="width:30px;height:30px" type="submit" aria-label="Yuqoriga" <?= $i === 0 ? 'disabled' : '' ?>>▲</button></form>
            <form method="post" action="/admin/categories.php" class="inline"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="move"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><input type="hidden" name="dir" value="down"><button class="icon-btn" style="width:30px;height:30px" type="submit" aria-label="Pastga" <?= $i === count($cats) - 1 ? 'disabled' : '' ?>>▼</button></form>
          </div>
        </td>
        <td><div style="display:flex;gap:10px;align-items:center"><?php if ($c['image']): ?><div class="thumb"><img src="/<?= e($c['image']) ?>" alt=""></div><?php endif; ?><strong><?= e($c['name']) ?></strong></div></td>
        <td><code><?= e($c['slug']) ?></code></td>
        <td class="num"><?= (int) $c['products_count'] ?></td>
        <td>
          <form method="post" action="/admin/categories.php" class="inline">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button class="pill <?= (int) $c['is_active'] ? 'on' : 'off' ?>" type="submit" style="cursor:pointer;border:0"><?= (int) $c['is_active'] ? 'Faol' : 'Yashirin' ?></button>
          </form>
        </td>
        <td>
          <div class="row-actions">
            <a class="btn ghost sm" href="/admin/categories.php?edit=<?= (int) $c['id'] ?>">Tahrirlash</a>
            <form method="post" action="/admin/categories.php" class="inline" data-confirm="Kategoriyani o‘chirasizmi?">
              <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <button class="btn danger sm" type="submit">O‘chirish</button>
            </form>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_foot();
