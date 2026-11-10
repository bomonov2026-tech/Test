import {readFileSync,writeFileSync} from 'node:fs';
const CDN='https://cdn.jsdelivr.net/npm/three@0.181.0/build/three.module.js';
const CDN2='https://unpkg.com/three@0.181.0/build/three.module.js';

/* Har bir qo'shimcha o'z blokida: THREE'dan olingan nomlar to'qnashmasin. */
function addon(path,names){
  let s=readFileSync(path,'utf8');
  s=s.replace(/^import\s*\{([\s\S]*?)\}\s*from\s*'three';/m,(m,n)=>'const {'+n+'} = THREE;');
  s=s.replace(/^export\s*\{[\s\S]*?\};\s*$/m,'');
  if(/^import |^export /m.test(s)) throw new Error('qolgan import/export: '+path);
  const outer=names.map(n=>'let __'+n+';').join(' ');
  const give=names.map(n=>'__'+n+' = '+n+';').join(' ');
  const bind=names.map(n=>'const '+n+' = __'+n+';').join(' ');
  return outer+'\n{\n'+s+'\n'+give+'\n}\n'+bind;
}
function game(path){
  let s=readFileSync(path,'utf8');
  s=s.replace(/^import[\s\S]*?from\s*'[^']*';\s*$/gm,'');   // hamma importlar
  s=s.replace(/^export\s+(const|function|class|let|var)\b/gm,'$1');
  s=s.replace(/^export\s*\{[\s\S]*?\};\s*$/gm,'');
  if(/^import |^export /m.test(s)) throw new Error('qolgan import/export: '+path);
  return s;
}
const parts=[
 '/* three.js qo\'shimchalari */',
 addon('vendor/BufferGeometryUtils.js',['mergeGeometries']),
 addon('vendor/RoomEnvironment.js',['RoomEnvironment']),
 addon('vendor/RoundedBoxGeometry.js',['RoundedBoxGeometry']),
 '/* o\'yin yadrosi */',
 game('sim.js'),
 game('veh.js'),
 game('scene.js')
];
const body=parts.join('\n');
// nomlar to'qnashuvi tekshiruvi
const names={},dup=[];
for(const m of body.matchAll(/^(?:const|let|var|function|class)\s+([A-Za-z_$][\w$]*)/gm)){
  if(names[m[1]])dup.push(m[1]); names[m[1]]=1;
}
if(dup.length) console.log('DIQQAT, takroriy nomlar:',[...new Set(dup)]);

const out=`/* TartibOn — SHASHKA 3D poyga moduli. Manba: shashka-traffic-source (React'siz).
   three.js CDN'dan yuklanadi, qolgani shu fayl ichida. */
let LOADED=null;
export function shLoad(){ if(LOADED) return LOADED; LOADED=(async()=>{
let THREE;
try{ THREE=await import('${CDN}'); }
catch(e){ THREE=await import('${CDN2}'); }
${body}
return { RaceScene, VEHICLE_MODELS, SIMULATION_VERSION, MAX_SPEED, MAX_TICKS, LANES };
})(); return LOADED; }
`;
writeFileSync('sh-game.js',out);
console.log('sh-game.js',out.length,'bayt');
