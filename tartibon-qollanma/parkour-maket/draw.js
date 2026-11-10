/* Perspektivali 3D parkour — maket chizish */
const W=1080,H=1920;
const GOLD='#F5C542', CYAN='#36D1FF';
function hzY(){return H*0.40}
function P(ctx,x,z,y){                     /* 3D -> ekran. z: masofa, x: yon, y: balandlik */
  const f=620, hz=H*0.40, cam=520;
  const s=f/(z+f);
  return [W/2 + x*s*1.0, hz + (cam - y)*s*1.0, s];
}
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);
  c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}

function sky(c){
  const g=c.createLinearGradient(0,0,0,H*0.55);
  g.addColorStop(0,'#14153A');g.addColorStop(.45,'#4A2A63');g.addColorStop(.8,'#B4506A');g.addColorStop(1,'#F08A5D');
  c.fillStyle=g;c.fillRect(0,0,W,hzY()+4);
  const sy=H*0.40;
  const sg=c.createRadialGradient(W*0.5,sy,20,W*0.5,sy,H*0.17);
  sg.addColorStop(0,'rgba(255,226,160,.95)');sg.addColorStop(1,'rgba(255,150,90,0)');
  c.fillStyle=sg;c.beginPath();c.arc(W*0.5,sy,H*0.17,0,7);c.fill();
}
function skyline(c){                       /* orqa fon binolari */
  c.fillStyle='rgba(22,16,40,.78)';
  let x=-40;let i=0;
  while(x<W+60){const w=60+((i*53)%90),h=120+((i*97)%260);
    c.fillRect(x,hzY()-h,w,h+8);
    c.fillStyle='rgba(255,200,120,.10)';
    for(let k=0;k<Math.floor(h/46);k++)for(let j=0;j<Math.floor(w/34);j++)
      if(((i+k+j)*7)%5<2)c.fillRect(x+10+j*34,hzY()-h+12+k*46,12,16);
    c.fillStyle='rgba(22,16,40,.78)';x+=w+16;i++}
}
function road(c,scroll){
  const lanes=[-290,0,290], EDGE=520, FAR=5000;
  /* yon yer (yo'ldan tashqari) */
  c.fillStyle='#17121F'; c.fillRect(0,hzY(),W,H-hzY());
  /* yo'l yuzasi — to'g'ri perspektiva */
  const fl=P(c,-EDGE,FAR,0), fr=P(c,EDGE,FAR,0), nl=P(c,-EDGE,-430,0), nr=P(c,EDGE,-430,0);
  c.fillStyle='#3A2F4D';
  c.beginPath();c.moveTo(fl[0],fl[1]);c.lineTo(fr[0],fr[1]);c.lineTo(nr[0],nr[1]);c.lineTo(nl[0],nl[1]);c.closePath();c.fill();
  /* chetlari */
  c.strokeStyle='rgba(255,185,115,.38)';c.lineWidth=4;
  c.beginPath();c.moveTo(fl[0],fl[1]);c.lineTo(nl[0],nl[1]);c.stroke();
  c.beginPath();c.moveTo(fr[0],fr[1]);c.lineTo(nr[0],nr[1]);c.stroke();
  /* yo'lak chiziqlari */
  c.strokeStyle='rgba(255,255,255,.13)';c.lineWidth=3;
  [-145,145].forEach(x=>{const a=P(c,x,FAR,0),b=P(c,x,-430,0);
    c.beginPath();c.moveTo(a[0],a[1]);c.lineTo(b[0],b[1]);c.stroke()});
  /* ko'ndalang chiziqlar */
  for(let z=0;z<2600;z+=90){const zz=z-(scroll%90);if(zz<-80)continue;
    const l=P(c,-EDGE,zz,0),r=P(c,EDGE,zz,0);
    c.strokeStyle='rgba(255,255,255,'+(0.22*(1-Math.max(0,zz)/2600))+')';
    c.lineWidth=Math.max(1.5,7*l[2]);
    c.beginPath();c.moveTo(l[0],l[1]);c.lineTo(r[0],r[1]);c.stroke()}
  return lanes;
}
function box(c,x,z,w,h,col){
  const b=P(c,x-w/2,z,0), b2=P(c,x+w/2,z,0), t=P(c,x-w/2,z,h);
  const sw=(b2[0]-b[0]), sh=(b[1]-t[1]);
  c.fillStyle=col; c.fillRect(b[0],b[1]-sh,sw,sh);
  c.fillStyle='rgba(255,255,255,.14)'; c.fillRect(b[0],b[1]-sh,sw,Math.max(2,sh*0.13));
  c.strokeStyle='rgba(0,0,0,.4)';c.lineWidth=2;c.strokeRect(b[0],b[1]-sh,sw,sh);
}
function coin(c,x,z,y){
  const p=P(c,x,z,y), r=26*p[2];
  c.fillStyle=GOLD; c.beginPath();c.ellipse(p[0],p[1],r*0.62,r,0,0,7);c.fill();
  c.fillStyle='rgba(255,255,255,.45)';c.beginPath();c.ellipse(p[0]-r*0.18,p[1]-r*0.2,r*0.2,r*0.4,0,0,7);c.fill();
}
/* orqadan ko'rinadigan stickman */
function runner(c,lane,phase,air){
  const p=P(c,lane,10,0), s=p[2]*1.65;
  const x=p[0], y=p[1]-(air?70*p[2]:0);
  c.save();c.translate(x,y);c.scale(s,s);
  c.strokeStyle='#0E0E14';c.fillStyle='#0E0E14';c.lineWidth=11;c.lineCap='round';c.lineJoin='round';
  const a=Math.sin(phase)*0.9,b=-a;
  /* oyoqlar */
  [a,b].forEach((k,i)=>{c.beginPath();c.moveTo(0,-54);
    const kx=14*Math.sin(k),ky=-54+30*Math.cos(k);
    c.lineTo(kx,ky);c.lineTo(kx+(air?10:4)*(i?-1:1),0);c.stroke()});
  /* tana */
  c.beginPath();c.moveTo(0,-54);c.lineTo(0,-104);c.stroke();
  /* qo'llar */
  [b,a].forEach((k,i)=>{c.beginPath();c.moveTo(0,-100);
    const ex=(i?1:-1)*18+12*Math.sin(k), ey=-100+24*Math.cos(k);
    c.lineTo(ex,ey);c.lineTo(ex+(i?10:-10),ey+20);c.stroke()});
  /* bosh */
  c.beginPath();c.arc(0,-122,19,0,7);c.fill();
  /* sharf */
  c.strokeStyle=CYAN;c.lineWidth=9;
  c.beginPath();c.moveTo(0,-112);
  c.quadraticCurveTo(-34,-104+Math.sin(phase)*8,-62,-118+Math.cos(phase*0.7)*10);c.stroke();
  c.restore();
  /* soya */
  const sp=P(c,lane,10,0);
  c.fillStyle='rgba(0,0,0,.33)';c.beginPath();c.ellipse(sp[0],sp[1]+4,44*sp[2],12*sp[2],0,0,7);c.fill();
}
