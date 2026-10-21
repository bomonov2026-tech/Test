// 3D building blocks: studio environment, phone, browser window, glass UI panels, logo, particles, trails, beams.
import * as THREE from 'three';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
import { TextGeometry } from 'three/addons/geometries/TextGeometry.js';
import { rng } from './lib.js';

export const BRAND = { blue: new THREE.Color('#2f80ed'), violet: new THREE.Color('#5b5cf6'), sky: new THREE.Color('#4c8dff'), bg: new THREE.Color('#05070c') };

const SDF = /* glsl */`
float sdRR(vec2 p, vec2 b, float r) { vec2 q = abs(p) - b + r; return length(max(q, 0.0)) + min(max(q.x, q.y), 0.0) - r; }`;

/* ---------- studio environment for reflections (softboxes + brand-coloured strips) ---------- */
export function studioEnv(renderer) {
  const s = new THREE.Scene(); s.background = new THREE.Color(0x020306);
  const box = (w, h, color, k, pos, look) => {
    const m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ color: new THREE.Color(color).multiplyScalar(k), side: THREE.DoubleSide }));
    m.position.set(...pos); m.lookAt(...look); s.add(m);
  };
  box(14, 5, '#ffffff', 3.2, [0, 9, 4], [0, 0, 0]);        // top softbox
  box(2.2, 14, '#3b82f6', 4.0, [-10, 1, 2], [0, 0, 0]);    // left blue strip
  box(2.2, 14, '#7c5cff', 3.2, [10, 1, -1], [0, 0, 0]);    // right violet strip
  box(16, 6, '#dfe8ff', 0.95, [0, 1.5, 11], [0, 0, 0]);   // big front softbox (keeps chrome logo bright)
  box(20, 6, '#0b1a33', 1.0, [0, -6, 0], [0, 0, 0]);       // dim floor bounce
  const pm = new THREE.PMREMGenerator(renderer); const rt = pm.fromScene(s, 0.035); pm.dispose();
  return rt.texture;
}

/* ---------- backdrop sphere with soft radial glow ---------- */
export function backdrop() {
  const m = new THREE.ShaderMaterial({
    side: THREE.BackSide, depthWrite: false,
    uniforms: { uGlow: { value: new THREE.Vector3(0, 0, 0.4) }, uTint: { value: new THREE.Color('#0d2a66') }, uK: { value: 1 } },
    vertexShader: `varying vec3 vDir; void main(){ vDir = normalize(position); gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: `varying vec3 vDir; uniform vec3 uGlow; uniform vec3 uTint; uniform float uK;
      void main(){ float g = pow(max(dot(normalize(vDir), normalize(vec3(uGlow.x, uGlow.y, -1.0))), 0.0), 18.0);
        float h = smoothstep(-0.6, 0.5, vDir.y);
        vec3 c = vec3(0.004,0.006,0.012) + uTint * (g * 0.22 * uGlow.z) + vec3(0.0,0.004,0.012) * h;
        gl_FragColor = vec4(c * uK, 1.0); }`,
  });
  const mesh = new THREE.Mesh(new THREE.SphereGeometry(90, 48, 24), m); mesh.renderOrder = -10; return mesh;
}

/* ---------- screen material (phone + browser): scrolling texture, page slide, power-on ---------- */
export function screenMaterial(o) {
  return new THREE.ShaderMaterial({
    transparent: true,
    uniforms: {
      uA: { value: null }, uB: { value: null }, uAH: { value: 844 }, uBH: { value: 844 }, uView: { value: o.view },
      uScrollA: { value: 0 }, uScrollB: { value: 0 }, uMix: { value: 0 }, uOn: { value: 1 },
      uSize: { value: new THREE.Vector2(o.w, o.h) }, uRad: { value: o.r }, uSB: { value: o.sb || 0 }, uBright: { value: 0.86 },
      uSheen: { value: 0 }, uTap: { value: new THREE.Vector4(0, 0, 0, 0) },
    },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: SDF + `varying vec2 vUv; uniform sampler2D uA, uB; uniform float uAH, uBH, uView, uScrollA, uScrollB, uMix, uOn, uRad, uSB, uBright, uSheen; uniform vec2 uSize; uniform vec4 uTap;
      vec3 page(sampler2D t, float H, float sc, vec2 q){ float y = sc + (1.0 - q.y) * uView; return texture2D(t, vec2(q.x, 1.0 - y / H)).rgb; }
      void main(){
        vec2 p = (vUv - 0.5) * uSize; float d = sdRR(p, uSize * 0.5, uRad); float aa = fwidth(d);
        float mask = 1.0 - smoothstep(-aa, aa, d); if (mask <= 0.0) discard;
        vec2 q = vec2(vUv.x, vUv.y / (1.0 - uSB));
        vec3 col;
        if (vUv.y > 1.0 - uSB) {
          col = vec3(0.0044, 0.0056, 0.0080);
          vec2 ip = p - vec2(0.0, uSize.y * 0.5 - uSB * uSize.y * 0.52); float di = sdRR(ip, vec2(uSize.x * 0.15, uSB * uSize.y * 0.27), uSB * uSize.y * 0.27);
          col = mix(col, vec3(0.0), 1.0 - smoothstep(-aa, aa, di));
        } else {
          float xa = q.x + uMix * 0.28; vec3 a = page(uA, uAH, uScrollA, vec2(xa, q.y)) * (1.0 - uMix * 0.55);
          float xb = q.x - (1.0 - uMix);
          col = a;
          if (uMix > 0.0 && xb >= 0.0) { vec3 b = page(uB, uBH, uScrollB, vec2(xb, q.y)); float sh = smoothstep(0.0, 0.04, xb); col = mix(a * 0.6, b, sh); }
        }
        col *= uBright * uOn;
        if (uTap.w > 0.0) { float r = length((vUv - uTap.xy) * vec2(uSize.x / uSize.y, 1.0)); float rr = uTap.z * 0.09;
          col += vec3(0.75, 0.85, 1.0) * (exp(-pow((r - rr) / 0.006, 2.0)) * 0.7 + (1.0 - smoothstep(rr - 0.004, rr, r)) * 0.18) * (1.0 - uTap.z) * uTap.w; }
        float s = vUv.x * 0.55 + vUv.y; col += vec3(0.9, 0.95, 1.0) * 0.045 * smoothstep(0.16, 0.0, abs(s - uSheen)) * uOn;
        gl_FragColor = vec4(col, mask);
      }`,
  });
}

function roundedRectShape(w, h, r) {
  const s = new THREE.Shape(), x = -w / 2, y = -h / 2;
  s.moveTo(x + r, y); s.lineTo(x + w - r, y); s.quadraticCurveTo(x + w, y, x + w, y + r); s.lineTo(x + w, y + h - r); s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
  s.lineTo(x + r, y + h); s.quadraticCurveTo(x, y + h, x, y + h - r); s.lineTo(x, y + r); s.quadraticCurveTo(x, y, x + r, y); return s;
}
function planeUV(geo) { // ShapeGeometry UVs are in shape units → normalise to 0..1
  geo.computeBoundingBox(); const b = geo.boundingBox, uv = geo.attributes.uv, pos = geo.attributes.position;
  for (let i = 0; i < uv.count; i++) uv.setXY(i, (pos.getX(i) - b.min.x) / (b.max.x - b.min.x), (pos.getY(i) - b.min.y) / (b.max.y - b.min.y));
  return geo;
}

/* ---------- smartphone ---------- */
export const PHONE = { sw: 1.5617, sh: 3.38 };   // 390 x 844 css px → world
export function phone() {
  const g = new THREE.Group();
  const SB = 0.055, sw = PHONE.sw, sh = PHONE.sh / (1 - SB), bw = sw + 0.13, bh = sh + 0.13, depth = 0.17;
  const titanium = new THREE.MeshPhysicalMaterial({ color: 0x3a3f49, metalness: 1, roughness: 0.26, clearcoat: 0.6, clearcoatRoughness: 0.15 });
  const body = new THREE.Mesh(new RoundedBoxGeometry(bw, bh, depth, 8, 0.27), titanium); g.add(body);
  const glassMat = new THREE.MeshPhysicalMaterial({ color: 0x010203, metalness: 0, roughness: 0.04, clearcoat: 1, clearcoatRoughness: 0.03, reflectivity: 0.6 });
  const front = new THREE.Mesh(planeUV(new THREE.ShapeGeometry(roundedRectShape(bw - 0.03, bh - 0.03, 0.255), 24)), glassMat); front.position.z = depth / 2 + 0.0015; g.add(front);
  const mat = screenMaterial({ w: sw, h: sh, r: 0.2, view: 844, sb: SB });
  const screen = new THREE.Mesh(new THREE.PlaneGeometry(sw, sh), mat); screen.position.z = depth / 2 + 0.003; g.add(screen);
  const btn = (y, h, side) => { const b = new THREE.Mesh(new RoundedBoxGeometry(0.03, h, 0.06, 3, 0.012), titanium); b.position.set(side * (bw / 2 + 0.006), y, 0); g.add(b); };
  btn(0.75, 0.42, 1); btn(0.95, 0.24, -1); btn(0.55, 0.4, -1); btn(0.08, 0.4, -1);
  const back = new THREE.Mesh(new RoundedBoxGeometry(0.62, 0.62, 0.04, 4, 0.14), new THREE.MeshPhysicalMaterial({ color: 0x23262d, metalness: 1, roughness: 0.2 }));
  back.position.set(-bw / 2 + 0.42, bh / 2 - 0.42, -depth / 2 - 0.015); g.add(back);
  g.userData = { screen: mat, size: [bw, bh] };
  return g;
}

/* ---------- desktop browser window ---------- */
export function browser(titleTex) {
  const g = new THREE.Group(), w = 5.2, ch = 3.25, bar = 0.26, h = ch + bar;
  const frame = new THREE.Mesh(new RoundedBoxGeometry(w + 0.06, h + 0.06, 0.05, 6, 0.09), new THREE.MeshPhysicalMaterial({ color: 0x151a22, metalness: 0.7, roughness: 0.3, clearcoat: 1, clearcoatRoughness: 0.1 }));
  g.add(frame);
  const title = new THREE.Mesh(new THREE.PlaneGeometry(w, bar), new THREE.MeshBasicMaterial({ map: titleTex, transparent: true }));
  title.position.set(0, h / 2 - bar / 2, 0.027); g.add(title);
  const mat = screenMaterial({ w, h: ch, r: 0.06, view: 900 }); mat.uniforms.uAH.value = 900;
  const scr = new THREE.Mesh(new THREE.PlaneGeometry(w, ch), mat); scr.position.set(0, -bar / 2, 0.027); g.add(scr);
  g.userData = { screen: mat };
  return g;
}
export function browserTitle() {
  const c = document.createElement('canvas'); c.width = 2080; c.height = 104; const x = c.getContext('2d');
  x.fillStyle = '#161b24'; x.fillRect(0, 0, c.width, c.height);
  [['#ff5f57', 52], ['#febc2e', 100], ['#28c840', 148]].forEach(([col, cx]) => { x.fillStyle = col; x.beginPath(); x.arc(cx, 52, 15, 0, 7); x.fill(); });
  x.fillStyle = '#0f131a'; const pw = 720; x.beginPath(); x.roundRect((c.width - pw) / 2, 22, pw, 60, 30); x.fill();
  x.fillStyle = '#c8d0dc'; x.font = '600 30px Inter'; x.textAlign = 'center'; x.textBaseline = 'middle'; x.fillText('USD/UZS Premium', c.width / 2, 53);
  const t = new THREE.CanvasTexture(c); t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 8; return t;
}

/* ---------- glass UI panel with a real interface crop ---------- */
export function panel(tex, w, opt = {}) {
  const img = tex.image, h = w * img.height / img.width, m = 0.18, r = opt.r != null ? opt.r : w * 0.055;
  const g = new THREE.Group();
  const glass = new THREE.Mesh(new RoundedBoxGeometry(w + 0.05, h + 0.05, 0.035, 5, Math.min(r + 0.02, h / 2)),
    new THREE.MeshPhysicalMaterial({ color: 0x0a1426, metalness: 0.3, roughness: 0.14, clearcoat: 1, clearcoatRoughness: 0.08, transparent: true, opacity: 0.6 }));
  glass.position.z = -0.022; g.add(glass);
  const mat = new THREE.ShaderMaterial({
    transparent: true, depthWrite: false,
    uniforms: { uTex: { value: tex }, uSize: { value: new THREE.Vector2(w, h) }, uOut: { value: new THREE.Vector2(w + m * 2, h + m * 2) }, uRad: { value: r },
      uOp: { value: 1 }, uEdge: { value: opt.edge != null ? opt.edge : 1 }, uBright: { value: opt.bright || 0.86 }, uHi: { value: new THREE.Vector4(0, 0, 0, 0) }, uTap: { value: new THREE.Vector4(0, 0, 0, 0) }, uSheen: { value: -1 } },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: SDF + `varying vec2 vUv; uniform sampler2D uTex; uniform vec2 uSize, uOut; uniform float uRad, uOp, uEdge, uBright, uSheen; uniform vec4 uHi, uTap;
      void main(){
        vec2 p = (vUv - 0.5) * uOut; float d = sdRR(p, uSize * 0.5, uRad); float aa = fwidth(d);
        vec2 t = p / uSize + 0.5; vec4 tx = texture2D(uTex, clamp(t, 0.0, 1.0));
        float inside = 1.0 - smoothstep(-aa, aa, d);
        vec3 col = tx.rgb * uBright; float a = tx.a * inside;
        if (uHi.z > 0.0) { float band = smoothstep(uHi.x - 0.01, uHi.x + 0.01, t.y) * (1.0 - smoothstep(uHi.y - 0.01, uHi.y + 0.01, t.y));
          col = mix(col, col * 1.15 + vec3(0.06, 0.16, 0.42), band * uHi.z * 0.55);
          float bar = band * (1.0 - smoothstep(0.0, 0.012, t.x)) ; col += vec3(0.3, 0.55, 1.6) * bar * uHi.z; }
        if (uTap.w > 0.0) { float rr = uTap.z * 0.16; float r = length((t - uTap.xy) * vec2(uSize.x / uSize.y, 1.0));
          col += vec3(0.8, 0.9, 1.0) * (exp(-pow((r - rr) / 0.008, 2.0)) * 0.9 + (1.0 - smoothstep(rr - 0.004, rr, r)) * 0.2) * (1.0 - uTap.z) * uTap.w; }
        float s = t.x * 0.5 + t.y; col += vec3(0.9, 0.95, 1.0) * 0.05 * smoothstep(0.12, 0.0, abs(s - uSheen)) * inside;
        float e = exp(-abs(d) / 0.012) * uEdge; vec3 ec = mix(vec3(0.18, 0.5, 1.0), vec3(0.36, 0.36, 1.0), t.x) * (0.35 + 0.65 * smoothstep(-0.2, 1.2, t.y + (1.0 - t.x) * 0.4));
        col += ec * e * 0.9 * inside; float ea = e * 0.55 * (1.0 - inside);
        gl_FragColor = vec4(col + ec * ea * 1.6, max(a, ea)) * vec4(1.0, 1.0, 1.0, uOp);
      }`,
  });
  const face = new THREE.Mesh(new THREE.PlaneGeometry(w + m * 2, h + m * 2), mat); face.position.z = 0.0; face.renderOrder = 2; g.add(face);
  g.userData = { mat, glass: glass.material, w, h };
  return g;
}
export function setPanelOpacity(p, o) { p.userData.mat.uniforms.uOp.value = o; p.userData.glass.opacity = 0.6 * o; p.visible = o > 0.002; }

/* ---------- 3D logo: ▶ Usd Go24 ---------- */
export function logo(font) {
  const g = new THREE.Group(), size = 0.9, depth = 0.24;
  const silver = new THREE.MeshPhysicalMaterial({ color: 0xf2f5fb, metalness: 1, roughness: 0.22, clearcoat: 0.8, clearcoatRoughness: 0.08 });
  const blue = new THREE.MeshPhysicalMaterial({ color: 0x2f80ed, metalness: 0.85, roughness: 0.2, clearcoat: 1, clearcoatRoughness: 0.06, emissive: 0x0d3fa8, emissiveIntensity: 0.22 });
  const parts = [];
  const tri = new THREE.Shape(), th = size * 0.74, tw = th * 0.9, rr = 0.07;
  tri.moveTo(0, th / 2 - rr); tri.quadraticCurveTo(0, th / 2, rr * 0.9, th / 2 - rr * 0.5); tri.lineTo(tw - rr, rr * 0.55); tri.quadraticCurveTo(tw, 0, tw - rr, -rr * 0.55);
  tri.lineTo(rr * 0.9, -th / 2 + rr * 0.5); tri.quadraticCurveTo(0, -th / 2, 0, -th / 2 + rr); tri.lineTo(0, th / 2 - rr);
  const tg = new THREE.ExtrudeGeometry(tri, { depth, bevelEnabled: true, bevelThickness: 0.03, bevelSize: 0.022, bevelSegments: 5, curveSegments: 16 });
  const triM = new THREE.Mesh(tg, silver); triM.position.y = size * 0.36; parts.push({ mesh: triM, w: tw, gap: 0.3 });
  const res = font.data.resolution, glyphs = font.data.glyphs;
  for (const [txt, mat] of [['Usd', silver], [' ', null], ['Go24', blue]]) {
    for (const ch of txt) {
      const adv = (glyphs[ch] ? glyphs[ch].ha : 300) / res * size;
      if (!mat) { parts.push({ space: adv }); continue; }
      const geo = new TextGeometry(ch, { font, size, depth, curveSegments: 10, bevelEnabled: true, bevelThickness: 0.025, bevelSize: 0.014, bevelSegments: 4 });
      geo.computeBoundingBox(); const bb = geo.boundingBox; const cx = (bb.min.x + bb.max.x) / 2;
      geo.translate(-cx, 0, 0);
      const m = new THREE.Mesh(geo, mat); parts.push({ mesh: m, w: adv, cx });
    }
  }
  let x = 0; const letters = [];
  for (const p of parts) {
    if (p.space) { x += p.space * 0.8; continue; }
    const holder = new THREE.Group(); holder.add(p.mesh);
    if (p.cx !== undefined) { holder.position.x = x + p.cx; x += p.w * 0.97; } else { holder.position.x = x; x += p.w + p.gap; }
    g.add(holder); letters.push(holder);
  }
  g.children.forEach(c => { c.position.x -= x / 2; c.position.z -= depth / 2; c.userData.x0 = c.position.x; });
  g.userData = { letters, width: x };
  return g;
}

/* ---------- particles with fake depth-of-field ---------- */
export function particles(n, box, seed) {
  const r = rng(seed), pos = new Float32Array(n * 3), s = new Float32Array(n * 4);
  for (let i = 0; i < n; i++) { pos.set([r(), r(), r()], i * 3); s.set([r(), r(), r(), r()], i * 4); }
  const geo = new THREE.BufferGeometry(); geo.setAttribute('position', new THREE.BufferAttribute(pos, 3)); geo.setAttribute('aS', new THREE.BufferAttribute(s, 4));
  const mat = new THREE.ShaderMaterial({
    transparent: true, depthWrite: false, blending: THREE.AdditiveBlending,
    uniforms: { uT: { value: 0 }, uMin: { value: new THREE.Vector3(...box[0]) }, uSize: { value: new THREE.Vector3(...box[1]) }, uFocus: { value: 8 }, uA: { value: 1 }, uPx: { value: 1080 / (2 * Math.tan(16 * Math.PI / 180)) }, uVel: { value: new THREE.Vector3(0.05, 0.12, 0.25) } },
    vertexShader: `attribute vec4 aS; uniform float uT, uFocus, uPx; uniform vec3 uMin, uSize, uVel; varying float vA, vB; varying vec3 vC;
      void main(){ vec3 p = uMin + mod(position * uSize + uVel * uT * (0.5 + aS.x) + vec3(0.0, sin(uT * 0.4 + aS.y * 6.28) * 0.15, 0.0), uSize);
        vec4 mv = modelViewMatrix * vec4(p, 1.0); float z = -mv.z; float coc = clamp(abs(z - uFocus) * 0.09, 0.0, 1.0);
        float base = 0.012 + aS.z * 0.02; gl_PointSize = min((base + coc * 0.12) * uPx / max(z, 0.1), 70.0);
        vA = (0.55 + 0.45 * sin(uT * (1.0 + aS.w * 2.0) + aS.y * 40.0)) / (1.0 + coc * coc * 26.0) * smoothstep(0.3, 1.5, z); vB = coc;
        vC = mix(vec3(0.35, 0.6, 1.0), vec3(0.62, 0.55, 1.0), aS.w); if (aS.x > 0.86) vC = vec3(0.9, 0.95, 1.0);
        gl_Position = projectionMatrix * mv; }`,
    fragmentShader: `uniform float uA; varying float vA, vB; varying vec3 vC;
      void main(){ float d = length(gl_PointCoord - 0.5) * 2.0; float soft = exp(-d * d * 4.0); float disc = (1.0 - smoothstep(0.82, 1.0, d)) * (0.75 + 0.25 * smoothstep(0.5, 0.95, d));
        float a = mix(soft, disc * 0.6, smoothstep(0.1, 0.5, vB)) * vA * uA; if (a < 0.002) discard; gl_FragColor = vec4(vC * a * 2.2, 1.0); }`,
  });
  const p = new THREE.Points(geo, mat); p.frustumCulled = false; return p;
}

/* ---------- glowing light trails ---------- */
export function trail(points, color, width = 0.014) {
  const curve = new THREE.CatmullRomCurve3(points.map(p => new THREE.Vector3(...p)), false, 'centripetal');
  const N = 260, pos = [], u = [], side = [], idx = [];
  for (let i = 0; i <= N; i++) {
    const t = i / N, p = curve.getPointAt(t), tg = curve.getTangentAt(t), nrm = new THREE.Vector3().crossVectors(tg, new THREE.Vector3(0, 0, 1)).normalize();
    if (nrm.lengthSq() < 0.01) nrm.set(0, 1, 0);
    for (const s of [-1, 1]) { pos.push(p.x + nrm.x * width * s, p.y + nrm.y * width * s, p.z + nrm.z * width * s); u.push(t); side.push(s); }
    if (i < N) { const a = i * 2; idx.push(a, a + 1, a + 2, a + 1, a + 3, a + 2); }
  }
  const geo = new THREE.BufferGeometry(); geo.setAttribute('position', new THREE.Float32BufferAttribute(pos, 3)); geo.setAttribute('aU', new THREE.Float32BufferAttribute(u, 1)); geo.setAttribute('aSide', new THREE.Float32BufferAttribute(side, 1)); geo.setIndex(idx);
  const mat = new THREE.ShaderMaterial({
    transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide,
    uniforms: { uHead: { value: 0 }, uLen: { value: 0.35 }, uC: { value: new THREE.Color(color) }, uA: { value: 1 } },
    vertexShader: `attribute float aU, aSide; varying float vU, vS; void main(){ vU = aU; vS = aSide; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: `uniform float uHead, uLen, uA; uniform vec3 uC; varying float vU, vS;
      void main(){ float b = uHead - vU; if (b < 0.0 || b > uLen) discard; float f = 1.0 - b / uLen; float core = exp(-vS * vS * 3.0);
        float a = pow(f, 2.2) * core * uA; gl_FragColor = vec4((uC * 2.5 + vec3(1.4) * pow(f, 12.0)) * a, 1.0); }`,
  });
  const m = new THREE.Mesh(geo, mat); m.frustumCulled = false; return m;
}

/* ---------- soft additive glow sprite & volumetric beam ---------- */
export function glow(color, size, k = 1) {
  const m = new THREE.ShaderMaterial({ transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, uniforms: { uC: { value: new THREE.Color(color) }, uK: { value: k } },
    vertexShader: `varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }`,
    fragmentShader: `uniform vec3 uC; uniform float uK; varying vec2 vUv; void main(){ float d = length(vUv - 0.5) * 2.0; float a = exp(-d * d * 5.0) * (1.0 - smoothstep(0.8, 1.0, d)); gl_FragColor = vec4(uC * a * uK, 1.0); }` });
  return new THREE.Mesh(new THREE.PlaneGeometry(size, size), m);
}
export function beam(color, r, h, k = 1) {
  const geo = new THREE.CylinderGeometry(0.05, r, h, 64, 1, true); geo.translate(0, -h / 2, 0);
  const m = new THREE.ShaderMaterial({ transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, side: THREE.DoubleSide,
    uniforms: { uC: { value: new THREE.Color(color) }, uK: { value: k }, uT: { value: 0 } },
    vertexShader: `varying vec2 vUv; varying vec3 vN, vV; void main(){ vUv = uv; vec4 mv = modelViewMatrix * vec4(position,1.0); vN = normalize(normalMatrix * normal); vV = normalize(-mv.xyz); gl_Position = projectionMatrix * mv; }`,
    fragmentShader: `uniform vec3 uC; uniform float uK, uT; varying vec2 vUv; varying vec3 vN, vV;
      void main(){ float e = pow(abs(dot(vN, vV)), 2.5); float along = pow(vUv.y, 1.6) * smoothstep(0.0, 0.25, vUv.y);
        float n = 0.75 + 0.25 * sin(vUv.x * 40.0 + uT * 0.6) * sin(vUv.x * 17.0 - uT * 0.4);
        gl_FragColor = vec4(uC * e * along * n * uK * 0.16, 1.0); }` });
  const mesh = new THREE.Mesh(geo, m); mesh.renderOrder = 5; return mesh;
}
