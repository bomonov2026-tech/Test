<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

const PRODUCT_FLAGS = ['is_active', 'is_available', 'is_featured', 'is_recommended', 'is_new', 'is_combo'];

/* ---------- POST harakatlari ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');

    switch ($action) {
        case 'save_product':
            $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);
            $name = v_str((string) ($_POST['name'] ?? ''), 160, true);
            $description = v_str((string) ($_POST['description'] ?? ''), 2000);
            $price = v_int($_POST['price'] ?? null, 0, 100000000);
            $discount = v_int($_POST['discount_percent'] ?? 0, 0, 90, 0);
            $unit = v_str((string) ($_POST['unit'] ?? 'dona'), 40) ?: 'dona';
            $sort = v_int($_POST['sort_order'] ?? 0, -9999, 9999, 0);
            $categoryId = v_int($_POST['category_id'] ?? null, 1, PHP_INT_MAX);
            $back = $id ? '/admin/products.php?id=' . $id : '/admin/products.php?new=1';

            if ($name === null || $name === '') {
                flash_set('error', 'Nomini kiriting');
                redirect($back);
            }
            if ($price === null) {
                flash_set('error', 'Narx 0 dan katta butun son bo‘lishi kerak');
                redirect($back);
            }
            if ($description === null) {
                flash_set('error', 'Tavsif juda uzun');
                redirect($back);
            }
            if ($categoryId !== null && db_one('SELECT id FROM categories WHERE id = :id', [':id' => $categoryId]) === null) {
                flash_set('error', 'Kategoriya topilmadi');
                redirect($back);
            }

            $newImage = null;
            if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
                $saved = save_uploaded_image($_FILES['image'], 'products');
                if (!$saved['ok']) {
                    flash_set('error', $saved['error']);
                    redirect($back);
                }
                $newImage = $saved['path'];
            }

            $flags = [];
            foreach (PRODUCT_FLAGS as $flag) {
                $flags[$flag] = v_bool($_POST[$flag] ?? false) ? 1 : 0;
            }

            $params = [
                ':cat' => $categoryId, ':name' => $name, ':descr' => $description !== '' ? $description : null,
                ':price' => $price, ':disc' => $discount, ':unit' => $unit, ':sort' => $sort,
                ':act' => $flags['is_active'], ':avail' => $flags['is_available'], ':feat' => $flags['is_featured'],
                ':rec' => $flags['is_recommended'], ':new' => $flags['is_new'], ':combo' => $flags['is_combo'],
            ];

            if ($id !== null) {
                $old = db_one('SELECT image FROM products WHERE id = :id', [':id' => $id]);
                if ($old === null) {
                    flash_set('error', 'Mahsulot topilmadi');
                    redirect('/admin/products.php');
                }
                $sql = 'UPDATE products SET category_id = :cat, name = :name, description = :descr, price = :price,
                        discount_percent = :disc, unit = :unit, sort_order = :sort, is_active = :act, is_available = :avail,
                        is_featured = :feat, is_recommended = :rec, is_new = :new, is_combo = :combo'
                    . ($newImage !== null ? ', image = :img' : '') . ' WHERE id = :id';
                $params[':id'] = $id;
                if ($newImage !== null) {
                    $params[':img'] = $newImage;
                }
                db_exec($sql, $params);
                if ($newImage !== null) {
                    delete_upload($old['image'] ?? null);
                }
                admin_log('update_product', 'product', (string) $id, ['name' => $name, 'price' => $price]);
                flash_set('ok', 'Mahsulot saqlandi');
                redirect('/admin/products.php?id=' . $id);
            }

            $params[':img'] = $newImage;
            $newId = db_insert(
                'INSERT INTO products (category_id, name, description, price, discount_percent, unit, sort_order, is_active,
                    is_available, is_featured, is_recommended, is_new, is_combo, image)
                 VALUES (:cat, :name, :descr, :price, :disc, :unit, :sort, :act, :avail, :feat, :rec, :new, :combo, :img)',
                $params
            );
            admin_log('create_product', 'product', (string) $newId, ['name' => $name]);
            flash_set('ok', 'Mahsulot qo‘shildi. Endi qo‘shimchalarni sozlashingiz mumkin.');
            redirect('/admin/products.php?id=' . $newId);

        case 'delete_product':
            $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);
            $row = $id === null ? null : db_one('SELECT name, image FROM products WHERE id = :id', [':id' => $id]);
            if ($row !== null) {
                db_exec('DELETE FROM products WHERE id = :id', [':id' => $id]);
                delete_upload($row['image']);
                admin_log('delete_product', 'product', (string) $id, ['name' => $row['name']]);
                flash_set('ok', 'Mahsulot o‘chirildi');
            }
            redirect('/admin/products.php');

        case 'toggle_product':
            $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);
            $field = (string) ($_POST['field'] ?? '');
            if ($id !== null && in_array($field, PRODUCT_FLAGS, true)) {
                db_exec("UPDATE products SET {$field} = 1 - {$field} WHERE id = :id", [':id' => $id]);
                admin_log('toggle_product', 'product', (string) $id, ['field' => $field]);
            }
            redirect('/admin/products.php' . (($_POST['qs'] ?? '') !== '' ? '?' . http_build_query(['q' => substr((string) $_POST['qs'], 0, 80)]) : ''));

        case 'add_group':
            $pid = v_int($_POST['product_id'] ?? null, 1, PHP_INT_MAX);
            $gname = v_str((string) ($_POST['group_name'] ?? ''), 120, true);
            $gtype = ($_POST['group_type'] ?? '') === 'single' ? 'single' : 'multiple';
            if ($pid === null || $gname === null || $gname === '') {
                flash_set('error', 'Guruh nomini kiriting');
            } else {
                db_insert(
                    'INSERT INTO product_options (product_id, name, option_type, is_required, sort_order) VALUES (:p, :n, :t, :r, :s)',
                    [':p' => $pid, ':n' => $gname, ':t' => $gtype, ':r' => v_bool($_POST['group_required'] ?? false) ? 1 : 0,
                     ':s' => (int) db_val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_options WHERE product_id = :p', [':p' => $pid])]
                );
                admin_log('add_option_group', 'product', (string) $pid, ['group' => $gname]);
                flash_set('ok', 'Qo‘shimcha guruhi qo‘shildi');
            }
            redirect('/admin/products.php?id=' . (int) $pid);

        case 'delete_group':
            $gid = v_int($_POST['group_id'] ?? null, 1, PHP_INT_MAX);
            $g = $gid === null ? null : db_one('SELECT product_id FROM product_options WHERE id = :id', [':id' => $gid]);
            if ($g !== null) {
                db_exec('DELETE FROM product_options WHERE id = :id', [':id' => $gid]);
                admin_log('delete_option_group', 'product', (string) $g['product_id'], ['group_id' => $gid]);
                flash_set('ok', 'Guruh o‘chirildi');
                redirect('/admin/products.php?id=' . (int) $g['product_id']);
            }
            redirect('/admin/products.php');

        case 'add_value':
            $gid = v_int($_POST['group_id'] ?? null, 1, PHP_INT_MAX);
            $vname = v_str((string) ($_POST['value_name'] ?? ''), 120, true);
            $vprice = v_int($_POST['value_price'] ?? 0, 0, 100000000, 0);
            $g = $gid === null ? null : db_one('SELECT product_id FROM product_options WHERE id = :id', [':id' => $gid]);
            if ($g === null) {
                redirect('/admin/products.php');
            }
            if ($vname === null || $vname === '') {
                flash_set('error', 'Qo‘shimcha nomini kiriting');
            } else {
                db_insert(
                    'INSERT INTO product_option_values (option_id, name, price, sort_order) VALUES (:o, :n, :p, :s)',
                    [':o' => $gid, ':n' => $vname, ':p' => $vprice,
                     ':s' => (int) db_val('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_option_values WHERE option_id = :o', [':o' => $gid])]
                );
                admin_log('add_option_value', 'product', (string) $g['product_id'], ['value' => $vname, 'price' => $vprice]);
                flash_set('ok', 'Qo‘shimcha qo‘shildi');
            }
            redirect('/admin/products.php?id=' . (int) $g['product_id']);

        case 'toggle_value':
        case 'delete_value':
            $vid = v_int($_POST['value_id'] ?? null, 1, PHP_INT_MAX);
            $v = $vid === null ? null : db_one(
                'SELECT v.id, o.product_id FROM product_option_values v JOIN product_options o ON o.id = v.option_id WHERE v.id = :id',
                [':id' => $vid]
            );
            if ($v !== null) {
                if ($action === 'delete_value') {
                    db_exec('DELETE FROM product_option_values WHERE id = :id', [':id' => $vid]);
                } else {
                    db_exec('UPDATE product_option_values SET is_available = 1 - is_available WHERE id = :id', [':id' => $vid]);
                }
                admin_log($action, 'product', (string) $v['product_id'], ['value_id' => $vid]);
                redirect('/admin/products.php?id=' . (int) $v['product_id']);
            }
            redirect('/admin/products.php');

        default:
            redirect('/admin/products.php');
    }
}

/* ---------- GET: ro‘yxat yoki tahrir ---------- */
$editId = v_int($_GET['id'] ?? null, 1, PHP_INT_MAX);
$isNew = isset($_GET['new']);
$categories = db_all('SELECT id, name FROM categories ORDER BY sort_order, id');

admin_head($editId || $isNew ? ($editId ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot') : 'Mahsulotlar', 'products', $user);

if ($editId || $isNew):
    $p = $editId ? db_one('SELECT * FROM products WHERE id = :id', [':id' => $editId]) : null;
    if ($editId && $p === null) {
        echo '<div class="card empty">Mahsulot topilmadi. <a href="/admin/products.php">Ro‘yxatga qaytish</a></div>';
        admin_foot();
        exit;
    }
    $p = $p ?? ['id' => '', 'category_id' => '', 'name' => '', 'description' => '', 'price' => '', 'discount_percent' => 0,
        'unit' => 'dona', 'sort_order' => 0, 'image' => null, 'is_active' => 1, 'is_available' => 1,
        'is_featured' => 0, 'is_recommended' => 0, 'is_new' => 0, 'is_combo' => 0];
    $groups = [];
    if ($editId) {
        $values = [];
        foreach (db_all(
            'SELECT v.* FROM product_option_values v JOIN product_options o ON o.id = v.option_id
             WHERE o.product_id = :p ORDER BY v.sort_order, v.id',
            [':p' => $editId]
        ) as $v) {
            $values[(int) $v['option_id']][] = $v;
        }
        foreach (db_all('SELECT * FROM product_options WHERE product_id = :p ORDER BY sort_order, id', [':p' => $editId]) as $g) {
            $g['values'] = $values[(int) $g['id']] ?? [];
            $groups[] = $g;
        }
    }
    ?>
    <div class="toolbar">
      <a class="btn ghost sm" href="/admin/products.php">‹ Mahsulotlar</a>
    </div>
    <div class="grid two">
      <section class="card">
        <h2><?= $editId ? 'Mahsulot ma’lumotlari' : 'Yangi mahsulot' ?></h2>
        <form method="post" action="/admin/products.php" enctype="multipart/form-data" class="form">
          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="save_product">
          <input type="hidden" name="id" value="<?= e((string) $p['id']) ?>">
          <label class="field full"><span>Nomi *</span><input name="name" required maxlength="160" value="<?= e((string) $p['name']) ?>"></label>
          <label class="field"><span>Kategoriya</span>
            <select name="category_id">
              <option value="">— kategoriyasiz —</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (string) $p['category_id'] === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="field"><span>Narxi (so‘m) *</span><input name="price" type="number" min="0" max="100000000" step="100" required value="<?= e((string) $p['price']) ?>"></label>
          <label class="field"><span>Chegirma (%)</span><input name="discount_percent" type="number" min="0" max="90" value="<?= (int) $p['discount_percent'] ?>"></label>
          <label class="field"><span>O‘lchov birligi</span><input name="unit" maxlength="40" value="<?= e((string) $p['unit']) ?>" placeholder="dona, porsiya, 0.5 l"></label>
          <label class="field"><span>Tartib raqami</span><input name="sort_order" type="number" min="-9999" max="9999" value="<?= (int) $p['sort_order'] ?>"></label>
          <label class="field full"><span>Tavsifi</span><textarea name="description" maxlength="2000"><?= e((string) $p['description']) ?></textarea></label>
          <label class="field full"><span>Rasm (JPG, PNG, WEBP — 5 MB gacha)</span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($p['image'])): ?><img src="/<?= e($p['image']) ?>" alt="" style="width:120px;height:90px;object-fit:cover;border-radius:10px;margin-top:6px"><?php endif; ?>
          </label>
          <fieldset class="full" style="border:0;padding:0;margin:0">
            <div class="checks">
              <label class="check"><input type="checkbox" name="is_active" value="1" <?= (int) $p['is_active'] ? 'checked' : '' ?>> Menyuda ko‘rinsin</label>
              <label class="check"><input type="checkbox" name="is_available" value="1" <?= (int) $p['is_available'] ? 'checked' : '' ?>> Mavjud (sotuvda)</label>
              <label class="check"><input type="checkbox" name="is_featured" value="1" <?= (int) $p['is_featured'] ? 'checked' : '' ?>> Mashhur</label>
              <label class="check"><input type="checkbox" name="is_recommended" value="1" <?= (int) $p['is_recommended'] ? 'checked' : '' ?>> Tavsiya etilgan</label>
              <label class="check"><input type="checkbox" name="is_new" value="1" <?= (int) $p['is_new'] ? 'checked' : '' ?>> Yangi</label>
              <label class="check"><input type="checkbox" name="is_combo" value="1" <?= (int) $p['is_combo'] ? 'checked' : '' ?>> Kombo</label>
            </div>
          </fieldset>
          <div class="full"><button class="btn lg" type="submit">Saqlash</button></div>
        </form>
        <?php if ($editId): ?>
          <form method="post" action="/admin/products.php" style="margin-top:14px" data-confirm="Mahsulotni butunlay o‘chirasizmi?">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="id" value="<?= (int) $editId ?>">
            <button class="btn danger sm" type="submit">Mahsulotni o‘chirish</button>
          </form>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2>Qo‘shimchalar</h2>
        <p class="hint" style="margin-bottom:12px">Masalan: “Qo‘shimchalar” guruhida Piyoz (0 so‘m), Achchiq sous (2 000 so‘m). “Majburiy” guruhda mijoz kamida bittasini tanlashi shart.</p>
        <?php if (!$editId): ?>
          <p class="empty">Qo‘shimchalarni mahsulotni saqlagandan keyin qo‘shing.</p>
        <?php else: ?>
          <?php foreach ($groups as $g): ?>
            <div class="card flat" style="margin-bottom:12px">
              <div class="row-actions" style="justify-content:space-between;margin-bottom:8px">
                <strong><?= e($g['name']) ?></strong>
                <span class="pill"><?= $g['option_type'] === 'single' ? 'bitta' : 'bir nechta' ?> · <?= (int) $g['is_required'] ? 'majburiy' : 'ixtiyoriy' ?></span>
                <form method="post" action="/admin/products.php" class="inline" data-confirm="Guruh va uning qiymatlari o‘chirilsinmi?">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete_group">
                  <input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                  <button class="btn danger sm" type="submit">O‘chirish</button>
                </form>
              </div>
              <div class="table-wrap">
                <table>
                  <thead><tr><th>Nomi</th><th class="num">Narxi</th><th>Holat</th><th></th></tr></thead>
                  <tbody>
                  <?php if (!$g['values']): ?><tr><td colspan="4" class="muted">Qiymatlar yo‘q</td></tr><?php endif; ?>
                  <?php foreach ($g['values'] as $v): ?>
                    <tr>
                      <td><?= e($v['name']) ?></td>
                      <td class="num"><?= e(money((int) $v['price'])) ?></td>
                      <td><span class="pill <?= (int) $v['is_available'] ? 'on' : 'off' ?>"><?= (int) $v['is_available'] ? 'Mavjud' : 'Tugagan' ?></span></td>
                      <td class="row-actions">
                        <form method="post" action="/admin/products.php" class="inline">
                          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="action" value="toggle_value">
                          <input type="hidden" name="value_id" value="<?= (int) $v['id'] ?>">
                          <button class="btn ghost sm" type="submit"><?= (int) $v['is_available'] ? 'Tugadi' : 'Mavjud' ?></button>
                        </form>
                        <form method="post" action="/admin/products.php" class="inline" data-confirm="Qiymat o‘chirilsinmi?">
                          <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                          <input type="hidden" name="action" value="delete_value">
                          <input type="hidden" name="value_id" value="<?= (int) $v['id'] ?>">
                          <button class="btn danger sm" type="submit">×</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <form method="post" action="/admin/products.php" class="form" style="margin-top:10px;grid-template-columns:2fr 1fr auto">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="add_value">
                <input type="hidden" name="group_id" value="<?= (int) $g['id'] ?>">
                <input class="input" name="value_name" maxlength="120" placeholder="Qiymat nomi" required>
                <input class="input" name="value_price" type="number" min="0" max="100000000" step="100" value="0" aria-label="Narxi">
                <button class="btn sm" type="submit">Qo‘shish</button>
              </form>
            </div>
          <?php endforeach; ?>
          <form method="post" action="/admin/products.php" class="card flat form" style="grid-template-columns:1fr 1fr">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_group">
            <input type="hidden" name="product_id" value="<?= (int) $editId ?>">
            <label class="field"><span>Yangi guruh nomi</span><input name="group_name" maxlength="120" required placeholder="Qo‘shimchalar"></label>
            <label class="field"><span>Turi</span>
              <select name="group_type"><option value="multiple">Bir nechta tanlash</option><option value="single">Bitta tanlash</option></select>
            </label>
            <label class="check full"><input type="checkbox" name="group_required" value="1"> Majburiy tanlov</label>
            <div class="full"><button class="btn" type="submit">Guruh qo‘shish</button></div>
          </form>
        <?php endif; ?>
      </section>
    </div>
    <?php
    admin_foot();
    exit;
endif;

/* ---------- Ro‘yxat ---------- */
$q = v_str((string) ($_GET['q'] ?? ''), 80) ?? '';
$catFilter = v_int($_GET['cat'] ?? null, 1, PHP_INT_MAX);
$where = [];
$params = [];
if ($q !== '') {
    $where[] = 'p.name LIKE :q';
    $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
}
if ($catFilter !== null) {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $catFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = (int) db_val("SELECT COUNT(*) FROM products p {$whereSql}", $params);
[$limit, $offset, $page, $pages] = paginate($total, 30, (int) ($_GET['page'] ?? 1));
$rows = db_all(
    "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id
     {$whereSql} ORDER BY p.sort_order, p.id LIMIT {$limit} OFFSET {$offset}",
    $params
);
$qs = http_build_query(array_filter(['q' => $q, 'cat' => $catFilter]));
?>
<div class="toolbar">
  <form method="get" action="/admin/products.php" style="display:flex;gap:8px;flex-wrap:wrap">
    <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Mahsulot nomi" style="max-width:240px">
    <select class="input" name="cat">
      <option value="">Barcha kategoriyalar</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn ghost" type="submit">Filtrlash</button>
  </form>
  <a class="btn" href="/admin/products.php?new=1"><i data-lucide="plus"></i> Yangi mahsulot</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Rasm</th><th>Mahsulot</th><th>Kategoriya</th><th class="num">Narxi</th><th>O‘lchov</th>
        <th>Sotuvda</th><th>Menyuda</th><th>Belgilar</th><th class="num">Tartib</th><th></th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="10" class="empty">Mahsulot topilmadi</td></tr><?php endif; ?>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><div class="thumb"><?php if ($r['image']): ?><img src="/<?= e($r['image']) ?>" alt="" loading="lazy"><?php else: ?><i data-lucide="image"></i><?php endif; ?></div></td>
        <td><a href="/admin/products.php?id=<?= (int) $r['id'] ?>"><strong><?= e($r['name']) ?></strong></a></td>
        <td><?= e($r['category_name'] ?? '—') ?></td>
        <td class="num"><?= e(money((int) $r['price'])) ?><?php if ((int) $r['discount_percent'] > 0): ?><br><small class="muted">−<?= (int) $r['discount_percent'] ?>%</small><?php endif; ?></td>
        <td><?= e($r['unit']) ?></td>
        <td>
          <form method="post" action="/admin/products.php" class="inline">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_product"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="field" value="is_available"><input type="hidden" name="qs" value="<?= e($q) ?>">
            <button class="pill <?= (int) $r['is_available'] ? 'on' : 'off' ?>" type="submit" style="cursor:pointer;border:0"><?= (int) $r['is_available'] ? 'Mavjud' : 'Tugagan' ?></button>
          </form>
        </td>
        <td>
          <form method="post" action="/admin/products.php" class="inline">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="toggle_product"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <input type="hidden" name="field" value="is_active"><input type="hidden" name="qs" value="<?= e($q) ?>">
            <button class="pill <?= (int) $r['is_active'] ? 'on' : 'off' ?>" type="submit" style="cursor:pointer;border:0"><?= (int) $r['is_active'] ? 'Ko‘rinadi' : 'Yashirin' ?></button>
          </form>
        </td>
        <td>
          <?php
          $flagLabels = ['is_featured' => 'Mashhur', 'is_recommended' => 'Tavsiya', 'is_new' => 'Yangi', 'is_combo' => 'Kombo'];
          foreach ($flagLabels as $f => $label) {
              if ((int) $r[$f] === 1) {
                  echo '<span class="pill" style="margin:0 3px 3px 0">' . e($label) . '</span>';
              }
          }
          ?>
        </td>
        <td class="num"><?= (int) $r['sort_order'] ?></td>
        <td>
          <div class="row-actions">
            <a class="btn ghost sm" href="/admin/products.php?id=<?= (int) $r['id'] ?>">Tahrirlash</a>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<div class="toolbar">
  <span class="muted">Jami: <?= $total ?> ta</span>
  <?= pagination_html($page, $pages, $qs) ?>
</div>
<?php admin_foot();
