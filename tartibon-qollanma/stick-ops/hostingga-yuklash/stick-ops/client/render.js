import { MAP } from '../shared/game.js';

const COLORS = {blue:'#29dce5',red:'#ff776c'};
const clamp=(v,a,b)=>Math.max(a,Math.min(b,v));
function random(n){const x=Math.sin(n*127.1+311.7)*43758.5453;return x-Math.floor(x)}
function rounded(ctx,x,y,w,h,r=5){ctx.beginPath();ctx.roundRect(x,y,w,h,r)}

export class ArenaRenderer {
  constructor(canvas,minimap){
    this.canvas=canvas;this.ctx=canvas.getContext('2d');this.mini=minimap;this.mc=minimap.getContext('2d');
    this.width=0;this.height=0;this.view={x:800,y:500,scale:1};this.camera=null;this.previous=new Map();
    this.floor=document.createElement('canvas');this.floor.width=MAP.width;this.floor.height=MAP.height;
    this.paintMap(this.floor.getContext('2d'));
    this.resize=()=>{this.width=innerWidth;this.height=innerHeight;const dpr=Math.min(2,devicePixelRatio||1);canvas.width=Math.round(this.width*dpr);canvas.height=Math.round(this.height*dpr);this.dpr=dpr;};
    this.resize();this.observer=new ResizeObserver(this.resize);this.observer.observe(canvas);
  }
  paintMap(c){
    c.fillStyle='#d0b98f';c.fillRect(0,0,MAP.width,MAP.height);
    for(let y=0;y<MAP.height;y+=40)for(let x=0;x<MAP.width;x+=48){const r=random(x*3+y*7);c.fillStyle=`rgba(${r>.5?'255,245,214':'80,63,43'},${.025+r*.045})`;c.fillRect(x+1,y+1,46,38);c.strokeStyle='#9d8d711f';c.lineWidth=1;c.strokeRect(x+.5,y+.5,48,40);}
    for(let i=0;i<700;i++){const x=random(i*9)*MAP.width,y=random(i*7+18)*MAP.height;c.fillStyle=i%3?'#8f805a19':'#fff8d42c';c.fillRect(x,y,2+random(i)*7,1+random(i+1)*3)}
    // Worn courtyard paving is flat decoration; collision stays in MAP.
    for(const s of MAP.sites){
      c.save();c.beginPath();c.rect(s.x-172,s.y-144,344,308);c.clip();
      for(let row=0;row<12;row++)for(let col=0;col<12;col++){
        const x=s.x-180+col*32+(row%2)*16,y=s.y-150+row*28;
        const shade=random(row*43+col*19+s.x);
        c.fillStyle=shade>.55?'#e0cda477':'#bca78455';c.fillRect(x+1,y+1,30,26);
        c.strokeStyle='#a48c6b33';c.lineWidth=.7;c.strokeRect(x+1,y+1,30,26);
      }
      c.restore();
      c.strokeStyle='#f2ddb569';c.lineWidth=3;c.strokeRect(s.x-149,s.y-128,298,272);
      c.fillStyle='#d69a363e';c.beginPath();c.arc(s.x,s.y,s.r,0,Math.PI*2);c.fill();
      c.strokeStyle='#bc802b78';c.lineWidth=5;c.setLineDash([13,10]);c.stroke();c.setLineDash([]);
      c.fillStyle='#a57a3480';c.font='900 100px Arial';c.textAlign='center';c.textBaseline='middle';c.fillText(s.id,s.x,s.y+5);
      c.font='bold 10px Arial';c.fillText('BOMBA NUQTASI',s.x,s.y+66);
    }
    // Flat rugs are scenery, never solid obstacles.
    for(const [x,y,w,h] of [[660,325,92,125],[862,475,65,98],[440,630,65,100],[1080,600,58,80]]){c.save();c.translate(x,y);c.fillStyle='#96564866';c.fillRect(0,0,w,h);c.strokeStyle='#e2bd7877';c.lineWidth=3;c.strokeRect(6,6,w-12,h-12);c.beginPath();c.moveTo(w/2,15);c.lineTo(w-12,h/2);c.lineTo(w/2,h-15);c.lineTo(12,h/2);c.closePath();c.stroke();c.restore()}
    const walls=MAP.walls||[];
    for(let i=0;i<walls.length;i++){
      const b=walls[i];c.fillStyle='#343a2d45';c.fillRect(b.x+9,b.y+12,b.w,b.h);c.fillStyle='#70654e';c.fillRect(b.x-2,b.y-2,b.w+4,b.h+4);c.fillStyle='#dbca9e';c.fillRect(b.x,b.y,b.w,b.h);c.fillStyle='#b4a47e';c.fillRect(b.x,b.y+b.h-7,b.w,7);c.fillStyle='#f3e2b4';c.fillRect(b.x,b.y,b.w,4);c.fillStyle='#baa780';c.fillRect(b.x+b.w-5,b.y+4,5,Math.max(0,b.h-11));
      c.save();c.beginPath();c.rect(b.x,b.y,b.w,b.h);c.clip();c.strokeStyle='#9a886244';c.lineWidth=1;
      for(let y=b.y+22;y<b.y+b.h;y+=26){c.beginPath();c.moveTo(b.x,y);c.lineTo(b.x+b.w,y);c.stroke();for(let x=b.x+((y/26)%2)*27;x<b.x+b.w;x+=54){c.beginPath();c.moveTo(x,y-26);c.lineTo(x,y);c.stroke();}}
      c.restore();
      // Blue glazed shutters sit ON solid walls, never imply a walkable door.
      if(i>3&&b.w>115&&b.h<40){
        for(let j=0;j<Math.floor(b.w/95);j++){
          const x=b.x+23+j*95,y=b.y+5;
          c.fillStyle='#786e54';c.fillRect(x-2,y-2,34,b.h-6);
          c.fillStyle='#1f657b';c.fillRect(x,y,30,b.h-10);
          c.strokeStyle='#6bb5bd';c.lineWidth=1;c.strokeRect(x+3,y+2,24,b.h-14);
          c.strokeStyle='#143e57';c.beginPath();c.moveTo(x+15,y+1);c.lineTo(x+15,y+b.h-11);c.stroke();
          c.fillStyle='#e8d4a6';c.fillRect(x-3,b.y+b.h-5,36,3);
        }
      }else if(i>3&&b.h>110&&b.w<40){
        for(let j=0;j<Math.floor(b.h/95);j++){
          const x=b.x+5,y=b.y+22+j*95;
          c.fillStyle='#786e54';c.fillRect(x-2,y-2,b.w-6,34);
          c.fillStyle='#236f83';c.fillRect(x,y,b.w-10,30);
          c.strokeStyle='#6bb5bd';c.lineWidth=1;c.strokeRect(x+2,y+3,b.w-14,24);
          c.strokeStyle='#143e57';c.beginPath();c.moveTo(x+1,y+15);c.lineTo(x+b.w-11,y+15);c.stroke();
        }
      }
      if(b.w>80&&b.h>35&&i%3===0){for(let k=0;k<12;k++){const x=b.x+8+random(i*91+k)*Math.max(1,b.w-16),y=b.y+5+random(i*71+k)*Math.min(35,b.h-10);c.fillStyle=k%2?'#577849':'#688b4c';c.beginPath();c.arc(x,y,5+random(k+i)*7,0,7);c.fill();}}
    }
    // Central market roof: all stalls/produce stay within its solid rectangle.
    const market=walls.find(b=>b.x===670&&b.y===300);
    if(market){
      const b=market;c.save();c.beginPath();c.rect(b.x,b.y,b.w,b.h);c.clip();
      c.fillStyle='#c1a77c';c.fillRect(b.x+7,b.y+8,b.w-14,b.h-16);
      c.fillStyle='#76543b';c.fillRect(b.x+15,b.y+21,b.w-30,89);
      c.fillStyle='#ad4d3e';c.fillRect(b.x+16,b.y+16,b.w-32,76);
      for(let i=0;i<10;i++){
        const x=b.x+16+i*(b.w-32)/10;c.fillStyle=i%2?'#edd5a1':'#b75540';c.fillRect(x,b.y+16,(b.w-32)/10,76);
        c.fillStyle=i%2?'#d0b887':'#873f32';c.beginPath();c.arc(x+(b.w-32)/20,b.y+92,(b.w-32)/20,0,Math.PI);c.fill();
      }
      c.fillStyle='#66503b';c.fillRect(b.x+15,b.y+105,b.w-30,4);
      for(let j=0;j<5;j++){
        const x=b.x+21+j*44,y=b.y+116;c.fillStyle='#765438';c.fillRect(x,y,36,31);
        c.fillStyle='#b4874e';c.fillRect(x+2,y+2,32,27);c.strokeStyle='#654528';c.lineWidth=1;c.strokeRect(x+2,y+2,32,27);
        for(let k=0;k<9;k++){
          c.fillStyle=['#e69a30','#89a445','#dd7040','#7c9b42','#e6b440'][j];
          c.beginPath();c.arc(x+7+(k%3)*10,y+7+Math.floor(k/3)*9,4,0,7);c.fill();
          c.fillStyle='#f4d58e77';c.beginPath();c.arc(x+6+(k%3)*10,y+6+Math.floor(k/3)*9,1.5,0,7);c.fill();
        }
      }
      c.fillStyle='#f4d5a2';c.fillRect(b.x,b.y,b.w,5);c.restore();
    }
    // Boundary plants are clipped to walls, keeping every traversable lane clear.
    for(const b of walls.slice(0,4)){
      c.save();c.beginPath();c.rect(b.x,b.y,b.w,b.h);c.clip();
      const horizontal=b.w>b.h,len=horizontal?b.w:b.h;
      for(let n=0;n<len;n+=84){
        const x=horizontal?b.x+n+22:b.x+b.w/2,y=horizontal?b.y+b.h/2:b.y+n+22;
        c.fillStyle='#986447';c.beginPath();c.arc(x,y,11,0,7);c.fill();
        c.fillStyle='#5c7440';c.beginPath();c.arc(x+2,y+1,10,0,7);c.fill();
        for(let k=0;k<5;k++){const a=k*1.256+random(n)*3;
          c.fillStyle=k%2?'#688d47':'#829b4d';c.beginPath();c.ellipse(x+Math.cos(a)*7,y+Math.sin(a)*7,9,5,a,0,7);c.fill();}
        if(n%168===0){c.fillStyle='#c98072';c.beginPath();c.arc(x+4,y-3,2.5,0,7);c.fill();}
      }
      c.restore();
    }
    for(const b of MAP.crates||[]){
      c.fillStyle='#3a362b45';c.fillRect(b.x+6,b.y+8,b.w,b.h);c.fillStyle='#a47641';c.fillRect(b.x,b.y,b.w,b.h);c.fillStyle='#c59657';c.fillRect(b.x+4,b.y+4,b.w-8,b.h-8);
      c.strokeStyle='#977043';c.lineWidth=1;for(let x=b.x+13;x<b.x+b.w-5;x+=13){c.beginPath();c.moveTo(x,b.y+5);c.lineTo(x,b.y+b.h-5);c.stroke();}
      c.strokeStyle='#73532e';c.lineWidth=2;c.strokeRect(b.x+4,b.y+4,b.w-8,b.h-8);c.beginPath();c.moveTo(b.x+6,b.y+6);c.lineTo(b.x+b.w-6,b.y+b.h-6);c.moveTo(b.x+b.w-6,b.y+6);c.lineTo(b.x+6,b.y+b.h-6);c.stroke();c.strokeStyle='#e5ba76';c.lineWidth=2;c.strokeRect(b.x+1,b.y+1,b.w-2,b.h-2);
      c.fillStyle='#69533a';for(const [x,y] of [[b.x+7,b.y+7],[b.x+b.w-7,b.y+7],[b.x+7,b.y+b.h-7],[b.x+b.w-7,b.y+b.h-7]])c.fillRect(x-1,y-1,2,2);
    }
    c.textAlign='center';c.textBaseline='middle';c.font='bold 14px Arial';
    for(const team of ['blue','red']){const pts=MAP.spawns[team];if(!pts?.length)continue;const x=pts.reduce((s,p)=>s+p.x,0)/pts.length,y=pts.reduce((s,p)=>s+p.y,0)/pts.length;c.fillStyle=team==='blue'?'#339ca951':'#af574e51';c.fillText(team==='blue'?'HIMOYA BAZASI':'HUJUM BAZASI',x,y+(team==='blue'?35:-35))}
  }
  screenToWorld(x,y){return{x:(x-this.width/2)/this.view.scale+this.view.x,y:(y-this.height/2)/this.view.scale+this.view.y}}
  draw(state,id,{preview=false,dt=.016,time=0}={}){
    const c=this.ctx,w=this.width,h=this.height;
    c.setTransform(this.dpr,0,0,this.dpr,0,0);c.clearRect(0,0,w,h);c.fillStyle='#353b33';c.fillRect(0,0,w,h);
    const players=state?.players||[];let followed=players.find(p=>p.id===state?.spectatingId)||players.find(p=>p.id===id);
    let scale,x,y;
    if(preview){scale=Math.max(w/MAP.width,h/MAP.height);x=MAP.width/2;y=MAP.height/2;this.camera=null;}
    else{scale=Math.max(w/1000,h/650);const tx=clamp(followed?.x??800,w/(2*scale),MAP.width-w/(2*scale)),ty=clamp(followed?.y??500,h/(2*scale),MAP.height-h/(2*scale));if(!this.camera)this.camera={x:tx,y:ty};const a=Math.min(1,dt*8);this.camera.x+=(tx-this.camera.x)*a;this.camera.y+=(ty-this.camera.y)*a;x=this.camera.x;y=this.camera.y;}
    this.view={x,y,scale};c.save();c.translate(w/2,h/2);c.scale(scale,scale);c.translate(-x,-y);c.drawImage(this.floor,0,0);
    if(!preview){
      for(const p of players){if(!p.alive){c.save();c.translate(p.x,p.y);c.rotate((p.angle||0)+.4);c.globalAlpha=.32;c.strokeStyle='#1a2524';c.lineWidth=5;c.lineCap='round';c.beginPath();c.moveTo(-13,-8);c.lineTo(12,9);c.moveTo(-6,11);c.lineTo(10,-10);c.stroke();c.restore();}}
      for(const p of players){if(!p.alive)continue;this.drawPlayer(c,p,p.id===id,time,dt)}
      const b=state?.bomb;
      if(b&&!b.carrierId&&(b.planted||b.x!=null)){c.save();c.translate(b.x,b.y);c.fillStyle=b.planted?'#ed5748':'#bda24e';c.shadowColor=b.planted?'#ff513b':'#ffdf8e';c.shadowBlur=b.planted?12:3;rounded(c,-8,-6,16,12,2);c.fill();c.fillStyle='#292b21';c.fillRect(-5,-3,10,6);c.fillStyle=b.planted&&Math.sin(time*8)>.1?'#ff341d':'#cfda77';c.fillRect(1,-1,4,2);if(b.planted){c.beginPath();c.arc(0,0,21+Math.sin(time*5)*3,0,7);c.lineWidth=1;c.strokeStyle='#f4604380';c.stroke()}c.restore()}
      for(const e of state?.effects||[]){if(e.type!=='shot')continue;c.strokeStyle=e.team==='blue'?'#fff0ad':'#ffc587';c.lineWidth=2.1;c.globalAlpha=clamp((e.ttl??.12)*6,0,.85);c.beginPath();c.moveTo(e.x,e.y);c.lineTo(e.x2,e.y2);c.stroke();c.globalAlpha=1;c.fillStyle='#fff1be';c.beginPath();c.arc(e.x,e.y,3,0,7);c.fill()}
      // Gentle edge shade improves contrast without exposing hidden opponents.
      const grad=c.createRadialGradient(x,y,160,x,y,700);grad.addColorStop(0,'#0c151b00');grad.addColorStop(1,'#0c151b55');c.fillStyle=grad;c.fillRect(0,0,MAP.width,MAP.height);
    }
    c.restore();
    if(!preview)this.drawMinimap(state,id);
  }
  drawPlayer(c,p,self,time,dt){
    let old=this.previous.get(p.id);if(!old||Math.hypot(old.x-p.x,old.y-p.y)>180)old={x:p.x,y:p.y,angle:p.angle||0};const a=Math.min(1,dt*20);old.x+=(p.x-old.x)*a;old.y+=(p.y-old.y)*a;const da=Math.atan2(Math.sin((p.angle||0)-old.angle),Math.cos((p.angle||0)-old.angle));old.angle+=da*a;this.previous.set(p.id,old);
    const moving=Math.hypot(old.x-p.x,old.y-p.y)>1;const step=moving?Math.sin(time*15+p.x)*5:1;const col=COLORS[p.team]||COLORS.blue;
    c.save();c.translate(old.x,old.y);c.fillStyle='#1e2d263d';c.beginPath();c.ellipse(4,7,16,11,0,0,7);c.fill();
    c.strokeStyle=col;c.lineWidth=self?3:1.5;c.globalAlpha=self?.95:.62;c.beginPath();c.arc(0,0,self?21:18,0,7);c.stroke();c.globalAlpha=1;
    if(self){c.strokeStyle='#fff4b1';c.lineWidth=2;c.beginPath();c.moveTo(-5,30);c.lineTo(0,25);c.lineTo(5,30);c.stroke()}
    c.save();c.rotate(old.angle);c.lineCap='round';c.lineJoin='round';c.strokeStyle='#131e21';c.lineWidth=5;
    c.beginPath();c.moveTo(-5,-4);c.lineTo(-14,-9-step);c.lineTo(-20,-7-step);c.moveTo(-5,5);c.lineTo(-13,10+step);c.lineTo(-19,12+step);c.stroke();
    c.lineWidth=4;c.beginPath();c.moveTo(1,-6);c.lineTo(13,-9);c.lineTo(19,-1);c.moveTo(2,7);c.lineTo(13,10);c.lineTo(19,4);c.stroke();
    c.fillStyle='#1b2526';rounded(c,13,-2,p.weapon==='sniper'?30:p.weapon==='pistol'?15:24,5,1);c.fill();c.fillStyle='#41494a';c.fillRect(16,-3,10,3);c.fillStyle='#6b6554';c.fillRect(14,1,6,4);
    if(p.weapon==='knife'){c.fillStyle='#d8e3dd';c.beginPath();c.moveTo(18,-2);c.lineTo(37,-1);c.lineTo(27,4);c.lineTo(18,3);c.closePath();c.fill()}
    c.fillStyle='#151e20';c.beginPath();c.arc(0,0,10.5,0,7);c.fill();c.strokeStyle='#070e10';c.lineWidth=1.5;c.stroke();c.strokeStyle=col;c.lineWidth=4;c.beginPath();c.arc(0,0,10,-1.9,.25);c.stroke();c.strokeStyle=col;c.lineWidth=2.5;c.beginPath();c.moveTo(-6,-8);c.quadraticCurveTo(-18,-16,-22,-8);c.stroke();c.fillStyle='#d4e2de';c.fillRect(5,-4,3,2);c.restore();
    c.fillStyle='#122224b8';rounded(c,-19,-33,38,5,1);c.fill();c.fillStyle=p.hp>30?'#8ed879':'#ff7863';c.fillRect(-18,-32,Math.max(0,36*(p.hp??100)/100),3);
    if(self||p.name){c.font=self?'bold 9px Arial':'8px Arial';c.textAlign='center';c.textBaseline='bottom';c.fillStyle='#15201fbb';c.fillText(String(p.name||'').slice(0,15),.8,-36);c.fillStyle=self?'#f8ffed':'#e9f3e0';c.fillText(String(p.name||'').slice(0,15),0,-37)}
    c.restore();
  }
  drawMinimap(state,id){
    const c=this.mc,w=this.mini.width,h=this.mini.height,s=Math.min(w/MAP.width,h/MAP.height);c.clearRect(0,0,w,h);c.fillStyle='#142225';c.fillRect(0,0,w,h);c.save();c.scale(s,s);c.fillStyle='#8e9477';for(const b of MAP.walls||[])c.fillRect(b.x,b.y,b.w,b.h);c.fillStyle='#99875c';for(const b of MAP.crates||[])c.fillRect(b.x,b.y,b.w,b.h);
    c.font='bold 100px Arial';c.textAlign='center';c.textBaseline='middle';for(const site of MAP.sites){c.fillStyle='#e2ca78';c.fillText(site.id,site.x,site.y)}
    for(const p of state?.players||[]){if(!p.alive)continue;c.fillStyle=p.id===id?'#fff4b3':COLORS[p.team];c.beginPath();c.arc(p.x,p.y,p.id===id?22:17,0,7);c.fill()}
    if(state?.bomb?.planted){c.strokeStyle='#ff8a49';c.lineWidth=10;c.beginPath();c.arc(state.bomb.x,state.bomb.y,35,0,7);c.stroke()}c.restore();
  }
  reset(){this.camera=null;this.previous.clear()}
  destroy(){this.observer.disconnect()}
}
