<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
send_security_headers('admin');
admin_session_start();

if (admin_current() !== null) {
    redirect(admin_current()['role'] === 'kitchen' ? '/admin/kitchen.php' : '/admin/dashboard.php');
}

$error = '';
$locked = false;
$failKey = 'login_fail';
$lockKey = 'login_locked_until';

if (($_SESSION[$lockKey] ?? 0) > time()) {
    $locked = true;
    $error = 'Juda ko‘p noto‘g‘ri urinish. 15 daqiqadan keyin qayta urinib ko‘ring.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$locked) {
    csrf_require();
    $username = v_str((string) ($_POST['username'] ?? ''), 50) ?? '';
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Login va parolni kiriting';
    } elseif (($admin = admin_login($username, $password)) !== null) {
        unset($_SESSION[$failKey], $_SESSION[$lockKey]);
        redirect($admin['role'] === 'kitchen' ? '/admin/kitchen.php' : '/admin/dashboard.php');
    } else {
        $_SESSION[$failKey] = (int) ($_SESSION[$failKey] ?? 0) + 1;
        if ($_SESSION[$failKey] >= 5) {
            $_SESSION[$lockKey] = time() + 900;
            $_SESSION[$failKey] = 0;
            $error = 'Juda ko‘p noto‘g‘ri urinish. 15 daqiqaga bloklandi.';
        } else {
            $error = 'Login yoki parol noto‘g‘ri';
        }
    }
}

$brand = e(setting('restaurant_name', 'Dasturxon'));
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Kirish · Admin · <?= $brand ?></title>
<link rel="icon" href="/assets/images/icon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/admin.css?v=3">
</head>
<body class="admin">
<div class="login-wrap">
  <div class="card login-card">
    <div class="brand"><span class="brand-mark"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg></span><span><?= $brand ?><small>Boshqaruv paneli</small></span></div>
    <h2>Tizimga kirish</h2>
    <?php if ($error !== ''): ?><div class="alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="/admin/login.php" class="form" style="grid-template-columns:1fr;margin-top:10px" autocomplete="on">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <label class="field"><span>Login</span><input name="username" required maxlength="50" autocomplete="username" autofocus <?= $locked ? 'disabled' : '' ?>></label>
      <label class="field"><span>Parol</span><input name="password" type="password" required maxlength="200" autocomplete="current-password" <?= $locked ? 'disabled' : '' ?>></label>
      <button class="btn block lg" type="submit" <?= $locked ? 'disabled' : '' ?>>Kirish</button>
    </form>
    <p class="hint" style="margin-top:14px">Hisob yaratish: serverda <code>php tools/create_admin.php</code></p>
  </div>
</div>
</body>
</html>
