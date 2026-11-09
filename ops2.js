const {chromium}=require('playwright');
(async()=>{const br=await chromium.launch();
 const pg=await br.newPage({viewport:{width:440,height:950},deviceScaleFactor:2});
 const errs=[]; pg.on('pageerror',e=>errs.push(String(e).slice(0,150)));
 pg.on('console',m=>{if(m.type()==='error')errs.push('C:'+m.text().slice(0,110))});
 await pg.route('**/telegram-web-app.js',r=>r.abort());
 await pg.addInitScript(()=>{window.__IDU={user:{id:9,first_name:'Men'}}});
 await pg.goto('http://127.0.0.1:8777/app.html'); await pg.waitForTimeout(1200);
 await pg.evaluate(()=>go('ops')); await pg.waitForTimeout(2200);
 const fr=pg.frames().find(f=>f.url().includes('stick-ops'));
 if(!fr){console.log('iframe yo\'q');await br.close();return}
 console.log('xatolar (HTTP orqali):',errs.length?errs.slice(0,3):'yo\'q');
 // mashq tugmasini bosamiz
 const clicked=await fr.evaluate(()=>{
   const b=[...document.querySelectorAll('button,a,[role=button]')].find(x=>/MASHQ/i.test(x.innerText||''));
   if(b){b.click();return b.innerText.trim().slice(0,40)} return null;});
 console.log('bosildi:',clicked);
 await pg.waitForTimeout(3500);
 const st=await fr.evaluate(()=>({
   txt:document.body.innerText.replace(/\n{2,}/g,'\n').slice(0,300),
   canvas:!!document.querySelector('canvas'),
   w:(document.querySelector('canvas')||{}).width}));
 console.log('\n--- mashq ---\ncanvas:',st.canvas,'kenglik:',st.w);
 console.log(st.txt.split('\n').map(x=>'  '+x).join('\n'));
 await pg.screenshot({path:'ops_play.png'});
 console.log('\nxatolar:',errs.length?errs.slice(0,4):'yo\'q');
 await br.close();})();
