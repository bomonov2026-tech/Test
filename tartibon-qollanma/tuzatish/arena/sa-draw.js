/* ===== Stickman Arena — chizish (canvas) ===== */
var SADraw = (function(){
var BLUE='#36D1FF', RED='#FF4D6D', GOLD='#F5C542';
function rr(c,x,y,w,h,r){c.beginPath();c.moveTo(x+r,y);c.arcTo(x+w,y,x+w,y+h,r);c.arcTo(x+w,y+h,x,y+h,r);
  c.arcTo(x,y+h,x,y,r);c.arcTo(x,y,x+w,y,r);c.closePath()}

function bg(c,W,H,t){
  var g=c.createLinearGradient(0,0,0,H);
  g.addColorStop(0,'#2B1B46'); g.addColorStop(.42,'#7A3A5B'); g.addColorStop(.72,'#D1663F'); g.addColorStop(1,'#F2A25C');
  c.fillStyle=g; c.fillRect(0,0,W,H);
  /* quyosh */
  var sy=H*0.56, sr=H*0.20;
  var sg=c.createRadialGradient(W*0.5,sy,sr*0.2,W*0.5,sy,sr);
  sg.addColorStop(0,'rgba(255,226,150,.95)'); sg.addColorStop(1,'rgba(255,170,90,0)');
  c.fillStyle=sg; c.beginPath(); c.arc(W*0.5,sy,sr,0,6.2832); c.fill();
  /* tog'lar */
  c.fillStyle='rgba(40,22,52,.55)';
  c.beginPath(); c.moveTo(0,H*0.66);
  for(var i=0;i<=6;i++){ var x=W*i/6; c.lineTo(x, H*(0.66 - (i%2?0.10:0.05) - ((i*37)%11)/260)); }
  c.lineTo(W,H); c.lineTo(0,H); c.fill();
  /* ustunlar va bayroqlar */
  c.fillStyle='rgba(28,16,36,.78)';
  [0.07,0.885].forEach(function(px){
    var x=W*px, w=W*0.045, y=H*0.17, h=H*0.52;
    c.fillRect(x,y,w,h); c.fillRect(x-w*0.22,y,w*1.44,H*0.035);
    c.fillStyle='rgba(200,40,60,.85)'; c.fillRect(x+w*0.2,y+H*0.05,w*0.6,H*0.20);
    c.fillStyle='rgba(28,16,36,.78)';
  });
  /* yer */
  var fy=H*0.80;
  c.fillStyle='#3A2740'; c.fillRect(0,fy,W,H-fy);
  c.strokeStyle='rgba(0,0,0,.30)'; c.lineWidth=1.5;
  for(var r2=0;r2<3;r2++){ var yy=fy+8+r2*((H-fy)/3);
    c.beginPath(); c.moveTo(0,yy); c.lineTo(W,yy); c.stroke();
    for(var k=0;k<10;k++){ var xx=((k*W/9)+(r2%2?W/18:0))%W;
      c.beginPath(); c.moveTo(xx,yy); c.lineTo(xx,yy+((H-fy)/3)); c.stroke(); } }
  c.fillStyle='rgba(255,180,110,.10)'; c.fillRect(0,fy,W,3);
  return fy;
}
/* stickman: x,y — oyoq ostidagi nuqta */
function man(c,x,y,sc,dir,col,anim,ph,wp,hit){
  c.save(); c.translate(x,y); c.scale(dir*sc,sc);
  var ink='#0E0E12';
  c.lineWidth=5.2; c.lineCap='round'; c.lineJoin='round'; c.strokeStyle=ink; c.fillStyle=ink;
  var lean=0, kneeA=0, kneeB=0, armA=0, armB=0, bodyY=0;
  if(anim==='yurish'){ kneeA=Math.sin(ph)*0.85; kneeB=-kneeA; armA=-kneeA*0.8; armB=kneeA*0.8; }
  else if(anim==='sakrash'){ kneeA=-0.9; kneeB=0.5; armA=-1.5; armB=-1.1; }
  else if(anim==='zarba'){ lean=0.16; armA=-1.75; armB=0.5; kneeA=0.5; kneeB=-0.35; }
  else if(anim==='blok'){ lean=-0.12; armA=-0.9; armB=-0.7; kneeA=0.35; kneeB=-0.3; }
  else if(anim==='zarba_yedi'){ lean=-0.3; armA=0.8; armB=1.0; }
  else if(anim==='maglub'){ c.rotate(-1.35); bodyY=-6; }
  else { kneeA=Math.sin(ph*0.35)*0.12; kneeB=-kneeA; armA=kneeA*0.5; armB=-kneeA*0.5; }
  c.rotate(lean); c.translate(0,bodyY);
  var hipY=-46, shY=-74, headY=-88;
  /* oyoqlar */
  [kneeA,kneeB].forEach(function(a,i){
    c.beginPath(); c.moveTo(0,hipY);
    var kx=16*Math.sin(a), ky=hipY+24*Math.cos(a);
    c.lineTo(kx,ky); c.lineTo(kx+(i?-4:6)+10*Math.sin(a*0.6), 0); c.stroke();
  });
  /* tana */
  c.beginPath(); c.moveTo(0,hipY); c.lineTo(0,shY); c.stroke();
  /* qo'llar + qurol */
  c.beginPath(); c.moveTo(0,shY); var e1x=20*Math.sin(armB), e1y=shY+20*Math.cos(armB);
  c.lineTo(e1x,e1y); c.lineTo(e1x+14*Math.sin(armB*0.7), e1y+14*Math.cos(armB*0.7)); c.stroke();
  c.beginPath(); c.moveTo(0,shY); var hx=22*Math.sin(armA), hy=shY+22*Math.cos(armA);
  var fx=hx+16*Math.sin(armA*0.75), fy2=hy+16*Math.cos(armA*0.75);
  c.lineTo(hx,hy); c.lineTo(fx,fy2); c.stroke();
  if(wp && wp!=='musht'){
    c.save(); c.translate(fx,fy2); c.rotate(armA+1.35);
    c.strokeStyle='#D7DEE8'; c.fillStyle='#D7DEE8';
    var L={xanjar:22,qilich:40,nayza:62,bolta:28,bolgha:26}[wp]||30;
    c.lineWidth=wp==='nayza'?3.4:5; c.beginPath(); c.moveTo(0,0); c.lineTo(L,0); c.stroke();
    if(wp==='bolta'){ c.beginPath(); c.moveTo(L-4,-11); c.lineTo(L+9,0); c.lineTo(L-4,11); c.closePath(); c.fill(); }
    if(wp==='bolgha'){ c.fillRect(L-6,-10,15,20); }
    if(wp==='nayza'){ c.beginPath(); c.moveTo(L,-5); c.lineTo(L+13,0); c.lineTo(L,5); c.closePath(); c.fill(); }
    c.restore();
  }
  /* bosh + sharf */
  c.strokeStyle=ink; c.fillStyle=ink;
  c.beginPath(); c.arc(0,headY,12.5,0,6.2832); c.fill();
  c.strokeStyle=col; c.lineWidth=5;
  c.beginPath(); c.moveTo(-6,headY+4);
  c.quadraticCurveTo(-26,headY+10+Math.sin(ph*0.8)*4,-40,headY+2+Math.cos(ph*0.6)*6); c.stroke();
  /* ko'z */
  c.fillStyle='#fff'; c.fillRect(3,headY-4,7,3.2);
  if(hit>0){ c.strokeStyle='rgba(255,90,90,.9)'; c.lineWidth=4;
    c.beginPath(); c.arc(0,headY-6,30+ (6-hit)*4,0,6.2832); c.stroke(); }
  c.restore();
}
function hpbar(c,x,y,w,h,v,max,col,flip){
  rr(c,x,y,w,h,h/2); c.fillStyle='rgba(0,0,0,.55)'; c.fill();
  var p=Math.max(0,Math.min(1,v/max)), bw=Math.max(0,(w-4)*p);
  if(bw>0){ if(flip) rr(c,x+2+(w-4-bw),y+2,bw,h-4,(h-4)/2); else rr(c,x+2,y+2,bw,h-4,(h-4)/2);
    c.fillStyle=col; c.fill(); }
  c.strokeStyle='rgba(255,255,255,.18)'; c.lineWidth=1.4; rr(c,x,y,w,h,h/2); c.stroke();
}
function draw(c,W,H,S,names){
  var fy=bg(c,W,H,S.t);
  var scale=(H*0.78-fy*0+1)/1; 
  var sc=(fy*0.52)/96;                       /* stickman balandligi maydonga moslashadi */
  for(var i=0;i<2;i++){
    var p=S.p[i];
    var px=(p.x/1000)*W, py=fy - (p.y/100)*(fy*0.30);
    man(c,px,py,sc,p.side===0?1:-1,p.side===0?BLUE:RED,p.anim,S.t*0.28,p.wp,p.hitFx);
  }
  /* HUD */
  var pad=W*0.035, bw=W*0.30, bh=Math.max(10,H*0.030);
  c.font='700 '+Math.round(H*0.034)+'px Inter,system-ui,sans-serif'; c.textBaseline='middle';
  c.fillStyle=BLUE; c.textAlign='left';  c.fillText((names&&names[0])||'1-jangchi', pad, H*0.065);
  c.fillStyle=RED;  c.textAlign='right'; c.fillText((names&&names[1])||'2-jangchi', W-pad, H*0.065);
  hpbar(c,pad,H*0.095,bw,bh,S.p[0].hp,S.p[0].hpMax,BLUE,false);
  hpbar(c,W-pad-bw,H*0.095,bw,bh,S.p[1].hp,S.p[1].hpMax,RED,true);
  /* taymer */
  var sec=Math.max(0,Math.ceil(S.left/30));
  c.textAlign='center'; c.fillStyle='#fff';
  c.font='800 '+Math.round(H*0.055)+'px Inter,system-ui,sans-serif';
  c.fillText(('0'+Math.floor(sec/60)).slice(-2)+':'+('0'+(sec%60)).slice(-2), W/2, H*0.085);
  if(S.phase==='suddenDeath'){ c.fillStyle=GOLD; c.font='800 '+Math.round(H*0.034)+'px Inter,sans-serif';
    c.fillText("OLTIN ZARBA", W/2, H*0.145); }
  /* sanoq */
  if(S.phase==='countdown'){
    c.fillStyle='rgba(0,0,0,.45)'; c.fillRect(0,0,W,H);
    var n=Math.ceil(S.cd/30), big=n>0?String(n):'JANG!';
    var k=1 - (S.cd%30)/30;
    c.save(); c.translate(W/2,H*0.48); c.scale(1+0.35*(1-k),1+0.35*(1-k));
    c.fillStyle=GOLD; c.textAlign='center';
    c.font='900 '+Math.round(H*0.26)+'px Inter,system-ui,sans-serif';
    c.fillText(big,0,0); c.restore();
  }
  if(S.phase==='finished'){
    c.fillStyle='rgba(0,0,0,.52)'; c.fillRect(0,0,W,H);
    c.textAlign='center'; c.fillStyle=GOLD;
    c.font='900 '+Math.round(H*0.11)+'px Inter,system-ui,sans-serif';
    c.fillText((names&&names[S.winner])?names[S.winner]+' YUTDI':'G‘OLIB', W/2, H*0.44);
    c.fillStyle='rgba(255,255,255,.8)'; c.font='600 '+Math.round(H*0.038)+'px Inter,sans-serif';
    var r={jon:"Raqib yiqildi", vaqt:"Vaqt tugadi", oltin_zarba:"Oltin zarba"}[S.reason]||'';
    c.fillText(r, W/2, H*0.56);
  }
}
return {draw:draw};
})();
if (typeof module!=='undefined') module.exports=SADraw;
