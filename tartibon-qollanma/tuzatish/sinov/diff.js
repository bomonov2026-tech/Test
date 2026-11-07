// Havola natijasini katakni bosish natijasi bilan solishtiradi
const {chromium}=require('playwright'); const path=require('path');
const P=require('./pairs.json');
const snap = `(()=>({pg:(document.querySelector('.pg.on')||{}).id||null,
  cw:(typeof CW!=='undefined'?CW:null), mk:(typeof MK!=='undefined'?MK.p:null)}))()`;
(async()=>{
 const br=await chromium.launch(); let ok=0, bad=[];
 for(const c of P){
   // 1) katakni bosish (haqiqat)
   const a=await br.newPage(); await a.route('**/telegram-web-app.js',r=>r.abort());
   await a.addInitScript(()=>{window.__IDU={user:{id:1,first_name:'T'}}});
   await a.goto('file://'+path.resolve('app.html')); await a.waitForTimeout(700);
   await a.evaluate(c.onclick); await a.waitForTimeout(400);
   const want=await a.evaluate(snap); await a.close();
   // 2) havola orqali
   const b=await br.newPage(); await b.route('**/telegram-web-app.js',r=>r.abort());
   await b.addInitScript(k=>{window.__IDU={start_param:k,user:{id:1,first_name:'T'}}}, c.key);
   await b.goto('file://'+path.resolve('app.html')); await b.waitForTimeout(1100);
   const got=await b.evaluate(snap); await b.close();
   const same = want.pg===got.pg && want.cw===got.cw && want.mk===got.mk;
   if(same) ok++; else bad.push({c,want,got});
 }
 console.log('✓ katak bilan bir xil: '+ok+'/'+P.length);
 bad.forEach(x=>console.log('\n✗ '+x.c.key+'  ('+x.c.nom+')\n   katak bosilganda: '+JSON.stringify(x.want)+
   '\n   havola orqali:    '+JSON.stringify(x.got)));
 await br.close();
})();
