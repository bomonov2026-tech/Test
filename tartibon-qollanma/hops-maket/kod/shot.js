const {chromium}=require('playwright'); const path=require('path');
const R=[[-90,430,1190],[470,310,1050],[890,340,1260]];
(async()=>{const br=await chromium.launch({args:['--force-color-profile=srgb','--font-render-hinting=none']});
 const pg=await br.newPage({viewport:{width:1080,height:1920},deviceScaleFactor:1});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,300)));
 await pg.goto('file://'+path.resolve('page.html'));await pg.waitForTimeout(400);
 if(errs.length){console.log('XATO:',errs);await br.close();return}
 await pg.evaluate(R=>scene({hops:47,best:86,hat:'🎩 4-daraja',left:'07:20',leader:86,
   roofs:R,man:[250,1190,1.72],pose:'charge',ph:0.4,pw:0.62,arc:[250,1190,620,310,1050]}),R);
 await pg.waitForTimeout(120);await pg.screenshot({path:'n1.png'});
 await pg.evaluate(R=>res({hops:86,roofs:R}),R);
 await pg.waitForTimeout(120);await pg.screenshot({path:'n2.png'});
 await pg.evaluate(()=>post());
 await pg.waitForTimeout(120);await pg.screenshot({path:'n3.png'});
 await pg.evaluate(()=>sheet());
 await pg.waitForTimeout(120);await pg.screenshot({path:'n4.png'});
 console.log('xato:',errs.length?errs:'yo\'q');await br.close();})();
