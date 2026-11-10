const {chromium}=require('playwright'); const path=require('path');
(async()=>{
 const br=await chromium.launch(); const pg=await br.newPage();
 await pg.route('**/telegram-web-app.js', r=>r.abort());
 await pg.addInitScript(()=>{
   window.__IDU={start_param:'s_adp',user:{id:1,first_name:'Yangi'}};
   window.__PHONE=false;                      // yangi foydalanuvchi: telefon yo'q
   setTimeout(()=>{window.__PHONE=true},3000); // 3 s dan keyin ulashdi
 });
 await pg.goto('file://'+path.resolve('app.html'));
 const snap=async t=>{await pg.waitForTimeout(t);return pg.evaluate(()=>({
   on:Array.from(document.querySelectorAll('.pg.on')).map(e=>e.id),
   gate:(document.getElementById('gate')||{style:{}}).style.display}))};
 console.log('1.5 s — telefon hali yo\'q :', JSON.stringify(await snap(1500)));
 const b=await snap(6000);
 console.log('7.5 s — telefon ulashildi :', JSON.stringify(b));
 console.log(b.on.join()==='p-adp'
   ? '\n✓ Telefon so\'ralgandan keyin ham havola ishladi — Savdogar ochildi'
   : '\n✗ Havola yo\'qoldi, bosh sahifada qoldi');
 await br.close();
})();
