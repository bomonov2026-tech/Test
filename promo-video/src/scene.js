// PO'LATHON — cinematic 3D promo. Deterministic: renderFrame(i) draws frame i (30 fps).
import * as THREE from 'three';
import { FontLoader } from 'three/addons/loaders/FontLoader.js';
import { TextGeometry } from 'three/addons/geometries/TextGeometry.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';

const W = 1080, H = 1920, FPS = 30;
const SCENE_STARTS = [0, 5, 12, 18, 24, 32];

// ---------- helpers ----------
const clamp = (x, a = 0, b = 1) => Math.min(b, Math.max(a, x));
const smooth = (a, b, x) => { const t = clamp((x - a) / (b - a)); return t * t * (3 - 2 * t); };
const easeIO = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const hash = (n) => { const s = Math.sin(n * 127.1 + 311.7) * 43758.5453; return s - Math.floor(s); };
function mulberry(seed) {
  return () => {
    seed |= 0; seed = (seed + 0x6d2b79f5) | 0;
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}
const rnd = mulberry(20240607);
const rr = (a, b) => a + (b - a) * rnd();

function ctex(w, h, draw) {
  const c = document.createElement('canvas'); c.width = w; c.height = h;
  const g = c.getContext('2d');
  draw(g, w, h, mulberry(w * 31 + h * 7));
  const t = new THREE.CanvasTexture(c);
  t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 4;
  return t;
}

// ---------- renderer / scene ----------
const renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
renderer.setPixelRatio(1);
renderer.setSize(W, H);
renderer.setClearColor(0x050304, 1);
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 1.0;
document.body.appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.background = new THREE.Color(0x050304);
scene.fog = new THREE.FogExp2(0x0a0503, 0.03);
const camera = new THREE.PerspectiveCamera(50, W / H, 0.02, 300);
scene.add(camera);

const envTex = new THREE.PMREMGenerator(renderer).fromScene(new RoomEnvironment(), 0.04).texture;

// ---------- textures ----------
const glowTex = ctex(256, 256, (g, w, h) => {
  const gr = g.createRadialGradient(w / 2, h / 2, 0, w / 2, h / 2, w / 2);
  gr.addColorStop(0, 'rgba(255,255,255,1)');
  gr.addColorStop(0.2, 'rgba(255,255,255,0.55)');
  gr.addColorStop(0.5, 'rgba(255,255,255,0.12)');
  gr.addColorStop(1, 'rgba(255,255,255,0)');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
});
const smokeTex = ctex(256, 256, (g, w, h, R) => {
  g.clearRect(0, 0, w, h);
  for (let i = 0; i < 24; i++) {
    const x = w * (0.25 + 0.5 * R()), y = h * (0.25 + 0.5 * R()), r = w * (0.12 + 0.2 * R());
    const gr = g.createRadialGradient(x, y, 0, x, y, r);
    gr.addColorStop(0, `rgba(255,255,255,${0.22 + 0.25 * R()})`);
    gr.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = gr; g.fillRect(0, 0, w, h);
  }
});
const woodTex = ctex(1024, 512, (g, w, h, R) => {
  const planks = 6;
  for (let p = 0; p < planks; p++) {
    const y0 = (p * h) / planks;
    g.fillStyle = `rgb(${70 + R() * 22 | 0},${42 + R() * 10 | 0},${24 + R() * 6 | 0})`;
    g.fillRect(0, y0, w, h / planks);
    for (let i = 0; i < 60; i++) {
      g.strokeStyle = `rgba(20,10,4,${0.08 + R() * 0.14})`;
      g.lineWidth = 1 + R() * 2;
      g.beginPath();
      let y = y0 + R() * (h / planks);
      g.moveTo(0, y);
      for (let x = 0; x <= w; x += 64) { y += (R() - 0.5) * 3; g.lineTo(x, y); }
      g.stroke();
    }
    g.fillStyle = 'rgba(10,5,2,0.85)'; g.fillRect(0, y0, w, 3);
  }
});
woodTex.wrapS = woodTex.wrapT = THREE.RepeatWrapping;
const meatTex = ctex(512, 512, (g, w, h, R) => {
  const gr = g.createLinearGradient(0, 0, w, h);
  gr.addColorStop(0, '#9a3320'); gr.addColorStop(1, '#5e1a10');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
  for (let i = 0; i < 260; i++) {
    g.fillStyle = `rgba(${60 + R() * 40 | 0},${10 + R() * 10 | 0},5,${0.25 + R() * 0.3})`;
    g.beginPath(); g.arc(R() * w, R() * h, 2 + R() * 10, 0, Math.PI * 2); g.fill();
  }
  for (let i = 0; i < 40; i++) {                       // fat marbling
    g.strokeStyle = `rgba(245,212,170,${0.35 + R() * 0.35})`;
    g.lineWidth = 1.5 + R() * 3;
    g.beginPath(); let x = R() * w, y = R() * h; g.moveTo(x, y);
    for (let k = 0; k < 6; k++) { x += (R() - 0.5) * 40; y += (R() - 0.5) * 30; g.lineTo(x, y); }
    g.stroke();
  }
  for (let i = 0; i < 90; i++) {                       // char / grill marks
    g.fillStyle = `rgba(30,8,4,${0.4 + R() * 0.4})`;
    g.beginPath(); g.arc(R() * w, R() * h, 1 + R() * 3, 0, 7); g.fill();
  }
});
const crustTex = ctex(512, 512, (g, w, h, R) => {
  const gr = g.createRadialGradient(w / 2, h * 0.9, 10, w / 2, h / 2, w * 0.7);
  gr.addColorStop(0, '#f2bd66'); gr.addColorStop(0.6, '#c27a2e'); gr.addColorStop(1, '#7a3c14');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
  for (let i = 0; i < 500; i++) {
    g.fillStyle = R() < 0.5 ? `rgba(90,40,10,${0.2 + R() * 0.3})` : `rgba(255,226,150,${0.2 + R() * 0.3})`;
    g.beginPath(); g.arc(R() * w, R() * h, 1 + R() * 4, 0, 7); g.fill();
  }
});
const breadTex = ctex(512, 512, (g, w, h, R) => {
  const gr = g.createRadialGradient(w / 2, h / 2, 20, w / 2, h / 2, w / 2);
  gr.addColorStop(0, '#ebb872'); gr.addColorStop(0.7, '#c98a43'); gr.addColorStop(1, '#8e5524');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
  for (let i = 0; i < 400; i++) {
    g.fillStyle = `rgba(80,40,10,${0.2 + R() * 0.25})`;
    g.beginPath(); g.arc(R() * w, R() * h, 1 + R() * 3, 0, 7); g.fill();
  }
  for (let r = 60; r < w / 2; r += 38) {
    g.strokeStyle = 'rgba(120,60,20,0.25)'; g.lineWidth = 3;
    g.beginPath(); g.arc(w / 2, h / 2, r, 0, 7); g.stroke();
  }
});
const wallTex = ctex(512, 512, (g, w, h, R) => {
  g.fillStyle = '#4a3d33'; g.fillRect(0, 0, w, h);
  for (let i = 0; i < 2500; i++) {
    const v = (60 + R() * 40) | 0;
    g.fillStyle = `rgba(${v},${v - 8},${v - 16},0.35)`;
    g.fillRect(R() * w, R() * h, 2 + R() * 3, 2 + R() * 3);
  }
  g.strokeStyle = 'rgba(20,15,10,0.6)'; g.lineWidth = 2;
  for (let x = 0; x <= w; x += 128) { g.beginPath(); g.moveTo(x, 0); g.lineTo(x, h); g.stroke(); }
  for (let y = 0; y <= h; y += 128) { g.beginPath(); g.moveTo(0, y); g.lineTo(w, y); g.stroke(); }
});
const skyTex = ctex(4, 512, (g, w, h) => {
  const gr = g.createLinearGradient(0, 0, 0, h);
  gr.addColorStop(0, '#03040a'); gr.addColorStop(0.6, '#0b0d1c');
  gr.addColorStop(0.88, '#3a1d0c'); gr.addColorStop(1, '#120804');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
});

// ---------- materials ----------
const goldMat = new THREE.MeshStandardMaterial({ color: 0xe9b04e, metalness: 1, roughness: 0.2, envMap: envTex, envMapIntensity: 1.6, emissive: 0x3a1800, emissiveIntensity: 0.35 });
const creamMat = new THREE.MeshStandardMaterial({ color: 0xfff1dc, roughness: 0.35, emissive: 0x221008, emissiveIntensity: 0.3, envMap: envTex, envMapIntensity: 0.6 });
const coalMat = new THREE.MeshStandardMaterial({ color: 0x1a0a04, emissive: 0xff4a10, emissiveIntensity: 1.2, roughness: 0.8 });
const rodMat = new THREE.MeshStandardMaterial({ color: 0x2b2622, metalness: 0.85, roughness: 0.45, envMap: envTex, envMapIntensity: 0.8 });
const meatMat = new THREE.MeshPhysicalMaterial({ map: meatTex, roughness: 0.55, clearcoat: 0.15, clearcoatRoughness: 0.4, emissive: 0x2a0a02, emissiveIntensity: 0.25, envMap: envTex, envMapIntensity: 0.5 });
const crustMat = new THREE.MeshStandardMaterial({ map: crustTex, roughness: 0.6, emissive: 0x2a1000, emissiveIntensity: 0.2 });
const breadMat = new THREE.MeshStandardMaterial({ map: breadTex, roughness: 0.7 });
const clayMat = new THREE.MeshStandardMaterial({ color: 0x7d3b1c, roughness: 0.95, side: THREE.DoubleSide });
const plateMat = new THREE.MeshPhysicalMaterial({ color: 0xf2ece2, roughness: 0.25, clearcoat: 1, clearcoatRoughness: 0.1, side: THREE.DoubleSide, envMap: envTex, envMapIntensity: 0.6 });
const woodMat = new THREE.MeshStandardMaterial({ map: woodTex, roughness: 0.6 });
const wallMat = new THREE.MeshStandardMaterial({ map: wallTex, roughness: 0.9 });
const glassMat = new THREE.MeshStandardMaterial({ color: 0x0c1218, roughness: 0.08, metalness: 0.4, envMap: envTex, envMapIntensity: 1.2 });
const groundMat = new THREE.MeshStandardMaterial({ color: 0x1b130e, roughness: 0.35, metalness: 0.1 });

// ---------- lights ----------
const hemi = new THREE.HemisphereLight(0x4a3020, 0x0a0503, 0.3); scene.add(hemi);
const key = new THREE.DirectionalLight(0xffb070, 0.3); key.position.set(-2, 4, 3); scene.add(key);
const rim = new THREE.DirectionalLight(0x7f9cff, 0.3); rim.position.set(3, 2, -3); scene.add(rim);
const fireLight = new THREE.PointLight(0xff7a2a, 2, 4, 1.5); scene.add(fireLight);
const fillLight = new THREE.PointLight(0xffa860, 0, 8, 1.5); scene.add(fillLight);
const lampA = new THREE.PointLight(0xffb060, 0, 6, 1.5); scene.add(lampA);
const lampB = new THREE.PointLight(0xffb060, 0, 6, 1.5); scene.add(lampB);

// ---------- scene groups ----------
const groups = SCENE_STARTS.map(() => { const g = new THREE.Group(); scene.add(g); return g; });

// ---------- builders ----------
function coalBed(parent, n, sx, sz, y, size, mat) {
  const im = new THREE.InstancedMesh(new THREE.IcosahedronGeometry(size, 1), mat, n);
  const m = new THREE.Matrix4(), q = new THREE.Quaternion(), s = new THREE.Vector3(), p = new THREE.Vector3(), e = new THREE.Euler();
  for (let i = 0; i < n; i++) {
    p.set(rr(-sx, sx), y + rr(0, 0.02), rr(-sz, sz));
    e.set(rr(0, 6), rr(0, 6), rr(0, 6)); q.setFromEuler(e);
    const k = rr(0.6, 1.4); s.set(k, k * 0.7, k * 0.9);
    m.compose(p, q, s); im.setMatrixAt(i, m);
  }
  im.instanceMatrix.needsUpdate = true;
  parent.add(im);
  return im;
}
function flame(parent, x, y, z, sc, color, op = 0.9) {
  const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: glowTex, color, transparent: true, opacity: op, depthWrite: false, blending: THREE.AdditiveBlending }));
  s.position.set(x, y, z);
  s.userData = { sc, ph: rr(0, 10) };
  parent.add(s);
  return s;
}
function animFlames(list, t) {
  for (const s of list) {
    const { sc, ph } = s.userData;
    const f = 0.85 + 0.15 * Math.sin(t * 13 + ph) + 0.07 * Math.sin(t * 29 + ph * 2);
    s.scale.set(sc * f * 0.85, sc * f * 1.7, 1);
    s.material.opacity = 0.5 + 0.4 * f;
  }
}
function points(parent, n, color, size, gen) {
  const geo = new THREE.BufferGeometry();
  const pos = new Float32Array(n * 3);
  geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
  const mat = new THREE.PointsMaterial({ map: glowTex, color, size, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, sizeAttenuation: true });
  const p = new THREE.Points(geo, mat);
  p.userData.update = (t) => {
    for (let i = 0; i < n; i++) { const v = gen(i, t); pos[i * 3] = v[0]; pos[i * 3 + 1] = v[1]; pos[i * 3 + 2] = v[2]; }
    geo.attributes.position.needsUpdate = true;
  };
  parent.add(p);
  return p;
}
function makePuffs(parent, n, o) {
  const list = [];
  for (let i = 0; i < n; i++) {
    const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: smokeTex, color: o.color ?? 0x8c7f76, transparent: true, opacity: 0, depthWrite: false }));
    parent.add(s);
    list.push({ s, t0: -rr(0, o.life), life: o.life * rr(0.8, 1.2), x: o.x + rr(-o.spread, o.spread) / 2, y0: o.y, z: o.z + rr(-o.zs, o.zs) / 2,
      rise: o.rise * rr(0.8, 1.2), sc: o.sc * rr(0.7, 1.3), op: o.op * rr(0.6, 1.2), ph: rr(0, 6), rot: rr(0, 6) });
  }
  return list;
}
function animPuffs(list, t) {
  for (const p of list) {
    const age = (((t - p.t0) % p.life) + p.life) % p.life;
    const k = age / p.life;
    p.s.position.set(p.x + Math.sin(age * 0.9 + p.ph) * 0.06 * (0.3 + k), p.y0 + p.rise * age, p.z);
    p.s.scale.setScalar(p.sc * (0.5 + 1.5 * k));
    p.s.material.opacity = p.op * Math.sin(Math.PI * k);
    p.s.material.rotation = p.rot + age * 0.25;
  }
}
function chunkGeo(seed) {
  const g = new THREE.IcosahedronGeometry(0.05, 3);
  const p = g.attributes.position;
  for (let i = 0; i < p.count; i++) {
    const x = p.getX(i), y = p.getY(i), z = p.getZ(i);
    const n = 1 + 0.16 * (Math.sin(x * 23 + seed) * Math.cos(y * 19) + Math.sin(z * 17 + y * 11)) * 0.5;
    p.setXYZ(i, x * n * 1.3, y * n * 0.8, z * n);
  }
  g.computeVertexNormals();
  return g;
}
const chunkGeos = [chunkGeo(1.1), chunkGeo(2.7), chunkGeo(4.2)];
function somsaGeo(R) {
  const s = new THREE.Shape();
  s.moveTo(-R, 0);
  s.absarc(0, 0, R, Math.PI, 0, true);
  s.lineTo(-R, 0);
  return new THREE.ExtrudeGeometry(s, { depth: 0.01, bevelEnabled: true, bevelThickness: 0.03, bevelSize: 0.025, bevelSegments: 3, curveSegments: 28 });
}
const somsaG14 = somsaGeo(0.14);
const somsaG20 = somsaGeo(0.2);

const FONT = new FontLoader().parse(await (await fetch('./public/fonts/brand-bold.json')).json());
function makeText(str, width, mat) {
  const geo = new TextGeometry(str, { font: FONT, size: 1, height: 0.22, curveSegments: 6, bevelEnabled: true, bevelThickness: 0.025, bevelSize: 0.02, bevelSegments: 3 });
  geo.computeBoundingBox();
  const bb = geo.boundingBox;
  const w = bb.max.x - bb.min.x;
  geo.translate(-(bb.min.x + bb.max.x) / 2, -(bb.min.y + bb.max.y) / 2, -(bb.min.z + bb.max.z) / 2);
  const m = new THREE.Mesh(geo, mat.clone());
  m.material.transparent = true; m.material.opacity = 0;
  m.scale.setScalar(width / w);
  return m;
}
function setText(m, op, dy = 0) {
  m.material.opacity = op;
  m.position.y = m.userData.y + dy;
}

// ---------- SCENE 0: cinematic intro (0–5s) ----------
const texts = {};
{
  const g = groups[0];
  coalBed(g, 240, 0.9, 0.9, 0.0, 0.05, coalMat);
  const fl = [];
  for (let i = 0; i < 14; i++) fl.push(flame(g, rr(-0.8, 0.8), 0.25, rr(-0.9, -0.3), rr(0.5, 0.9), i % 2 ? 0xff7a2a : 0xffc070));
  const embers = points(g, 320, 0xffa040, 0.035, (i, t) => {
    const L = 3.2, age = (t * 0.55 + hash(i) * L) % L;
    return [(hash(i * 3) - 0.5) * 1.8 + Math.sin(t * 1.7 + i) * 0.05, 0.1 + age * 0.9, (hash(i * 7) - 0.5) * 1.6];
  });
  const puffs = makePuffs(g, 16, { life: 4.5, x: 0, y: 0.6, z: 0, spread: 1.8, zs: 1.4, rise: 0.45, sc: 1.4, op: 0.32, color: 0x6e625a });
  texts.intro = makeText('TEZ KUNDA…', 0.75, creamMat);
  texts.intro.userData.y = 1.2; texts.intro.position.set(0, 1.2, -0.6);
  g.add(texts.intro);
  g.userData.update = (t) => {
    animFlames(fl, t); embers.userData.update(t); animPuffs(puffs, t);
    coalMat.emissiveIntensity = 1.0 + 0.35 * Math.sin(t * 11) + 0.2 * Math.sin(t * 23);
    const op = smooth(1.6, 2.6, t) * (1 - smooth(4.1, 4.9, t));
    setText(texts.intro, op, 0.05 * (1 - smooth(1.6, 2.8, t)));
  };
}

// ---------- SCENE 1: shashlik on coals (5–12s) ----------
const skewers = [];
{
  const g = groups[1];
  const grate = new THREE.Group(); grate.position.y = 0.8; g.add(grate);
  for (let i = 0; i < 12; i++) {
    const r = new THREE.Mesh(new THREE.BoxGeometry(0.012, 0.012, 1.1), rodMat);
    r.position.set(-0.6 + i * 0.11, 0, 0); grate.add(r);
  }
  for (const z of [-0.55, 0.55]) {
    const r = new THREE.Mesh(new THREE.BoxGeometry(1.4, 0.02, 0.02), rodMat);
    r.position.set(0, 0, z); grate.add(r);
  }
  coalBed(g, 260, 0.7, 0.3, 0.52, 0.05, coalMat);
  for (let k = 0; k < 5; k++) {
    const sk = new THREE.Group();
    sk.position.set(0, 0.93, -0.24 + k * 0.12);
    const rod = new THREE.Mesh(new THREE.CylinderGeometry(0.008, 0.008, 1.25, 8), rodMat);
    rod.rotation.z = Math.PI / 2; sk.add(rod);
    for (let c = 0; c < 6; c++) {
      const ch = new THREE.Mesh(chunkGeos[(c + k) % 3], meatMat);
      ch.position.set(-0.5 + c * 0.19 + (hash(k * 9 + c) - 0.5) * 0.03, (hash(c + k * 7) - 0.5) * 0.02, 0);
      ch.rotation.set(hash(c) * 6, hash(c + 3) * 6, 0);
      sk.add(ch);
    }
    skewers.push(sk); g.add(sk);
  }
  const fl = [];
  for (let i = 0; i < 12; i++) fl.push(flame(g, rr(-0.5, 0.5), 0.62, rr(-0.25, 0.25), rr(0.3, 0.45), i % 2 ? 0xff6a1a : 0xffb050));
  const drips = points(g, 90, 0xffa040, 0.03, (i, t) => {
    const L = 0.9, age = (t * 0.9 + hash(i) * L) % L;
    return [(hash(i * 3) - 0.5) * 1.0, 0.92 - (age / L) * 0.3, (hash(i * 5) - 0.5) * 0.5];
  });
  const sizzle = points(g, 120, 0xffc080, 0.025, (i, t) => {
    const L = 1.6, age = (t * 0.7 + hash(i * 2) * L) % L;
    return [(hash(i * 4) - 0.5) * 1.2 + Math.sin(t * 3 + i) * 0.04, 0.95 + age * 0.5, (hash(i * 6) - 0.5) * 0.6];
  });
  const puffs = makePuffs(g, 22, { life: 4.0, x: 0, y: 1.0, z: 0, spread: 1.4, zs: 0.8, rise: 0.4, sc: 0.9, op: 0.28, color: 0x9b8a7e });
  g.userData.update = (t) => {
    skewers.forEach((sk, k) => { sk.rotation.x = t * 0.7 + k * 0.4; });
    animFlames(fl, t); drips.userData.update(t); sizzle.userData.update(t); animPuffs(puffs, t);
    coalMat.emissiveIntensity = 1.0 + 0.35 * Math.sin(t * 11) + 0.2 * Math.sin(t * 23);
  };
}

// ---------- SCENE 2: tandoor with somsa (12–18s) ----------
{
  const g = groups[2];
  const body = new THREE.Mesh(new THREE.LatheGeometry([
    new THREE.Vector2(0.5, 0), new THREE.Vector2(0.58, 0.35), new THREE.Vector2(0.62, 0.7),
    new THREE.Vector2(0.56, 1.05), new THREE.Vector2(0.48, 1.25), new THREE.Vector2(0.44, 1.3)], 64), clayMat);
  g.add(body);
  const rim = new THREE.Mesh(new THREE.TorusGeometry(0.45, 0.035, 12, 64), clayMat);
  rim.rotation.x = Math.PI / 2; rim.position.y = 1.3; g.add(rim);
  const inner = new THREE.Mesh(new THREE.LatheGeometry([
    new THREE.Vector2(0.0, 0.1), new THREE.Vector2(0.4, 0.12), new THREE.Vector2(0.47, 0.6), new THREE.Vector2(0.42, 1.2)], 48),
    new THREE.MeshBasicMaterial({ color: 0xff6a1a, side: THREE.BackSide, transparent: true, opacity: 0.9 }));
  g.add(inner);
  for (let k = 0; k < 7; k++) {
    const a = (k / 7) * Math.PI * 2 + 0.2;
    const y = 0.7 + hash(k) * 0.35;
    const s = new THREE.Mesh(somsaG14, crustMat);
    s.position.set(Math.cos(a) * 0.36, y, Math.sin(a) * 0.36);
    s.scale.setScalar(0.9 + hash(k + 9) * 0.3);
    s.lookAt(0, y, 0);
    g.add(s);
  }
  const hero = new THREE.Mesh(somsaG20, crustMat);
  hero.position.set(0.1, 1.36, 0.5); hero.lookAt(0.25, 1.5, 1.1); hero.rotateX(-0.5); g.add(hero);
  const fl = [];
  for (let i = 0; i < 7; i++) fl.push(flame(g, Math.cos(i) * 0.25, 0.35 + (i % 3) * 0.1, Math.sin(i) * 0.25, 0.9, i % 2 ? 0xff7a2a : 0xffc070, 0.75));
  const puffs = makePuffs(g, 14, { life: 4.5, x: 0, y: 1.35, z: 0, spread: 1.0, zs: 1.0, rise: 0.38, sc: 1.0, op: 0.2, color: 0xcfc4bb });
  const embers = points(g, 120, 0xffa040, 0.03, (i, t) => {
    const L = 3.0, age = (t * 0.6 + hash(i) * L) % L;
    return [(hash(i * 3) - 0.5) * 1.0, 1.4 + age * 0.6, (hash(i * 7) - 0.5) * 1.0];
  });
  g.userData.update = (t) => { animFlames(fl, t); animPuffs(puffs, t); embers.userData.update(t); };
}

// ---------- SCENE 3: premium table reveal (18–24s) ----------
{
  const g = groups[3];
  const top = new THREE.Mesh(new THREE.BoxGeometry(3.0, 0.1, 1.7), woodMat);
  top.position.set(0, 0.8, 0); g.add(top);
  for (const [x, z] of [[-1.35, -0.75], [1.35, -0.75], [-1.35, 0.75], [1.35, 0.75]]) {
    const leg = new THREE.Mesh(new THREE.BoxGeometry(0.12, 0.75, 0.12), woodMat);
    leg.position.set(x, 0.375, z); g.add(leg);
  }
  const plate = (x, y, z) => { const m = new THREE.Mesh(new THREE.LatheGeometry([
    new THREE.Vector2(0.0, 0.0), new THREE.Vector2(0.16, 0.0), new THREE.Vector2(0.2, 0.01),
    new THREE.Vector2(0.27, 0.03), new THREE.Vector2(0.28, 0.04), new THREE.Vector2(0.27, 0.045),
    new THREE.Vector2(0.2, 0.035), new THREE.Vector2(0.0, 0.035)], 48), plateMat); m.position.set(x, 0.85, z); g.add(m); return m; };
  plate(-0.55, 0, 0.15); plate(0.55, 0, -0.15);
  // shashlik on plate 1
  for (let k = 0; k < 2; k++) {
    const sk = new THREE.Group(); sk.position.set(-0.55, 0.93, 0.06 - k * 0.14); sk.rotation.y = 0.1 * k;
    const rod = new THREE.Mesh(new THREE.CylinderGeometry(0.008, 0.008, 0.72, 8), rodMat);
    rod.rotation.z = Math.PI / 2; sk.add(rod);
    for (let c = 0; c < 5; c++) {
      const ch = new THREE.Mesh(chunkGeos[(c + k) % 3], meatMat);
      ch.position.set(-0.27 + c * 0.135, 0, 0); ch.rotation.set(hash(c + k) * 6, hash(c * 2) * 6, 0); sk.add(ch);
    }
    g.add(sk);
  }
  // somsa on plate 2
  const somsaPts = [[0.46, -0.02, -0.25], [0.66, -0.02, -0.25], [0.5, -0.02, -0.05], [0.64, -0.02, -0.04]];
  somsaPts.forEach(([x, y, z], i) => {
    const s = new THREE.Mesh(somsaG14, crustMat);
    s.rotation.set(-Math.PI / 2, 0, (i * Math.PI) / 2.5); s.position.set(x, 0.885 + i * 0.005, z); g.add(s);
  });
  // non (bread)
  const bread = new THREE.Mesh(new THREE.CylinderGeometry(0.36, 0.36, 0.06, 48), [breadMat, breadMat, breadMat]);
  bread.position.set(0.0, 0.9, 0.45); g.add(bread);
  // salad bowl
  const bowl = new THREE.Mesh(new THREE.LatheGeometry([
    new THREE.Vector2(0.0, 0.0), new THREE.Vector2(0.12, 0.02), new THREE.Vector2(0.2, 0.09),
    new THREE.Vector2(0.23, 0.14), new THREE.Vector2(0.235, 0.145)], 48), plateMat);
  bowl.position.set(0.0, 0.85, -0.45); g.add(bowl);
  const tomatoMat = new THREE.MeshStandardMaterial({ color: 0xc81e14, roughness: 0.35, emissive: 0x200000, emissiveIntensity: 0.2 });
  const cukeMat = new THREE.MeshStandardMaterial({ color: 0x4f9a2a, roughness: 0.5 });
  for (let i = 0; i < 14; i++) {
    const a = hash(i) * 6.28, rad = 0.05 + hash(i + 40) * 0.14;
    const t1 = new THREE.Mesh(new THREE.SphereGeometry(0.035, 12, 10), tomatoMat);
    t1.position.set(Math.cos(a) * rad, 0.98 + hash(i + 2) * 0.08, -0.45 + Math.sin(a) * rad); g.add(t1);
    const c1 = new THREE.Mesh(new THREE.CylinderGeometry(0.035, 0.035, 0.012, 16), cukeMat);
    c1.position.set(Math.cos(a + 1) * rad * 0.9, 0.97 + hash(i + 5) * 0.1, -0.45 + Math.sin(a + 1) * rad * 0.9);
    c1.rotation.set(hash(i) * 3, hash(i + 1) * 3, 0); g.add(c1);
  }
  // warm bokeh behind
  const bokehMats = [];
  for (let i = 0; i < 26; i++) {
    const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: glowTex, color: [0xffa040, 0xff7a20, 0xffc070][i % 3], transparent: true, opacity: 0.25, depthWrite: false, blending: THREE.AdditiveBlending }));
    const a = hash(i * 1.7) * Math.PI * 2;
    s.position.set(Math.cos(a) * 6.5, 0.5 + hash(i * 3.1) * 3.2, Math.sin(a) * 6.5);
    s.scale.setScalar(0.7 + hash(i * 5.3) * 1.2);
    s.userData.ph = hash(i) * 6; g.add(s); bokehMats.push(s);
  }
  // steam from food
  const steam = makePuffs(g, 10, { life: 4.0, x: 0, y: 1.0, z: 0, spread: 2.0, zs: 1.2, rise: 0.25, sc: 0.8, op: 0.12, color: 0xd6cbc0 });
  g.userData.update = (t) => {
    bokehMats.forEach((s) => { s.material.opacity = 0.18 + 0.12 * Math.sin(t * 1.3 + s.userData.ph); });
    animPuffs(steam, t);
  };
}

// ---------- SCENE 4: restaurant exterior + brand (24–32s) ----------
const brandTexts = {};
{
  const g = groups[4];
  const sky = new THREE.Mesh(new THREE.SphereGeometry(80, 32, 16), new THREE.MeshBasicMaterial({ map: skyTex, side: THREE.BackSide, fog: false }));
  g.add(sky);
  const ground = new THREE.Mesh(new THREE.PlaneGeometry(80, 80), groundMat);
  ground.rotation.x = -Math.PI / 2; g.add(ground);
  const lower = new THREE.Mesh(new THREE.BoxGeometry(3.6, 3.2, 2.2), wallMat);
  lower.position.set(0, 1.6, 0); g.add(lower);
  const upper = new THREE.Mesh(new THREE.BoxGeometry(2.6, 1.3, 1.8), wallMat);
  upper.position.set(0, 3.85, -0.1); g.add(upper);
  const roof = new THREE.Mesh(new THREE.BoxGeometry(2.8, 0.08, 2.0), glassMat);
  roof.position.set(0, 4.55, -0.1); g.add(roof);
  const awning = new THREE.Mesh(new THREE.BoxGeometry(3.2, 0.08, 0.6), new THREE.MeshStandardMaterial({ color: 0x6b1d12, roughness: 0.6 }));
  awning.position.set(0, 2.3, 1.4); g.add(awning);
  const signMat = new THREE.MeshStandardMaterial({ color: 0x3a1a08, emissive: 0xffa040, emissiveIntensity: 1.2 });
  const sign = new THREE.Mesh(new THREE.BoxGeometry(2.4, 0.22, 0.05), signMat);
  sign.position.set(0, 2.52, 1.15); g.add(sign);
  for (let i = 0; i < 5; i++) for (const y of [1.0, 2.0]) {
    if (y === 2.0 && i === 2) continue;
    const w = new THREE.Mesh(new THREE.PlaneGeometry(0.45, 0.6), new THREE.MeshBasicMaterial({ color: 0xffc27a, transparent: true, opacity: 0.85 }));
    w.position.set(-1.45 + i * 0.72, y + 0.1, 1.115); g.add(w);
  }
  const door = new THREE.Mesh(new THREE.PlaneGeometry(0.9, 1.5), new THREE.MeshBasicMaterial({ color: 0xffb060, transparent: true, opacity: 0.8 }));
  door.position.set(0, 0.8, 1.115); g.add(door);
  for (let i = 0; i < 2; i++) {
    const side = i ? 1 : -1;
    const post = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 2.6, 8), rodMat);
    post.position.set(side * 2.3, 1.3, 1.6); g.add(post);
    const bulb = flame(g, side * 2.3, 2.7, 1.6, 0.9, 0xffb060, 0.9);
  }
  const stars = points(g, 500, 0xffffff, 0.12, (i) => {
    const a = hash(i) * Math.PI * 2, e = 0.2 + hash(i + 7) * 1.2;
    return [Math.cos(a) * Math.cos(e) * 40, 6 + Math.sin(e) * 35, Math.sin(a) * Math.cos(e) * 40 - 10];
  });
  stars.material.opacity = 0.7;
  brandTexts.title = makeText('PO‘LATHON', 2.4, goldMat);
  brandTexts.title.userData.y = 3.5; brandTexts.title.position.set(0, 3.5, 3.4); g.add(brandTexts.title);
  brandTexts.sub = makeText('SHASHLIK • SOMSA', 1.8, creamMat);
  brandTexts.sub.userData.y = 2.95; brandTexts.sub.position.set(0, 2.95, 3.4); g.add(brandTexts.sub);
  const puffs = makePuffs(g, 10, { life: 5, x: 0, y: 0.2, z: 1.5, spread: 6, zs: 4, rise: 0.12, sc: 2.2, op: 0.12, color: 0x7d6d62 });
  g.userData.update = (t) => {
    animPuffs(puffs, t);
    stars.userData.update(t);
    setText(brandTexts.title, smooth(25.8, 27.6, t), 0.4 * (1 - smooth(25.8, 27.6, t)));
    setText(brandTexts.sub, smooth(27.0, 28.6, t), 0.3 * (1 - smooth(27.0, 28.6, t)));
  };
}

// ---------- SCENE 5: final announcement (32–40s) ----------
const finalTexts = {};
{
  const g = groups[5];
  const back = new THREE.Mesh(new THREE.PlaneGeometry(30, 30), new THREE.MeshBasicMaterial({ color: 0x120804 }));
  back.position.z = -3; g.add(back);
  const fl = [];
  for (let i = 0; i < 16; i++) fl.push(flame(g, rr(-3.5, 3.5), -2.6, -2.2, rr(1.5, 2.4), i % 2 ? 0xff6a1a : 0xffb050, 0.6));
  const puffs = makePuffs(g, 18, { life: 6, x: 0, y: -1.5, z: -2, spread: 8, zs: 2, rise: 0.25, sc: 2.4, op: 0.22, color: 0x6e5f55 });
  const embers = points(g, 260, 0xffa040, 0.04, (i, t) => {
    const L = 4.5, age = (t * 0.6 + hash(i) * L) % L;
    return [(hash(i * 3) - 0.5) * 6, -2.4 + age * 1.2, -1.5 + (hash(i * 7) - 0.5) * 2];
  });
  const glow = new THREE.Sprite(new THREE.SpriteMaterial({ map: glowTex, color: 0xff8a30, transparent: true, opacity: 0.35, depthWrite: false, blending: THREE.AdditiveBlending }));
  glow.position.set(0, 0.45, -0.5); glow.scale.setScalar(5); g.add(glow);
  finalTexts.logo = makeText('PO‘LATHON', 2.2, goldMat);
  finalTexts.logo.userData.y = 0.45; finalTexts.logo.position.set(0, 0.45, 0.2); g.add(finalTexts.logo);
  finalTexts.sub = makeText('SHASHLIK • SOMSA', 1.5, creamMat);
  finalTexts.sub.userData.y = -0.15; finalTexts.sub.position.set(0, -0.15, 0.2); g.add(finalTexts.sub);
  finalTexts.cta = makeText('TEZ KUNDA OCHILAMIZ!', 2.0, goldMat);
  finalTexts.cta.userData.y = 1.7; finalTexts.cta.position.set(0, 1.7, 0.2); g.add(finalTexts.cta);
  finalTexts.addr = makeText('ESHONGUZAR — PO‘LATHON', 2.0, creamMat);
  finalTexts.addr.userData.y = -1.25; finalTexts.addr.position.set(0, -1.25, 0.2); g.add(finalTexts.addr);
  g.userData.update = (t) => {
    animFlames(fl, t); animPuffs(puffs, t); embers.userData.update(t);
    glow.material.opacity = 0.3 + 0.1 * Math.sin(t * 5);
    setText(finalTexts.logo, smooth(32.0, 33.0, t), 0);
    setText(finalTexts.sub, smooth(32.4, 33.4, t), 0);
    setText(finalTexts.cta, smooth(33.2, 34.2, t), 0);
    setText(finalTexts.addr, smooth(34.6, 35.6, t), 0);
  };
}

// ---------- camera ----------
function camKeys(t, keys) {
  for (let i = 0; i < keys.length - 1; i++) {
    const a = keys[i], b = keys[i + 1];
    if (t <= b[0]) {
      const k = easeIO(clamp((t - a[0]) / (b[0] - a[0])));
      return a.slice(1).map((v, j) => v + (b[j + 1] - v) * k);
    }
  }
  return keys[keys.length - 1].slice(1);
}
function updateCamera(t, idx) {
  let p;
  if (idx === 0) p = camKeys(t, [[0, 0.3, 0.45, 1.4, 0, 1.2, -0.6], [5, 0.0, 1.0, 0.7, 0, 1.2, -0.6]]);
  else if (idx === 1) p = camKeys(t, [[5, 0.55, 1.25, 1.25, 0, 0.95, 0], [9, 0.2, 1.0, 0.7, 0, 0.95, 0], [12, 0.05, 1.05, 0.85, 0.1, 0.92, 0]]);
  else if (idx === 2) p = camKeys(t, [[12, 0, 3.4, 2.4, 0, 0.7, 0], [18, 0.2, 2.1, 1.7, 0, 1.0, 0]]);
  else if (idx === 3) {
    const th = Math.PI * (0.08 + 0.84 * easeIO(clamp((t - 18) / 6)));
    const r = 3.5;
    p = [Math.sin(th) * r, 1.55 + 0.2 * Math.sin(th), Math.cos(th) * r, 0, 0.85, 0];
  } else if (idx === 4) p = camKeys(t, [[24, 0, 0.6, 8.5, 0, 2.0, 0], [32, 0, 4.2, 9.0, 0, 3.7, 0]]);
  else p = camKeys(t, [[32, 0, 0.3, 6.0, 0, 0.3, 0], [40, 0, 0.3, 4.6, 0, 0.3, 0]]);
  const sx = Math.sin(t * 1.7) * 0.012, sy = Math.sin(t * 2.3) * 0.01;
  camera.position.set(p[0] + sx, p[1] + sy, p[2]);
  camera.lookAt(p[3], p[4], p[5]);
}

// ---------- overlays (fades / flashes) ----------
function overlay(color) {
  const m = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), new THREE.MeshBasicMaterial({ color, transparent: true, opacity: 0, depthTest: false, depthWrite: false }));
  m.position.set(0, 0, -1); m.renderOrder = 999; camera.add(m);
  return m;
}
const blackOv = overlay(0x000000);
const flashOv = overlay(0xffa45a);

function sceneIndexAt(t) {
  let idx = 0;
  for (let i = 0; i < SCENE_STARTS.length; i++) if (t >= SCENE_STARTS[i]) idx = i;
  return idx;
}

function updateLights(t, idx) {
  const fl = 1 + 0.12 * Math.sin(t * 13) + 0.08 * Math.sin(t * 27);
  key.intensity = [0.3, 0.5, 0.15, 1.1, 0.7, 0.15][idx];
  rim.intensity = [0.3, 0.4, 0.25, 0.6, 0.5, 0.3][idx];
  hemi.intensity = [0.25, 0.3, 0.2, 0.45, 0.45, 0.2][idx];
  fireLight.intensity = [3.0, 2.4, 4.5, 0.0, 0.0, 3.0][idx] * fl;
  fireLight.color.set(idx === 4 ? 0xffa050 : 0xff7a2a);
  fireLight.position.set(0, [0.3, 0.6, 0.5, 0, 0, -0.4][idx], idx === 2 ? 0.0 : 0.1);
  fillLight.intensity = [0, 0, 0, 3.0, 2.0, 0][idx];
  fillLight.position.set(0, 2.0, 1.2);
  lampA.intensity = idx === 4 ? 3.0 : 0; lampA.position.set(-2.3, 2.7, 1.6);
  lampB.intensity = idx === 4 ? 3.0 : 0; lampB.position.set(2.3, 2.7, 1.6);
}

function updateOverlays(t) {
  let black = 1 - smooth(0, 0.8, t);
  black = Math.max(black, smooth(38.9, 39.95, t));
  blackOv.material.opacity = black;
  let flash = 0;
  for (const b of [5, 12, 18, 24, 32]) flash = Math.max(flash, Math.max(0, 1 - Math.abs(t - b) / 0.22) * 0.75);
  flash = Math.max(flash, Math.max(0, 1 - Math.abs(t - 39.35) / 0.3) * 0.9);
  flashOv.material.opacity = flash;
}

// ---------- public API ----------
function renderFrame(frame) {
  const t = frame / FPS;
  const idx = sceneIndexAt(t);
  groups.forEach((g, i) => { g.visible = i === idx; });
  updateCamera(t, idx);
  updateLights(t, idx);
  const g = groups[idx];
  if (g.userData.update) g.userData.update(t);
  updateOverlays(t);
  renderer.render(scene, camera);
  return renderer.domElement.toDataURL('image/jpeg', 0.95);
}

window.renderFrame = renderFrame;
window.READY = true;

window.__dbg = { scene, groups, camera, renderer };
