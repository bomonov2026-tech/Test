<?php
declare(strict_types=1);

/**
 * Admin yoki oshpaz hisobini yaratish (faqat CLI).
 *   php tools/create_admin.php
 * Parol password_hash() bilan xeshlanadi, ochiq holda saqlanmaydi.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Faqat CLI');
}

require dirname(__DIR__) . '/includes/bootstrap.php';

function ask(string $question): string
{
    echo $question;
    return trim((string) fgets(STDIN));
}

/** Parolni ekranga chiqarmasdan o‘qiydi (Linux/macOS terminalida). */
function ask_secret(string $question): string
{
    echo $question;
    @shell_exec('stty -echo 2>/dev/null');
    $value = trim((string) fgets(STDIN));
    @shell_exec('stty echo 2>/dev/null');
    echo "\n";
    return $value;
}

echo "=== Yangi xodim hisobi ===\n";
$username = ask('Login (lotin harflar, raqam, _ ; 3-50 belgi): ');
if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
    exit("Xato: login noto‘g‘ri.\n");
}
if (db_one('SELECT id FROM admins WHERE username = :u', [':u' => $username])) {
    exit("Xato: bunday login allaqachon mavjud.\n");
}
$fullName = (string) v_str(ask('To‘liq ismi: '), 120);
$role = strtolower(ask('Rol (admin / kitchen) [admin]: ')) ?: 'admin';
if (!in_array($role, ['admin', 'kitchen'], true)) {
    exit("Xato: rol faqat admin yoki kitchen bo‘lishi mumkin.\n");
}
$tgId = ask('Telegram ID (ixtiyoriy, bo‘sh qoldirish mumkin): ');
if ($tgId !== '' && !preg_match('/^-?\d{5,15}$/', $tgId)) {
    exit("Xato: Telegram ID raqam bo‘lishi kerak.\n");
}

$password = ask_secret('Parol (kamida 10 belgi): ');
$confirm = ask_secret('Parolni qayta kiriting: ');
if (strlen($password) < 10) {
    exit("Xato: parol kamida 10 belgi bo‘lishi kerak.\n");
}
if (!hash_equals($password, $confirm)) {
    exit("Xato: parollar mos kelmadi.\n");
}

db_insert(
    'INSERT INTO admins (username, password_hash, full_name, role, telegram_id) VALUES (:u, :h, :n, :r, :t)',
    [
        ':u' => $username,
        ':h' => password_hash($password, PASSWORD_DEFAULT),
        ':n' => $fullName,
        ':r' => $role,
        ':t' => $tgId !== '' ? (int) $tgId : null,
    ]
);
echo "Tayyor: '{$username}' ({$role}) hisobi yaratildi. Kirish: /admin/login.php\n";
