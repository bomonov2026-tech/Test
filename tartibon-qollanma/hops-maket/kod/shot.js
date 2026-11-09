const {chromium}=require('playwright'); const path=require('path');
const ROOFS=[[-80,420,1180],[470,300,1040],[880,340,1250]];
(async()=>{const br=await chromium.launch({args:['--force-color-profile=srgb','--font-render-hinting=none']});
 const pg=await br.newPage({viewport:{width:1080,height:1920},deviceScaleFactor:1});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,200)));
 await pg.goto('file://'+path.resolve('page.html'));await pg.waitForTimeout(400);
 if(errs.length){console.log('XATO:',errs);await br.close();return}
 await pg.evaluate(R=>scene({
   hops:47, best:86, hat:'🎩 4-daraja', left:'07:20', leader:86,
   roofs:R, man:[250,1180,1.5], pose:'charge', hatCol:'#FFC928', pw:0.62,
   arc:[250,1180,610,300,1040]
 }),ROOFS);
 await pg.waitForTimeout(150);await pg.screenshot({path:'h1.png'});
 await pg.evaluate(R=>res({hops:86, roofs:R}),ROOFS);
 await pg.waitForTimeout(150);await pg.screenshot({path:'h2.png'});
 await pg.evaluate(()=>post({}));
 await pg.waitForTimeout(150);await pg.screenshot({path:'h3.png'});
 console.log('xato:',errs.length?errs:'yo\'q');await br.close();})();
