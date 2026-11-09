/* STICK OPS shared, deterministic simulation. Run combat on the server;
 * browsers use the same module only for local practice and map rendering. */
export const MAP = {
  width: 1600, height: 1000,
  walls: [
    {x:0,y:0,w:1600,h:36},{x:0,y:964,w:1600,h:36},
    {x:0,y:36,w:36,h:928},{x:1564,y:36,w:36,h:928},
    {x:100,y:130,w:370,h:24},{x:100,y:130,w:24,h:362},
    {x:100,y:468,w:150,h:24},{x:370,y:468,w:124,h:24},
    {x:470,y:130,w:24,h:120},{x:470,y:360,w:24,h:132},
    {x:1106,y:130,w:394,h:24},{x:1476,y:130,w:24,h:362},
    {x:1106,y:468,w:124,h:24},{x:1350,y:468,w:150,h:24},
    {x:1106,y:130,w:24,h:120},{x:1106,y:360,w:24,h:132},
    {x:670,y:300,w:260,h:160},
    {x:170,y:660,w:240,h:24},{x:1190,y:660,w:240,h:24},
    {x:520,y:735,w:24,h:150},{x:1056,y:735,w:24,h:150}
  ],
  crates: [
    {x:180,y:215,w:58,h:58},{x:358,y:337,w:64,h:64},
    {x:1206,y:215,w:58,h:58},{x:1360,y:337,w:64,h:64},
    {x:570,y:495,w:64,h:90},{x:966,y:495,w:64,h:90},
    {x:740,y:625,w:120,h:44},{x:275,y:800,w:70,h:50},
    {x:1255,y:800,w:70,h:50}
  ],
  sites: [{id:'A',x:300,y:300,r:110},{id:'B',x:1300,y:300,r:110}],
  spawns: {
    blue: [720,760,800,840,880].map(x=>({x,y:90})),
    red: [720,760,800,840,880].map(x=>({x,y:910}))
  }
};

export const WEAPONS = {
  knife:{name:'Pichoq',cost:0,damage:45,range:55,cooldown:.55,magazine:0,reserve:0,reloadTime:0,speed:235},
  pistol:{name:'Pistolet',cost:0,damage:30,range:650,cooldown:.32,magazine:12,reserve:60,reloadTime:1.35,speed:215},
  smg:{name:'SMG',cost:1200,damage:18,range:600,cooldown:.09,magazine:30,reserve:120,reloadTime:1.9,speed:215},
  rifle:{name:'Avtomat',cost:1800,damage:29,range:900,cooldown:.14,magazine:30,reserve:90,reloadTime:2.1,speed:195},
  shotgun:{name:'Drobovik',cost:1500,damage:18,range:280,cooldown:.8,magazine:6,reserve:30,reloadTime:2.4,speed:200},
  sniper:{name:'Snayper',cost:3500,damage:110,range:1400,cooldown:1.3,magazine:5,reserve:20,reloadTime:2.8,speed:170}
};

const SOLIDS=[...MAP.walls,...MAP.crates];
const RADIUS=15;
const finite=(v,d=0)=>Number.isFinite(v)?v:d;
const distance=(a,b)=>Math.hypot(a.x-b.x,a.y-b.y);
const other=team=>team==='blue'?'red':'blue';

function rectangleHit(x,y,dx,dy,rect,max=Infinity) {
  let lo=0,hi=max;
  for(const [p,d,min,maxv] of [[x,dx,rect.x,rect.x+rect.w],[y,dy,rect.y,rect.y+rect.h]]) {
    if(Math.abs(d)<1e-9){if(p<min||p>maxv)return Infinity;}
    else {let a=(min-p)/d,b=(maxv-p)/d;if(a>b)[a,b]=[b,a];lo=Math.max(lo,a);hi=Math.min(hi,b);if(lo>hi)return Infinity;}
  }
  return lo;
}

function wallDistance(x,y,dx,dy,max) {
  let result=max;
  for(const r of SOLIDS) result=Math.min(result,rectangleHit(x,y,dx,dy,r,max));
  return result;
}

export function lineOfSight(a,b,c,d) {
  if(typeof a==='number'){a={x:a,y:b};b={x:c,y:d};}
  if(!a||!b)return false;
  const length=distance(a,b);if(length<.01)return true;
  return wallDistance(a.x,a.y,(b.x-a.x)/length,(b.y-a.y)/length,length)>=length-.1;
}

function blocked(x,y,radius=RADIUS) {
  return SOLIDS.some(r=>{
    const cx=Math.max(r.x,Math.min(x,r.x+r.w)),cy=Math.max(r.y,Math.min(y,r.y+r.h));
    return (x-cx)**2+(y-cy)**2 < radius**2;
  });
}

function move(player,mx,my,dt) {
  mx=finite(mx);my=finite(my);
  const size=Math.hypot(mx,my);if(size>1){mx/=size;my/=size;}
  const speed=WEAPONS[player.weapon].speed;
  const dx=mx*speed*dt,dy=my*speed*dt,steps=Math.max(1,Math.ceil(Math.hypot(dx,dy)/6));
  for(let i=0;i<steps;i++){
    const nx=Math.max(36+RADIUS,Math.min(1564-RADIUS,player.x+dx/steps));
    if(!blocked(nx,player.y))player.x=nx;
    const ny=Math.max(36+RADIUS,Math.min(964-RADIUS,player.y+dy/steps));
    if(!blocked(player.x,ny))player.y=ny;
  }
}

function resetBomb(state) {
  const carrier=state.players.find(p=>p.team==='red'&&p.connected!==false);
  state.bomb={carrierId:carrier?.id||null,planted:false,x:carrier?.x||800,y:carrier?.y||900,
    site:null,timer:35,progress:0,action:null,actorId:null};
}

function setupRound(state) {
  const indices={blue:0,red:0};
  for(const p of state.players){
    const spawn=MAP.spawns[p.team][indices[p.team]++%5];
    Object.assign(p,{x:spawn.x,y:spawn.y,angle:p.team==='blue'?Math.PI/2:-Math.PI/2,
      hp:p.connected===false?0:100,armor:50,alive:p.connected!==false,
      weapon:'pistol',ammo:12,reserve:60,cooldown:0,reloading:0,
      interactProgress:0,botBought:false,_aiPoint:null,_aiPathAt:0});
  }
  resetBomb(state);
  state.phase='buy';state.timeLeft=10;state.roundWinner=null;state.effects=[];
  state.lastEvent='Qurol tanlang';
}

export function createMatch(roster,{practice=false}={}) {
  const used=new Set();const counts={blue:0,red:0};
  const players=[];
  for(const item of roster||[]){
    if(players.length===10)break;
    const id=String(item.id);const team=item.team==='red'?'red':'blue';
    if(used.has(id)||counts[team]>=5)continue;
    used.add(id);counts[team]++;
    players.push({id,name:String(item.name||'Jangchi').slice(0,30),team,
      bot:!!item.bot,connected:item.connected!==false,credits:2400,kills:0,deaths:0});
  }
  const state={phase:'buy',timeLeft:10,round:1,score:{blue:0,red:0},players,
    bomb:null,effects:[],elapsed:0,winner:null,roundWinner:null,lastEvent:'Qurol tanlang',
    practice:!!practice,effectSequence:0};
  setupRound(state);return state;
}

export function buyWeapon(state,id,key) {
  const player=state.players.find(p=>p.id===String(id));
  if(!player)return {ok:false,error:'O‘yinchi topilmadi'};
  if(state.phase!=='buy')return {ok:false,error:'Xarid vaqti tugagan'};
  if(!player.alive)return {ok:false,error:'Jangchi faol emas'};
  const w=Object.prototype.hasOwnProperty.call(WEAPONS,key)?WEAPONS[key]:null;
  if(!w)return {ok:false,error:'Qurol topilmadi'};
  if(player.credits<w.cost)return {ok:false,error:'Tanga yetarli emas'};
  if(player.weapon===key)return {ok:false,error:'Bu qurol tanlangan'};
  player.credits-=w.cost;player.weapon=key;player.ammo=w.magazine;
  player.reserve=w.reserve;player.reloading=0;player.cooldown=0;
  return {ok:true};
}

function finishRound(state,winner,reason) {
  if(state.phase==='round_end'||state.phase==='finished')return;
  state.score[winner]++;state.roundWinner=winner;state.lastEvent=reason;
  for(const p of state.players)p.credits=Math.min(12000,p.credits+(p.team===winner?2200:1500));
  state.effects=[];
  if(state.score[winner]>=5){state.phase='finished';state.winner=winner;state.timeLeft=0;}
  else {state.phase='round_end';state.timeLeft=5;}
}

function shoot(state,player) {
  const w=WEAPONS[player.weapon];
  if(player.cooldown>0||player.reloading>0||(w.magazine>0&&player.ammo<=0))return;
  player.cooldown=w.cooldown;if(w.magazine>0)player.ammo--;
  const offsets=player.weapon==='shotgun'?[-.14,-.07,0,.07,.14]:[0];
  for(const offset of offsets){
    const dx=Math.cos(player.angle+offset),dy=Math.sin(player.angle+offset);
    let range=wallDistance(player.x,player.y,dx,dy,w.range),victim=null;
    for(const target of state.players){
      if(target===player||!target.alive||target.team===player.team)continue;
      const vx=target.x-player.x,vy=target.y-player.y,projection=vx*dx+vy*dy;
      if(projection<0||projection>range+RADIUS)continue;
      const perpendicular=vx*vx+vy*vy-projection*projection;
      if(perpendicular>RADIUS*RADIUS)continue;
      const hit=Math.max(0,projection-Math.sqrt(Math.max(0,RADIUS*RADIUS-perpendicular)));
      if(hit<range){range=hit;victim=target;}
    }
    if(victim){
      const armorDamage=Math.min(victim.armor,w.damage*.35);victim.armor-=armorDamage;
      victim.hp=Math.max(0,victim.hp-(w.damage-armorDamage));
      if(victim.hp<=0){
        victim.alive=false;victim.deaths++;player.kills++;player.credits=Math.min(12000,player.credits+150);
        state.lastEvent=player.name+' → '+victim.name;
      }
    }
    state.effects.push({id:++state.effectSequence,type:'shot',x:player.x,y:player.y,
      x2:player.x+dx*range,y2:player.y+dy*range,team:player.team,ttl:.14});
  }
}

function updateBomb(state,inputs,dt) {
  const bomb=state.bomb;
  let carrier=state.players.find(p=>p.id===bomb.carrierId);
  if(carrier&&!carrier.alive){bomb.x=carrier.x;bomb.y=carrier.y;bomb.carrierId=null;carrier=null;}
  if(!bomb.planted&&!carrier){
    carrier=state.players.find(p=>p.alive&&p.team==='red'&&distance(p,bomb)<30);
    if(carrier)bomb.carrierId=carrier.id;
  }
  if(carrier&&!bomb.planted){bomb.x=carrier.x;bomb.y=carrier.y;}
  let actor=null,action=null;
  if(!bomb.planted&&carrier&&inputs.get(carrier.id)?.interact){
    const site=MAP.sites.find(s=>distance(carrier,s)<s.r);
    if(site){actor=carrier;action='plant';bomb.site=site.id;}
  }
  if(bomb.planted){
    actor=state.players.find(p=>p.alive&&p.team==='blue'&&distance(p,bomb)<60&&inputs.get(p.id)?.interact);
    if(actor)action='defuse';
  }
  if(actor){
    const input=inputs.get(actor.id)||{};
    if(Math.hypot(finite(input.mx),finite(input.my))>.12){actor=null;action=null;}
  }
  if(actor){
    if(bomb.actorId!==actor.id||bomb.action!==action)bomb.progress=0;
    bomb.actorId=actor.id;bomb.action=action;bomb.progress+=dt;
    if(action==='plant'&&bomb.progress>=3){
      bomb.planted=true;bomb.x=actor.x;bomb.y=actor.y;bomb.carrierId=null;
      bomb.timer=35;bomb.progress=0;bomb.actorId=null;bomb.action=null;
      actor.credits=Math.min(12000,actor.credits+300);state.lastEvent='Bomba o‘rnatildi';
    }else if(action==='defuse'&&bomb.progress>=5){
      actor.credits=Math.min(12000,actor.credits+300);
      finishRound(state,'blue','Bomba zararsizlantirildi');return;
    }
  }else{bomb.progress=0;bomb.actorId=null;bomb.action=null;}
  if(bomb.planted){
    bomb.timer=Math.max(0,bomb.timer-dt);
    if(bomb.timer<=0)finishRound(state,'red','Bomba portladi');
  }
}

export function stepMatch(state,inputMap=new Map(),delta=1/30) {
  const dt=Math.max(0,Math.min(.1,finite(delta)));
  if(!dt||state.phase==='finished')return state;
  const inputs=inputMap instanceof Map?inputMap:new Map(Object.entries(inputMap||{}));
  state.elapsed+=dt;
  state.effects=state.effects.filter(e=>(e.ttl-=dt)>0).slice(-80);
  state.timeLeft=Math.max(0,state.timeLeft-dt);
  if(state.phase==='buy'){
    if(state.timeLeft<=1e-7){state.phase='countdown';state.timeLeft=3;state.lastEvent='Jangga tayyorlaning';}
    return state;
  }
  if(state.phase==='countdown'){
    if(state.timeLeft<=1e-7){state.phase='live';state.timeLeft=90;state.lastEvent='JANG!';}
    return state;
  }
  if(state.phase==='round_end'){
    if(state.timeLeft<=1e-7){state.round++;setupRound(state);}
    return state;
  }
  if(state.phase!=='live')return state;
  for(const p of state.players){
    if(!p.alive)continue;
    const input=inputs.get(p.id)||{};
    if(Number.isFinite(input.angle))p.angle=Math.atan2(Math.sin(input.angle),Math.cos(input.angle));
    move(p,input.mx,input.my,dt);
    p.cooldown=Math.max(0,p.cooldown-dt);
    if(p.reloading>0){
      p.reloading=Math.max(0,p.reloading-dt);
      if(p.reloading<=1e-7){
        const count=Math.min(WEAPONS[p.weapon].magazine-p.ammo,p.reserve);
        p.ammo+=count;p.reserve-=count;p.reloading=0;
      }
    }
    const w=WEAPONS[p.weapon];
    if(input.reload&&p.reloading<=0&&p.ammo<w.magazine&&p.reserve>0)p.reloading=w.reloadTime;
    if(input.fire&&!input.interact)shoot(state,p);
  }
  updateBomb(state,inputs,dt);
  if(state.phase!=='live')return state;
  const blue=state.players.some(p=>p.team==='blue'&&p.alive);
  const red=state.players.some(p=>p.team==='red'&&p.alive);
  if(!blue)finishRound(state,'red','Himoyachilar yengildi');
  else if(!red&&!state.bomb.planted)finishRound(state,'blue','Hujumchilar yengildi');
  else if(state.timeLeft<=0&&!state.bomb.planted)finishRound(state,'blue','Vaqt tugadi');
  return state;
}

export function snapshotFor(state,id) {
  id=String(id);
  const self=state.players.find(p=>p.id===id);
  const team=self?.team||'blue';
  const observers=state.players.filter(p=>p.team===team&&p.alive);
  const canSee=target=>observers.some(p=>distance(p,target)<=1000&&lineOfSight(p,target));
  const visible=state.players.filter(p=>p.team===team||(!p.alive&&canSee(p))||(p.alive&&canSee(p)));
  const players=visible.map(p=>{
    const out={id:p.id,name:p.name,team:p.team,x:p.x,y:p.y,angle:p.angle,
      hp:Math.ceil(p.hp),armor:Math.ceil(p.armor),alive:p.alive,weapon:p.weapon,
      kills:p.kills,deaths:p.deaths,connected:p.connected!==false,bot:p.bot};
    if(p.id===id)Object.assign(out,{ammo:p.ammo,reserve:p.reserve,credits:p.credits,reloading:p.reloading});
    return out;
  });
  const bomb={...state.bomb};
  if(!bomb.planted){
    const carrier=state.players.find(p=>p.id===bomb.carrierId);
    if(!(carrier?.team===team)&&!canSee(bomb)){
      bomb.x=null;bomb.y=null;bomb.carrierId=null;bomb.site=null;
      bomb.progress=0;bomb.action=null;bomb.actorId=null;
    }
  }
  const effects=state.effects.filter(e=>e.team===team||canSee(e)).map(e=>({...e}));
  return {phase:state.phase,timeLeft:state.timeLeft,round:state.round,score:{...state.score},
    players,bomb,effects,winner:state.winner,roundWinner:state.roundWinner,
    youId:id,spectatingId:self?.alive?id:(observers[0]?.id||null),lastEvent:state.lastEvent,
    roster:state.players.map(p=>({id:p.id,name:p.name,team:p.team,kills:p.kills,deaths:p.deaths,
      alive:p.alive,connected:p.connected!==false,bot:p.bot}))};
}

/* Fixed navigation grid for practice bots. Only server/local practice calls AI. */
const CELL=40,COLS=40,ROWS=25;
const navOpen=Array.from({length:COLS*ROWS},(_,i)=>!blocked((i%COLS+.5)*CELL,(Math.floor(i/COLS)+.5)*CELL,RADIUS+2));
function cellOf(p){return Math.max(0,Math.min(ROWS-1,Math.floor(p.y/CELL)))*COLS+Math.max(0,Math.min(COLS-1,Math.floor(p.x/CELL)));}
function route(from,to){
  const start=cellOf(from),target=cellOf(to);if(start===target)return to;
  const queue=[start],seen=new Set([start]),parent=new Map();let end=start,best=Infinity;
  for(let qi=0;qi<queue.length;qi++){
    const current=queue[qi],cx=current%COLS,cy=Math.floor(current/COLS);
    const dd=Math.hypot(cx-target%COLS,cy-Math.floor(target/COLS));
    if(dd<best){best=dd;end=current;}if(current===target)break;
    for(const [nx,ny] of [[cx-1,cy],[cx+1,cy],[cx,cy-1],[cx,cy+1]]){
      const next=ny*COLS+nx;
      if(nx<0||ny<0||nx>=COLS||ny>=ROWS||seen.has(next)||!navOpen[next])continue;
      seen.add(next);parent.set(next,current);queue.push(next);
    }
  }
  while(parent.has(end)&&parent.get(end)!==start)end=parent.get(end);
  return {x:(end%COLS+.5)*CELL,y:(Math.floor(end/COLS)+.5)*CELL};
}

export function botInputs(state) {
  const result=new Map();
  for(const p of state.players){
    if(!p.bot||!p.alive||p.connected===false)continue;
    if(state.phase==='buy'&&!p.botBought){
      buyWeapon(state,p.id,p.credits>=1800?'rifle':p.credits>=1200?'smg':'pistol');p.botBought=true;
    }
    const input={mx:0,my:0,angle:p.angle,fire:false,reload:false,interact:false};
    if(state.phase!=='live'){result.set(p.id,input);continue;}
    const enemies=state.players.filter(e=>e.alive&&e.team!==p.team&&lineOfSight(p,e)&&distance(p,e)<950);
    enemies.sort((a,b)=>distance(p,a)-distance(p,b));
    let target=null;
    const bomb=state.bomb;
    if(enemies.length){
      target=enemies[0];input.angle=Math.atan2(target.y-p.y,target.x-p.x);
      input.fire=true;
      if(distance(p,target)<WEAPONS[p.weapon].range*.8){
        input.mx=Math.cos(state.elapsed*1.3+state.players.indexOf(p))*.35;
        input.my=Math.sin(state.elapsed*1.3+state.players.indexOf(p))*.35;
      }
    }
    if(p.ammo===0||(!enemies.length&&p.ammo<WEAPONS[p.weapon].magazine*.5))input.reload=true;
    let objective=null;
    if(bomb.planted){
      if(p.team==='blue'){
        objective=bomb;
        if(distance(p,bomb)<55){input.mx=input.my=0;input.interact=true;input.fire=false;}
      }else objective={x:bomb.x+(state.players.indexOf(p)%2?100:-100),y:bomb.y+100};
    }else if(p.team==='red'){
      if(bomb.carrierId===p.id){
        objective=MAP.sites[state.round%2];
        if(distance(p,objective)<objective.r-15&&!enemies.length){input.mx=input.my=0;input.interact=true;}
      }else if(!bomb.carrierId)objective=bomb;
      else {const carrier=state.players.find(e=>e.id===bomb.carrierId);objective=carrier?{x:carrier.x+(state.players.indexOf(p)%2?50:-50),y:carrier.y+70}:MAP.sites[0];}
    }else objective=MAP.sites[state.players.indexOf(p)%2];
    if(objective&&!input.interact&&!enemies.length){
      if(distance(p,objective)>45){
        let point=objective;
        if(!lineOfSight(p,objective)){
          if(!p._aiPoint||state.elapsed-p._aiPathAt>.4||distance(p,p._aiPoint)<12){
            p._aiPoint=route(p,objective);p._aiPathAt=state.elapsed;
          }
          point=p._aiPoint;
        }
        const angle=Math.atan2(point.y-p.y,point.x-p.x);
        input.mx=Math.cos(angle);input.my=Math.sin(angle);input.angle=angle;
      }
    }
    result.set(p.id,input);
  }
  return result;
}
