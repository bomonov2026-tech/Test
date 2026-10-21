// Captures the real KursPremium / Usd Go24 Mini App (dark theme) from a running instance.
// Usage: node capture.js <app_url> <out_dir> <initData>
const { chromium } = require(process.env.PW || 'playwright');
const [url, out, init] = process.argv.slice(2);
const stub = (scheme) => `window.Telegram={WebApp:{colorScheme:'${scheme}',initData:${JSON.stringify(init)},initDataUnsafe:{user:{id:1,username:'UsdGo24',first_name:'Usd'}},ready(){},expand(){},onEvent(){},BackButton:{show(){},hide(){},onClick(){}},showConfirm(q,c){c(true)},showAlert(q,c){c&&c()},setHeaderColor(){},setBackgroundColor(){},disableVerticalSwipes(){},HapticFeedback:{impactOccurred(){},selectionChanged(){},notificationOccurred(){}}}}`;
async function page(b, w, h, dsf) {
  const p = await b.newPage({ viewport: { width: w, height: h }, deviceScaleFactor: dsf, colorScheme: 'dark' });
  p.on('pageerror', e => console.log('PAGEERR', e.message));
  await p.route('**/telegram-web-app.js', r => r.fulfill({ contentType: 'application/javascript', body: stub('dark') }));
  await p.goto(url); await p.waitForTimeout(1800);
  await p.evaluate(() => { try { localStorage.setItem('th', 'dark'); } catch (e) {} });
  return p;
}
(async () => {
  const b = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
  const p = await page(b, 390, 844, 3);
  const shot = async (name, full) => { await p.waitForTimeout(900); if (full) await p.addStyleTag({ content: 'nav{display:none!important}' }); await p.screenshot({ path: `${out}/${name}.png`, fullPage: !!full }); if (full) await p.evaluate(() => { const st = [...document.querySelectorAll('style')].pop(); st.remove(); }); console.log('ok', name); };
  await shot('home'); await shot('home_full', true);
  await p.evaluate(() => mo(true)); await shot('menu'); await p.evaluate(() => mo(false));
  const go = async (v, pre) => { await p.evaluate(([v, pre]) => { if (pre) eval(pre); go(v); }, [v, pre || '']); await p.waitForTimeout(1500); };
  await go('pr'); await shot('prayer'); await shot('prayer_full', true);
  await go('chats', "CW='Kanal'"); await shot('channels'); await shot('channels_full', true);
  await go('gd'); await shot('guard'); await shot('guard_full', true);
  await go('dp'); await shot('morning'); await shot('morning_full', true);
  await go('st'); await shot('stats');
  await go('calc'); await shot('calc');
  await go('gw'); await shot('giveaway');
  await go('po'); await shot('posts');
  const d = await page(b, 1440, 900, 2);
  await d.waitForTimeout(800); await d.screenshot({ path: `${out}/desktop_home.png` }); console.log('ok desktop');
  await b.close();
})();
