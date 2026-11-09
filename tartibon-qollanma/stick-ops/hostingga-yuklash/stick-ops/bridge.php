<?php
/* PHP 7.4+. Called only after the existing TartibOn auth_user()/phone checks. */
function ops_session_response($user) {
    $base = array(
        'botUsername' => defined('BOT_USERNAME') ? BOT_USERNAME : '',
        'appUrl' => defined('APP_URL') ? APP_URL : '',
        'name' => trim((isset($user['first_name']) ? $user['first_name'] : '') . ' ' . (isset($user['last_name']) ? $user['last_name'] : ''))
    );
    $file = dirname(__DIR__) . '/ops-config.php';
    if (!is_file($file)) {
        out(array_merge($base, array('ok' => false, 'error' => "Online server hali sozlanmagan. Hozircha mashq rejimida o'ynashingiz mumkin.")), 503);
    }
    $config = require $file;
    $url = is_array($config) && isset($config['ws_url']) ? (string)$config['ws_url'] : '';
    $secret = is_array($config) && isset($config['shared_secret']) ? (string)$config['shared_secret'] : '';
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'wss' ||
        isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) ||
        strlen($secret) < 32 || strpos($secret, 'REPLACE_') !== false || strpos($url, '.example') !== false) {
        out(array_merge($base, array('ok' => false, 'error' => "Online server sozlamalari tayyor emas. Mashq rejimi ishlaydi.")), 503);
    }
    $now = time();
    $name = mb_substr($base['name'] !== '' ? $base['name'] : 'Jangchi', 0, 32);
    $claims = array('aud' => 'stick-ops', 'sub' => (string)$user['id'], 'name' => $name,
        'iat' => $now, 'exp' => $now + 120, 'jti' => bin2hex(random_bytes(16)));
    $payload = rtrim(strtr(base64_encode(json_encode($claims, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    $signature = rtrim(strtr(base64_encode(hash_hmac('sha256', $payload, $secret, true)), '+/', '-_'), '=');
    out(array_merge($base, array('ok' => true, 'token' => $payload . '.' . $signature,
        'wsUrl' => $url, 'expiresAt' => $now + 120)));
}
