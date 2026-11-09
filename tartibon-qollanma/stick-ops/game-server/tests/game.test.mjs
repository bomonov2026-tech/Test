import test from 'node:test';
import assert from 'node:assert/strict';
import {MAP,WEAPONS,createMatch,stepMatch,buyWeapon,snapshotFor,lineOfSight,botInputs} from '../shared/game.js';

const roster=[{id:'blue',name:'Aziz',team:'blue'},{id:'red',name:'Sardor',team:'red'}];
function live(){const s=createMatch(roster);s.phase='live';s.timeLeft=90;return s;}
function tick(s,inputs={},count=1){for(let i=0;i<count;i++)stepMatch(s,new Map(Object.entries(inputs)),.1);return s;}

test('match limits 5 per side, starts with server-owned inventory and a bomb carrier',()=>{
  const s=createMatch(Array.from({length:20},(_,i)=>({id:String(i),team:i<10?'blue':'red'})));
  assert.equal(s.players.length,10);assert.equal(s.phase,'buy');
  assert.equal(s.players.filter(p=>p.team==='blue').length,5);
  assert.equal(s.players[0].credits,2400);assert.equal(s.players[0].ammo,12);
  assert.ok(s.players.find(p=>p.id===s.bomb.carrierId&&p.team==='red'));
});

test('buy, 3-second countdown, live transition freezes movement until live',()=>{
  const s=createMatch(roster),x=s.players[0].x;
  tick(s,{blue:{mx:1}},100);assert.equal(s.phase,'countdown');assert.equal(s.timeLeft,3);
  assert.equal(s.players[0].x,x);
  tick(s,{},30);assert.equal(s.phase,'live');assert.equal(s.timeLeft,90);
});

test('weapon purchase validates cost, unknown keys and timing',()=>{
  const s=createMatch(roster);
  assert.equal(buyWeapon(s,'blue','sniper').ok,false);
  assert.equal(buyWeapon(s,'blue','__proto__').ok,false);
  assert.equal(buyWeapon(s,'blue','rifle').ok,true);
  assert.equal(s.players[0].credits,600);assert.equal(s.players[0].ammo,30);
  assert.equal(buyWeapon(s,'blue','rifle').ok,false);
  s.phase='live';assert.equal(buyWeapon(s,'blue','knife').ok,false);
});

test('movement normalizes diagonal input, rejects nonfinite values and caps long ticks',()=>{
  const s=live(),p=s.players[0];Object.assign(p,{x:800,y:800});
  tick(s,{blue:{mx:1000,my:1000}});
  assert.ok(Math.abs(Math.hypot(p.x-800,p.y-800)-WEAPONS.pistol.speed*.1)<1e-7);
  const x=p.x,y=p.y;tick(s,{blue:{mx:Infinity,my:NaN}});assert.equal(p.x,x);assert.equal(p.y,y);
  stepMatch(s,new Map([['blue',{mx:1}]]),1000);
  assert.ok(p.x-x<=WEAPONS.pistol.speed*.1+.001);
});

test('walls and crates stop movement and LOS, including repeated high input',()=>{
  const s=live(),p=s.players[0];Object.assign(p,{x:640,y:350});
  tick(s,{blue:{mx:1,my:0}},50);
  assert.ok(p.x<=655.001);assert.ok(p.x>=650);
  assert.equal(lineOfSight({x:600,y:350},{x:1000,y:350}),false);
  assert.equal(lineOfSight(600,600,1000,600),true);
  Object.assign(p,{x:540,y:540});tick(s,{blue:{mx:1}},50);assert.ok(p.x<=555.001);
});

test('server fire obeys range/walls, ammo and cooldown; reload consumes reserve',()=>{
  const s=live(),[a,b]=s.players;Object.assign(a,{x:600,y:350});Object.assign(b,{x:1000,y:350});
  tick(s,{blue:{angle:0,fire:true}});assert.equal(b.hp,100);assert.equal(a.ammo,11);
  tick(s,{blue:{angle:0,fire:true}});assert.equal(a.ammo,11);
  Object.assign(a,{x:600,y:600,cooldown:0});Object.assign(b,{x:800,y:600});
  tick(s,{blue:{angle:0,fire:true}});assert.ok(b.hp<100);assert.ok(b.armor<50);
  a.ammo=0;tick(s,{blue:{reload:true}});assert.ok(a.reloading>0);
  tick(s,{},15);assert.equal(a.ammo,12);assert.equal(a.reserve,48);
});

test('only carrier inside a site can plant, movement/release cancels, defenders defuse',()=>{
  const s=live(),[blue,red]=s.players;Object.assign(red,{x:300,y:300});
  Object.assign(blue,{x:600,y:600});
  tick(s,{blue:{interact:true}},35);assert.equal(s.bomb.planted,false);
  tick(s,{red:{interact:true}},10);assert.ok(s.bomb.progress>.9);
  tick(s,{red:{interact:false}});assert.equal(s.bomb.progress,0);
  tick(s,{red:{interact:true}},31);assert.equal(s.bomb.planted,true);assert.equal(s.bomb.site,'A');
  Object.assign(blue,{x:315,y:300});tick(s,{blue:{interact:true}},51);
  assert.equal(s.phase,'round_end');assert.equal(s.roundWinner,'blue');assert.equal(s.score.blue,1);
});

test('planted bomb persists after all attackers die; timeout and explosion use correct winners',()=>{
  const s=live();Object.assign(s.bomb,{planted:true,carrierId:null,x:300,y:300,site:'A',timer:1});
  s.players[1].alive=false;s.players[1].hp=0;s.timeLeft=.1;
  tick(s,{},5);assert.equal(s.phase,'live');tick(s,{},6);
  assert.equal(s.roundWinner,'red');assert.equal(s.score.red,1);
  const t=live();t.timeLeft=.05;tick(t);assert.equal(t.roundWinner,'blue');
});

test('round reward happens once and final score ends match',()=>{
  const s=live();s.score.blue=4;s.players[1].alive=false;s.players[1].hp=0;
  tick(s);assert.equal(s.phase,'finished');assert.equal(s.winner,'blue');assert.equal(s.players[0].credits,4600);
  tick(s,{},50);assert.equal(s.score.blue,5);assert.equal(s.players[0].credits,4600);
});

test('snapshot hides wall-obscured opponents and their private inventory',()=>{
  const s=live(),[blue,red]=s.players;Object.assign(blue,{x:600,y:350});Object.assign(red,{x:1000,y:350});
  let view=snapshotFor(s,'blue');assert.equal(view.players.length,1);assert.equal(view.bomb.carrierId,null);
  assert.equal(view.players[0].credits,2400);
  Object.assign(blue,{x:600,y:600});Object.assign(red,{x:800,y:600});view=snapshotFor(s,'blue');
  assert.equal(view.players.length,2);assert.equal('credits' in view.players[1],false);
  assert.equal('ammo' in view.players[1],false);assert.equal(view.roster.length,2);
});

test('practice bot inputs use objectives, not remote player state',()=>{
  const s=createMatch([{id:'human',team:'blue'},...Array.from({length:9},(_,i)=>({id:'b'+i,team:i<4?'blue':'red',bot:true}))],{practice:true});
  const before=s.players[1].credits;botInputs(s);assert.equal(s.players[1].weapon,'rifle');assert.equal(s.players[1].credits,before-1800);
  s.phase='live';const controls=botInputs(s);assert.equal(controls.size,9);
  for(const input of controls.values()){assert.ok(Number.isFinite(input.angle));assert.ok(Math.hypot(input.mx,input.my)<=1.001);}
  assert.ok(MAP.sites.every(site=>Number.isFinite(site.x)&&Number.isFinite(site.y)));
});

test('ten practice bots navigate the map, plant a bomb and complete a full match',()=>{
  const s=createMatch(Array.from({length:10},(_,i)=>({id:String(i),name:'Bot '+i,team:i<5?'blue':'red',bot:true})),{practice:true});
  let planted=false;
  for(let frame=0;frame<18000&&s.phase!=='finished';frame++){
    stepMatch(s,botInputs(s),1/30);planted ||= s.bomb.planted;
  }
  assert.equal(s.phase,'finished');assert.ok(s.score.blue===5||s.score.red===5);
  assert.ok(planted);assert.ok(s.players.some(p=>p.kills>0));
  assert.ok(s.players.every(p=>Number.isFinite(p.x)&&Number.isFinite(p.y)));
});
