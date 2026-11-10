/* Subway Surfers uslubidagi yorqin perspektivali 3D */
const W=1080,H=1920, GOLD='#FFC928', CYAN='#18C8F5';
function hzY(){return H*0.355}
function P(x,z,y){ const f=1500, cam=720, s=f/(z+f); return [W/2+x*s, hzY()+(cam-y)*s, s]; }
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);
  c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}

function sky(c){
  const g=c.createLinearGradient(0,0,0,hzY()+10);
  g.addColorStop(0,'#1FA7E8'); g.addColorStop(.55,'#63D0F5'); g.addColorStop(1,'#BEEEFF');
  c.fillStyle=g; c.fillRect(0,0,W,hzY()+10);
  /* bulutlar */
  c.fillStyle='rgba(255,255,255,.92)';
  [[170,180,1],[560,120,.72],[880,210,.9],[350,290,.55]].forEach(b=>{
    const s=b[2]; c.beginPath();
    c.arc(b[0],b[1],60*s,0,7); c.arc(b[0]+62*s,b[1]+16*s,44*s,0,7);
    c.arc(b[0]-58*s,b[1]+20*s,38*s,0,7); c.arc(b[0]+16*s,b[1]-28*s,40*s,0,7); c.fill();
  });
}
function city(c){
  /* uzoqdagi binolar — yorqin */
  const base=hzY()+8; let x=-50,i=0;
  const col=['#8E7CC3','#A48BD4','#7C6BB0','#B39BE0'];
  while(x<W+80){ const w=74+((i*47)%96), h=120+((i*83)%230);
    c.fillStyle=col[i%4]; c.fillRect(x,base-h,w,h+6);
    c.fillStyle='rgba(255,255,255,.30)';
    for(let k=0;k<Math.floor(h/52);k++) for(let j=0;j<Math.floor(w/30);j++)
      if(((i+k*3+j)*7)%4<2) c.fillRect(x+9+j*30,base-h+14+k*52,13,18);
    x+=w+14; i++; }
  /* daraxtlar */
  c.fillStyle='#3FA85C';
  for(let k=0;k<9;k++){ const px=40+k*126; c.beginPath(); c.arc(px,base-14,26,0,7); c.fill(); }
}
function track(c,scroll){
  const lanes=[-235,0,235], EDGE=385, FAR=9000;
  /* yon yer */
  c.fillStyle='#C9A86A'; c.fillRect(0,hzY()+6,W,H-hzY());
  /* ballast */
  const fl=P(-EDGE,FAR,0), fr=P(EDGE,FAR,0), nl=P(-EDGE,-240,0), nr=P(EDGE,-240,0);
  c.fillStyle='#9B9188';
  c.beginPath();c.moveTo(fl[0],fl[1]);c.lineTo(fr[0],fr[1]);c.lineTo(nr[0],nr[1]);c.lineTo(nl[0],nl[1]);c.closePath();c.fill();
  /* shpallar */
  for(let z=0;z<5200;z+=120){ const zz=z-(scroll%120); if(zz<-220) continue;
    const a=P(-EDGE*0.94,zz,0), b=P(EDGE*0.94,zz,0), t=Math.max(2,34*a[2]);
    c.fillStyle='#6E5B46'; c.fillRect(a[0],a[1]-t/2,b[0]-a[0],t); }
  /* relslar — har yo'lak chetida */
  c.lineCap='butt';
  lanes.forEach(L=>{ [-80,80].forEach(o=>{
    const a=P(L+o,FAR,0), b=P(L+o,-240,0);
    c.strokeStyle='#E8E4DD'; c.lineWidth=Math.max(2,11*b[2]);
    c.beginPath();c.moveTo(a[0],a[1]);c.lineTo(b[0],b[1]);c.stroke(); }); });
  /* chekka devorlar */
  [-1,1].forEach(s=>{
    const a=P(s*EDGE,FAR,0), b=P(s*EDGE,-240,0), at=P(s*EDGE,FAR,190), bt=P(s*EDGE,-240,190);
    c.fillStyle=s<0?'#E0B36A':'#D9A95F';
    c.beginPath();c.moveTo(a[0],a[1]);c.lineTo(at[0],at[1]);c.lineTo(bt[0],bt[1]);c.lineTo(b[0],b[1]);c.closePath();c.fill(); });
  return lanes;
}
/* poyezd — uzun to'siq */
function train(c,x,z,len,col){
  const h=300;
  const b1=P(x-120,z,0), b2=P(x+120,z,0), t1=P(x-120,z,h), f1=P(x-120,z+len,0), ft=P(x-120,z+len,h);
  /* yon tomoni */
  c.fillStyle=col[1];
  c.beginPath();c.moveTo(b1[0],b1[1]);c.lineTo(f1[0],f1[1]);c.lineTo(ft[0],ft[1]);c.lineTo(t1[0],t1[1]);c.closePath();c.fill();
  /* old yuzasi */
  const tw=b2[0]-b1[0], th=b1[1]-t1[1];
  c.fillStyle=col[0]; c.fillRect(b1[0],b1[1]-th,tw,th);
  c.fillStyle='rgba(255,255,255,.22)'; c.fillRect(b1[0],b1[1]-th,tw,th*0.16);
  /* derazalar */
  c.fillStyle='#BFE9FF';
  rr(c,b1[0]+tw*0.14,b1[1]-th*0.74,tw*0.72,th*0.34,th*0.07); c.fill();
  c.strokeStyle='rgba(0,0,0,.25)';c.lineWidth=Math.max(1.5,3*b1[2]);
  rr(c,b1[0],b1[1]-th,tw,th,6*b1[2]); c.stroke();
}
function barrier(c,x,z,low){
  const h=low?110:230, b1=P(x-115,z,0), b2=P(x+115,z,0), t=P(x-115,z,h);
  const w=b2[0]-b1[0], hh=b1[1]-t[1];
  c.fillStyle=low?'#F2663F':'#F2A93F'; c.fillRect(b1[0],b1[1]-hh,w,hh);
  c.fillStyle='rgba(255,255,255,.85)';
  for(let i=0;i<4;i++) c.fillRect(b1[0]+w*(i*0.25+0.03),b1[1]-hh,w*0.11,hh);
  c.strokeStyle='rgba(0,0,0,.28)';c.lineWidth=Math.max(1.5,3*b1[2]);c.strokeRect(b1[0],b1[1]-hh,w,hh);
}
function coinArc(c,x,z0,n){
  for(let i=0;i<n;i++){
    const z=z0+i*110, y=100+Math.sin(i/(n-1)*Math.PI)*150;
    const p=P(x,z,y), r=34*p[2];
    if(r<1.2) continue;
    c.fillStyle=GOLD; c.beginPath(); c.ellipse(p[0],p[1],r*0.66,r,0,0,7); c.fill();
    c.strokeStyle='#C98A06'; c.lineWidth=Math.max(1,3*p[2]); c.stroke();
    c.fillStyle='rgba(255,255,255,.6)'; c.beginPath();c.ellipse(p[0]-r*0.2,p[1]-r*0.25,r*0.18,r*0.38,0,0,7);c.fill();
  }
}
function runner(c,lane,phase,air){
  const p=P(lane,20,0), s=p[2]*2.05, x=p[0], y=p[1]-(air?150*p[2]:0);
  c.fillStyle='rgba(0,0,0,.25)';c.beginPath();c.ellipse(p[0],p[1]+8,74*p[2],20*p[2],0,0,7);c.fill();
  c.save();c.translate(x,y);c.scale(s,s);
  c.strokeStyle='#1A1A22';c.fillStyle='#1A1A22';c.lineWidth=12;c.lineCap='round';c.lineJoin='round';
  const a=air?-1.0:Math.sin(phase)*0.95, b=air?0.6:-a;
  [a,b].forEach((k,i)=>{c.beginPath();c.moveTo(0,-56);
    const kx=15*Math.sin(k),ky=-56+32*Math.cos(k);
    c.lineTo(kx,ky);c.lineTo(kx+(air?12:5)*(i?-1:1),0);c.stroke()});
  c.beginPath();c.moveTo(0,-56);c.lineTo(0,-110);c.stroke();
  [b,a].forEach((k,i)=>{c.beginPath();c.moveTo(0,-106);
    const ex=(i?1:-1)*20+13*Math.sin(k), ey=-106+(air?-18:26)*Math.cos(k);
    c.lineTo(ex,ey);c.lineTo(ex+(i?12:-12),ey+(air?-14:22));c.stroke()});
  c.beginPath();c.arc(0,-130,21,0,7);c.fill();
  /* sariq shapka — rangli detal */
  c.fillStyle='#FFC928'; c.beginPath(); c.arc(0,-136,21,Math.PI,0); c.fill();
  c.fillRect(-21,-138,42,7);
  /* moviy sharf */
  c.strokeStyle=CYAN;c.lineWidth=10;
  c.beginPath();c.moveTo(0,-118);
  c.quadraticCurveTo(-36,-108+Math.sin(phase)*9,-68,-124+Math.cos(phase*0.7)*11);c.stroke();
  c.restore();
}
