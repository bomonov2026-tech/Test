/* HUD va ekranlar */
function hud(c,o){
  /* yuqori panel */
  c.textBaseline='middle';
  rr(c,40,70,300,96,28);c.fillStyle='rgba(12,10,20,.62)';c.fill();
  c.fillStyle='rgba(255,255,255,.55)';c.font='700 26px Inter,sans-serif';c.textAlign='left';
  c.fillText('MASOFA',70,104);
  c.fillStyle='#fff';c.font='900 52px Inter,sans-serif';c.fillText(o.dist+' m',70,146);

  rr(c,W-340,70,300,96,28);c.fillStyle='rgba(12,10,20,.62)';c.fill();
  c.fillStyle=GOLD;c.font='900 46px Inter,sans-serif';c.textAlign='right';
  c.fillText('🪙 '+o.gold,W-70,120);
  c.fillStyle='rgba(255,255,255,.55)';c.font='700 24px Inter,sans-serif';
  c.fillText(o.place,W-70,152);

  /* turnir taymeri */
  rr(c,W/2-170,186,340,74,24);c.fillStyle='rgba(245,197,66,.16)';c.fill();
  c.strokeStyle='rgba(245,197,66,.55)';c.lineWidth=2;rr(c,W/2-170,186,340,74,24);c.stroke();
  c.textAlign='center';c.fillStyle=GOLD;c.font='800 32px Inter,sans-serif';
  c.fillText('⏳ TURNIR · '+o.left,W/2,224);
}
/* yetakchi chizig'i */
function leaderLine(c,z,name,dist){
  const l=P(c,-380,z,0), r=P(c,380,z,0), t=P(c,0,z,230);
  const g=c.createLinearGradient(l[0],0,r[0],0);
  g.addColorStop(0,'rgba(54,209,255,0)');g.addColorStop(.5,'rgba(54,209,255,.85)');g.addColorStop(1,'rgba(54,209,255,0)');
  c.strokeStyle=g;c.lineWidth=Math.max(3,10*l[2]);
  c.beginPath();c.moveTo(l[0],l[1]);c.lineTo(r[0],r[1]);c.stroke();
  const bw=360*t[2]*1.6, bh=66*t[2]*1.6;
  rr(c,t[0]-bw/2,t[1]-bh,bw,bh,bh/2);c.fillStyle='rgba(10,30,48,.9)';c.fill();
  c.strokeStyle=CYAN;c.lineWidth=2;rr(c,t[0]-bw/2,t[1]-bh,bw,bh,bh/2);c.stroke();
  c.fillStyle=CYAN;c.textAlign='center';c.font='800 '+Math.round(30*t[2]*1.6)+'px Inter,sans-serif';
  c.fillText('🥇 '+name+' · '+dist+' m',t[0],t[1]-bh/2);
}
/* pastki boshqaruv maslahati */
function controls(c){
  const y=H-250;
  c.textAlign='center';
  [['◀',W*0.16,'CHAP'],['▲',W*0.5,'SAKRASH'],['▶',W*0.84,'O‘NG']].forEach(k=>{
    c.beginPath();c.arc(k[1],y,86,0,7);c.fillStyle='rgba(255,255,255,.08)';c.fill();
    c.strokeStyle='rgba(255,255,255,.18)';c.lineWidth=3;c.stroke();
    c.fillStyle='rgba(255,255,255,.9)';c.font='800 56px Inter,sans-serif';c.fillText(k[0],k[1],y);
    c.fillStyle='rgba(255,255,255,.45)';c.font='700 24px Inter,sans-serif';c.fillText(k[2],k[1],y+120);
  });
  c.fillStyle='rgba(255,255,255,.35)';c.font='600 26px Inter,sans-serif';
  c.fillText('yoki ekranni suring',W/2,H-60);
}
