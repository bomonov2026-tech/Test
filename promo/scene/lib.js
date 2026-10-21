// Shared helpers: easing, deterministic RNG, keyframe interpolation, canvas text.
import * as THREE from 'three';

export const clamp = (x, a = 0, b = 1) => Math.min(b, Math.max(a, x));
export const lerp = (a, b, t) => a + (b - a) * t;
export const range = (t, a, b) => clamp((t - a) / (b - a));
export const ease = {
  inOut: t => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2),
  out: t => 1 - Math.pow(1 - t, 3),
  outQuint: t => 1 - Math.pow(1 - t, 5),
  in: t => t * t * t,
  outBack: t => { const c1 = 1.5, c3 = c1 + 1; return 1 + c3 * Math.pow(t - 1, 3) + c1 * Math.pow(t - 1, 2); },
  inOutSine: t => -(Math.cos(Math.PI * t) - 1) / 2,
};
// smooth 0→1→0 envelope: rises over [a,a+i], holds, falls over [b-o,b]
export const env = (t, a, b, i = 0.4, o = 0.4) => Math.min(ease.out(range(t, a, a + i)), 1 - ease.in(range(t, b - o, b)));

export function rng(seed) {
  return () => { seed |= 0; seed = (seed + 0x6D2B79F5) | 0; let t = Math.imul(seed ^ (seed >>> 15), 1 | seed); t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t; return ((t ^ (t >>> 14)) >>> 0) / 4294967296; };
}

// Cubic Hermite through keys [{t, v:[...]}] with Catmull-Rom tangents scaled for uneven spacing.
export function track(keys) {
  return t => {
    if (t <= keys[0].t) return keys[0].v.slice();
    const n = keys.length; if (t >= keys[n - 1].t) return keys[n - 1].v.slice();
    let i = 0; while (keys[i + 1].t < t) i++;
    const k0 = keys[Math.max(0, i - 1)], k1 = keys[i], k2 = keys[i + 1], k3 = keys[Math.min(n - 1, i + 2)];
    const dt = k2.t - k1.t, s = (t - k1.t) / dt, s2 = s * s, s3 = s2 * s;
    const h00 = 2 * s3 - 3 * s2 + 1, h10 = s3 - 2 * s2 + s, h01 = -2 * s3 + 3 * s2, h11 = s3 - s2;
    return k1.v.map((_, j) => {
      const m1 = k1.hold ? 0 : (k2.v[j] - k0.v[j]) / (k2.t - k0.t || 1) * dt;
      const m2 = k2.hold ? 0 : (k3.v[j] - k1.v[j]) / (k3.t - k1.t || 1) * dt;
      return h00 * k1.v[j] + h10 * m1 + h01 * k2.v[j] + h11 * m2;
    });
  };
}

// Text rendered to a canvas texture. lines: [{text, font, color, size, tracking, dy}]
export function textCanvas(lines, opt = {}) {
  const pad = opt.pad || 8, scale = opt.scale || 1;
  const c = document.createElement('canvas'), x = c.getContext('2d');
  let w = 0, h = pad;
  for (const l of lines) {
    x.font = l.font; x.letterSpacing = (l.tracking || 0) + 'px';
    l.w = Math.ceil(x.measureText(l.text).width) + (l.extraW || 0); l.y = h + (l.dy || 0); h = l.y + l.size * 1.25; w = Math.max(w, l.w);
  }
  c.width = Math.ceil((w + pad * 2) * scale); c.height = Math.ceil((h + pad) * scale);
  x.scale(scale, scale);
  for (const l of lines) {
    x.font = l.font; x.letterSpacing = (l.tracking || 0) + 'px'; x.textBaseline = 'top'; x.fillStyle = l.color;
    const lx = opt.align === 'center' ? pad + (w - l.w) / 2 : pad;
    if (l.draw) l.draw(x, lx, l.y); else x.fillText(l.text, lx, l.y);
  }
  const tex = new THREE.CanvasTexture(c); tex.colorSpace = THREE.SRGBColorSpace; tex.anisotropy = 8;
  tex.userData = { w: c.width / scale, h: c.height / scale };
  return tex;
}
