/* ===== Walt (Telegram Wallet) uslubidagi iOS kataklar ===== */
const W=1080,H=2340;
const BG='#EFEFF4', CARD='#FFFFFF', TX='#000000', SUB='#8A8A8E',
      BLUE='#2F80ED', SEP='#E5E5EA', PILL='#E9E9EE';
const M=46, R=34, FONT='Inter, system-ui, sans-serif';

function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);
  c.arcTo(x+w,y+h,x,y+h,r);c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}
function card(c,x,y,w,h,r){c.save();c.shadowColor='rgba(16,24,40,.05)';c.shadowBlur=18;c.shadowOffsetY=3;
  rr(c,x,y,w,h,r||R);c.fillStyle=CARD;c.fill();c.restore()}
function T(c,s,x,y,f,col,al){c.font=f;c.fillStyle=col;c.textAlign=al||'left';c.textBaseline='middle';c.fillText(s,x,y)}
function chev(c,x,y){c.strokeStyle='#C4C4C8';c.lineWidth=7;c.lineCap='round';c.lineJoin='round';
  c.beginPath();c.moveTo(x-9,y-16);c.lineTo(x+9,y);c.lineTo(x-9,y+16);c.stroke()}

/* ---- dumaloq ikonka ---- */
function disc(c,x,y,d,fill){c.beginPath();c.arc(x,y,d/2,0,7);c.fillStyle=fill;c.fill()}

/* ---- belgilar (oq chiziqli) ---- */
const GL={
 megafon:function(c,s){c.beginPath();c.moveTo(-14*s,-4*s);c.lineTo(4*s,-16*s);c.lineTo(4*s,16*s);c.lineTo(-14*s,4*s);c.closePath();c.fill();
   c.fillRect(-21*s,-5*s,8*s,10*s); c.beginPath();c.arc(10*s,0,4*s,0,7);c.fill();
   c.strokeStyle=c.fillStyle;c.lineWidth=3*s;c.beginPath();c.arc(10*s,0,11*s,-0.9,0.9);c.stroke()},
 odamlar:function(c,s){
   /* orqadagi kichik figura */
   c.beginPath();c.arc(11*s,-11*s,6*s,0,7);c.fill();
   c.beginPath();c.moveTo(2*s,10*s);c.quadraticCurveTo(11*s,-2*s,20*s,10*s);c.closePath();c.fill();
   /* oldingi figura uchun bo'shliq */
   c.fillStyle=c.__bg;
   c.beginPath();c.arc(-4*s,-2*s,12*s,0,7);c.fill();
   c.beginPath();c.moveTo(-20*s,18*s);c.quadraticCurveTo(-4*s,-1*s,12*s,18*s);c.closePath();c.fill();
   /* oldingi katta figura */
   c.fillStyle=c.__fg;
   c.beginPath();c.arc(-4*s,-2*s,9*s,0,7);c.fill();
   c.beginPath();c.moveTo(-17*s,17*s);c.quadraticCurveTo(-4*s,2*s,9*s,17*s);c.closePath();c.fill()},
 qalam:function(c,s){c.save();c.rotate(-0.72);
   rr(c,-6*s,-18*s,12*s,8*s,3*s);c.fill();
   c.fillRect(-6*s,-11*s,12*s,22*s);
   c.beginPath();c.moveTo(-6*s,11*s);c.lineTo(6*s,11*s);c.lineTo(0,20*s);c.closePath();c.fill();
   c.fillStyle=c.__bg;c.fillRect(-6*s,-11*s,12*s,3*s);c.fillStyle=c.__fg;c.restore()},
 hamyon:function(c,s){rr(c,-18*s,-13*s,36*s,26*s,7*s);c.fill();
   c.fillStyle=c.__bg;c.beginPath();c.arc(13*s,2*s,5*s,0,7);c.fill();c.fillStyle=c.__fg},
 grafik:function(c,s){c.strokeStyle=c.fillStyle;c.lineWidth=4.5*s;c.lineCap='round';c.lineJoin='round';
   c.beginPath();c.moveTo(-16*s,10*s);c.lineTo(-5*s,-3*s);c.lineTo(3*s,5*s);c.lineTo(16*s,-11*s);c.stroke();
   c.beginPath();c.moveTo(8*s,-12*s);c.lineTo(17*s,-12*s);c.lineTo(17*s,-3*s);c.closePath();c.fill()},
 mashina:function(c,s){
   c.beginPath();c.moveTo(-13*s,-4*s);c.lineTo(-9*s,-15*s);c.lineTo(9*s,-15*s);c.lineTo(13*s,-4*s);c.closePath();c.fill();
   rr(c,-19*s,-5*s,38*s,15*s,6*s);c.fill();
   c.fillStyle=c.__bg;rr(c,-10*s,-13*s,20*s,8*s,3*s);c.fill();c.fillStyle=c.__fg;
   c.beginPath();c.arc(-11*s,11*s,5*s,0,7);c.fill();c.beginPath();c.arc(11*s,11*s,5*s,0,7);c.fill()},
 kitob:function(c,s){rr(c,-16*s,-17*s,32*s,34*s,5*s);c.fill();
   c.fillStyle=c.__bg;
   c.fillRect(-9*s,-10*s,18*s,4*s);c.fillRect(-9*s,-2*s,18*s,4*s);c.fillRect(-9*s,6*s,11*s,4*s);
   c.fillStyle=c.__fg},
 pul:function(c,s){c.font='900 '+(30*s)+'px '+FONT;c.textAlign='center';c.textBaseline='middle';c.fillText('$',0,0)},
 royxat:function(c,s){for(var i=-1;i<2;i++){c.beginPath();c.arc(-13*s,i*11*s,3*s,0,7);c.fill();
   rr(c,-5*s,i*11*s-2.5*s,20*s,5*s,2.5*s);c.fill()}},
 almash:function(c,s){c.strokeStyle=c.fillStyle;c.lineWidth=4.5*s;c.lineCap='round';c.lineJoin='round';
   c.beginPath();c.moveTo(-14*s,-6*s);c.lineTo(14*s,-6*s);c.stroke();
   c.beginPath();c.moveTo(8*s,-13*s);c.lineTo(15*s,-6*s);c.lineTo(8*s,1*s);c.stroke();
   c.beginPath();c.moveTo(14*s,8*s);c.lineTo(-14*s,8*s);c.stroke();
   c.beginPath();c.moveTo(-8*s,1*s);c.lineTo(-15*s,8*s);c.lineTo(-8*s,15*s);c.stroke()},
 plus:function(c,s){c.strokeStyle=c.fillStyle;c.lineWidth=5*s;c.lineCap='round';
   c.beginPath();c.moveTo(-13*s,0);c.lineTo(13*s,0);c.moveTo(0,-13*s);c.lineTo(0,13*s);c.stroke()},
 samolyot:function(c,s){c.beginPath();c.moveTo(-16*s,-2*s);c.lineTo(17*s,-13*s);c.lineTo(5*s,15*s);
   c.lineTo(1*s,3*s);c.closePath();c.fill()},
 olmos:function(c,s){c.beginPath();c.moveTo(0,-14*s);c.lineTo(15*s,-2*s);c.lineTo(0,16*s);c.lineTo(-15*s,-2*s);c.closePath();c.fill()},
 uy:function(c,s){c.beginPath();c.moveTo(0,-15*s);c.lineTo(16*s,-1*s);c.lineTo(16*s,15*s);c.lineTo(-16*s,15*s);c.lineTo(-16*s,-1*s);c.closePath();c.fill()},
 sozlama:function(c,s){c.lineWidth=4.5*s;c.lineCap='round';c.strokeStyle=c.__fg;
   c.beginPath();c.moveTo(-15*s,-8*s);c.lineTo(15*s,-8*s);c.moveTo(-15*s,8*s);c.lineTo(15*s,8*s);c.stroke();
   [[-3,-8],[6,8]].forEach(function(p){c.beginPath();c.arc(p[0]*s,p[1]*s,6*s,0,7);c.fillStyle=c.__bg;c.fill();
     c.strokeStyle=c.__fg;c.lineWidth=4.5*s;c.stroke()});c.fillStyle=c.__fg},
 nuqta:function(c,s){[-11,0,11].forEach(function(d){c.beginPath();c.arc(d*s,0,3.4*s,0,7);c.fill()})},
 x:function(c,s){c.strokeStyle=c.fillStyle;c.lineWidth=5.5*s;c.lineCap='round';
   c.beginPath();c.moveTo(-11*s,-11*s);c.lineTo(11*s,11*s);c.moveTo(11*s,-11*s);c.lineTo(-11*s,11*s);c.stroke()}
};
function icon(c,name,x,y,s,col,bg){c.save();c.translate(x,y);c.__fg=col;c.__bg=bg||'#FFFFFF';
  c.fillStyle=col;c.strokeStyle=col;GL[name](c,s);c.restore()}
