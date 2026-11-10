/* ===== Yorug' fon ===== */
const W=1080,H=1920,GOLD='#FFB300',CY='#1AA7E8',NAVY='#14243D';
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);
  c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}

function sky(c){
  const g=c.createLinearGradient(0,0,0,H);
  g.addColorStop(0,'#5CC6F5');g.addColorStop(.30,'#9FDDF8');
  g.addColorStop(.58,'#D8F0FB');g.addColorStop(.80,'#FDEFD0');g.addColorStop(1,'#FFE2AE');
  c.fillStyle=g;c.fillRect(0,0,W,H);
  /* quyosh */
  const sx=W*0.76, sy=H*0.30;
  const sg=c.createRadialGradient(sx,sy,30,sx,sy,420);
  sg.addColorStop(0,'rgba(255,252,225,.95)');sg.addColorStop(.45,'rgba(255,240,190,.45)');
  sg.addColorStop(1,'rgba(255,235,170,0)');
  c.fillStyle=sg;c.beginPath();c.arc(sx,sy,420,0,7);c.fill();
  c.fillStyle='rgba(255,255,245,.96)';c.beginPath();c.arc(sx,sy,92,0,7);c.fill();
}
function cloud(c,x,y,s,a){
  c.save();c.translate(x,y);c.scale(s,s);c.fillStyle='rgba(255,255,255,'+(a||0.92)+')';
  [[0,0,58],[52,-16,44],[-54,8,42],[100,6,34],[-100,14,30]].forEach(b=>{
    c.beginPath();c.arc(b[0],b[1],b[2],0,7);c.fill()});
  c.beginPath();rr(c,-120,-4,250,44,22);c.fill();
  c.restore();
}
function clouds(c){
  cloud(c,210,250,1.00,.95); cloud(c,840,430,0.74,.80);
  cloud(c,420,600,0.56,.62); cloud(c,960,180,0.52,.70);
}
/* uzoq binolar — ikki qatlam */
function far(c){
  const base=H*0.70;
  const layer=(tone,alpha,step,hmul,off)=>{
    let x=off,i=0;
    while(x<W+80){
      const w=100+((i*73)%140), h=(170+((i*97)%300))*hmul;
      c.fillStyle='rgba('+tone+','+alpha+')';
      c.fillRect(x,base-h,w,h+260);
      c.fillStyle='rgba(255,255,255,.30)';
      for(let k=0;k<Math.floor(h/66);k++)for(let j=0;j<Math.floor(w/40);j++)
        if(((i+k*2+j)*5)%4<2)c.fillRect(x+14+j*40,base-h+22+k*66,15,22);
      x+=w+step;i++;
    }};
  layer('150,186,214',.55,14,1.00,-40);
  layer('110,154,192',.70,18,0.72,-110);
  /* ko'cha tubi — tomlar orasidagi bo'shliq osmon emas, chuqurlik bo'lib ko'rinsin */
  const hg=c.createLinearGradient(0,H*0.58,0,H);
  hg.addColorStop(0,'rgba(188,212,234,0)');
  hg.addColorStop(.55,'rgba(142,174,206,.55)');
  hg.addColorStop(1,'rgba(96,130,166,.86)');
  c.fillStyle=hg;c.fillRect(0,H*0.58,W,H*0.42);
}
/* oldingi tom */
function roof(c,x,w,top){
  const b=H+40;
  const g=c.createLinearGradient(x,top,x,b);
  g.addColorStop(0,'#F2F6FA');g.addColorStop(.35,'#D9E3ED');g.addColorStop(1,'#B9C8D8');
  c.fillStyle=g;c.fillRect(x,top,w,b-top);
  c.fillStyle='#8FA6BC';c.fillRect(x,top,w,20);
  c.fillStyle='#FFFFFF';c.fillRect(x,top,w,6);
  c.fillStyle='rgba(32,56,88,.16)';c.fillRect(x,top+20,w,10);
  /* derazalar */
  for(let k=0;k<Math.floor((b-top)/96);k++)
    for(let j=0;j<Math.floor(w/74);j++){
      const wx=x+24+j*74, wy=top+52+k*96;
      c.fillStyle=((k*3+j)*7)%5<3?'rgba(90,135,175,.42)':'rgba(255,255,255,.55)';
      rr(c,wx,wy,34,42,7);c.fill();
    }
  c.strokeStyle='rgba(42,70,105,.22)';c.lineWidth=3;c.strokeRect(x+1.5,top+1.5,w-3,b-top);
}
/* kuch ko'rsatkichi */
function power(c,x,y,p){
  const w=28,h=200;
  rr(c,x-w/2,y-h,w,h,w/2);c.fillStyle='rgba(255,255,255,.80)';c.fill();
  c.strokeStyle='rgba(20,36,61,.22)';c.lineWidth=4;rr(c,x-w/2,y-h,w,h,w/2);c.stroke();
  const fh=h*p;
  rr(c,x-w/2+5,y-fh+3,w-10,Math.max(0,fh-8),(w-10)/2);
  const g=c.createLinearGradient(0,y-h,0,y);
  g.addColorStop(0,'#FF3B5C');g.addColorStop(.5,GOLD);g.addColorStop(1,'#19C37D');
  c.fillStyle=g;c.fill();
}
