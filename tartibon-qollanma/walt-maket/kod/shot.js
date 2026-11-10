const {chromium}=require('playwright'); const path=require('path');
(async()=>{const br=await chromium.launch({args:['--force-color-profile=srgb','--font-render-hinting=none']});
 const pg=await br.newPage({viewport:{width:1080,height:2340},deviceScaleFactor:1});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,250)));
 await pg.goto('file://'+path.resolve('page.html'));await pg.waitForTimeout(400);
 if(errs.length){console.log('XATO:',errs);await br.close();return}
 for(const [fn,f] of [['home','t1'],['services','t2'],['channel','t3']]){
   await pg.evaluate(n=>window[n](),fn);await pg.waitForTimeout(120);
   await pg.screenshot({path:f+'.png'});
 }
 console.log('xato:',errs.length?errs:'yo\'q');await br.close();})();
