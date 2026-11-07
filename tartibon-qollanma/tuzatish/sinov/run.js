const {chromium}=require('playwright'); const path=require('path');
const CASES=require('./cases.json');
(async()=>{
 const br=await chromium.launch();
 let okn=0, bad=[];
 for(const [key,want,nom] of CASES){
   const pg=await br.newPage({viewport:{width:420,height:900}});
   await pg.route('**/telegram-web-app.js', r=>r.abort());
   await pg.addInitScript(k=>{window.__IDU={start_param:k,user:{id:1,first_name:'Test'}}}, key);
   await pg.goto('file://'+path.resolve('app.html'));
   await pg.waitForTimeout(900);
   const r=await pg.evaluate(()=>({
     on:Array.from(document.querySelectorAll('.pg.on')).map(e=>e.id),
     err:window.__ERR.slice(0,3)
   }));
   const got=r.on.join(',');
   const pass = r.on.length===1 && r.on[0]==='p-'+want;
   if(pass) okn++; else bad.push({key,nom,want:'p-'+want,got,err:r.err});
   await pg.close();
 }
 console.log('✓ to\'g\'ri ochildi: '+okn+'/'+CASES.length);
 if(bad.length){console.log('\n--- xato ---');bad.forEach(b=>console.log(
   b.key+'  ('+b.nom+')\n   kutilgan: '+b.want+'\n   bo\'ldi:   '+(b.got||'(hech narsa)')+
   (b.err.length?'\n   xato:    '+b.err.join(' | '):'')));}
 await br.close();
})();
