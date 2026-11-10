<?php
require __DIR__ . '/sh_sim.php';
function bits($v) { $h = unpack('H*', pack('E', $v)); return $h[1]; }
$cases = json_decode(file_get_contents(__DIR__ . '/cases.json'), true);
$ok = 0; $bad = array();
foreach ($cases as $i => $c) {
    $r = sh_replay($c['seed'], $c['inputs'], $c['ticks']);
    if ($r === null) { $bad[] = "#$i: replay null"; continue; }
    $e = $c['exp'];
    $got = array('d' => (int)floor($r['distance']), 'near' => $r['near'],
                 'crashed' => $r['crashed'], 'ticks' => $r['ticks'],
                 'db' => bits($r['distance']), 'xb' => bits($r['x']), 'sb' => bits($r['speed']));
    $diff = array();
    foreach ($e as $k => $v) if ((string)$got[$k] !== (string)$v) $diff[] = "$k: php=" . var_export($got[$k], true) . " js=" . var_export($v, true);
    if ($diff) $bad[] = "#$i (tick {$c['ticks']}): " . implode(' | ', $diff); else $ok++;
}
echo "mos keldi: $ok / " . count($cases) . "\n";
foreach (array_slice($bad, 0, 6) as $b) echo "  XATO $b\n";
