// Usd Go24 promo — deterministic timeline. window.renderAt(t) renders the frame at t seconds.
import * as THREE from 'three';
import { TTFLoader } from 'three/addons/loaders/TTFLoader.js';
import { Font } from 'three/addons/loaders/FontLoader.js';
import { Reflector } from 'three/addons/objects/Reflector.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';
import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { clamp, lerp, range, ease, env, track, textCanvas, rng } from './lib.js';
import * as O from './objects.js';

const W = 1920, H = 1080, DUR = 42;
const A = '../assets/';
const q = new URLSearchParams(location.search), SCALE = +(q.get('scale') || 1);

/* ---------- renderer, composer ---------- */
const renderer = new THREE.WebGLRenderer({ antialias: false, preserveDrawingBuffer: true, powerPreference: 'high-performance' });
renderer.setPixelRatio(1); renderer.setSize(W * SCALE, H * SCALE); renderer.domElement.style.cssText = `width:${W * SCALE}px;height:${H * SCALE}px`;
renderer.toneMapping = THREE.NeutralToneMapping; renderer.toneMappingExposure = 1.0; renderer.outputColorSpace = THREE.SRGBColorSpace;
document.body.appendChild(renderer.domElement);
const scene = new THREE.Scene(); scene.background = O.BRAND.bg.clone();
const camera = new THREE.PerspectiveCamera(32, W / H, 0.05, 200); scene.add(camera);
scene.environment = O.studioEnv(renderer); scene.environmentIntensity = 0.85;

const rt = new THREE.WebGLRenderTarget(W * SCALE, H * SCALE, { type: THREE.HalfFloatType, samples: 4 });
const composer = new EffectComposer(renderer, rt);
composer.addPass(new RenderPass(scene, camera));
const bloom = new UnrealBloomPass(new THREE.Vector2(W * SCALE, H * SCALE), 0.62, 0.55, 0.92); composer.addPass(bloom);
const grade = new ShaderPass({
  uniforms: { tDiffuse: { value: null }, uFlash: { value: 0 }, uRes: { value: new THREE.Vector2(W, H) } },
  vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
  fragmentShader: `uniform sampler2D tDiffuse; uniform float uFlash; varying vec2 vUv;
    void main(){ vec2 c = vUv - 0.5; float r2 = dot(c, c); vec2 off = c * r2 * 0.012;
      vec3 col = vec3(texture2D(tDiffuse, vUv + off).r, texture2D(tDiffuse, vUv).g, texture2D(tDiffuse, vUv - off).b);
      float l = dot(col, vec3(0.2126, 0.7152, 0.0722));
      col = mix(col, col * vec3(0.92, 1.0, 1.1), smoothstep(0.25, 0.0, l) * 0.6);          // cool shadows
      col += vec3(0.0015, 0.003, 0.007);                                                   // lifted blue blacks
      col += vec3(0.55, 0.72, 1.0) * uFlash;
      col *= mix(1.0, smoothstep(1.05, 0.25, sqrt(r2) * 1.35), 0.55);                      // vignette
      gl_FragColor = vec4(col, 1.0); }`,
});
composer.addPass(grade);
composer.addPass(new OutputPass());
const finish = new ShaderPass({
  uniforms: { tDiffuse: { value: null }, uFade: { value: 0 }, uSeed: { value: 0 } },
  vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
  fragmentShader: `uniform sampler2D tDiffuse; uniform float uFade, uSeed; varying vec2 vUv;
    float h(vec2 p){ return fract(sin(dot(p, vec2(12.9898, 78.233)) + uSeed) * 43758.5453); }
    void main(){ vec3 c = texture2D(tDiffuse, vUv).rgb; c += (h(vUv * 1000.0) - 0.5) * 0.022; c *= 1.0 - uFade; gl_FragColor = vec4(c, 1.0); }`,
});
composer.addPass(finish);

/* ---------- HUD (crisp titles, composited after post) ---------- */
const hud = new THREE.Scene(), hudCam = new THREE.OrthographicCamera(0, W, H, 0, -10, 10);
const TEXT_FS = `uniform sampler2D uTex; uniform float uOp, uW, uBright; varying vec2 vUv;
  void main(){ vec4 t = texture2D(uTex, vUv); float m = 1.0 - smoothstep(uW * 1.2 - 0.2, uW * 1.2, vUv.x);
    gl_FragColor = vec4(t.rgb * uBright, t.a * m * uOp);
    #include <colorspace_fragment>
  }`;
function textMesh(tex, k, hudMode) {
  const m = new THREE.ShaderMaterial({ transparent: true, depthWrite: false, depthTest: !hudMode, toneMapped: false,
    uniforms: { uTex: { value: tex }, uOp: { value: 0 }, uW: { value: 1 }, uBright: { value: hudMode ? 1 : 0.86 } },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: hudMode ? TEXT_FS : TEXT_FS.replace('#include <colorspace_fragment>', '') });
  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(tex.userData.w * k, tex.userData.h * k), m); mesh.renderOrder = 20;
  mesh.userData.size = [tex.userData.w * k, tex.userData.h * k]; return mesh;
}
function setText(mesh, op, wipe = 1) { mesh.material.uniforms.uOp.value = op; mesh.material.uniforms.uW.value = wipe; mesh.visible = op > 0.002; }

/* ---------- assets ---------- */
const texLoader = new THREE.TextureLoader();
const loadTex = (u) => new Promise((res, rej) => texLoader.load(A + u, t => { t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 8; t.generateMipmaps = true; t.minFilter = THREE.LinearMipmapLinearFilter; res(t); }, undefined, rej));
async function loadFont(file) { const d = await new Promise((res, rej) => Object.assign(new TTFLoader(), { reversed: true }).load(A + 'fonts/' + file, res, undefined, rej)); return new Font(d); }
async function cssFonts() {
  for (const [w, f] of [[800, 'Inter-ExtraBold'], [700, 'Inter-Bold'], [600, 'Inter-SemiBold'], [500, 'Inter-Medium'], [400, 'Inter-Regular']]) {
    const ff = new FontFace('Inter', `url(${A}fonts/${f}.otf)`, { weight: String(w) }); await ff.load(); document.fonts.add(ff);
  }
}

const S = {};   // all scene objects
async function build() {
  await cssFonts();
  const names = ['screens/home', 'screens/home_full', 'screens/prayer_full', 'screens/channels_full', 'screens/guard_full', 'screens/desktop_home',
    'crops/hero', 'crops/cbu', 'crops/banks', 'crops/prayer_w', 'crops/prayer_l', 'crops/channel', 'crops/guard', 'crops/guard2',
    'screens/bot_card_a', 'screens/bot_card_b', 'screens/bot_prayer_post'];
  const T = {}; await Promise.all(names.map(async n => { T[n.split('/')[1]] = await loadTex(n + '.png'); }));
  S.T = T;
  const font = await loadFont('Inter-ExtraBold.otf');

  scene.add(S.backdrop = O.backdrop());
  // lights
  const key = new THREE.DirectionalLight(0xffffff, 1.3); key.position.set(3, 5, 6); scene.add(key);
  const rimB = new THREE.SpotLight(0x3b82f6, 260, 0, 0.6, 0.6, 2); rimB.position.set(-6, 3, -5); scene.add(rimB);
  const rimV = new THREE.SpotLight(0x7c5cff, 220, 0, 0.6, 0.6, 2); rimV.position.set(6, 2.5, -5); scene.add(rimV);
  S.sweep = new THREE.PointLight(0xffffff, 0, 0, 2); scene.add(S.sweep);

  scene.add(S.parts = O.particles(1400, [[-14, -8, -26], [28, 16, 34]], 7));
  S.glow = O.glow('#2f6bff', 9, 0.6); S.glow.position.z = -1.5; scene.add(S.glow);
  S.beamA = O.beam('#4c8dff', 3.2, 12, 1); S.beamA.position.set(-0.6, 7, -2.5); S.beamA.rotation.z = 0.12; scene.add(S.beamA);
  S.beamB = O.beam('#7c6bff', 2.6, 12, 0.8); S.beamB.position.set(1.4, 7, -3.2); S.beamB.rotation.z = -0.18; scene.add(S.beamB);

  // floor with real reflections, faded by a radial overlay
  const floorShader = {
    uniforms: { color: { value: null }, tDiffuse: { value: null }, textureMatrix: { value: null }, uC: { value: new THREE.Vector2(0, 0) }, uK: { value: 1 } },
    vertexShader: `uniform mat4 textureMatrix; varying vec4 vUv; varying vec3 vP;
      void main(){ vUv = textureMatrix * vec4(position, 1.0); vP = (modelMatrix * vec4(position, 1.0)).xyz; gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0); }`,
    fragmentShader: `uniform vec3 color; uniform sampler2D tDiffuse; uniform vec2 uC; uniform float uK; varying vec4 vUv; varying vec3 vP;
      void main(){ vec2 uv = vUv.xy / vUv.w; vec3 c = vec3(0.0); float bl = 0.006;
        c += texture2D(tDiffuse, uv).rgb * 0.28; c += texture2D(tDiffuse, uv + vec2(bl, 0.0)).rgb * 0.18; c += texture2D(tDiffuse, uv - vec2(bl, 0.0)).rgb * 0.18;
        c += texture2D(tDiffuse, uv + vec2(0.0, bl * 1.8)).rgb * 0.18; c += texture2D(tDiffuse, uv - vec2(0.0, bl * 1.8)).rgb * 0.18;
        float d = length((vP.xz - uC) * vec2(0.42, 0.7)); float a = exp(-d * d * 0.16) * uK;
        gl_FragColor = vec4(c * color + vec3(0.004, 0.007, 0.016), a); }`,
  };
  S.floor = new Reflector(new THREE.PlaneGeometry(40, 40), { textureWidth: 960 * SCALE, textureHeight: 540 * SCALE, color: 0x5a6070, clipBias: 0.003, shader: floorShader, multisample: 0 });
  S.floor.material.transparent = true; S.floor.material.depthWrite = false; S.floor.material.uniforms.color.value = new THREE.Color(0.42, 0.45, 0.52);
  S.floor.rotation.x = -Math.PI / 2; S.floor.position.y = -2.25; scene.add(S.floor);
  S.floorFade = { material: { uniforms: S.floor.material.uniforms }, visible: true };

  // opening: chrome rings + light trails
  const chrome = () => new THREE.MeshPhysicalMaterial({ color: 0xdfe6f2, metalness: 1, roughness: 0.12, clearcoat: 1, transparent: true });
  S.rings = [[2.2, 0.016], [2.75, 0.011], [3.3, 0.022]].map(([r, tb]) => { const m = new THREE.Mesh(new THREE.TorusGeometry(r, tb, 24, 220), chrome()); scene.add(m); return m; });
  const R = rng(42); S.trails = [];
  for (let i = 0; i < 7; i++) {
    const a0 = R() * 6.28, turns = 0.9 + R() * 0.6, dir = R() > 0.5 ? 1 : -1, pts = [];
    for (let k = 0; k <= 14; k++) { const s = k / 14, rad = lerp(11, 0.25, Math.pow(s, 0.8)), a = a0 + dir * turns * 6.28 * s; pts.push([Math.cos(a) * rad, Math.sin(a) * rad * 0.62 + (R() - 0.5) * 0.3, lerp(-9 - R() * 6, 0, s)]); }
    const tr = O.trail(pts, i % 2 ? '#7c6bff' : '#3b8bff', 0.012 + R() * 0.01); tr.userData.d = R() * 0.5; scene.add(tr); S.trails.push(tr);
  }
  // hero light rings + rate line
  S.lightRings = [3.0, 3.6].map((r, i) => { const m = new THREE.Mesh(new THREE.TorusGeometry(r, 0.008, 12, 260), new THREE.MeshBasicMaterial({ color: new THREE.Color(i ? '#7c6bff' : '#3b8bff').multiplyScalar(3.2), transparent: true })); scene.add(m); return m; });
  const lp = []; const R2 = rng(9); let y = -1.3; for (let i = 0; i <= 16; i++) { y += (R2() - 0.32) * 0.36; lp.push([-6 + i * 0.75, y, -3.2 + Math.sin(i) * 0.2]); }
  S.rateLine = O.trail(lp, '#4c8dff', 0.02); S.rateLine.material.uniforms.uLen.value = 1.4; scene.add(S.rateLine);

  // logo
  S.logo = O.logo(font); scene.add(S.logo);
  S.tagline = textMesh(textCanvas([{ text: 'Valyuta kurslari', font: '500 64px Inter', color: '#b9c3d4', size: 64, tracking: 6 }], { scale: 2, align: 'center' }), 0.0062);
  scene.add(S.tagline);

  // devices
  S.phone = O.phone(); scene.add(S.phone);
  S.browser = O.browser(O.browserTitle()); scene.add(S.browser);
  S.browser.userData.screen.uniforms.uA.value = T.desktop_home;

  // feature panels
  const P = (tex, w, o) => { const p = O.panel(tex, w, o); scene.add(p); p.visible = false; return p; };
  S.f = {
    hero: P(T.hero, 2.55), cbu: P(T.cbu, 2.15), prayer: P(T.prayer_w, 1.9), post: P(T.bot_prayer_post, 1.95, { bright: 0.8, r: 0.1 }),
    channel: P(T.channel, 2.3), cardA: P(T.bot_card_a, 1.75, { bright: 0.8, r: 0.1 }), cardB: P(T.bot_card_b, 1.75, { bright: 0.8, r: 0.1 }),
    guard: P(T.guard, 2.15), guard2: P(T.guard2, 1.95),
  };
  S.ring = ['hero', 'prayer_w', 'channel', 'bot_card_a', 'guard', 'bot_prayer_post', 'cbu', 'banks'].map(n => P(T[n], 1.3, n.startsWith('bot') ? { bright: 0.8, r: 0.08 } : {}));

  // HUD titles
  const title = (num, t1, t2) => textMesh(textCanvas([
    { text: num, font: '700 26px Inter', color: '#5b8cff', size: 26, tracking: 4, extraW: 70, draw: (x, lx, ly) => { x.fillText(num, lx, ly); x.fillStyle = '#3b6fe0'; x.fillRect(lx + 52, ly + 13, 56, 3); } },
    { text: t1, font: '800 76px Inter', color: '#f3f6fb', size: 76, tracking: -1, dy: 14 },
    { text: t2, font: '500 31px Inter', color: '#9aa6b8', size: 31, dy: 2 },
  ], { pad: 10 }), 1, true);
  S.titles = [
    title('01', 'Valyuta kurslari', "Markaziy bank va banklar bo'yicha kurslar"),
    title('02', 'Namoz vaqtlari', "13 hudud bo'yicha · kanalga rasm bilan"),
    title('03', 'Avtomatik postlar', 'Kanal va guruhlaringizga — avtomatik'),
    title('04', 'Guruh himoyasi', "Reklama, havola, spam va so'kinishlardan"),
  ];
  S.titles.forEach(m => { hud.add(m); m.position.set(112 + m.userData.size[0] / 2, H - 64 - m.userData.size[1] / 2, 0); });

  // final brand texts (world space)
  S.endSub = textMesh(textCanvas([{ text: 'Valyuta kurslari  ·  Namoz vaqtlari  ·  Guruh himoyasi', font: '500 46px Inter', color: '#aeb9cb', size: 46, tracking: 1 }], { scale: 2, align: 'center' }), 0.0036);
  const pill = textCanvas([{ text: '@UsdGo24', font: '700 54px Inter', color: '#ffffff', size: 54, extraW: 150, draw: (x, lx, ly) => {
    const w = x.measureText('@UsdGo24').width, pw = w + 150, ph = 92; const g = x.createLinearGradient(lx, 0, lx + pw, 0); g.addColorStop(0, '#2f80ed'); g.addColorStop(1, '#5b5cf6');
    x.fillStyle = g; x.beginPath(); x.roundRect(lx, ly - 16, pw, ph, 46); x.fill();
    x.fillStyle = '#ffffff'; x.beginPath(); const px = lx + 44, py = ly + 30; x.moveTo(px - 16, py - 2); x.lineTo(px + 18, py - 16); x.lineTo(px + 10, py + 18); x.lineTo(px + 1, py + 6); x.closePath(); x.fill();
    x.fillText('@UsdGo24', lx + 84, ly + 6); } }], { scale: 2, pad: 24 });
  S.endPill = textMesh(pill, 0.0042);
  scene.add(S.endSub, S.endPill);
}

/* ---------- helpers ---------- */
const V = (a) => new THREE.Vector3(...a);
const tmpM = new THREE.Matrix4(), tmpQ = new THREE.Quaternion(), tmpQ2 = new THREE.Quaternion(), tmpE = new THREE.Euler();
function placeCam(p, l, fov = 32, roll = 0) {
  camera.position.set(p[0], p[1], p[2]); camera.up.set(Math.sin(roll), Math.cos(roll), 0); camera.lookAt(l[0], l[1], l[2]);
  if (camera.fov !== fov) { camera.fov = fov; camera.updateProjectionMatrix(); }
}
function setPhone(pos, ry, rx = 0, s = 1) { S.phone.position.set(...pos); S.phone.rotation.set(rx, ry, 0); S.phone.scale.setScalar(s); S.phone.updateMatrixWorld(); }
const scr = () => S.phone.userData.screen.uniforms;
function setPage(a, scrollA, b = null, scrollB = 0, mix = 0) {
  const u = scr(), T = S.T; u.uA.value = T[a]; u.uAH.value = T[a].image.height / 3; u.uScrollA.value = scrollA;
  if (b) { u.uB.value = T[b]; u.uBH.value = T[b].image.height / 3; u.uScrollB.value = scrollB; } u.uMix.value = mix;
}
// world pose of a point on the phone screen (css x,y within the 390x844 viewport)
function phonePoint(cssX, cssY) {
  const sw = O.PHONE.sw, sh = O.PHONE.sh, SB = 0.055, full = sh / (1 - SB);
  const lx = (cssX / 390 - 0.5) * sw, ly = full / 2 - SB * full - cssY / 844 * sh;
  return new THREE.Vector3(lx, ly, 0.12).applyMatrix4(S.phone.matrixWorld);
}
// panel flies out of the phone screen (from css rect) to a target pose
function flyPanel(p, t, t0, dur, src, dst, out) {
  if (t < t0) { O.setPanelOpacity(p, 0); return; }
  const k = ease.outQuint(range(t, t0, t0 + dur));
  const from = phonePoint(src[0], src[1]); const fromQ = S.phone.quaternion.clone(); const fromS = (src[2] / 390 * O.PHONE.sw * S.phone.scale.x) / p.userData.w;
  const to = V(dst.pos); tmpE.set(dst.rx || 0, dst.ry || 0, dst.rz || 0); const toQ = tmpQ2.setFromEuler(tmpE);
  p.position.lerpVectors(from, to, k); p.position.z += Math.sin(k * Math.PI) * 0.6;
  p.quaternion.slerpQuaternions(fromQ, toQ, k); p.scale.setScalar(lerp(fromS, dst.s || 1, k));
  let op = range(t, t0, t0 + 0.12);
  if (out) { const ko = ease.in(range(t, out, out + 0.45)); p.position.add(V([-1.6 * ko, 0.25 * ko, 1.4 * ko])); op *= 1 - ko; }
  // gentle float
  p.position.y += Math.sin(t * 0.9 + dst.pos[0]) * 0.025 * k;
  O.setPanelOpacity(p, op);
}

/* ---------- timeline ---------- */
const camS1 = track([{ t: 0, v: [0, 0, 9.8, 0, 0, 0] }, { t: 3.8, v: [0, 0.05, 7.4, 0, 0.02, 0] }, { t: 4.8, v: [0, 0.06, 7.0, 0, 0.03, 0] }, { t: 6.0, v: [0, 0.1, 1.2, 0, 0.1, 0] }]);
const camS2 = track([{ t: 6, v: [-0.5, 1.4, 9.8, 0, 0.45, 0] }, { t: 8.4, v: [-1.45, 0.55, 8.6, 0.55, 0.12, -0.5] }, { t: 12, v: [-2.1, 0.3, 7.7, 0.85, 0.02, -0.7] }]);
const camS3 = track([{ t: 12, v: [0.5, 0.25, 8.4, 0.15, -0.05, 0] }, { t: 15.5, v: [-0.15, 0.05, 7.9, -0.05, -0.1, 0] }, { t: 19, v: [0.3, -0.05, 8.0, 0.1, -0.12, 0] },
  { t: 22.5, v: [-0.2, 0.12, 7.8, -0.05, -0.08, 0] }, { t: 26, v: [0.1, 0.05, 7.5, 0.05, -0.05, 0] }]);
const camS5 = track([{ t: 34, v: [0.2, 0.35, 9.2, 0.1, 0.05, 0] }, { t: 36, v: [0.05, 0.2, 8.5, 0.05, 0.05, 0] }, { t: 42, v: [0, 0.15, 7.9, 0, 0.05, 0] }]);

function hideAll() {
  [...Object.values(S.f), ...S.ring].forEach(p => O.setPanelOpacity(p, 0));
  S.titles.forEach(m => setText(m, 0)); [S.tagline, S.endSub, S.endPill].forEach(m => setText(m, 0));
  S.rings.forEach(r => r.visible = false); S.trails.forEach(r => r.visible = false); S.lightRings.forEach(r => r.visible = false);
  S.rateLine.visible = false; S.logo.visible = false; S.phone.visible = false; S.browser.visible = false;
  S.floor.visible = S.floorFade.visible = true; S.beamA.visible = S.beamB.visible = true; S.sweep.intensity = 0;
  scr().uOn.value = 1; scr().uTap.value.set(0, 0, 0, 0);
  Object.values(S.f).forEach(p => { p.userData.mat.uniforms.uHi.value.set(0, 0, 0, 0); p.userData.mat.uniforms.uTap.value.set(0, 0, 0, 0); });
}

function logoAnim(t, t0, pos, s) {
  S.logo.visible = true; S.logo.position.set(...pos); S.logo.scale.setScalar(s); S.logo.rotation.set(0, Math.sin(t * 0.35) * 0.06, 0);
  S.logo.userData.letters.forEach((L, i) => {
    const ti = t0 + (i === 0 ? -0.08 : 0.06 + i * 0.05), k = range(t, ti, ti + (i === 0 ? 0.55 : 0.65));
    L.visible = t >= ti;
    if (i === 0) { const kb = ease.outBack(k); L.scale.setScalar(Math.max(0.001, kb)); L.rotation.set(0, (1 - ease.out(k)) * -2.6, 0); L.position.z = -0.12; }
    else { const ko = ease.out(k); L.rotation.set((1 - ko) * -1.45, 0, 0); L.position.z = -0.12 + (1 - ko) * -1.2; L.position.y = (1 - ko) * -0.35; L.scale.setScalar(0.7 + 0.3 * ko); }
  });
}

function frame(t) {
  hideAll();
  let flash = 0, fade = 0, glowK = 0.5, bgGlow = 0.4, focus = 8;
  const hit = (th, a = 0.45, d = 5) => { if (t >= th) flash += a * Math.exp(-(t - th) * d); };
  S.parts.material.uniforms.uT.value = t; S.beamA.material.uniforms.uT.value = t; S.beamB.material.uniforms.uT.value = t;

  if (t < 6) { /* ===== S1 opening ===== */
    const c = camS1(t); placeCam(c.slice(0, 3), c.slice(3), 32, Math.sin(t * 0.4) * 0.01); focus = 7.5;
    fade = 1 - ease.out(range(t, 0, 0.9));
    S.floor.visible = S.floorFade.visible = false; S.beamA.visible = S.beamB.visible = t > 1.8;
    S.beamA.material.uniforms.uK.value = ease.out(range(t, 1.9, 3)) * 0.8; S.beamB.material.uniforms.uK.value = ease.out(range(t, 1.9, 3)) * 0.6;
    S.trails.forEach((tr, i) => { const h = range(t, 0.15 + tr.userData.d, 1.95 + tr.userData.d * 0.2) * 1.25; tr.visible = h > 0 && h < 1.6; tr.material.uniforms.uHead.value = h; tr.material.uniforms.uA.value = 1 - range(t, 1.9, 2.4) * 0.6; });
    S.rings.forEach((r, i) => {
      r.visible = t > 0.3; const conv = ease.inOutSine(range(t, 0.3, 2.0)), after = ease.out(range(t, 2.0, 5.2));
      r.scale.setScalar(lerp(2.6 + i * 0.5, 1, conv) * (1 + after * (0.9 + i * 0.25)));
      r.rotation.set(1.2 - conv * 1.0 + i * 0.35 + t * 0.12 * (i % 2 ? 1 : -1), 0.4 * i + t * 0.25, t * 0.1 * (i + 1));
      r.material.opacity = Math.min(range(t, 0.3, 1.0), 1 - range(t, 2.6, 3.8)); r.position.z = -0.3;
    });
    logoAnim(t, 1.95, [0, 0.12, 0], 0.8);
    const push = ease.in(range(t, 4.8, 6.0)); S.logo.position.z += push * 0.5;
    setText(S.tagline, Math.min(range(t, 3.0, 3.5), 1 - range(t, 4.6, 5.1)), ease.out(range(t, 3.0, 3.9))); S.tagline.position.set(0, -0.82, 0);
    S.sweep.intensity = 22 * env(t, 2.2, 4.2, 0.3, 0.6); S.sweep.position.set(lerp(-6, 6, ease.inOutSine(range(t, 2.2, 4.2))), 2.0, 4.2);
    hit(2.0, 0.42, 5); hit(5.88, 0.0); flash += 0.9 * ease.in(range(t, 5.55, 6.0));
    glowK = 0.15 + 0.9 * Math.exp(-Math.max(0, t - 2.0) * 1.2) * (t > 1.9 ? 1 : 0) + 0.25 * range(t, 2, 3); S.glow.position.set(0, 0.1, -1.8); bgGlow = 0.2 + range(t, 1.9, 3) * 0.5;
  } else if (t < 12) { /* ===== S2 product reveal ===== */
    const c = camS2(t); placeCam(c.slice(0, 3), c.slice(3), 32); focus = 6.8;
    flash = 0.9 * Math.exp(-(t - 6) * 6);
    const kr = ease.out(range(t, 6.0, 7.9));
    setPhone([0, lerp(-4.6, 0, kr), 0], lerp(-1.1, -0.28, kr) + Math.sin(t * 0.5) * 0.02, lerp(0.25, 0, kr));
    S.phone.visible = true;
    const on = ease.out(range(t, 7.35, 7.75)); scr().uOn.value = on;
    const s1 = ease.inOut(range(t, 9.6, 10.6)), s2 = ease.inOut(range(t, 11.0, 11.85));
    setPage('home_full', lerp(0, 360, s1) + (748 - 360) * s2);
    scr().uSheen.value = lerp(-0.4, 1.8, range(t, 6.6, 8.6));
    S.browser.visible = true; const kb = ease.outQuint(range(t, 8.0, 9.5));
    S.browser.position.set(lerp(7.5, 2.75, kb), 0.6 + Math.sin(t * 0.6) * 0.03, -2.6); S.browser.rotation.set(0, lerp(-0.9, -0.42, kb), 0);
    S.browser.userData.screen.uniforms.uOn.value = ease.out(range(t, 8.6, 9.2)); S.browser.userData.screen.uniforms.uSheen.value = lerp(-0.4, 1.8, range(t, 8.4, 10));
    S.floorFade.material.uniforms.uC.value.set(0.8, -0.6);
    flash += 0.7 * ease.in(range(t, 11.6, 12.0));
    glowK = 0.35; S.glow.position.set(0.6, 0.6, -3.5); bgGlow = 0.55;
  } else if (t < 26) { /* ===== S3 features ===== */
    const c = camS3(t); placeCam(c.slice(0, 3), c.slice(3), 32); focus = 7.6;
    flash = 0.7 * Math.exp(-(t - 12) * 6);
    setPhone([2.45, -0.1, -0.8], -0.38 + Math.sin(t * 0.4) * 0.02, 0, 0.94); S.phone.visible = true;
    S.floorFade.material.uniforms.uC.value.set(1, -0.5);
    // phone page navigation
    const nav = (t0) => ease.inOut(range(t, t0, t0 + 0.55));
    if (t < 15.5) setPage('home_full', lerp(748, 0, ease.inOut(range(t, 12.0, 12.7))));
    else if (t < 19) setPage('home_full', 0, 'prayer_full', 0, nav(15.5));
    else if (t < 22.5) setPage('prayer_full', 0, 'channels_full', 330, nav(19.0));
    else setPage('channels_full', 330, 'guard_full', 420, nav(22.5));
    if (scr().uMix.value >= 1) { const u = scr(); u.uA.value = u.uB.value; u.uAH.value = u.uBH.value; u.uScrollA.value = u.uScrollB.value; u.uMix.value = 0; }
    const F = S.f, segs = [[12, 15.5], [15.5, 19], [19, 22.5], [22.5, 26]];
    segs.forEach(([a, b], i) => { const m = S.titles[i]; setText(m, env(t, a + 0.3, b - 0.05, 0.5, 0.35), ease.out(range(t, a + 0.3, a + 1.1))); m.position.y = H - 64 - m.userData.size[1] / 2 + (1 - ease.out(range(t, a + 0.3, a + 0.9))) * -18; });
    // F1 rates
    flyPanel(F.hero, t, 12.35, 1.0, [14, 74, 362], { pos: [-1.35, -0.37, 0.55], ry: 0.12 }, 15.2);
    flyPanel(F.cbu, t, 12.85, 1.0, [14, 396, 362], { pos: [0.75, -1.37, -0.35], ry: -0.1 }, 15.25);
    if (t > 14.35) F.hero.userData.mat.uniforms.uTap.value.set(0.674, 0.9, ease.out(range(t, 14.4, 15.0)), t < 15.0 ? 1 : 0);
    F.hero.userData.mat.uniforms.uSheen.value = lerp(-0.3, 1.8, range(t, 13.2, 14.2));
    // F2 prayer
    flyPanel(F.prayer, t, 15.85, 1.0, [14, 1092 - 748, 362], { pos: [-1.55, -0.64, 0.5], ry: 0.12 }, 18.7);
    flyPanel(F.post, t, 16.75, 1.0, [14, 300, 362], { pos: [0.55, -0.67, -0.45], ry: -0.14 }, 18.75);
    F.prayer.userData.mat.uniforms.uSheen.value = lerp(-0.3, 1.8, range(t, 16.7, 17.7));
    // F3 channels
    flyPanel(F.channel, t, 19.35, 1.0, [14, 176, 362], { pos: [-1.5, -0.62, 0.5], ry: 0.12 }, 22.2);
    [20.0, 20.35, 20.7, 21.05].forEach((tc, j) => { const bands = [[80, 104], [106, 130], [132, 176], [178, 222]][j];
      if (t > tc && t < tc + 0.75) F.channel.userData.mat.uniforms.uHi.value.set(1 - bands[1] / 404, 1 - bands[0] / 404, env(t, tc, tc + 0.75, 0.12, 0.4), 0); });
    flyPanel(F.cardA, t, 21.2, 0.95, [14, 300, 362], { pos: [0.65, 0.23, -0.9], ry: -0.18, rz: 0.02 }, 22.25);
    flyPanel(F.cardB, t, 21.6, 0.95, [14, 400, 362], { pos: [0.85, -1.47, -0.6], ry: -0.16, rz: -0.02 }, 22.3);
    // F4 guard
    flyPanel(F.guard, t, 22.85, 1.0, [14, 120, 362], { pos: [-1.45, -0.27, 0.5], ry: 0.12 }, 25.65);
    flyPanel(F.guard2, t, 23.3, 1.0, [14, 452, 362], { pos: [0.6, -1.57, -0.25], ry: -0.1 }, 25.7);
    [23.7, 24.1, 24.5].forEach((tc, j) => { const b = [[0, 80], [84, 160], [166, 240]][j];
      if (t > tc && t < tc + 0.8) F.guard.userData.mat.uniforms.uHi.value.set(1 - b[1] / 326, 1 - b[0] / 326, env(t, tc, tc + 0.8, 0.12, 0.45), 0); });
    if (t > 24.9 && t < 25.7) F.guard2.userData.mat.uniforms.uHi.value.set(1 - 70 / 222, 1, env(t, 24.9, 25.7, 0.12, 0.45), 0);
    flash += 0.8 * ease.in(range(t, 25.65, 26.0));
    glowK = 0.3; S.glow.position.set(-0.8, 0, -3); bgGlow = 0.5;
  } else if (t < 34) { /* ===== S4 hero ===== */
    const ko = ease.inOut(range(t, 26, 31.7)), kp = ease.inOut(range(t, 31.4, 33.4));
    const ang = lerp(-0.8, 0.5, ko), rad = lerp(8.4, 7.3, ko), cy = lerp(1.1, 0.35, ko);
    const orbit = [Math.sin(ang) * rad, cy, Math.cos(ang) * rad];
    const heroP = [1.6, -0.3, 6.9];
    const p = orbit.map((v, i) => lerp(v, heroP[i], kp)); const l = [0, lerp(0.05, 0.18, kp), 0];
    placeCam(p, l, lerp(32, 29, kp), Math.sin(t * 0.3) * 0.008); focus = lerp(7.5, 5.0, kp);
    flash = 0.75 * Math.exp(-(t - 26) * 5); hit(32.0, 0.35, 4);
    setPhone([0, 0.05 + Math.sin(t * 0.8) * 0.03, 0], lerp(-0.35, 0.3, range(t, 26, 34)), 0, 1); S.phone.visible = true;
    setPage('home_full', lerp(0, 748, ease.inOut(range(t, 27.5, 30.5))) * (1 - ease.inOut(range(t, 31.2, 32.4))));
    scr().uSheen.value = lerp(-0.4, 1.8, range(t, 31.6, 33.2));
    const burst = ease.out(range(t, 32.0, 33.2));
    S.ring.forEach((pn, i) => {
      const a = i / S.ring.length * 6.283 + t * 0.11 + 0.3, r = 3.15 + kp * 1.6 + burst * 0.4 + (i % 2) * 0.35, y = Math.sin(i * 1.7) * (1.15 + kp * 0.5);
      pn.position.set(Math.sin(a) * r, y, Math.cos(a) * r - 0.4); tmpM.lookAt(camera.position, pn.position, camera.up); tmpQ.setFromRotationMatrix(tmpM);
      tmpE.set(0, a, 0); tmpQ2.setFromEuler(tmpE); pn.quaternion.slerpQuaternions(tmpQ2, tmpQ, 0.75); pn.scale.setScalar(1);
      const near = clamp((pn.position.distanceTo(camera.position) - 2.6) / 1.6);
      O.setPanelOpacity(pn, ease.out(range(t, 26.2 + i * 0.12, 27.0 + i * 0.12)) * (1 - ease.in(range(t, 33.6, 34))) * near);
    });
    S.lightRings.forEach((r, i) => { r.visible = true; r.rotation.set(1.25 + i * 0.25 + Math.sin(t * 0.3) * 0.1, t * (i ? -0.15 : 0.2), 0.3 * i); r.scale.setScalar(1 + burst * 0.15);
      r.material.opacity = ease.out(range(t, 26.4, 27.6)) * (0.7 + 0.3 * Math.exp(-Math.max(0, t - 32) * 2)); });
    S.rateLine.visible = true; S.rateLine.material.uniforms.uHead.value = lerp(0, 1.4, ease.inOut(range(t, 26.6, 31.0))); S.rateLine.material.uniforms.uA.value = 0.8;
    S.browser.visible = true; S.browser.position.set(-1.0, 0.9, -5.2); S.browser.rotation.set(0, 0.25, 0); S.browser.userData.screen.uniforms.uOn.value = 0.55;
    S.floorFade.material.uniforms.uC.value.set(0, 0);
    flash += 0.85 * ease.in(range(t, 33.65, 34.0));
    glowK = 0.55 + burst * 0.3 * Math.exp(-Math.max(0, t - 32.3) * 1.5); S.glow.position.set(0, 0.2, -2.2); bgGlow = 0.8;
  } else { /* ===== S5 final brand ===== */
    const c = camS5(t); placeCam(c.slice(0, 3), c.slice(3), 32); focus = 8.3;
    flash = 0.8 * Math.exp(-(t - 34) * 5); hit(36.0, 0.3, 4.5);
    const kp = ease.outQuint(range(t, 34.0, 35.6));
    setPhone([lerp(4.6, 2.45, kp), -0.25 + Math.sin(t * 0.7) * 0.025, -0.4], lerp(-0.9, -0.42, kp) + Math.sin(t * 0.45) * 0.02, 0, 0.92); S.phone.visible = true;
    setPage('home_full', 0); scr().uSheen.value = lerp(-0.4, 1.8, range(t, 36.3, 37.8));
    logoAnim(t, 35.4, [-1.45, 0.62, 0], 0.6);
    setText(S.endSub, range(t, 36.55, 36.9), ease.out(range(t, 36.55, 37.5))); S.endSub.position.set(-1.45, -0.28 + (1 - ease.out(range(t, 36.55, 37.2))) * -0.08, 0);
    setText(S.endPill, range(t, 37.05, 37.35), ease.out(range(t, 37.05, 37.9))); S.endPill.position.set(-1.45, -1.05 + (1 - ease.out(range(t, 37.05, 37.7))) * -0.08, 0);
    S.sweep.intensity = 6 * env(t, 36.1, 38.2, 0.3, 0.7); S.sweep.position.set(lerp(-6.5, 3, ease.inOutSine(range(t, 36.1, 38.2))), 2.2, 4.5);
    S.lightRings.forEach((r, i) => { r.visible = true; r.position.set(-1.45, 0.62, -0.6); r.rotation.set(Math.PI / 2 - 0.12 + i * 0.05, 0, 0); const k = ease.out(range(t, 36.0, 37.4));
      r.scale.setScalar(0.2 + k * (0.42 + i * 0.08)); r.material.opacity = ((1 - k) * 0.9 + 0.06) * range(t, 35.9, 36.02); });
    S.floorFade.material.uniforms.uC.value.set(0.5, -0.4);
    glowK = 0.4 + 0.35 * Math.exp(-Math.max(0, t - 36) * 1.3) * (t > 35.9 ? 1 : 0); S.glow.position.set(-1.4, 0.5, -2.4); bgGlow = 0.7;
  }
  S.parts.material.uniforms.uFocus.value = focus;
  S.glow.material.uniforms.uK.value = glowK; S.glow.quaternion.copy(camera.quaternion);
  S.backdrop.material.uniforms.uGlow.value.set(0, -0.05, bgGlow);
  grade.uniforms.uFlash.value = flash; finish.uniforms.uFade.value = fade; finish.uniforms.uSeed.value = (t * 30) % 97;
  S.titles.forEach(m => { m.material.uniforms.uOp.value *= 1 - fade; });
}

window.renderAt = (t) => {
  frame(t);
  renderer.autoClear = true; composer.render();
  renderer.autoClear = false; renderer.setRenderTarget(null); renderer.clearDepth(); renderer.render(hud, hudCam); renderer.autoClear = true;
};
window.grab = async (qual = 0.95) => {
  const b = await new Promise(r => renderer.domElement.toBlob(r, 'image/jpeg', qual)); const u = new Uint8Array(await b.arrayBuffer());
  let s = ''; for (let i = 0; i < u.length; i += 32768) s += String.fromCharCode.apply(null, u.subarray(i, i + 32768)); return btoa(s);
};
window.DUR = DUR; window.S = S; window.hud = hud; window.R3 = { renderer, hudCam };
build().then(() => { window.renderAt(0); window.ready = true; }).catch(e => { console.error('BUILD', e.stack || e); window.failed = String(e); });
