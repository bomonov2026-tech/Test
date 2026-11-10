const {chromium}=require('playwright'); const path=require('path');
(async()=>{const br=await chromium.launch();
 const pg=await br.newPage({viewport:{width:1080,height:700},deviceScaleFactor:1});
 const errs=[];pg.on('pageerror',e=>errs.push(String(e).slice(0,300)));
 await pg.goto('file://'+path.resolve('page.html'));await pg.waitForTimeout(300);
 await pg.evaluate(()=>{
   const cv=document.getElementById('c');cv.width=1080;cv.height=700;
   const c=cv.getContext('2d');c.fillStyle='#EFF5FA';c.fillRect(0,0,1080,700);
   ['idle','charge','fly','land'].forEach((p,i)=>{ninja(c,150+i*260,600,2.6,p,0.5)});
 });
 await pg.waitForTimeout(120);await pg.screenshot({path:'zoom.png'});
 console.log('xato:',errs.length?errs:'yo\'q');await br.close();})();
