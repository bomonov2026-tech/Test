/* ===== TartibOn ninja — stickman qahramon ===== */
const INK='#0B0B11', INK2='#262630', RED='#E01E2D', RED2='#B3121F';

function limb(c,a,b,wa,wb,col){
  const dx=b[0]-a[0],dy=b[1]-a[1],d=Math.hypot(dx,dy)||1,nx=-dy/d,ny=dx/d;
  c.fillStyle=col;
  c.beginPath();
  c.moveTo(a[0]+nx*wa/2,a[1]+ny*wa/2);
  c.lineTo(b[0]+nx*wb/2,b[1]+ny*wb/2);
  c.lineTo(b[0]-nx*wb/2,b[1]-ny*wb/2);
  c.lineTo(a[0]-nx*wa/2,a[1]-ny*wa/2);
  c.closePath();c.fill();
  c.beginPath();c.arc(a[0],a[1],wa/2,0,7);c.fill();
  c.beginPath();c.arc(b[0],b[1],wb/2,0,7);c.fill();
}
function ribbon(c,pts,w0,w1,col){
  const n=pts.length,L=[],R=[];
  for(let i=0;i<n;i++){
    const p=pts[i],a=pts[Math.max(0,i-1)],b=pts[Math.min(n-1,i+1)];
    let dx=b[0]-a[0],dy=b[1]-a[1];const d=Math.hypot(dx,dy)||1;dx/=d;dy/=d;
    const w=(w0+(w1-w0)*(i/(n-1)))/2;
    L.push([p[0]-dy*w,p[1]+dx*w]);R.push([p[0]+dy*w,p[1]-dx*w]);
  }
  c.beginPath();c.moveTo(L[0][0],L[0][1]);
  for(let i=1;i<n;i++)c.lineTo(L[i][0],L[i][1]);
  for(let i=n-1;i>=0;i--)c.lineTo(R[i][0],R[i][1]);
  c.closePath();c.fillStyle=col;c.fill();
}
function tailPts(ox,oy,ang,len,amp,ph){
  const p=[],n=16;
  for(let i=0;i<=n;i++){const t=i/n;
    const x=ox+Math.cos(ang)*len*t - Math.sin(ang)*Math.sin(t*3.4+ph)*amp*t;
    const y=oy+Math.sin(ang)*len*t + Math.cos(ang)*Math.sin(t*3.4+ph)*amp*t;
    p.push([x,y]);}
  return p;
}
/* g'azabli oq ko'zlar — o'ngga qaragan */
function eyes(c,hx,hy,r){
  c.save();c.translate(hx,hy);
  const k=r/24;
  const draw=(x,y,rot,sc,fl)=>{c.save();c.translate(x,y);c.rotate(rot);
    c.scale((fl?-sc:sc)*k,sc*k);
    c.beginPath();c.moveTo(-8,-4.6);
    c.quadraticCurveTo(0,-6.4,9.5,0.6);
    c.quadraticCurveTo(1,3.4,-8,3.6);
    c.closePath();c.fillStyle='#fff';c.fill();c.restore()};
  draw(-0.44*r, -0.02*r,  0.30, 0.80, false);
  draw( 0.32*r,  0.06*r, -0.30, 1.00, true);
  c.restore();
}
/* bosh bog'ichi */
function band(c,hx,hy,r,ang){
  c.save();c.translate(hx,hy);c.rotate(ang);
  c.beginPath();c.arc(0,0,r,0,7);c.clip();
  c.fillStyle=RED;c.fillRect(-r-4,-0.66*r,2*r+8,0.34*r);
  c.fillStyle='rgba(255,255,255,.16)';c.fillRect(-r-4,-0.66*r,2*r+8,0.10*r);
  c.restore();
}
/* POZALAR: oyoq uchi 0 nuqtada (fly uchun — havoda) */
const POSE={
 idle:{hip:[0,-96],sh:[2,-140],hd:[6,-174],
   kL:[-12,-50],fL:[-18,0], kR:[12,-48],fR:[18,0],
   eL:[-16,-114],hL:[-24,-88], eR:[18,-114],hR:[26,-90], lean:0.04},
 charge:{hip:[-8,-64],sh:[6,-106],hd:[16,-140],
   kL:[12,-36],fL:[-16,0], kR:[18,-34],fR:[-4,0],
   eL:[-24,-92],hL:[-48,-80], eR:[-18,-96],hR:[-44,-86], lean:0.24},
 fly:{hip:[0,-76],sh:[8,-118],hd:[16,-150],
   kL:[18,-66],fL:[0,-28], kR:[28,-70],fR:[14,-32],
   eL:[26,-128],hL:[44,-146], eR:[36,-126],hR:[58,-142], lean:0.16},
 land:{hip:[-2,-78],sh:[4,-120],hd:[10,-154],
   kL:[14,-42],fL:[-12,0], kR:[20,-40],fR:[4,0],
   eL:[-26,-106],hL:[-48,-94], eR:[28,-108],hR:[50,-98], lean:0.10}
};
/* asosiy chizuvchi */
function ninja(c,x,y,s,pose,ph,shadow){
  const P=POSE[pose]||POSE.idle; ph=ph||0;
  c.save();c.translate(x,y);c.scale(s,s);
  if(shadow!==false){
    c.fillStyle='rgba(18,34,58,.22)';
    c.beginPath();c.ellipse(0,4,46,11,0,0,7);c.fill();
  }
  const R=24;
  /* --- orqadagi oyoq / qo'l --- */
  limb(c,P.hip,P.kL,19,15,INK2); limb(c,P.kL,P.fL,15,11,INK2);
  limb(c,P.sh,P.eL,15,12,INK2);  limb(c,P.eL,P.hL,12,9,INK2);
  /* --- sharf va bog'ich dumlari (tana ortida) --- */
  const bx=P.hd[0]-R*0.88, by=P.hd[1]-R*0.44;
  ribbon(c,tailPts(bx,by,Math.PI*1.07,44,11,ph),12,3,RED2);
  ribbon(c,tailPts(bx,by+8,Math.PI*1.01,35,10,ph+1.7),9,3,RED);
  const sx=P.hd[0]-10, sy=P.hd[1]+R*0.86;
  ribbon(c,tailPts(sx,sy,Math.PI*1.05,56,16,ph+0.8),19,4,RED);
  /* --- tana --- */
  limb(c,P.hip,P.sh,26,24,INK);
  /* --- oldingi oyoq / qo'l --- */
  limb(c,P.hip,P.kR,20,16,INK); limb(c,P.kR,P.fR,16,12,INK);
  limb(c,P.sh,P.eR,16,13,INK);  limb(c,P.eR,P.hR,13,10,INK);
  /* --- sharf yoqasi --- */
  c.save();c.translate(P.hd[0]-3,P.hd[1]+R*0.84);c.rotate(P.lean);
  c.fillStyle=RED2;c.beginPath();c.ellipse(-3,2,17,9,0,0,7);c.fill();
  c.fillStyle=RED;c.beginPath();c.ellipse(0,-1,16,8,0,0,7);c.fill();
  c.restore();
  /* --- bosh --- */
  c.fillStyle=INK;c.beginPath();c.arc(P.hd[0],P.hd[1],R,0,7);c.fill();
  band(c,P.hd[0],P.hd[1],R,P.lean-0.06);
  eyes(c,P.hd[0],P.hd[1],R);
  c.restore();
}
