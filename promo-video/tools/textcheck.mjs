import * as THREE from 'three';
import { FontLoader } from 'three/addons/loaders/FontLoader.js';
import { TextGeometry } from 'three/addons/geometries/TextGeometry.js';
import fs from 'fs';
const font = new FontLoader().parse(JSON.parse(fs.readFileSync('public/fonts/inter-black.json', 'utf8')));
for (const s of ['T', 'O', 'SHASHLIK', 'PO‘LATHON']) {
  const g = new TextGeometry(s, { font, size: 1, height: 0.22, curveSegments: 6, bevelEnabled: true, bevelThickness: 0.025, bevelSize: 0.02, bevelSegments: 3 });
  g.computeBoundingBox();
  const bb = g.boundingBox;
  const pos = g.attributes.position.array;
  let maxAbs = 0; for (const v of pos) maxAbs = Math.max(maxAbs, Math.abs(v));
  console.log(s, 'bbox x', bb.min.x.toFixed(3), bb.max.x.toFixed(3), 'y', bb.min.y.toFixed(3), bb.max.y.toFixed(3), 'z', bb.min.z.toFixed(3), bb.max.z.toFixed(3), 'maxAbs', maxAbs.toFixed(2), 'tris', pos.length / 9);
}
