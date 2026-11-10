const {chromium}=require('playwright');
(async()=>{
 const br=await chromium.launch({args:['--use-gl=angle','--use-angle=swiftshader','--enable-unsafe-swiftshader']});
 const pg=await br.newPage({viewport:{width:430,height:860},deviceScaleFactor:1});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,260)));
 pg.on('console',m=>{if(m.type()==='error')errs.push('con: '+m.text().slice(0,200))});
 await pg.route('**/telegram.org/**',r=>r.abort());
 await pg.route('**/fonts.googleapis.com/**',r=>r.abort());
 await pg.goto('http://127.0.0.1:8733/');
 await pg.waitForTimeout(1200);
 console.log('sahifa xatolari:',await pg.evaluate(()=>window.__ERR));

 // SHASHKA bo'limiga o'tamiz
 await pg.evaluate(()=>go('sh'));
 await pg.waitForTimeout(900);
 const panel=await pg.evaluate(()=>{const e=document.getElementById('shb');return e?e.innerText.replace(/\n+/g,' | ').slice(0,260):'YO\'Q'});
 console.log('panel:',panel);
 await pg.screenshot({path:'e_panel.png'});

 // poygani boshlaymiz
 await pg.evaluate(()=>shOpen());
 await pg.waitForTimeout(9000);
 console.log('sahna:',await pg.evaluate(()=>{const s=document.getElementById('shst');
   return {ochiq:s&&!s.classList.contains('off'),ov:document.getElementById('shov').className,run:SH.run,sc:!!SH.sc}}));
 await pg.screenshot({path:'e_race.png'});

 // gaz va burilish
 await pg.evaluate(()=>SH.sc.setPedals({throttle:true}));
 await pg.waitForTimeout(2500);
 await pg.evaluate(()=>SH.sc.steer(1));
 await pg.waitForTimeout(2000);
 console.log('HUD:',await pg.evaluate(()=>({tezlik:document.getElementById('shspd').textContent,
   masofa:document.getElementById('shdis').textContent,yaqin:document.getElementById('shnr').textContent})));
 await pg.screenshot({path:'e_hud.png'});

 // to'qnashuvni majburan chaqiramiz: tezlikni oshirib kutamiz
 const t0=Date.now();
 while(Date.now()-t0<90000){
   const over=await pg.evaluate(()=>!document.getElementById('shov').classList.contains('off'));
   if(over)break;
   await pg.waitForTimeout(1000);
 }
 await pg.waitForTimeout(2500);
 console.log('yakun oynasi:',await pg.evaluate(()=>{const e=document.getElementById('shov');
   return e.classList.contains('off')?'(hali ochilmadi)':e.innerText.replace(/\n+/g,' | ').slice(0,200)}));
 await pg.screenshot({path:'e_over.png'});
 console.log('api chaqiruvlar:',await pg.evaluate(()=>window.__API.filter(a=>a.indexOf('sh_')===0)));
 console.log('xatolar:',errs.length?errs.slice(0,5):'yo\'q');
 await br.close();})();
