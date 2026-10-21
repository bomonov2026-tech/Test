// Renders frames of scene/index.html. Usage: node render.js <outDir> [fps=30] [from=0] [to=end] [--stills t1,t2,...] [--scale s]
const { chromium } = require(process.env.PW || 'playwright');
const http = require('http'), fs = require('fs'), path = require('path');
const root = __dirname, args = process.argv.slice(2);
const out = args[0], fps = +(args[1] || 30);
const opt = (k) => { const i = args.indexOf(k); return i > -1 ? args[i + 1] : null; };
const types = { '.js': 'application/javascript', '.html': 'text/html', '.png': 'image/png', '.otf': 'font/otf', '.json': 'application/json' };
const srv = http.createServer((q, s) => { const f = path.join(root, decodeURIComponent(q.url.split('?')[0])); fs.readFile(f, (e, d) => { if (e) { s.writeHead(404); s.end(); return; } s.writeHead(200, { 'Content-Type': types[path.extname(f)] || 'application/octet-stream' }); s.end(d); }); });
(async () => {
  await new Promise(r => srv.listen(0, r)); const port = srv.address().port;
  fs.mkdirSync(out, { recursive: true });
  const scale = +(opt('--scale') || 1);
  const b = await chromium.launch({ executablePath: process.env.CHROME || '/opt/pw-browsers/chromium', args: ['--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', '--disable-gpu-vsync'] });
  const p = await b.newPage({ viewport: { width: 1920 * scale, height: 1080 * scale } });
  p.on('console', m => { if (m.type() === 'error' || m.type() === 'warning') console.log('console:', m.text()); });
  p.on('pageerror', e => console.log('pageerror:', e.message));
  await p.goto(`http://127.0.0.1:${port}/scene/index.html?scale=${scale}`);
  await p.waitForFunction('window.ready || window.failed', null, { timeout: 120000 });
  const failed = await p.evaluate('window.failed'); if (failed) { console.log('FAILED', failed); process.exit(1); }
  const dur = await p.evaluate('window.DUR');
  const stills = opt('--stills');
  const list = stills ? stills.split(',').map(Number) : null;
  const from = stills ? 0 : +(args[2] || 0), to = stills ? list.length : Math.min(+(args[3] || Math.round(dur * fps)), Math.round(dur * fps));
  const t0 = Date.now();
  for (let i = from; i < to; i++) {
    const t = list ? list[i] : i / fps;
    await p.evaluate(t => window.renderAt(t), t);
    const d = await p.evaluate(() => window.grab(0.96));
    fs.writeFileSync(path.join(out, list ? `still_${String(t).replace('.', '_')}.jpg` : `f_${String(i).padStart(5, '0')}.jpg`), Buffer.from(d, 'base64'));
    if ((i - from) % 30 === 29) console.log(`frame ${i + 1}/${to}  ${((Date.now() - t0) / (i - from + 1) / 1000).toFixed(2)} s/frame`);
  }
  console.log('done', to - from, 'frames in', ((Date.now() - t0) / 1000).toFixed(0), 's');
  await b.close(); srv.close();
})();
