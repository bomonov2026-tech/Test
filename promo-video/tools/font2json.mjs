// Converts an OTF/TTF font into three.js typeface JSON (for TextGeometry).
import opentype from 'opentype.js';
import fs from 'fs';
const [,, inPath, outPath] = process.argv;
const font = opentype.loadSync(inPath);
const upm = font.unitsPerEm;
const chars = [];
for (let c = 32; c <= 126; c++) chars.push(String.fromCharCode(c));
chars.push('‘', '’', 'ʻ', '—', '•', '…', '–', '·');
const glyphs = {};
const fmt = (n) => Math.round(n * 100) / 100;
for (const ch of chars) {
  const g = font.charToGlyph(ch);
  if (!g) continue;
  const p = g.path; // font units, y-up (getPath flips y)
  let o = '';
  for (const cmd of p.commands) {
    switch (cmd.type) {
      case 'M': o += `m ${fmt(cmd.x)} ${fmt(cmd.y)} `; break;
      case 'L': o += `l ${fmt(cmd.x)} ${fmt(cmd.y)} `; break;
      case 'Q': o += `q ${fmt(cmd.x)} ${fmt(cmd.y)} ${fmt(cmd.x1)} ${fmt(cmd.y1)} `; break; // three.js: end point first, then control
      case 'C': o += `b ${fmt(cmd.x)} ${fmt(cmd.y)} ${fmt(cmd.x1)} ${fmt(cmd.y1)} ${fmt(cmd.x2)} ${fmt(cmd.y2)} `; break; // end, cp1, cp2
      case 'Z': break;
    }
  }
  glyphs[ch] = { ha: fmt(g.advanceWidth), o: o.trim() };
}
const json = {
  glyphs,
  familyName: font.names.fontFamily?.en || 'Inter',
  ascender: font.ascender, descender: font.descender,
  underlinePosition: -100, underlineThickness: 50,
  boundingBox: { yMin: font.descender, xMin: 0, yMax: font.ascender, xMax: upm },
  resolution: upm, cssFontWeight: 'bold', cssFontStyle: 'normal',
};
fs.writeFileSync(outPath, JSON.stringify(json));
console.log('glyphs:', Object.keys(glyphs).length, 'upm:', upm, '->', outPath);
