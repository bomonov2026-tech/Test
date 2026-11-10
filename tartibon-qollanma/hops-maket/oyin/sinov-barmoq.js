const {chromium}=require('playwright');
(async()=>{
 const br=await chromium.launch();
 const pg=await br.newPage({viewport:{width:430,height:860}});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,250)));
 await pg.goto('file://'+process.argv[2]); await pg.waitForTimeout(500);
 await pg.evaluate(()=>{try{localStorage.removeItem('hops_best')}catch(e){}});
 await pg.reload(); await pg.waitForTimeout(500);

 // haqiqiy barmoq: bosish -> kutish -> qo'yib yuborish
 const log=[];
 await pg.mouse.move(215,600);
 await pg.mouse.down(); await pg.waitForTimeout(60); await pg.mouse.up();  // menyudan chiqish
 await pg.waitForTimeout(300);
 log.push('menyudan keyin: '+await pg.evaluate(()=>window.HOPS.G.state));

 for(let i=0;i<12;i++){
   // kerakli ushlab turish vaqtini hisoblab, shuncha millisekund bosib turamiz
   const ms=await pg.evaluate(()=>{
     const G=window.HOPS.G;
     if(G.p.ph!=='ready') return 0;
     const GRAV=2200,ANG=Math.PI/3,VMIN=420,VMAX=1150;
     const nx=G.roofs[G.p.roof+1];
     const D=(nx.x+nx.w*0.5)-G.p.x, dy=nx.top-G.p.y;
     const c=Math.cos(ANG), den=2*c*c*(dy+D*Math.tan(ANG));
     let p=(Math.sqrt(GRAV*D*D/den)-VMIN)/(VMAX-VMIN);
     p=Math.max(0.02,Math.min(1,p));
     return Math.round(p*window.HOPS.qtime()*1000);
   });
   if(!ms){await pg.waitForTimeout(120); i--; continue}
   await pg.mouse.down(); await pg.waitForTimeout(ms); await pg.mouse.up();
   await pg.waitForTimeout(900);
 }
 const st=await pg.evaluate(()=>{const G=window.HOPS.G;
   return {sakrash:G.hops,holat:G.state,rekord:G.best,toza:G.perfects}});
 console.log(log.join('\n'));
 console.log('haqiqiy bosish bilan:',JSON.stringify(st));
 await pg.screenshot({path:'s_real.png'});
 console.log('xato:',errs.length?errs:'yo\'q');
 await br.close();})();
