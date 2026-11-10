<?php
require __DIR__.'/sh_sim.php';
$c=json_decode(file_get_contents(__DIR__.'/cases.json'),true)[0];
function t($nom,$seed,$in,$ticks){ $r=sh_replay($seed,$in,$ticks);
  printf("%-46s -> %s\n",$nom,$r===null?"RAD ETILDI":("qabul, ".(int)floor($r['distance'])." m")); }
t('haqiqiy poyga',$c['seed'],$c['inputs'],$c['ticks']);
t('ticks oshirildi (+600)',$c['seed'],$c['inputs'],$c['ticks']+600);
t('ticks kamaytirildi (-50)',$c['seed'],$c['inputs'],$c['ticks']-50);
$x=$c['inputs']; if(count($x)>3){$t0=$x[1]['tick'];$x[1]['tick']=$x[2]['tick'];$x[2]['tick']=$t0;}
t('tartib buzildi',$c['seed'],$x,$c['ticks']);
$x=$c['inputs']; $x[0]['turbo']=true;
t('begona maydon (turbo)',$c['seed'],$x,$c['ticks']);
$x=$c['inputs']; $x[0]['direction']=3;
t('direction=3',$c['seed'],$x,$c['ticks']);
t('urug\' almashtirildi',(int)$c['seed']+1,$c['inputs'],$c['ticks']);
t('bo\'sh boshqaruv, uzoq poyga',$c['seed'],array(),50000);
$x=array(); for($i=0;$i<300;$i++)$x[]=array('tick'=>$i*3,'throttle'=>true);
t('faqat gaz, 100000 tick',$c['seed'],$x,100000);
