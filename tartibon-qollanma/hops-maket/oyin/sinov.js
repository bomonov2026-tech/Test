const {chromium}=require('playwright');
const file=process.argv[2];
const AIM=`
  const GRAV=2200,ANG=Math.PI/3,VMIN=420,VMAX=1150;
  function pw(G,aim){                    // aim: 0=yaqin chet, 1=uzoq chet
    const nx=G.roofs[G.p.roof+1];
    const D=(nx.x+nx.w*aim)-G.p.x, dy=nx.top-G.p.y;
    const c=Math.cos(ANG), den=2*c*c*(dy+D*Math.tan(ANG));
    return (Math.sqrt(GRAV*D*D/den)-VMIN)/(VMAX-VMIN);
  }`;
(async()=>{
 const br=await chromium.launch();
 const pg=await br.newPage({viewport:{width:430,height:860},deviceScaleFactor:2});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,300)));
 pg.on('console',m=>{if(m.type()==='error')errs.push('console: '+m.text().slice(0,200))});
 await pg.goto('file://'+file); await pg.waitForTimeout(500);
 if(errs.length){console.log('YUKLASHDA XATO:',errs);await br.close();process.exit(1)}

 const bot=await pg.evaluate(new Function('seed',AIM+`
   const H=window.HOPS,G=H.G; H.boshla(seed);
   let s=0,maxc=0,maxn=0;
   while(G.state==='play'&&s<60*300){
     if(G.p.ph==='ready'){
       const c=pw(G,0.5), n=pw(G,0.12);
       if(c>maxc)maxc=c; if(n>maxn)maxn=n;
       G.pw=Math.max(0,Math.min(1,c<=1?c:n)); G.hold=true; H.qoyildi();
     }
     H.qadam(1/60); s++;
   }
   return {hops:G.hops,state:G.state,sek:+(s/60).toFixed(1),
           markaz_max_quvvat:+maxc.toFixed(2), yaqin_max_quvvat:+maxn.toFixed(2)};`),12345);
 console.log('mukammal bot:',JSON.stringify(bot));

 const det=await pg.evaluate(()=>{
   const H=window.HOPS,G=H.G;
   function run(){H.boshla(777);const a=[0.31,0.58,0.42,0.77,0.5,0.66,0.38,0.9,0.45,0.6];
     let i=0,s=0;
     while(G.state==='play'&&s<3600){if(G.p.ph==='ready'){G.pw=a[i%a.length];i++;G.hold=true;H.qoyildi()}H.qadam(1/60);s++}
     return G.hops+':'+Math.round(G.p.x)+':'+s}
   return [run(),run(),run()];
 });
 console.log('determinizm:',det[0]===det[1]&&det[1]===det[2]?'OK ('+det[0]+')':'XATO '+JSON.stringify(det));

 for(const err of [0.020,0.035,0.055]){
   const hum=await pg.evaluate(new Function('E',AIM+`
     const H=window.HOPS,G=H.G; const res=[];
     for(let t=0;t<40;t++){
       H.boshla(5000+t); let s=0;
       while(G.state==='play'&&s<60*600){
         if(G.p.ph==='ready'){
           const c=pw(G,0.5), n=pw(G,0.12);
           const jt=E/window.HOPS.qtime();          // vaqt xatosi -> quvvat xatosi
           let p=(c<=1?c:n)+(Math.random()*2-1)*jt;
           G.pw=Math.max(0,Math.min(1,p)); G.hold=true; H.qoyildi();
         }
         H.qadam(1/60); s++;
       }
       res.push(G.hops);
     }
     res.sort((a,b)=>a-b);
     return {min:res[0],q1:res[10],median:res[20],q3:res[30],max:res[39],
             ortacha:+(res.reduce((a,b)=>a+b,0)/40).toFixed(1)};`),err);
   console.log('oyinchi ±'+Math.round(err*1000)+' ms kechikish:',JSON.stringify(hum));
 }

 await pg.evaluate(()=>{const H=window.HOPS;H.boshla();H.G.state='menu'});
 await pg.waitForTimeout(350); await pg.screenshot({path:'s_menyu.png'});
 await pg.evaluate(new Function(AIM+`
   const H=window.HOPS,G=H.G;H.boshla(2024);let s=0;
   while(G.hops<23&&s<60*300){
     if(G.p.ph==='ready'){const c=pw(G,0.5),n=pw(G,0.12);
       G.pw=Math.max(0,Math.min(1,c<=1?c:n));G.hold=true;H.qoyildi()}
     H.qadam(1/60);s++;}
   G.cam=G.p.x-(innerWidth/(innerHeight/1920))*0.30;
   G.hold=true;G.pw=0.62;G.p.ph='ready';`));
 await pg.waitForTimeout(350); await pg.screenshot({path:'s_oyin.png'});
 await pg.evaluate(()=>{const G=window.HOPS.G;G.state='over';G.t=1;G.perfects=9;G.bstreak=4});
 await pg.waitForTimeout(350); await pg.screenshot({path:'s_natija.png'});
 console.log('xato:',errs.length?errs:'yo\'q');
 await br.close();
})();
