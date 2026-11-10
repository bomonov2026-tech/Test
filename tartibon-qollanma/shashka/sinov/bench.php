<?php
require __DIR__.'/sh_sim.php';
$c=json_decode(file_get_contents(__DIR__.'/cases.json'),true);
$tot=0;$tk=0;$t0=microtime(true);
foreach($c as $x){ sh_replay($x['seed'],$x['inputs'],$x['ticks']); $tk+=$x['ticks']; $tot++; }
$ms=(microtime(true)-$t0)*1000;
printf("%d poyga, jami %d tick, %.0f ms -> bitta poyga %.2f ms, 60 s poyga ~%.0f ms\n",
  $tot,$tk,$ms,$ms/$tot,$ms/$tk*3600);
