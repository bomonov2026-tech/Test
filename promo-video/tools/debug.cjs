const { chromium } = require('playwright');
const fs = require('fs');
(async () => {
  const browser = await chromium.launch({ args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const page = await browser.newPage({ viewport: { width: 1080, height: 1920 } });
  await page.goto('http://127.0.0.1:8123/render.html');
  await page.waitForFunction('window.READY === true', null, { timeout: 180000 });
  const info = await page.evaluate(() => {
    const out = {};
    window.__dbg.groups.forEach((g, i) => {
      out[i] = g.children.map((c) => `${c.type}:${c.geometry ? c.geometry.type : ''}:${c.material ? (c.material.type) : ''}`);
    });
    return out;
  });
  console.log(JSON.stringify(info, null, 1).slice(0, 3000));
  await browser.close();
})();
