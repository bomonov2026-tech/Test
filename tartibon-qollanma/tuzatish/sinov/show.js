const {chromium}=require('playwright'); const path=require('path');
const K=['s_mykanal','s_myguruh','s_ksyt','s_adp','s_pnm','s_obd'];
const snap=`(()=>({pg:(document.querySelector('.pg.on')||{}).id||null,
 sarlavha:((document.querySelector('.pg.on')||{}).querySelector?((document.querySelector('.pg.on').querySelector('h1')||{}).textContent||'').trim():''),
 cw:(typeof CW!=='undefined'?CW:null), mk:(typeof MK!=='undefined'?MK.p:null)}))()`;
(async()=>{const br=await chromium.launch();
 for(const k of K){const p=await br.newPage();await p.route('**/telegram-web-app.js',r=>r.abort());
  await p.addInitScript(x=>{window.__IDU={start_param:x,user:{id:1,first_name:'T'}}},k);
  await p.goto('file://'+path.resolve('app.html'));await p.waitForTimeout(1100);
  const r=await p.evaluate(snap);
  console.log(k.padEnd(12)+' -> '+String(r.pg).padEnd(8)+'  "'+r.sarlavha+'"'+(r.cw?'  CW='+r.cw:'')+(r.mk?'  MK='+r.mk:''));
  await p.close();}
 await br.close();})();
