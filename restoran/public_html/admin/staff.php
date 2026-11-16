<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_require(['admin']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    $id = v_int($_POST['id'] ?? null, 1, PHP_INT_MAX);

    switch ($action) {
        case 'create':
            $username = (string) ($_POST['username'] ?? '');
            $fullName = v_str((string) ($_POST['full_name'] ?? ''), 120) ?? '';
            $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'kitchen';
            $password = (string) ($_POST['password'] ?? '');
            $tg = trim((string) ($_POST['telegram_id'] ?? ''));
            if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
                flash_set('error', 'Login: 3–50 ta lotin harf, raqam yoki _');
            } elseif (strlen($password) < 10) {
                flash_set('error', 'Parol kamida 10 belgi bo‘lishi kerak');
            } elseif ($tg !== '' && !preg_match('/^-?\d{5,15}$/', $tg)) {
                flash_set('error', 'Telegram ID noto‘g‘ri');
            } elseif (db_one('SELECT id FROM admins WHERE username = :u', [':u' => $username]) !== null) {
                flash_set('error', 'Bunday login mavjud');
            } else {
                $newId = db_insert(
                    'INSERT INTO admins (username, password_hash, full_name, role, telegram_id) VALUES (:u, :h, :n, :r, :t)',
                    [':u' => $username, ':h' => password_hash($password, PASSWORD_DEFAULT), ':n' => $fullName, ':r' => $role, ':t' => $tg !== '' ? (int) $tg : null]
                );
                admin_log('create_staff', 'admin', (string) $newId, ['role' => $role]);
                flash_set('ok', 'Xodim qo‘shildi');
            }
            redirect('/admin/staff.php');

        case 'toggle':
            if ($id !== null && $id !== (int) $user['id']) {
                db_exec('UPDATE admins SET is_active = 1 - is_active WHERE id = :id', [':id' => $id]);
                admin_log('toggle_staff', 'admin', (string) $id);
                flash_set('ok', 'Xodim holati o‘zgartirildi');
            } else {
                flash_set('error', 'O‘zingizni bloklab bo‘lmaydi');
            }
            redirect('/admin/staff.php');

        case 'reset_password':
            $password = (string) ($_POST['password'] ?? '');
            if ($id !== null && strlen($password) >= 10) {
                db_exec('UPDATE admins SET password_hash = :h WHERE id = :id', [':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
                admin_log('reset_staff_password', 'admin', (string) $id);
                flash_set('ok', 'Parol yangilandi');
            } else {
                flash_set('error', 'Parol kamida 10 belgi bo‘lishi kerak');
            }
            redirect('/admin/staff.php');

        default:
            redirect('/admin/staff.php');
    }
}

$staff = db_all('SELECT id, username, full_name, role, telegram_id, is_active, last_login_at FROM admins ORDER BY role, id');

admin_head('Xodimlar', 'staff', $user);
?>
<div class="grid two">
  <section class="card">
    <h2>Xodimlar</h2>
    <div class="table-wrap" style="margin-top:12px">
      <table>
        <thead><tr><th>Login</th><th>Ism</th><th>Rol</th><th>Oxirgi kirish</th><th>Holat</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($staff as $s): ?>
          <tr>
            <td><strong><?= e($s['username']) ?></strong><?= (int) $s['id'] === (int) $user['id'] ? ' <small class="muted">(siz)</small>' : '' ?></td>
            <td><?= e($s['full_name']) ?></td>
            <td><span class="pill"><?= $s['role'] === 'admin' ? 'Administrator' : 'Oshpaz' ?></span></td>
            <td><small><?= e($s['last_login_at'] ? substr((string) $s['last_login_at'], 0, 16) : '—') ?></small></td>
            <td><span class="pill <?= (int) $s['is_active'] ? 'on' : 'off' ?>"><?= (int) $s['is_active'] ? 'Faol' : 'Bloklangan' ?></span></td>
            <td>
              <div class="row-actions">
                <?php if ((int) $s['id'] !== (int) $user['id']): ?>
                <form method="post" action="/admin/staff.php" class="inline">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                  <button class="btn ghost sm" type="submit"><?= (int) $s['is_active'] ? 'Bloklash' : 'Faollashtirish' ?></button>
                </form>
                <?php endif; ?>
                <form method="post" action="/admin/staff.php" class="inline" style="display:flex;gap:6px" data-confirm="Parolni almashtirasizmi?">
                  <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                  <input class="input" type="password" name="password" minlength="10" placeholder="Yangi parol" style="width:130px;min-height:30px;padding:4px 8px" aria-label="Yangi parol">
                  <button class="btn ghost sm" type="submit">Almashtirish</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <h2>Yangi xodim</h2>
    <form method="post" action="/admin/staff.php" class="form" style="grid-template-columns:1fr;margin-top:12px">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="create">
      <label class="field"><span>Login (3–50 belgi)</span><input name="username" required pattern="[a-zA-Z0-9_]{3,50}" maxlength="50" autocomplete="off"></label>
      <label class="field"><span>To‘liq ismi</span><input name="full_name" maxlength="120"></label>
      <label class="field"><span>Rol</span>
        <select name="role"><option value="kitchen">Oshpaz (faqat oshxona ekrani)</option><option value="admin">Administrator (to‘liq huquq)</option></select>
      </label>
      <label class="field"><span>Parol (kamida 10 belgi)</span><input name="password" type="password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
      <label class="field"><span>Telegram ID (ixtiyoriy, bildirishnoma uchun)</span><input name="telegram_id" inputmode="numeric" maxlength="16"></label>
      <button class="btn" type="submit">Qo‘shish</button>
    </form>
  </section>
</div>
<?php admin_foot();
