import { MAP, WEAPONS, createMatch, stepMatch, buyWeapon, snapshotFor, botInputs } from '../shared/game.js';
import { ArenaRenderer } from './render.js';

const $=id=>document.getElementById(id);
const renderer=new ArenaRenderer($('world'),$('minimap'));
const input={mx:0,my:0,angle:0,fire:false,reload:false,interact:false};
const keys=new Set();let pointerMove={x:0,y:0},pointerAim=null,mouseAim=null;
let mode='lobby',local=null,view=null,room=null,playerId='',ws=null,session=null,authenticated=false;
let stopping=false,retrying=false,retryCount=0,retryTimer=0,initialJoined=false,wantedAction=null,lastSnapshot=0;
let lastFrame=performance.now(),lastSend=0,lastUI=0,raf=0,eventKey='',lastBuyRound=0,toastTimer=0,frameRunning=true;
let pendingState=null,shopSignature='';
const show=(id,on=true)=>{$(id).hidden=!on;};
const clamp=(n,a,b)=>Math.max(a,Math.min(b,n));
function notify(message){$('toast').textContent=String(message);show('toast');clearTimeout(toastTimer);toastTimer=setTimeout(()=>show('toast',false),3500)}
function setConnection(message,kind=''){$('connection').textContent=message;$('connection').className='connection '+kind;const ready=authenticated&&!!ws&&ws.readyState===WebSocket.OPEN;$('create').disabled=!ready;$('join').disabled=!ready;}
function send(message){if(ws?.readyState===WebSocket.OPEN&&authenticated){ws.send(JSON.stringify(message));return true}return false}
function clearInput(){keys.clear();pointerMove={x:0,y:0};pointerAim=null;mouseAim=null;Object.assign(input,{mx:0,my:0,fire:false,reload:false,interact:false});for(const id of ['move-stick','aim-stick']){$(id).classList.remove('active');$(id).querySelector('i').style.transform='translate(-50%,-50%)'}$('interact').classList.remove('active');window.dispatchEvent(new Event('ops:reset-controls'));if(mode==='online')send({type:'input',...input});}
function requestSession(){if(parent===window){setConnection('Ilovadan oching. Hozir botlar bilan mashq qilishingiz mumkin.','offline');return}parent.postMessage({type:'ops:ready'},location.origin)}
function connect(data){
  if(mode==='practice')return;
  if(ws&&[WebSocket.OPEN,WebSocket.CONNECTING].includes(ws.readyState))return;
  let url;try{url=new URL(data.wsUrl);if(url.protocol!=='wss:'&&!(url.protocol==='ws:'&&['localhost','127.0.0.1','[::1]'].includes(url.hostname)))throw Error()}catch{setConnection('O‘yin serveri manzili noto‘g‘ri. Mashq rejimi mavjud.','offline');return}
  authenticated=false;stopping=false;setConnection('Jang serveriga ulanmoqda…');
  try{ws=new WebSocket(url.href)}catch{setConnection('Serverga ulanib bo‘lmadi.','offline');return}
  const socket=ws;const timeout=setTimeout(()=>{if(!authenticated&&socket===ws)socket.close()},8000);
  socket.onopen=()=>socket.send(JSON.stringify({type:'auth',token:data.token}));
  socket.onmessage=event=>{
    if(socket!==ws)return;let msg;try{msg=JSON.parse(event.data)}catch{return}
    if(msg.type==='auth'){
      clearTimeout(timeout);authenticated=true;playerId=String(msg.playerId);retryCount=0;retrying=false;show('network',false);setConnection('● Jang serveriga ulandi','online');
      if(data.initialRoom&&!initialJoined){initialJoined=true;setTimeout(()=>{if(!room)send({type:'join',code:String(data.initialRoom).toUpperCase()})},150)}
      if(wantedAction){const action=wantedAction;wantedAction=null;send(action)}
    }else if(msg.type==='room'){
      room=msg.room;renderRoom();
    }else if(msg.type==='state'){
      lastSnapshot=performance.now();pendingState=msg.state;view=msg.state;playerId=String(msg.playerId||playerId);
      if(mode!=='online')enterGame('online');
    }else if(msg.type==='error'){notify(msg.message||'Xatolik yuz berdi.');}
    else if(msg.type==='bought'){notify('Qurol olindi.');}
    else if(msg.type==='left'){room=null;renderRoom();if(mode==='online')returnLobby(false);}
  };
  socket.onerror=()=>setConnection('Server bilan aloqa o‘rnatilmadi. Mashq rejimi mavjud.','offline');
  socket.onclose=()=>{
    clearTimeout(timeout);if(socket!==ws)return;ws=null;authenticated=false;setConnection('Server bilan aloqa uzildi.','offline');
    if(stopping||mode==='practice')return;
    clearInput();if(mode==='lobby'){room=null;renderRoom()}if(mode==='online'){$('network').textContent='Aloqa uzildi. Qayta ulanmoqda…';show('network')}
    if(retryCount<6){retrying=true;retryCount++;clearTimeout(retryTimer);retryTimer=setTimeout(requestSession,Math.min(10000,1000+retryCount*900));}
    else{retrying=false;if(mode==='online')$('network').textContent='Qayta ulanib bo‘lmadi. ← orqali chiqib qayta kiring.';}
  };
}
window.addEventListener('message',event=>{
  if(event.source!==parent||event.origin!==location.origin||event.data?.type!=='ops:session')return;
  const data=event.data;session=data;
  if(!data.ok){setConnection(data.error||'Online server sozlanmagan. Botlar bilan mashq qilish mumkin.','offline');if(mode==='online'){$('network').textContent=data.error||'Sessiyani yangilab bo‘lmadi.';show('network')}return}
  connect(data);
});
function action(message){if(send(message))return;wantedAction=message;setConnection('Ulanish yangilanmoqda…');retryCount=0;requestSession()}
function renderRoom(){
  show('room-panel',!!room);show('room-form',!room);
  if(!room)return;
  $('room-code').textContent=room.code;
  const roster=room.players||[];
  for(const team of ['blue','red']){
    const ps=roster.filter(p=>p.team===team);$(team+'-count').textContent=ps.length+'/5';const container=$(team+'-list');container.replaceChildren();
    for(let i=0;i<5;i++){const row=document.createElement('div');row.className='team-player'+(!ps[i]?' empty':String(ps[i].id)===playerId?' me':'');const dot=document.createElement('span');dot.className='dot';row.append(dot,document.createTextNode(ps[i]?(String(ps[i].name||'O‘yinchi')+(String(ps[i].id)===playerId?' · Siz':'')):'Bo‘sh joy'));container.append(row)}
    document.querySelector('[data-team="'+team+'"]').disabled=ps.length>=5||roster.find(p=>String(p.id)===playerId)?.team===team;
  }
  const blue=roster.filter(p=>p.team==='blue').length,red=roster.filter(p=>p.team==='red').length,host=String(room.hostId)===playerId;
  const canStart=room.status==='waiting'||room.status==='finished';$('start').disabled=!host||blue!==5||red!==5||!canStart;
  $('room-note').textContent=canStart?(host?(blue===5&&red===5?'Jamoalar tayyor. Jangni boshlang.':'Boshlash uchun har jamoaga 5 tadan o‘yinchi kerak.'):'Mezbon jangni boshlashini kuting.'):'Jang davom etmoqda.';
}
function enterGame(nextMode){
  mode=nextMode;show('lobby',false);show('game');show('buy',false);show('network',false);renderer.reset();lastBuyRound=0;shopSignature='';lastFrame=performance.now();eventKey='';
  $('mode').textContent=mode==='practice'?'MASHQ · 9 BOT':('XONA · '+(room?.code||''));clearInput();
}
function startPractice(){
  clearTimeout(retryTimer);stopping=true;if(ws){send({type:'leave'});ws.close();ws=null;authenticated=false}room=null;renderRoom();
  playerId='practice-you';const roster=[{id:playerId,name:session?.name||'Siz',team:'blue',bot:false}];
  ['Jasur','Madina','Bekzod','Temur','Aziz','Nodir','Sardor','Lola','Akmal'].forEach((name,i)=>roster.push({id:'bot-'+i,name,team:i<4?'blue':'red',bot:true}));
  local=createMatch(roster,{practice:true});view=snapshotFor(local,playerId);enterGame('practice');notify('Mashq boshlandi. Chapda yuring, o‘ngda nishonga olib oting.');
}
function returnLobby(leave=true){
  if(leave&&mode==='online')send({type:'leave'});clearInput();local=null;view=null;pendingState=null;mode='lobby';show('game',false);show('lobby');show('buy',false);show('announcement',false);show('network',false);renderer.reset();
  if(leave){room=null;renderRoom()}stopping=false;lastFrame=performance.now();if(!authenticated){retryCount=0;requestSession()}
}
function exitApp(){clearInput();stopping=true;clearTimeout(retryTimer);if(ws){send({type:'leave'});ws.close();ws=null}if(parent!==window)parent.postMessage({type:'ops:exit'},location.origin);else returnLobby()}
function maybeExit(){if(mode==='lobby')return exitApp();if(mode==='practice'||view?.phase==='finished'||confirm('Jangdan chiqasizmi? Jamoangiz sizsiz qoladi.'))returnLobby();}
function drawWeapons(){
  const self=view?.players?.find(p=>String(p.id)===playerId);const signature=[view?.round,view?.phase,self?.weapon,self?.credits].join(':');if(signature===shopSignature)return;shopSignature=signature;const list=$('weapon-list');list.replaceChildren();
  const glyphs={pistol:'╶━╸',smg:'╶━━',rifle:'╾━╤━',shotgun:'╾━━',sniper:'╾━╪━━',knife:'━━▷'};
  for(const [key,w] of Object.entries(WEAPONS)){
    const b=document.createElement('button');b.className='weapon-card'+(self?.weapon===key?' selected':'');b.dataset.weapon=key;
    const icon=document.createElement('span');icon.className='gun-icon';icon.textContent=glyphs[key]||'╾━━';
    const name=document.createElement('strong');name.textContent=w.name;const detail=document.createElement('small');detail.textContent=`Zarba ${w.damage} · Masofa ${w.range}`;
    const price=document.createElement('span');price.className='price';price.textContent=self?.weapon===key?'TANLANGAN':w.cost?'$ '+w.cost.toLocaleString('en-US'):'BEPUL';
    b.append(icon,name,detail,price);b.disabled=view?.phase!=='buy'||self?.weapon===key||(self?.credits??0)<w.cost;
    b.onclick=()=>{if(mode==='practice'){const r=buyWeapon(local,playerId,key);if(!r.ok)notify(r.error||'Qurolni olib bo‘lmadi.');view=snapshotFor(local,playerId);drawWeapons()}else send({type:'buy',weapon:key})};list.append(b);
  }
}
function toggleBuy(){if(mode==='lobby')return;if(view?.phase!=='buy'){notify('Qurol faqat raund oldidan olinadi.');return}show('buy',$('buy').hidden);if(!$('buy').hidden){clearInput();drawWeapons()}}
function updateUI(){
  if(!view||mode==='lobby')return;const self=view.players?.find(p=>String(p.id)===playerId),spectator=view.players?.find(p=>p.id===view.spectatingId),phase=view.phase;
  $('score-blue').textContent=view.score?.blue??0;$('score-red').textContent=view.score?.red??0;
  const seconds=Math.ceil(Math.max(0,view.bomb?.planted?view.bomb.timer:view.timeLeft));$('timer').textContent=String(Math.floor(seconds/60)).padStart(2,'0')+':'+String(seconds%60).padStart(2,'0');
  const labels={buy:'QUROL TANLASH',countdown:'TAYYORLANING',live:view.bomb?.planted?'BOMBA O‘RNATILDI':'RAUND '+view.round,round_end:'RAUND YAKUNI',finished:'O‘YIN YAKUNI'};$('phase-label').textContent=labels[phase]||phase;
  $('credits').textContent='$ '+Math.floor(self?.credits??0).toLocaleString('en-US');$('player-name').textContent=self?.name||'SIZ';$('health').textContent=Math.max(0,Math.ceil(self?.hp??0));$('health-fill').style.width=Math.max(0,self?.hp??0)+'%';$('armor').textContent='◇ '+Math.ceil(self?.armor??0);
  $('weapon-name').textContent=(WEAPONS[self?.weapon]?.name||self?.weapon||'').toUpperCase();$('ammo').textContent=self?.weapon==='knife'?'∞':self?.ammo??'–';$('reserve').textContent=self?.reserve??'–';$('reload-label').textContent=self?.reloading?'O‘Q ALMASHMOQDA…':'';
  $('buy-open').disabled=phase!=='buy';
  if(phase==='buy'&&lastBuyRound!==view.round){lastBuyRound=view.round;show('buy');clearInput();drawWeapons()}else if(phase!=='buy')show('buy',false);
  if(phase==='buy'&&!$('buy').hidden)drawWeapons();
  const announce=['countdown','round_end','finished'].includes(phase);show('announcement',announce);show('result-exit',phase==='finished');
  if(announce){
    $('announcement-top').textContent=phase==='countdown'?'RAUND '+view.round:phase==='finished'?'O‘YIN YAKUNLANDI':'RAUND YAKUNLANDI';
    const winner=phase==='finished'?view.winner:view.roundWinner;
    $('announcement-title').textContent=phase==='countdown'?String(Math.max(1,Math.ceil(view.timeLeft))):(winner==='blue'?'HIMOYA G‘OLIB':winner==='red'?'HUJUM G‘OLIB':'RAUND TUGADI');
    $('announcement-sub').textContent=phase==='countdown'?'Jamoang bilan birga harakat qil.':phase==='finished'?`${view.score?.blue??0} : ${view.score?.red??0}`:'Keyingi raundga tayyorlaning.';
  }
  show('spectating',!!self&&!self.alive&&phase==='live');$('spectating').textContent=spectator?'KUZATUV: '+spectator.name:'Keyingi raundni kuting';
  const b=view.bomb;show('bomb-progress',!!b?.action&&b?.actorId===playerId);if(b?.action){$('bomb-label').textContent=b.action==='plant'?'BOMBA O‘RNATILMOQDA…':'ZARARSIZLANTIRILMOQDA…';const total=b.action==='plant'?3:5;$('bomb-fill').style.width=clamp((b.progress||0)/total*100,0,100)+'%'}
  const event=typeof view.lastEvent==='string'?view.lastEvent:view.lastEvent?.text||view.lastEvent?.message||'';if(event!==eventKey){eventKey=event;$('event').textContent=event;}
  if(mode==='online'&&performance.now()-lastSnapshot>4000){$('network').textContent='Server javobi kechikmoqda…';show('network')}else if(authenticated)show('network',false);
}
function updateInput(){
  const keyboardX=(keys.has('KeyD')||keys.has('ArrowRight')?1:0)-(keys.has('KeyA')||keys.has('ArrowLeft')?1:0);
  const keyboardY=(keys.has('KeyS')||keys.has('ArrowDown')?1:0)-(keys.has('KeyW')||keys.has('ArrowUp')?1:0);
  input.mx=clamp(keyboardX+pointerMove.x,-1,1);input.my=clamp(keyboardY+pointerMove.y,-1,1);
  if(pointerAim)input.angle=Math.atan2(pointerAim.y,pointerAim.x);
  else if(mouseAim){const self=view?.players?.find(p=>String(p.id)===playerId);if(self){const point=renderer.screenToWorld(mouseAim.x,mouseAim.y);input.angle=Math.atan2(point.y-self.y,point.x-self.x)}}
  if(!$('buy').hidden){input.mx=0;input.my=0;input.fire=false;input.interact=false;}
}
function frame(now){
  if(!frameRunning)return;
  const dt=Math.min(.05,Math.max(.001,(now-lastFrame)/1000));lastFrame=now;
  if(mode==='practice'&&local){updateInput();const inputs=botInputs(local);inputs.set(playerId,{...input});stepMatch(local,inputs,dt);view=snapshotFor(local,playerId);}
  else if(mode==='online'){updateInput();if(now-lastSend>40){send({type:'input',...input});lastSend=now}}
  renderer.draw(view,playerId,{preview:mode==='lobby',dt,time:now/1000});
  if(now-lastUI>100){updateUI();lastUI=now}raf=requestAnimationFrame(frame);
}
function joystick(id,kind){
  const el=$(id),knob=el.querySelector('i');let pointer=null;
  const end=e=>{if(pointer!==e.pointerId)return;pointer=null;el.classList.remove('active');knob.style.transform='translate(-50%,-50%)';if(kind==='move')pointerMove={x:0,y:0};else{pointerAim=null;input.fire=false;}};
  const move=e=>{if(pointer!==e.pointerId)return;e.preventDefault();const rect=el.getBoundingClientRect(),dx=e.clientX-rect.left-rect.width/2,dy=e.clientY-rect.top-rect.height/2,r=rect.width*.33,d=Math.hypot(dx,dy),s=d>r?r/d:1;knob.style.transform=`translate(calc(-50% + ${dx*s}px),calc(-50% + ${dy*s}px))`;if(kind==='move')pointerMove={x:d<7?0:clamp(dx/r,-1,1),y:d<7?0:clamp(dy/r,-1,1)};else{pointerAim={x:dx,y:dy};input.fire=d>10;}};
  el.addEventListener('pointerdown',e=>{e.preventDefault();if(pointer!==null)return;pointer=e.pointerId;el.setPointerCapture(pointer);el.classList.add('active');move(e)});
  window.addEventListener('ops:reset-controls',()=>{const previous=pointer;pointer=null;if(previous!==null&&el.hasPointerCapture(previous))el.releasePointerCapture(previous)});
  el.addEventListener('pointermove',move);for(const event of ['pointerup','pointercancel','lostpointercapture'])el.addEventListener(event,end);
}
joystick('move-stick','move');joystick('aim-stick','aim');
function holdButton(id,key){const el=$(id);let pointer=null;el.addEventListener('pointerdown',e=>{e.preventDefault();pointer=e.pointerId;el.setPointerCapture(pointer);input[key]=true;el.classList.add('active')});for(const event of ['pointerup','pointercancel','lostpointercapture'])el.addEventListener(event,e=>{if(e.pointerId!==pointer)return;pointer=null;input[key]=false;el.classList.remove('active')});window.addEventListener('ops:reset-controls',()=>{const previous=pointer;pointer=null;el.classList.remove('active');if(previous!==null&&el.hasPointerCapture(previous))el.releasePointerCapture(previous)})}
holdButton('interact','interact');holdButton('reload','reload');
$('world').addEventListener('pointermove',e=>{if(e.pointerType==='mouse')mouseAim={x:e.clientX,y:e.clientY}});
$('world').addEventListener('pointerdown',e=>{if(mode==='lobby'||e.pointerType!=='mouse'||e.button!==0)return;e.preventDefault();mouseAim={x:e.clientX,y:e.clientY};input.fire=true;$('world').setPointerCapture(e.pointerId)});
for(const event of ['pointerup','pointercancel'])$('world').addEventListener(event,e=>{if(e.pointerType==='mouse')input.fire=false});
window.addEventListener('contextmenu',e=>{if(mode!=='lobby')e.preventDefault()});
window.addEventListener('keydown',e=>{
  if(mode==='lobby'||e.target instanceof HTMLInputElement)return;
  if(['KeyW','KeyA','KeyS','KeyD','ArrowUp','ArrowDown','ArrowLeft','ArrowRight','Space','KeyE','KeyR','KeyB'].includes(e.code))e.preventDefault();
  keys.add(e.code);if(e.code==='KeyR')input.reload=true;if(e.code==='KeyE')input.interact=true;if(e.code==='KeyB'&&!e.repeat)toggleBuy();if(e.code==='Escape'){$('buy').hidden?maybeExit():show('buy',false)}
});
window.addEventListener('keyup',e=>{keys.delete(e.code);if(e.code==='KeyR')input.reload=false;if(e.code==='KeyE')input.interact=false});window.addEventListener('blur',clearInput);
document.addEventListener('visibilitychange',()=>{
  clearInput();if(document.hidden){frameRunning=false;cancelAnimationFrame(raf)}else{frameRunning=true;lastFrame=performance.now();raf=requestAnimationFrame(frame);if(mode==='online'&&(!ws||ws.readyState===WebSocket.CLOSED)){retryCount=0;requestSession()}}
});
window.addEventListener('pagehide',()=>{stopping=true;clearInput();clearTimeout(retryTimer);cancelAnimationFrame(raf);frameRunning=false;if(ws)ws.close();renderer.destroy()});
document.querySelectorAll('[data-exit]').forEach(b=>b.onclick=exitApp);$('game-exit').onclick=maybeExit;$('result-exit').onclick=()=>returnLobby(mode!=='online');
$('practice').onclick=startPractice;$('create').onclick=()=>action({type:'create'});
$('join-form').onsubmit=e=>{e.preventDefault();const code=$('code').value.trim().toUpperCase();if(!/^[A-Z0-9]{4,8}$/.test(code))return notify('To‘g‘ri xona kodini kiriting.');action({type:'join',code})};
$('code').oninput=e=>e.target.value=e.target.value.toUpperCase().replace(/[^A-Z0-9]/g,'');
$('start').onclick=()=>send({type:'start'});$('leave-room').onclick=()=>{send({type:'leave'});room=null;renderRoom()};
document.querySelectorAll('[data-team]').forEach(b=>b.onclick=()=>send({type:'team',team:b.dataset.team}));
$('buy-open').onclick=toggleBuy;$('buy-close').onclick=()=>show('buy',false);
$('share').onclick=async()=>{
  if(!room)return;let text='STICK OPS 5v5 — xona kodi: '+room.code;
  const username=String(session?.botUsername||'').replace(/^@/,'');
  if(/^[A-Za-z0-9_]{5,32}$/.test(username))text='https://t.me/'+username+'?startapp=ops_'+encodeURIComponent(room.code);
  try{await navigator.clipboard.writeText(text);notify('Taklif havolasi nusxalandi. Guruhingizga yuboring.')}catch{prompt('Do‘stlaringizga yuboring:',text)}
};
requestSession();setTimeout(()=>{if(!session&&!authenticated)setConnection('Online ulanish kutilmoqda. Botlar bilan mashq qilish mumkin.','offline')},2500);raf=requestAnimationFrame(frame);
