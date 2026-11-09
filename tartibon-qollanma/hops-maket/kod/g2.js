/* ---- 2-ekran: natija paneli ---- */
function badge(c,x,y,w,h,f,t,tc,fs){
  rr(c,x,y,w,h,h/2);c.fillStyle=f;c.fill();
  c.textAlign='center';c.textBaseline='middle';c.fillStyle=tc;
  c.font='900 '+(fs||32)+'px Inter,sans-serif';c.fillText(t,x+w/2,y+h/2+1);
}
function row(c,x,y,w,k,v,vc){
  rr(c,x,y,w,86,20);c.fillStyle='rgba(255,255,255,.07)';c.fill();
  c.textBaseline='middle';
  c.textAlign='left';c.fillStyle='rgba(255,255,255,.72)';c.font='700 32px Inter,sans-serif';
  c.fillText(k,x+30,y+44);
  c.textAlign='right';c.fillStyle=vc||'#fff';c.font='900 36px Inter,sans-serif';
  c.fillText(v,x+w-30,y+44);
}
/* ---- 3-ekran: telegram post ---- */
function tgcard(c,x,y,w,h){
  rr(c,x,y,w,h,36);c.fillStyle='#1E1B2B';c.fill();
  c.strokeStyle='rgba(255,255,255,.08)';c.lineWidth=3;rr(c,x,y,w,h,36);c.stroke();
}
function prow(c,x,y,w,m,nm,sc,me){
  rr(c,x,y,w,96,24);c.fillStyle=me?'rgba(255,201,40,.16)':'rgba(255,255,255,.055)';c.fill();
  if(me){c.strokeStyle='rgba(255,201,40,.55)';c.lineWidth=3;rr(c,x,y,w,96,24);c.stroke()}
  c.textBaseline='middle';c.textAlign='center';
  c.font='900 40px Inter,sans-serif';c.fillStyle='#fff';c.fillText(m,x+56,y+49);
  c.textAlign='left';c.font='800 36px Inter,sans-serif';c.fillStyle='#fff';
  c.fillText(nm,x+108,y+48);
  c.textAlign='right';c.font='900 40px Inter,sans-serif';c.fillStyle=GOLD;
  c.fillText(sc,x+w-30,y+48);
  c.font='700 24px Inter,sans-serif';c.fillStyle='rgba(255,255,255,.5)';
}
function tgbtn(c,x,y,w,h,t,f,tc){
  rr(c,x,y,w,h,18);c.fillStyle=f;c.fill();
  c.textAlign='center';c.textBaseline='middle';c.fillStyle=tc;
  c.font='800 34px Inter,sans-serif';c.fillText(t,x+w/2,y+h/2+1);
}
