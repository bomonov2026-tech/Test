<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);

    switch ($action) {
        case 'save':
            $title = v_str((string) ($_POST['title'] ?? ''), 160, true);
            $subtitle = v_str((string) ($_POST['subtitle'] ?? ''), 255) ?? '';
            $productId = v_int($_POST['product_id'] ?? null, 1, PHP_INT_MAX);
            $sort = v_int($_POST['sort_order'] ?? 0, -9999, 9999, 0);
            $active = v_bool($_POST['is_active'] ?? false) ? 1 : 0;
            if ($title === null || $title === '') {
                flash_set('error', 'Banner sarlavhasini kiriting');
                redirect('/admin/banners.php');
            }
            if ($productId !== null && db_one('SELECT id FROM products WHERE id = :id', [':id' => $productId]) === null) {
                $productId = null;
            }
            $newImage = null;
            if (isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
                $saved = save_uploaded_image($_FILES['image'], 'banners');
                if (!$saved['ok']) {
                    flash_set('error', $saved['error']);
                    redirect('/admin/banners.php');
                }
                $newImage = $saved['path'];
            }
            if ($id !== null) {
                $old = db_one('SELECT image FROM banners WHERE id = :id', [':id' => $id]);
                db_exec(
                    'UPDATE banners SET title = :t, subtitle = :s, product_id = :p, sort_order = :o, is_active = :a'
                    . ($newImage ? ', image = :img' : '') . ' WHERE id = :id',
                    array_filter([':t' => $title, ':s' => $subtitle ?: null, ':p' => $productId, ':o' => $sort, ':a' => $active, ':img' => $newImage, ':id' => $id], static fn($v) => $v !== null)
                );
                if ($newImage && $old) {
                    delete_upload($old['image']);
                }
                admin_log('update_banner', 'banner', (string) $id);
                flash_set('ok', 'Banner saqlandi');
            } else {
                $newId = db_insert(
                    'INSERT INTO banners (title, subtitle, image, product_id, sort_order, is_active) VALUES (:t, :s, :img, :p, :o, :a)',
                    [':t' => $title, ':s' => $subtitle ?: null, ':img' => $newImage, ':p' => $productId, ':o' => $sort, ':a' => $active]
                );
                admin_log('create_banner', 'banner', (string) $newId);
                flash_set('ok', 'Banner qo‘shildi');
            }
            redirect('/admin/banners.php');

        case 'toggle':
            if ($id !== null) {
                db_exec('UPDATE banners SET is_active = 1 - is_active WHERE id = :id', [':id' => $id]);
                admin_log('toggle_banner', 'banner', (string) $id);
            }
            redirect('/admin/banners.php');

        case 'delete':
            $row = $id === null ? null : db_one('SELECT image FROM banners WHERE id = :id', [':id' => $id]);
            if ($row !== null) {
                db_exec('DELETE FROM banners WHERE id = :id', [':id' => $id]);
                delete_upload($row['image']);
                admin_log('delete_banner', 'banner', (string) $id);
                flash_set('ok', 'Banner o‘chirildi');
            }
            redirect('/admin/banners.php');

        default:
            redirect('/admin/banners.php');
    }
}

$editId = v_int($_GET['edit'] ?? null, 1, PHP_INT_MAX);
$edit = $editId ? db_one('SELECT * FROM banners WHERE id = :id', [':id' => $editId]) : null;
$banners = db_all(
    'SELECT b.*, p.name AS product_name FROM banners b LEFT JOIN products p ON p.id = b.product_id ORDER BY b.sort_order, b.id'
);
$products = db_all('SELECT id, name FROM products WHERE is_active = 1 ORDER BY name');

admin_head('Reklama bannerlari', 'banners', $user);
?>
<div class="grid two">
  <section class="card">
    <h2><?= $edit ? 'Bannerni tahrirlash' : 'Yangi banner' ?></h2>
    <form method="post" action="/admin/banners.php" enctype="multipart/form-data" class="form" style="margin-top:12px;grid-template-columns:1fr">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
      <label class="field"><span>Sarlavha *</span><input name="title" required maxlength="160" value="<?= e($edit['title'] ?? '') ?>"></label>
      <label class="field"><span>Qo‘shimcha matn</span><input name="subtitle" maxlength="255" value="<?= e($edit['subtitle'] ?? '') ?>"></label>
      <label class="field"><span>Bosilganda ochiladigan mahsulot</span>
        <select name="product_id"><option value="">— menyu —</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) ($edit['product_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field"><span>Tartib raqami</span><input name="sort_order" type="number" min="-9999" max="9999" value="<?= (int) ($edit['sort_order'] ?? 0) ?>"></label>
      <label class="field"><span>Rasm (ixtiyoriy, JPG/PNG/WEBP, 5 MB)</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= !$edit || (int) $edit['is_active'] ? 'checked' : '' ?>> Faol</label>
      <div class="row-actions"><button class="btn" type="submit">Saqlash</button><?php if ($edit): ?><a class="btn ghost" href="/admin/banners.php">Bekor qilish</a><?php endif; ?></div>
    </form>
  </section>

  <section class="card">
    <h2>Bannerlar</h2>
    <p class="hint" style="margin:6px 0 12px">Rasm yuklanmasa, bannerda sarlavha matn ko‘rinishida chiqadi.</p>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Banner</th><th>Mahsulot</th><th>Holat</th><th></th></tr></thead>
        <tbody>
        <?php if (!$banners): ?><tr><td colspan="4" class="empty">Bannerlar yo‘q</td></tr><?php endif; ?>
        <?php foreach ($banners as $b): ?>
          <tr>
            <td><div style="display:flex;gap:10px;align-items:center"><?php if ($b['image']): ?><div class="thumb" style="width:64px;height:40px"><img src="/<?= e($b['image']) ?>" alt=""></div><?php endif; ?><div><strong><?= e($b['title']) ?></strong><br><small class="muted"><?= e((string) $b['subtitle']) ?></small></div></div></td>
            <td><small><?= e($b['product_name'] ?? '—') ?></small></td>
            <td>
              <form method="post" action="/admin/banners.php" class="inline">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                <button class="pill <?= (int) $b['is_active'] ? 'on' : 'off' ?>" type="submit" style="cursor:pointer;border:0"><?= (int) $b['is_active'] ? 'Faol' : 'Yashirin' ?></button>
              </form>
            </td>
            <td class="row-actions">
              <a class="btn ghost sm" href="/admin/banners.php?edit=<?= (int) $b['id'] ?>">Tahrirlash</a>
              <form method="post" action="/admin/banners.php" class="inline" data-confirm="Banner o‘chirilsinmi?">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                <button class="btn danger sm" type="submit">O‘chirish</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php admin_foot();
