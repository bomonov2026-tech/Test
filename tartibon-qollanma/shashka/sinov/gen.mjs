import {createSimulation,stepSimulation,MAX_TICKS} from './sim.js';
import {writeFileSync} from 'node:fs';
function bits(v){const b=new DataView(new ArrayBuffer(8));b.setFloat64(0,v);let s='';for(let i=0;i<8;i++)s+=b.getUint8(i).toString(16).padStart(2,'0');return s}
let seedR=777001;
function rnd(){seedR=(Math.imul(seedR,1664525)+1013904223)>>>0;return seedR/4294967296}
const cases=[];
for(let c=0;c<500;c++){
  const seed=Math.floor(rnd()*4294967295)>>>0;
  const s=createSimulation(seed);
  const inputs=[{tick:0,throttle:true}];
  let th=true,br=false;const inp0={tick:0,throttle:true};
  for(let t=0;t<9000;t++){ if(t===0){stepSimulation(s,0,inputs[0]);continue}
    if(s.crashed||s.finished)break;
    const inp={};let any=false;
    if(rnd()<0.035){const d=rnd()<0.5?-1:1;inp.direction=d;any=true}
    if(rnd()<0.03){th=!th;inp.throttle=th;any=true}
    if(rnd()<0.03){br=!br;inp.brake=br;any=true}
    if(any&&inputs.length<15000){inp.tick=t;inputs.push(inp)}
    const use=any&&inputs.length&&inputs[inputs.length-1].tick===t?inputs[inputs.length-1]:undefined;
    stepSimulation(s,use?.direction??0,use);
  }
  if(!s.crashed&&!s.finished)continue;      // tugamagan poygalarni tashlaymiz
  cases.push({seed,ticks:s.ticks,inputs,exp:{
    d:Math.floor(s.distance),near:s.nearMisses,crashed:s.crashed,ticks:s.ticks,
    db:bits(s.distance),xb:bits(s.x),sb:bits(s.speed)}});
}
writeFileSync('cases.json',JSON.stringify(cases));
console.log('holatlar:',cases.length,'o\'rtacha tick:',Math.round(cases.reduce((a,c)=>a+c.ticks,0)/cases.length));
