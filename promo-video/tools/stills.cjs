// Renders selected frames to build/stills/*.jpg for visual QA.
const { chromium } = require('playwright');
const fs = require('fs');
const times = (process.argv[2] || '1,4,8,10,14,16,20,22,27,30,35,38').split(',').map(Number);
(async () => {
  const browser = await chromium.launch({ args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const page = await browser.newPage({ viewport: { width: 1080, height: 1920 } });
  page.on('console', (m) => { if (m.type() === 'error' || m.type() === 'warning') console.log('[console]', m.text().slice(0, 300)); });
  page.on('pageerror', (e) => console.log('[pageerror]', e.message.slice(0, 400)));
  await page.goto('http://127.0.0.1:8123/render.html');
  await page.waitForFunction('window.READY === true', null, { timeout: 180000 });
  for (const t of times) {
    const frame = Math.round(t * 30);
    const t0 = Date.now();
    const url = await page.evaluate((f) => window.renderFrame(f), frame);
    fs.writeFileSync(`build/stills/t${String(t).replace('.', '_')}.jpg`, Buffer.from(url.split(',')[1], 'base64'));
    console.log('t=', t, 'ms=', Date.now() - t0);
  }
  await browser.close();
})().catch((e) => { console.error('FAIL', e); process.exit(1); });
