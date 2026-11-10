<?php
require __DIR__.'/sh_sim.php';
$in=json_decode(stream_get_contents(STDIN),true);
$r=sh_replay((int)$in['seed'],isset($in['inputs'])?$in['inputs']:array(),(int)$in['ticks']);
echo json_encode($r===null?array('ok'=>false,'error'=>"Natija tekshiruvdan o'tmadi"):
  array('ok'=>true,'m'=>(int)floor($r['distance']),'y'=>$r['near'],'best'=>(int)floor($r['distance']),
        'rekord'=>true,'rank'=>1,'plays'=>1,'oy'=>'2026-11','top'=>array(),'total'=>1));
