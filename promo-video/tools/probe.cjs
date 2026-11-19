// Renders one frame with selected object classes hidden, to isolate artifacts.
const { chromium } = require('playwright');
const fs = require('fs');
const [,, frame, mode] = process.argv;
(async () => {
  const browser = await chromium.launch({ args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const page = await browser.newPage({ viewport: { width: 1080, height: 1920 } });
  await page.goto('http://127.0.0.1:8123/render.html');
  await page.waitForFunction('window.READY === true', null, { timeout: 180000 });
  const url = await page.evaluate(([f, mode]) => {
    const { groups, scene } = window.__dbg;
    const hideIf = (pred) => groups.forEach((g) => g.traverse((o) => { if (pred(o)) o.visible = false; }));
    if (mode === 'notext') hideIf((o) => o.geometry && o.geometry.type === 'TextGeometry');
    if (mode === 'nosprite') hideIf((o) => o.isSprite);
    if (mode === 'nopoints') hideIf((o) => o.isPoints);
    if (mode === 'nooverlay') hideIf((o) => o.material && o.material.depthTest === false);
    window.renderFrame(f); // renders (re-sets visibility by scene index internally)
    window.__dbg.renderer.render(scene, window.__dbg.camera);
    return window.__dbg.renderer.domElement.toDataURL('image/jpeg', 0.9);
  }, [Number(frame), mode]);
  fs.writeFileSync(`build/probe_${mode}_${frame}.jpg`, Buffer.from(url.split(',')[1], 'base64'));
  await browser.close();
})();
