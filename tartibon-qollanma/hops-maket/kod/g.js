const W=1080,H=1920, GOLD='#FFC928', CY='#2BC4F5';
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);
  c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
function sky(c){
  const g=c.createLinearGradient(0,0,0,H);
  g.addColorStop(0,'#1B2A6B');g.addColorStop(.30,'#6B3F86');g.addColorStop(.52,'#D1596A');
  g.addColorStop(.68,'#F0894A');g.addColorStop(1,'#FFC06A');
  c.fillStyle=g;c.fillRect(0,0,W,H);
  /* quyosh */
  const sy=H*0.52;
  const sg=c.createRadialGradient(W*0.70,sy,20,W*0.70,sy,300);
  sg.addColorStop(0,'rgba(255,235,170,.95)');sg.addColorStop(1,'rgba(255,170,90,0)');
  c.fillStyle=sg;c.beginPath();c.arc(W*0.70,sy,300,0,7);c.fill();
  c.fillStyle='rgba(255,243,200,.9)';c.beginPath();c.arc(W*0.70,sy,86,0,7);c.fill();
  /* yulduzlar */
  c.fillStyle='rgba(255,255,255,.7)';
  [[120,140],[330,90],[520,200],[760,120],[940,230],[200,300],[660,60]].forEach(s=>{
    c.beginPath();c.arc(s[0],s[1],3.2,0,7);c.fill()});
}
/* orqa fon binolari */
function far(c){
  const base=H*0.70; let x=-30,i=0;
  while(x<W+60){ const w=90+((i*61)%120), h=180+((i*89)%320);
    c.fillStyle='rgba(42,26,68,'+(0.45+((i%3)*0.08))+')'; c.fillRect(x,base-h,w,h+200);
    c.fillStyle='rgba(255,210,140,.16)';
    for(let k=0;k<Math.floor(h/60);k++)for(let j=0;j<Math.floor(w/36);j++)
      if(((i+k*2+j)*5)%4<2)c.fillRect(x+12+j*36,base-h+18+k*60,14,20);
    x+=w+10;i++; }
}
/* tom — oldingi plan */
function roof(c,x,w,top){
  const b=H+40;
  c.fillStyle='#241A33'; c.fillRect(x,top,w,b-top);
  c.fillStyle='#3A2B52'; c.fillRect(x,top,w,18);           /* tom yuzasi */
  c.fillStyle='rgba(255,170,110,.22)'; c.fillRect(x,top,w,5);
  /* derazalar */
  c.fillStyle='rgba(255,205,130,.30)';
  for(let k=0;k<Math.floor((b-top)/92);k++)
    for(let j=0;j<Math.floor(w/70);j++)
      if(((k*3+j)*7)%5<3) c.fillRect(x+22+j*70,top+44+k*92,30,38);
  /* chet chizig'i */
  c.strokeStyle='rgba(0,0,0,.25)';c.lineWidth=3;c.strokeRect(x,top,w,b-top);
}
/* stickman — yondan, shlyapa bilan */
function man(c,x,y,s,pose,hat){
  c.save();c.translate(x,y);c.scale(s,s);
  c.strokeStyle='#15131C';c.fillStyle='#15131C';c.lineWidth=11;c.lineCap='round';c.lineJoin='round';
  let la=0.5,lb=-0.6,aa=-1.3,ab=0.7, body=0;
  if(pose==='charge'){ la=0.9;lb=0.75;aa=1.0;ab=1.15; body=14; }   /* cho'kkalab kuch to'playdi */
  if(pose==='fly'){ la=-0.9;lb=0.45;aa=-2.0;ab=-1.5; }
  c.translate(0,body);
  [la,lb].forEach((k,i)=>{c.beginPath();c.moveTo(0,-52);
    const kx=15*Math.sin(k), ky=-52+28*Math.cos(k);
    c.lineTo(kx,ky);c.lineTo(kx+(i?-9:11),0);c.stroke()});
  c.beginPath();c.moveTo(0,-52);c.lineTo(0,-102);c.stroke();
  [aa,ab].forEach(k=>{c.beginPath();c.moveTo(0,-98);
    const ex=20*Math.sin(k), ey=-98+22*Math.cos(k);
    c.lineTo(ex,ey);c.lineTo(ex+14*Math.sin(k*1.2),ey+16*Math.cos(k*1.2));c.stroke()});
  c.beginPath();c.arc(0,-122,20,0,7);c.fill();
  if(hat){ c.fillStyle=hat; c.beginPath();c.arc(0,-130,20,Math.PI,0);c.fill();
    c.fillRect(-30,-132,60,8); }
  c.restore();
}
/* kuch ko'rsatkichi */
function power(c,x,y,p){
  const w=26,h=190;
  rr(c,x-w/2,y-h,w,h,w/2); c.fillStyle='rgba(0,0,0,.42)';c.fill();
  const fh=h*p;
  rr(c,x-w/2+4,y-fh+2,w-8,Math.max(0,fh-6),(w-8)/2);
  const g=c.createLinearGradient(0,y-h,0,y); g.addColorStop(0,'#FF4D6D');g.addColorStop(.5,GOLD);g.addColorStop(1,'#39E08B');
  c.fillStyle=g;c.fill();
  c.strokeStyle='rgba(255,255,255,.55)';c.lineWidth=3;rr(c,x-w/2,y-h,w,h,w/2);c.stroke();
}
