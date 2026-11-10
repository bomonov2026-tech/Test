function chip(c,x,y,w,h,fill){rr(c,x,y,w,h,h/2);c.fillStyle=fill||'rgba(10,20,30,.42)';c.fill();
  c.strokeStyle='rgba(255,255,255,.28)';c.lineWidth=3;rr(c,x,y,w,h,h/2);c.stroke()}
function hud(c,o){
  c.textBaseline='middle';
  /* masofa — chapda */
  chip(c,40,66,330,104);
  c.textAlign='left';c.fillStyle='rgba(255,255,255,.75)';c.font='800 25px Inter,sans-serif';
  c.fillText('MASOFA',72,100);
  c.fillStyle='#fff';c.font='900 56px Inter,sans-serif';c.fillText(o.dist+' m',72,142);
  /* tanga — o'ngda */
  chip(c,W-330,66,290,104);
  c.textAlign='right';
  c.fillStyle=GOLD;c.font='900 50px Inter,sans-serif';c.fillText(o.gold,W-80,104);
  c.beginPath();c.ellipse(W-60,104,14,19,0,0,7);c.fillStyle=GOLD;c.fill();
  c.fillStyle='rgba(255,255,255,.75)';c.font='800 24px Inter,sans-serif';c.fillText(o.place,W-72,146);
  /* turnir taymeri */
  chip(c,W/2-190,190,380,78,'rgba(255,201,40,.92)');
  c.textAlign='center';c.fillStyle='#3A2A00';c.font='900 34px Inter,sans-serif';
  c.fillText('TURNIR  '+o.left,W/2,230);
}
function leaderLine(c,z,name,dist){
  const l=P(-430,z,0), r=P(430,z,0), t=P(0,z,250);
  c.strokeStyle='rgba(24,200,245,.95)';c.lineWidth=Math.max(4,13*l[2]);
  c.setLineDash([26*l[2],18*l[2]]);
  c.beginPath();c.moveTo(l[0],l[1]);c.lineTo(r[0],r[1]);c.stroke();
  c.setLineDash([]);
  const bw=Math.max(300,470*t[2]), bh=Math.max(54,86*t[2]);
  rr(c,t[0]-bw/2,t[1]-bh,bw,bh,bh/2);c.fillStyle='#0E7FA8';c.fill();
  c.strokeStyle='#8CE6FF';c.lineWidth=Math.max(2,4*t[2]);rr(c,t[0]-bw/2,t[1]-bh,bw,bh,bh/2);c.stroke();
  c.fillStyle='#fff';c.textAlign='center';c.font='900 '+Math.max(24,38*t[2])+'px Inter,sans-serif';
  c.fillText('1-O‘RIN  '+name+'  '+dist+' m',t[0],t[1]-bh/2);
}
function swipe(c){
  const y=H-104;
  c.textAlign='center'; c.textBaseline='middle';
  rr(c,W/2-430,y-44,860,88,44); c.fillStyle='rgba(10,20,30,.34)'; c.fill();
  c.fillStyle='rgba(255,255,255,.92)'; c.font='800 30px Inter,sans-serif';
  c.fillText('\u2191 sakrash    \u2193 sirpanish    \u2190 \u2192 yo\u2018lak', W/2, y);
}
