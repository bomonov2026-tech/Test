// Renders all frames in parallel workers. Usage: node tools/render_all.cjs <workers> [total]
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const WORKERS = Number(process.argv[2] || 3);
const TOTAL = Number(process.argv[3] || 1200);
const OUT = path.resolve('build/frames');
fs.mkdirSync(OUT, { recursive: true });
(async () => {
  const browser = await chromium.launch({ args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const chunk = Math.ceil(TOTAL / WORKERS);
  const jobs = [];
  for (let w = 0; w < WORKERS; w++) {
    jobs.push((async () => {
      const page = await browser.newPage({ viewport: { width: 1080, height: 1920 } });
      page.on('pageerror', (e) => console.log('[pageerror]', e.message.slice(0, 300)));
      await page.goto('http://127.0.0.1:8123/render.html');
      await page.waitForFunction('window.READY === true', null, { timeout: 180000 });
      const start = w * chunk, end = Math.min(TOTAL, start + chunk);
      for (let f = start; f < end; f++) {
        const url = await page.evaluate((i) => window.renderFrame(i), f);
        fs.writeFileSync(path.join(OUT, `f${String(f).padStart(5, '0')}.jpg`), Buffer.from(url.split(',')[1], 'base64'));
        if (f % 60 === 0) console.log(new Date().toISOString().slice(11, 19), 'frame', f, '/', TOTAL);
      }
      await page.close();
    })());
  }
  await Promise.all(jobs);
  await browser.close();
  console.log('DONE', TOTAL);
})().catch((e) => { console.error('FAIL', e); process.exit(1); });
