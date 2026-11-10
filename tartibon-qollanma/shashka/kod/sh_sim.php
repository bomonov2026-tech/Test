<?php
/* ------------------------------------------------------------------
   SHASHKA — deterministik poyga hisobi (serverda qayta tekshirish).
   src/game/simulation.ts ning aniq nusxasi. O'zgartirilsa, mijozdagi
   sh-game.js ham, SH_SIM_VER ham birga o'zgarishi shart.
   ------------------------------------------------------------------ */
define('SH_TICK', 60);
define('SH_MAX_TICKS', 108000);
define('SH_SIM_VER', 'uz-manual-v4');
define('SH_MAX_SPEED', 280);
define('SH_THROTTLE_ACC', 24);
define('SH_COAST_DEC', 6);
define('SH_BRAKE_DEC', 42);
define('SH_MAX_INPUTS', 15000);
define('SH_TRAFFIC_SPEED', 60);
define('SH_MIN_GAP', 52);
define('SH_MAX_GAP', 68);
define('SH_SPAWN_Z', -260);

function sh_lanes() { static $l = array(-5.25, -1.75, 1.75, 5.25); return $l; }
function sh_dims() {
    static $d = array(
        array(1.78, 4.5), array(1.86, 4.85), array(2.2, 7.9),
        array(1.45, 3.5), array(1.5, 3.4), array(1.6, 3.65));
    return $d;
}
/* JS Math.round: yarmi yuqoriga. PHP round() dan farq qilmasligi uchun qo'lda. */
function sh_round($v) { $f = floor($v); return (int)(($v - $f) >= 0.5 ? $f + 1 : $f); }
function sh_sign($v) { return $v > 0 ? 1 : ($v < 0 ? -1 : 0); }

function sh_rand(&$s) {
    $s['rng'] = ($s['rng'] * 1664525 + 1013904223) & 0xFFFFFFFF;
    return $s['rng'] / 4294967296;
}
function sh_half($kind) {
    $d = sh_dims();
    $t = isset($d[$kind]) ? $d[$kind] : $d[0];
    return array((1.94 + $t[0]) / 2 - 0.12, (4.45 + $t[1]) / 2 - 0.15); /* width, length */
}
function sh_spawn(&$s, $z) {
    $L = sh_lanes();
    $lane = (int)floor(sh_rand($s) * count($L));
    $kind = (int)floor(sh_rand($s) * count(sh_dims()));
    $color = (int)floor(sh_rand($s) * 6);
    $s['traffic'][] = array('lane' => $lane, 'x' => $L[$lane], 'z' => $z,
        'speed' => SH_TRAFFIC_SPEED, 'kind' => $kind, 'color' => $color, 'passed' => false);
}
function sh_spacing(&$s) { return SH_MIN_GAP + sh_rand($s) * (SH_MAX_GAP - SH_MIN_GAP); }

function sh_create($seed) {
    $L = sh_lanes();
    $s = array('seed' => $seed & 0xFFFFFFFF, 'rng' => $seed & 0xFFFFFFFF, 'ticks' => 0,
        'lane' => 1, 'x' => $L[1], 'distance' => 0.0, 'speed' => 100.0,
        'nearMisses' => 0, 'combo' => 0, 'throttle' => false, 'brake' => false,
        'traffic' => array(), 'spawnIn' => 0.0, 'crashed' => false, 'finished' => false,
        'comboUntil' => 0);
    sh_spawn($s, -68); sh_spawn($s, -126); sh_spawn($s, -184);
    $s['spawnIn'] = sh_spacing($s);
    return $s;
}
function sh_step(&$s, $dir, $th, $br) {
    if ($s['crashed'] || $s['finished']) return;
    $L = sh_lanes();
    if ($th !== null) $s['throttle'] = $th;
    if ($br !== null) $s['brake'] = $br;
    if ($dir) $s['lane'] = max(0, min(3, $s['lane'] + $dir));
    $prev = $s['speed'];
    $acc = $s['brake'] ? -SH_BRAKE_DEC : ($s['throttle'] ? SH_THROTTLE_ACC : -SH_COAST_DEC);
    $s['speed'] = max(0, min(SH_MAX_SPEED, $s['speed'] + $acc / SH_TICK));
    $lat = 13 * min(1, $s['speed'] / 40);
    $dx = $L[$s['lane']] - $s['x'];
    $s['x'] += sh_sign($dx) * min(abs($dx), $lat / SH_TICK);
    $avg = ($prev + $s['speed']) / 2;
    $s['distance'] += $avg / 3.6 / SH_TICK;
    $s['ticks']++;
    if ($s['ticks'] > $s['comboUntil']) $s['combo'] = 0;
    $n = count($s['traffic']);
    for ($i = 0; $i < $n; $i++) {
        $car =& $s['traffic'][$i];
        $car['z'] += ($avg - $car['speed']) / 3.6 / SH_TICK;
        $h = sh_half($car['kind']);
        /* floor() PHP'da float qaytaradi: === solishtirish uchun butun songa keltiramiz */
        $occ = (int)max(0, min(count($L) - 1, sh_round(($s['x'] - $L[0]) / 3.5)));
        if ($car['z'] > 0 && ($s['brake'] || $s['speed'] < SH_TRAFFIC_SPEED + 8)
            && $car['lane'] === $occ && abs($car['x'] - $s['x']) < $h[0] + 0.35) {
            $car['lane'] = $occ === count($L) - 1 ? $occ - 1 : $occ + 1;
        }
        $tdx = $L[$car['lane']] - $car['x'];
        $car['x'] += sh_sign($tdx) * min(abs($tdx), 8 / SH_TICK);
        if (abs($car['z']) < $h[1] && abs($car['x'] - $s['x']) < $h[0]) $s['crashed'] = true;
        if (!$car['passed'] && $car['z'] > $h[1] + 0.2) {
            $car['passed'] = true;
            $gap = abs($car['x'] - $s['x']);
            if ($gap < $h[0] + 1.2 && $gap >= $h[0] && !$s['crashed']) {
                $s['nearMisses']++;
                $s['combo'] = min(99, $s['combo'] + 1);
                $s['comboUntil'] = $s['ticks'] + 240;
            }
        }
        unset($car);
    }
    $keep = array();
    foreach ($s['traffic'] as $c) if ($c['z'] < 25 && $c['z'] > -420) $keep[] = $c;
    $s['traffic'] = $keep;
    $far = INF;
    foreach ($s['traffic'] as $c) if ($c['z'] < $far) $far = $c['z'];
    $next = is_infinite($far) ? SH_SPAWN_Z : $far - $s['spawnIn'];
    if ($next >= SH_SPAWN_Z) { sh_spawn($s, $next); $s['spawnIn'] = sh_spacing($s); }
    $s['finished'] = $s['ticks'] >= SH_MAX_TICKS;
}
/* Boshqaruv yozuvi to'g'rimi? JS validateInputs bilan bir xil qoidalar. */
function sh_check_inputs($in, $ticks) {
    if (!is_int($ticks) || $ticks < 1 || $ticks > SH_MAX_TICKS) return false;
    if (!is_array($in) || count($in) > $ticks || count($in) > SH_MAX_INPUTS) return false;
    $prev = -1; $k = 0;
    foreach ($in as $key => $x) {
        if ($key !== $k++) return false;                     /* haqiqiy ro'yxat bo'lsin */
        if (!is_array($x)) return false;
        foreach ($x as $f => $_) if (!in_array($f, array('tick', 'direction', 'throttle', 'brake'), true)) return false;
        if (!isset($x['tick']) || !is_int($x['tick'])) return false;
        if ($x['tick'] <= $prev || $x['tick'] >= $ticks) return false;
        $has = false;
        if (array_key_exists('direction', $x)) { if ($x['direction'] !== -1 && $x['direction'] !== 1) return false; $has = true; }
        if (array_key_exists('throttle', $x)) { if (!is_bool($x['throttle'])) return false; $has = true; }
        if (array_key_exists('brake', $x)) { if (!is_bool($x['brake'])) return false; $has = true; }
        if (!$has) return false;
        $prev = $x['tick'];
    }
    return true;
}
/* Poygani boshidan qayta o'ynab chiqadi. Xato bo'lsa null. */
function sh_replay($seed, $in, $ticks) {
    if (!sh_check_inputs($in, $ticks)) return null;
    $s = sh_create($seed);
    $i = 0; $n = count($in);
    for ($t = 0; $t < $ticks; $t++) {
        if ($s['crashed'] || $s['finished']) return null;    /* to'qnashuvdan keyin davom etgan */
        $d = 0; $th = null; $br = null;
        if ($i < $n && $in[$i]['tick'] === $t) {
            $x = $in[$i++];
            if (array_key_exists('direction', $x)) $d = $x['direction'];
            if (array_key_exists('throttle', $x)) $th = $x['throttle'];
            if (array_key_exists('brake', $x)) $br = $x['brake'];
        }
        sh_step($s, $d, $th, $br);
    }
    if (!$s['crashed'] && !$s['finished']) return null;       /* poyga tugamagan */
    return array('distance' => $s['distance'], 'near' => $s['nearMisses'],
                 'crashed' => $s['crashed'], 'ticks' => $s['ticks'],
                 'x' => $s['x'], 'speed' => $s['speed']);
}
