<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/includes/bootstrap.php';
admin_session_start();
require_method('POST');
csrf_require();

if (admin_current() !== null) {
    admin_log('logout', 'admin', (string) ($_SESSION['admin_id'] ?? ''));
}
admin_logout();
redirect('/admin/login.php');
