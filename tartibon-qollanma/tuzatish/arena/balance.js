const SA=require('./sa-core.js');
function mulberry(s){return function(){s|=0;s=s+0x6D2B79F5|0;var t=Math.imul(s^s>>>15,1|s);t=t+Math.imul(t^t>>>7,61|t)^t;return((t^t>>>14)>>>0)/4294967296}}
/* har jangga tasodifiy xarakter: tajovuzkorlik, reaksiya kechikishi, blok odati, afzal masofa */
function mkAI(rnd){
  return {agr:0.45+rnd()*0.5, lag:1+Math.floor(rnd()*7), blk:0.3+rnd()*0.55,
          pref:0.62+rnd()*0.34, err:0.03+rnd()*0.09, buf:[], jmp:0.002+rnd()*0.006};
}
function ai(S,i,A,rnd){
  const a=S.p[i],o=S.p[1-i],w=SA.WEAPONS[a.wp];
  const dx=(o.x-a.x)*a.face, reach=w.masofa+SA.PW;
  let I={l:false,r:false,j:false,a:false,b:false};
  if(rnd()<A.err) { A.buf.push(I); return A.buf.length>A.lag?A.buf.shift():{l:0,r:0,j:0,a:0,b:0}; }
  if(o.atk>0 && dx<reach*1.3 && rnd()<A.blk) I.b=true;
  else{
    const want=reach*A.pref;
    if(dx>want+6){ if(a.face>0)I.r=true; else I.l=true; }
    else if(dx<want-14 && rnd()>A.agr){ if(a.face>0)I.l=true; else I.r=true; }
    if(dx>0 && dx<reach*0.98 && a.cd===0 && rnd()<A.agr+0.35) I.a=true;
    if(rnd()<A.jmp) I.j=true;
  }
  A.buf.push(I);                                  /* reaksiya kechikishi */
  return A.buf.length>A.lag ? A.buf.shift() : {l:0,r:0,j:0,a:0,b:0};
}
function duel(P0,P1,seed){
  const r=mulberry(seed), A0=mkAI(r), A1=mkAI(r);
  let S=SA.newState(P0,P1,seed),n=0;
  while(S.phase!=='finished' && n<30*75){ SA.tick(S, ai(S,0,A0,r), ai(S,1,A1,r)); n++; }
  if(S.phase==='finished') return S.winner;
  return S.p[0].hp===S.p[1].hp ? -1 : (S.p[0].hp>S.p[1].hp?0:1);
}
function rate(P0,P1,N){let w=0,d=0;for(let i=0;i<N;i++){const r=duel(P0,P1,i*7919+13);if(r===0)w++;else if(r<0)d++}return w/(N-d||1)}
module.exports={rate,duel};
if(require.main===module){
  const N=240, BEPUL={ch:'oddiy',wp:'musht'};
  console.log("=== Bepul (Oddiy + musht) qurolli raqiblarga qarshi, "+N+" jang ===");
  [['oddiy','xanjar'],['oddiy','qilich'],['oddiy','nayza'],['oddiy','bolta'],['oddiy','bolgha'],
   ['ninja','qilich'],['ritsar','bolta'],['samuray','qilich'],['berserk','bolgha']].forEach(x=>{
    const p=rate(BEPUL,{ch:x[0],wp:x[1]},N);
    const bar='#'.repeat(Math.round(p*20)).padEnd(20,'.');
    console.log('  bepul vs '+(x[0]+'+'+x[1]).padEnd(16)+bar+' '+(p*100).toFixed(0)+'%');
  });
  console.log("\n=== Qurollar o'zaro (Oddiy personaj) ===");
  const WP=['musht','xanjar','qilich','nayza','bolta','bolgha'];
  console.log('          '+WP.map(w=>w.slice(0,6).padStart(7)).join(''));
  WP.forEach(a=>console.log('  '+a.padEnd(8)+WP.map(b=>a===b?'      —':((rate({ch:'oddiy',wp:a},{ch:'oddiy',wp:b},100)*100).toFixed(0)+'%').padStart(7)).join('')));
  console.log("\n=== Personajlar o'zaro (qilich) ===");
  const CH=['oddiy','ninja','ritsar','samuray','berserk'];
  console.log('           '+CH.map(c=>c.slice(0,7).padStart(8)).join(''));
  CH.forEach(a=>console.log('  '+a.padEnd(9)+CH.map(b=>a===b?'       —':((rate({ch:a,wp:'qilich'},{ch:b,wp:'qilich'},100)*100).toFixed(0)+'%').padStart(8)).join('')));
}
