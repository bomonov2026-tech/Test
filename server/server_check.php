<?php
/*
 * TartibOn — hosting mosligini tekshirish (faqat o'qiydi, hech narsani o'zgartirmaydi).
 *
 * Ishlatish:
 *   1) Faylni hostingga yuklang (masalan: public_html/server_check.php)
 *   2) Brauzerda oching:  https://SAYTINGIZ/server_check.php?k=TARTIBON
 *   3) Natijani skrinshot qilib menga yuboring
 *   4) TEKSHIRUVDAN SO'NG FAYLNI O'CHIRING
 *
 * Parol, token va baza ma'lumotlari ko'rsatilmaydi.
 */
header('Content-Type: text/html; charset=utf-8');
if (!isset($_GET['k']) || $_GET['k'] !== 'TARTIBON') { http_response_code(403); exit('Ruxsat yo\'q. Manzil oxiriga ?k=TARTIBON qo\'shing.'); }
@set_time_limit(60);
$rows = array();
function add($name, $val, $st, $note = '') { global $rows; $rows[] = array($name, $val, $st, $note); }   // st: ok | warn | bad | info
function ini($k) { $v = ini_get($k); return $v === false || $v === '' ? '—' : $v; }
function bytes($v) { $v = trim((string)$v); if ($v === '' || $v === '-1') return -1; $n = (float)$v; $u = strtolower(substr($v, -1)); if ($u === 'g') $n *= 1073741824; elseif ($u === 'm') $n *= 1048576; elseif ($u === 'k') $n *= 1024; return (int)$n; }

/* ---- PHP ---- */
$pv = PHP_VERSION; add('PHP versiyasi', $pv, version_compare($pv, '7.4.0', '>=') ? 'ok' : 'bad', 'Kamida 7.4 kerak, 8.1+ yaxshi');
foreach (array('curl' => 'Telegram bilan aloqa', 'pdo_mysql' => 'Baza', 'mbstring' => 'Matn (o\'zbekcha)', 'gd' => 'Rasm chizish (kurs, namoz rasmi)', 'json' => 'JSON', 'openssl' => 'Xavfsiz ulanish', 'zlib' => 'Siqish') as $e => $why)
    add('Kengaytma: ' . $e, extension_loaded($e) ? 'bor' : 'YO\'Q', extension_loaded($e) ? 'ok' : ($e === 'zlib' ? 'warn' : 'bad'), $why);
add('gd: PNG/JPEG', function_exists('imagepng') && function_exists('imagejpeg') ? 'bor' : 'YO\'Q', function_exists('imagepng') && function_exists('imagejpeg') ? 'ok' : 'bad', '');
$ml = bytes(ini('memory_limit')); add('memory_limit', ini('memory_limit'), ($ml === -1 || $ml >= 128 * 1048576) ? 'ok' : 'warn', '128M va undan ko\'p yaxshi');
$et = (int)ini('max_execution_time'); add('max_execution_time', ini('max_execution_time') . ' s', ($et === 0 || $et >= 30) ? 'ok' : 'warn', 'Cron va kurs yangilash uchun 30+ s');
$um = bytes(ini('upload_max_filesize')); $pm = bytes(ini('post_max_size')); add('upload_max_filesize / post_max_size', ini('upload_max_filesize') . ' / ' . ini('post_max_size'), 'info', 'Rasm yuklash uchun kamida 2M');
add('Vaqt mintaqasi (PHP)', date_default_timezone_get() . ' · hozir ' . date('H:i'), 'info', 'Ilova Toshkent vaqtini o\'zi qo\'yadi');

/* ---- exec / ffmpeg ---- */
$dis = array_map('trim', explode(',', (string)ini_get('disable_functions')));
add('disable_functions', $dis && $dis[0] !== '' ? implode(', ', array_slice($dis, 0, 12)) . (count($dis) > 12 ? ' …' : '') : 'hech narsa o\'chirilmagan', 'info', '');
$execOk = function_exists('exec') && !in_array('exec', $dis, true);
add('exec() ishlaydimi', $execOk ? 'ha' : 'yo\'q', $execOk ? 'ok' : 'warn', 'Video aylantirish uchun kerak (ixtiyoriy)');
if ($execOk) { @exec('ffmpeg -version 2>&1', $o, $rc); add('ffmpeg', $rc === 0 && $o ? trim(explode("\n", implode("\n", $o))[0]) : 'yo\'q', $rc === 0 ? 'ok' : 'info', 'Ixtiyoriy'); }

/* ---- Disk ---- */
$dir = __DIR__; $free = @disk_free_space($dir); $tot = @disk_total_space($dir);
if ($free !== false) add('Bo\'sh joy (server diski)', round($free / 1073741824, 1) . ' GB / ' . round($tot / 1073741824, 1) . ' GB', 'info', 'Sizning limitingiz (kvota) bundan kam bo\'lishi mumkin — panelda ko\'ring');
add('Papkaga yozish', is_writable($dir) ? 'mumkin' : 'mumkin emas', is_writable($dir) ? 'ok' : 'warn', '');
add('Vaqtinchalik papka', sys_get_temp_dir() . (is_writable(sys_get_temp_dir()) ? ' (yoziladi)' : ' (yozilmaydi)'), is_writable(sys_get_temp_dir()) ? 'ok' : 'warn', '');

/* ---- Tashqi ulanish (eng muhimi) ---- */
function probe($url, $ua = 'TartibOn-check') {
    if (!function_exists('curl_init')) return array(0, 0, 'cURL yo\'q');
    $c = curl_init($url); curl_setopt_array($c, array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_USERAGENT => $ua, CURLOPT_SSL_VERIFYPEER => 1));
    $t = microtime(true); $r = curl_exec($c); $code = (int)curl_getinfo($c, CURLINFO_HTTP_CODE); $err = curl_error($c); curl_close($c);
    return array($code, (int)round((microtime(true) - $t) * 1000), $r === false ? $err : '');
}
$targets = array(
    'Telegram API (bot uchun)' => array('https://api.telegram.org/', true),
    'cbu.uz (Markaziy bank kursi)' => array('https://cbu.uz/uz/arkhiv-kursov-valyut/json/', false),
    'islomapi.uz (namoz vaqti)' => array('https://islomapi.uz/api/present/day?region=Toshkent', false),
    'open-meteo.com (ob-havo)' => array('https://api.open-meteo.com/v1/forecast?latitude=41.3&longitude=69.2&current=temperature_2m', false),
    'mkbank.uz (bank kursi)' => array('https://mkbank.uz/', false),
    'youtube.com (RSS uchun)' => array('https://www.youtube.com/feeds/videos.xml?channel_id=UC_x5XG1OV2P6uZZ5FSM9Ttw', false),
);
foreach ($targets as $name => $t) {
    list($code, $ms, $err) = probe($t[0]);
    $ok = $code >= 200 && $code < 500 && $code !== 0;   // 404 ham ulanish borligini bildiradi
    add('Ulanish: ' . $name, $ok ? 'HTTP ' . $code . ' · ' . $ms . ' ms' : 'ULANMADI ' . ($err ? '(' . $err . ')' : 'HTTP ' . $code), $ok ? 'ok' : ($t[1] ? 'bad' : 'warn'), $t[1] ? 'Bu bo\'lmasa bot ISHLAMAYDI' : 'Bo\'lmasa shu xizmat ishlamaydi (zaxira yo\'li bor)');
}

/* ---- HTTPS / siqish ---- */
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
add('Sahifa HTTPS orqali', $https ? 'ha' : 'YO\'Q', $https ? 'ok' : 'bad', 'Telegram webhook va mini ilova uchun HTTPS shart');
add('Siqish (gzip)', (ini_get('zlib.output_compression') ? 'PHP yoqilgan' : (function_exists('ob_gzhandler') ? 'PHP orqali yoqish mumkin' : 'yo\'q')), 'info', '1,3 MB sahifa uchun muhim — server yoki PHP siqishi bo\'lishi kerak');
add('Server dasturi', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '—', 'info', '');
add('Webhook uchun IP', isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '—', 'info', 'IPv4 bo\'lishi kerak');

/* ---- Baza (faqat borligini tekshiradi) ---- */
add('PDO drayverlari', implode(', ', class_exists('PDO') ? PDO::getAvailableDrivers() : array('PDO yo\'q')), class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers(), true) ? 'ok' : 'bad', '');

/* ---- Natija ---- */
$bad = 0; $warn = 0; foreach ($rows as $r) { if ($r[2] === 'bad') $bad++; if ($r[2] === 'warn') $warn++; }
$col = array('ok' => '#16a34a', 'warn' => '#d97706', 'bad' => '#dc2626', 'info' => '#6b7280'); $ic = array('ok' => '✅', 'warn' => '⚠️', 'bad' => '❌', 'info' => 'ℹ️');
echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hosting tekshiruvi</title><body style="font:15px -apple-system,Segoe UI,Roboto,sans-serif;max-width:720px;margin:0 auto;padding:16px;background:#f3f5f9;color:#111">';
echo '<h2 style="margin:0 0 6px">TartibOn · hosting tekshiruvi</h2>';
echo '<div style="padding:12px 14px;border-radius:14px;margin:10px 0;background:' . ($bad ? '#fee2e2' : ($warn ? '#fef3c7' : '#dcfce7')) . '"><b>' . ($bad ? '❌ ' . $bad . ' ta jiddiy muammo' : ($warn ? '⚠️ ' . $warn . ' ta ogohlantirish, jiddiy muammo yo\'q' : '✅ Hammasi joyida')) . '</b></div>';
echo '<table style="width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden">';
foreach ($rows as $r) {
    echo '<tr style="border-top:1px solid #eef0f4"><td style="padding:9px 10px;vertical-align:top;width:34%"><b>' . htmlspecialchars($r[0]) . '</b></td><td style="padding:9px 6px;color:' . $col[$r[2]] . ';vertical-align:top">' . $ic[$r[2]] . ' ' . htmlspecialchars($r[1]) . ($r[3] ? '<div style="color:#8a93a3;font-size:12.5px;margin-top:2px">' . htmlspecialchars($r[3]) . '</div>' : '') . '</td></tr>';
}
echo '</table><p style="color:#8a93a3;font-size:13px">Tekshiruvdan so\'ng bu faylni serverdan o\'chiring.</p>';
