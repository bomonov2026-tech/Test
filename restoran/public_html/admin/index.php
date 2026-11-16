<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$user = admin_current();
redirect($user === null ? '/admin/login.php' : ($user['role'] === 'kitchen' ? '/admin/kitchen.php' : '/admin/dashboard.php'));
