/* Roblox uslubidagi bloklar — qo'zg'almas burchakli 3D (izometrikka yaqin) */
const W=1080,H=1920;
const TW=168, TH=86, TZ=72;      /* blok eni, chuqurligi, balandligi */
let OX=W/2-40, OY=640;
function iso(x,y,z){ return [OX+(x-y)*TW/2, OY+(x+y)*TH/2 - z*TZ]; }
function sh(hex,f){ const n=parseInt(hex.slice(1),16);
  let r=(n>>16)&255,g=(n>>8)&255,b=n&255;
  r=Math.min(255,r*f|0);g=Math.min(255,g*f|0);b=Math.min(255,b*f|0);
  return 'rgb('+r+','+g+','+b+')'; }
/* bitta kub */
function cube(c,x,y,z,col,h){
  h=h||1;
  const t=iso(x,y,z+h), tr=iso(x+1,y,z+h), tb=iso(x+1,y+1,z+h), tl=iso(x,y+1,z+h);
  const bl=iso(x,y+1,z), bb=iso(x+1,y+1,z), br=iso(x+1,y,z);
  /* ust */
  c.fillStyle=sh(col,1.0);
  c.beginPath();c.moveTo(t[0],t[1]);c.lineTo(tr[0],tr[1]);c.lineTo(tb[0],tb[1]);c.lineTo(tl[0],tl[1]);c.closePath();c.fill();
  /* chap yon */
  c.fillStyle=sh(col,.70);
  c.beginPath();c.moveTo(tl[0],tl[1]);c.lineTo(tb[0],tb[1]);c.lineTo(bb[0],bb[1]);c.lineTo(bl[0],bl[1]);c.closePath();c.fill();
  /* o'ng yon */
  c.fillStyle=sh(col,.86);
  c.beginPath();c.moveTo(tb[0],tb[1]);c.lineTo(tr[0],tr[1]);c.lineTo(br[0],br[1]);c.lineTo(bb[0],bb[1]);c.closePath();c.fill();
  /* qirra */
  c.strokeStyle='rgba(0,0,0,.14)';c.lineWidth=2;
  c.beginPath();c.moveTo(t[0],t[1]);c.lineTo(tr[0],tr[1]);c.lineTo(tb[0],tb[1]);c.lineTo(tl[0],tl[1]);c.closePath();c.stroke();
}
/* lava/suv yuzasi */
function slab(c,x,y,z,col,alpha){
  const t=iso(x,y,z), tr=iso(x+1,y,z), tb=iso(x+1,y+1,z), tl=iso(x,y+1,z);
  c.globalAlpha=alpha||1; c.fillStyle=col;
  c.beginPath();c.moveTo(t[0],t[1]);c.lineTo(tr[0],tr[1]);c.lineTo(tb[0],tb[1]);c.lineTo(tl[0],tl[1]);c.closePath();c.fill();
  c.globalAlpha=1;
}
/* bayroqcha — checkpoint */
function flag(c,x,y,z,col){
  const p=iso(x+0.5,y+0.5,z);
  c.strokeStyle='#5A4632';c.lineWidth=7;c.lineCap='round';
  c.beginPath();c.moveTo(p[0],p[1]);c.lineTo(p[0],p[1]-96);c.stroke();
  c.fillStyle=col;
  c.beginPath();c.moveTo(p[0]+3,p[1]-96);c.lineTo(p[0]+62,p[1]-76);c.lineTo(p[0]+3,p[1]-56);c.closePath();c.fill();
}
/* aylanuvchi to'siq */
function spinner(c,x,y,z,ang){
  const p=iso(x+0.5,y+0.5,z);
  c.save();c.translate(p[0],p[1]-14);
  c.fillStyle='#E23B3B';
  for(let i=0;i<2;i++){ c.save();c.rotate(ang+i*Math.PI/2);
    c.beginPath();c.ellipse(0,0,112,15,0,0,7);c.fill();c.restore(); }
  c.fillStyle='#8A1F1F';c.beginPath();c.arc(0,0,20,0,7);c.fill();
  c.restore();
}
/* Roblox uslubidagi qahramon */
function guy(c,x,y,z,jump){
  const p=iso(x+0.5,y+0.5,z), s=1;
  const yy=p[1]-(jump||0);
  c.save();c.translate(p[0],yy);
  const B=(dx,dy,w,h,col)=>{c.fillStyle=col;c.fillRect(dx,dy,w,h);
    c.fillStyle='rgba(0,0,0,.16)';c.fillRect(dx,dy+h-4,w,4);};
  /* soya */
  c.fillStyle='rgba(0,0,0,.22)';c.beginPath();c.ellipse(0,(jump||0)+4,40,16,0,0,7);c.fill();
  /* oyoqlar */
  B(-26,-44,22,44,'#2F6BD8'); B(4,-44,22,44,'#2F6BD8');
  /* tana */
  B(-30,-96,60,54,'#18B36B');
  /* qo'llar */
  B(-48,-94,17,48,'#F2C27B'); B(31,-94,17,48,'#F2C27B');
  /* bosh */
  B(-27,-150,54,52,'#FFD24A');
  /* yuz */
  c.fillStyle='#23282E';c.fillRect(-15,-134,9,11);c.fillRect(7,-134,9,11);
  c.fillRect(-11,-116,22,6);
  c.restore();
}
