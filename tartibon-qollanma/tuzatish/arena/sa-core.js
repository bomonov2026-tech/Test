/* ===== Stickman Arena — jang yadrosi (deterministik, serverda ham ishlaydi) =====
   Klient faqat input yuboradi; bu yadro holatni hisoblaydi.
   Hech qanday DOM/canvas yo'q — faqat matematika.                                */
var SA = (function(){

/* ---------- personajlar ---------- */
var CHARS = {
  oddiy:   {nom:"Oddiy",   tezlik:1.00, kuch:1.00, himoya:1.00, jon:100, narx:0,    rang:'#CFE9FF'},
  ninja:   {nom:"Ninja",   tezlik:1.32, kuch:0.94, himoya:0.97, jon:97,  narx:600,  rang:'#36D1FF'},
  ritsar:  {nom:"Ritsar",  tezlik:0.88, kuch:0.98, himoya:1.09, jon:108, narx:800,  rang:'#C8CEDA'},
  samuray: {nom:"Samuray", tezlik:1.08, kuch:1.10, himoya:0.95, jon:98,  narx:1100, rang:'#FF8A5C'},
  berserk: {nom:"Berserk", tezlik:0.96, kuch:1.28, himoya:0.82, jon:104, narx:1400, rang:'#FF4D6D'}
};
/* ---------- qurollar: zarar, masofa, tanaffus (kadr), zarba davomiyligi ---------- */
var WEAPONS = {
  musht:  {nom:"Musht",  zarar:9,  masofa:30, tanaffus:18, hit:5,  narx:0,    mv:1.14},
  xanjar: {nom:"Xanjar", zarar:10, masofa:42, tanaffus:21, hit:6,  narx:350,  mv:1.08},
  qilich: {nom:"Qilich", zarar:15, masofa:58, tanaffus:31, hit:9,  narx:700,  mv:1.00},
  nayza:  {nom:"Nayza",  zarar:16, masofa:80, tanaffus:34, hit:11, narx:900,  mv:0.96},
  bolta:  {nom:"Bolta",  zarar:21, masofa:52, tanaffus:42, hit:12, narx:1200, mv:0.94},
  bolgha: {nom:"Bolg'a", zarar:27, masofa:48, tanaffus:55, hit:14, narx:1500, mv:0.90}
};

/* ---------- maydon ---------- */
var W = 1000, GND = 0, PW = 26, PH = 96;   /* arena kengligi (o'yin birligi) */
var TICK = 1000/30;                         /* 30 Hz */
var DUEL_TICKS = 45*30, CD_TICKS = 3*30;

function mk(side, charKey, wKey){
  var c = CHARS[charKey] || CHARS.oddiy, w = WEAPONS[wKey] || WEAPONS.musht;
  return {
    side:side, ch:charKey, wp:wKey,
    x: side===0 ? W*0.30 : W*0.70, y:0, vx:0, vy:0,
    dir: side===0 ? 1 : -1,
    hp: c.jon, hpMax: c.jon,
    atk:0, atkDone:false, cd:0, blk:false, stun:0, face:side===0?1:-1,
    anim:'turish', animT:0, hitFx:0
  };
}
function newState(p0, p1, seed){
  return {
    t:0, phase:'countdown', cd:CD_TICKS, left:DUEL_TICKS,
    p:[mk(0,p0.ch,p0.wp), mk(1,p1.ch,p1.wp)],
    winner:-1, reason:'', seed:seed||1, ev:[]
  };
}
/* input: {l,r,j,a,b} — chap, o'ng, sakrash, zarba, blok */
function step(S, I0, I1){
  S.ev.length = 0;
  if (S.phase === 'countdown'){
    S.cd--; if (S.cd <= 0){ S.phase='fighting'; S.ev.push({e:'jang'}); }
    S.t++; return S;
  }
  if (S.phase !== 'fighting') { S.t++; return S; }

  var I = [I0||{}, I1||{}];
  for (var i=0;i<2;i++){
    var a = S.p[i], o = S.p[1-i], c = CHARS[a.ch], w = WEAPONS[a.wp], inp = I[i];
    a.face = (o.x >= a.x) ? 1 : -1;
    if (a.stun > 0){ a.stun--; a.blk=false; }
    else {
      a.blk = !!inp.b && a.atk===0 && a.y===0;
      /* yurish */
      var sp = 3.4 * c.tezlik * w.mv * (a.blk?0.35:1) * (a.atk>0?(0.18+0.34*w.mv):1);
      var mv = (inp.r?1:0) - (inp.l?1:0);
      a.vx = mv * sp;
      /* sakrash */
      if (inp.j && a.y===0 && a.atk===0){ a.vy = 13.2 * (0.92 + c.tezlik*0.08); a.anim='sakrash'; }
      /* zarba */
      if (inp.a && a.atk===0 && a.cd===0 && !a.blk){
        a.atk = Math.max(3, Math.round(w.hit / (c.tezlik*0.5+0.5)));
        a.atkDone = false;
        a.cd  = Math.max(6, Math.round(w.tanaffus / (c.tezlik*0.5+0.5)));
        S.ev.push({e:'zarba', i:i});
      }
    }
    /* fizika */
    a.x += a.vx;
    if (a.vy !== 0 || a.y > 0){ a.y += a.vy; a.vy -= 0.78; if (a.y <= 0){ a.y=0; a.vy=0; } }
    if (a.x < PW) a.x = PW;
    if (a.x > W-PW) a.x = W-PW;
    if (a.cd > 0) a.cd--;
  }
  /* zarbalarni hisoblash */
  for (var k=0;k<2;k++){
    var A = S.p[k], D = S.p[1-k];
    if (A.atk > 0){
      A.atk--;
      if (!A.atkDone && A.atk <= 1){
        A.atkDone = true;
        var wA = WEAPONS[A.wp], cA = CHARS[A.ch], cD = CHARS[D.ch];
        var reach = wA.masofa + PW;
        var dx = (D.x - A.x) * A.face;
        var dy = Math.abs(D.y - A.y);
        if (dx > -PW*0.5 && dx < reach && dy < PH*0.75){
          var dmg = wA.zarar * cA.kuch / cD.himoya;
          if (D.blk && (D.x - A.x)*D.face < 0){ dmg *= 0.25; S.ev.push({e:'blok', i:1-k}); }
          else { S.ev.push({e:'tegdi', i:1-k}); D.stun = 7; D.hitFx = 6; }
          dmg = Math.max(1, Math.round(dmg));
          D.hp = Math.max(0, D.hp - dmg);
          D.x += A.face * (D.blk ? 7 : 13);           /* orqaga surilish */
          if (D.x < PW) D.x = PW; if (D.x > W-PW) D.x = W-PW;
          if (D.hp <= 0){ S.phase='finished'; S.winner=k; S.reason='jon'; S.ev.push({e:'tugadi',w:k}); }
        }
      }
    }
    /* animatsiya nomi */
    if (D.hp<=0) D.anim='maglub';
    else if (A.atk>0) A.anim='zarba';
    else if (A.stun>0) A.anim='zarba_yedi';
    else if (A.blk) A.anim='blok';
    else if (A.y>0) A.anim='sakrash';
    else if (Math.abs(A.vx)>0.1) A.anim='yurish';
    else A.anim='turish';
    if (A.hitFx>0) A.hitFx--;
  }
  /* taymer */
  if (S.phase === 'fighting'){
    S.left--;
    if (S.left <= 0){
      if (S.p[0].hp !== S.p[1].hp){
        S.phase='finished'; S.winner = S.p[0].hp > S.p[1].hp ? 0 : 1; S.reason='vaqt';
        S.ev.push({e:'tugadi', w:S.winner});
      } else {
        S.phase='suddenDeath'; S.reason='teng'; S.ev.push({e:'oltin_zarba'});
      }
    }
  }
  S.t++;
  return S;
}
/* teng bo'lsa: birinchi tekkan zarba g'olibni aniqlaydi */
function stepSD(S, I0, I1){
  var before = [S.p[0].hp, S.p[1].hp];
  S.phase='fighting'; S.left=1e9; step(S, I0, I1); 
  if (S.phase==='fighting') S.phase='suddenDeath';
  for (var i=0;i<2;i++) if (S.p[i].hp < before[i]){
    S.phase='finished'; S.winner=1-i; S.reason='oltin_zarba';
    S.ev.push({e:'tugadi', w:S.winner}); break;
  }
  return S;
}
function tick(S, I0, I1){ return S.phase==='suddenDeath' ? stepSD(S,I0,I1) : step(S,I0,I1); }

return {CHARS:CHARS, WEAPONS:WEAPONS, W:W, PW:PW, PH:PH, TICK:TICK,
        DUEL_TICKS:DUEL_TICKS, CD_TICKS:CD_TICKS, newState:newState, tick:tick};
})();
if (typeof module !== 'undefined') module.exports = SA;
