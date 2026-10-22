<?php
declare(strict_types=1);

/* ========== TartibOn — animatsion intro (splash screen) ==========
 * index.php shu faylni ixtiyoriy ulaydi: fayl bo'lmasa panel introsiz ishlayveradi.
 *
 * Rejimlar:
 *   'play' — tasdiqlangan sahifa: intro to'liq o'ynaladi (≈3.8 s, keyin DOMdan o'chadi)
 *   'wait' — kirish (initData) sahifasi: faqat ko'k fon, panel ko'rinmasin;
 *            server javob bergach 'play' sahifasi shu fondan davom etadi
 *
 * Qachon ko'rsatiladi: sessionStorage (shu sessiyada bir marta) + localStorage
 * (Mini App qayta ochilganda intro_cooldown_min daqiqa ichida qayta ko'rsatilmaydi).
 * Majburan ko'rish: ?p=home&intro=1
 *
 * config.php (ixtiyoriy):
 *   'intro'              => false,                   // introni o'chirish
 *   'intro_cooldown_min' => 360,                     // qayta ochilganda kutish (daqiqa)
 *   'intro_video'        => '/intro/tartibon.mp4',   // CSS animatsiya o'rniga video (same-origin, ≤3.4 s)
 * JS o'chiq bo'lsa splash umuman chiqmaydi — sahifa odatdagidek ochiladi.
 */

function splash_enabled(): bool { return ($GLOBALS['CFG']['intro'] ?? true) !== false; }
function splash_video(): string {
    $v = (string)($GLOBALS['CFG']['intro_video'] ?? '');
    return preg_match('~^/[A-Za-z0-9/_.-]+\.(mp4|webm)$~', $v) ? $v : ''; // faqat shu saytdagi fayl (CSP: 'self')
}

/* <head> ichida, birinchi chizishdan oldin: <html> ga sp-intro / sp-wait klassini qo'yadi */
function splash_head(string $mode): string {
    if (!splash_enabled() || !in_array($mode, ['play', 'wait'], true)) return '';
    $cool = max(0, (int)($GLOBALS['CFG']['intro_cooldown_min'] ?? 360)) * 60000;
    return '<script>(function(){var d=document.documentElement,K="tartibon_intro",M="' . $mode . '",V=' . (splash_video() !== '' ? 'true' : 'false') . ';'
        . 'function g(s){try{return window[s].getItem(K)}catch(e){return null}}function p(s,v){try{window[s].setItem(K,v);return true}catch(e){return false}}'
        . 'var f=/[?&]intro=1(&|$)/.test(location.search);'
        . 'if(!f&&(g("sessionStorage")||Date.now()-(+g("localStorage")||0)<' . $cool . '))return;'
        // xotira ishlamasa intro har sahifada takrorlanmasin
        . 'if(M==="wait"){try{sessionStorage.setItem(K+"_t","1");sessionStorage.removeItem(K+"_t")}catch(e){return}d.classList.add("sp-wait");return}'
        . 'var ok=p("sessionStorage","1");ok=p("localStorage",String(Date.now()))||ok;if(!ok&&!f)return;'
        . 'd.classList.add("sp-intro");if(V&&!(window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches))d.classList.add("sp-video")})();</script>';
}

/* <body> boshida: splash komponenti (CSS + belgi + boshqaruvchi JS) */
function splash_body(string $mode): string {
    if (!splash_enabled() || !in_array($mode, ['play', 'wait'], true)) return '';
    $planes = '';
    // [chap, yuqori, o'lcham, burilish, siljish, shaffoflik, kechikish]
    foreach ([['6%', '14%', '46px', '-18deg', '70px', '.22', '.10s'], ['78%', '9%', '30px', '12deg', '50px', '.16', '.22s'],
              ['84%', '58%', '58px', '-28deg', '80px', '.18', '.05s'], ['12%', '72%', '36px', '8deg', '60px', '.14', '.30s'],
              ['46%', '86%', '24px', '-10deg', '40px', '.12', '.18s'], ['36%', '5%', '20px', '20deg', '36px', '.12', '.36s']] as [$x, $y, $s, $r, $dx, $o, $k])
        $planes .= '<i class="sp-pl" style="left:' . $x . ';top:' . $y . ';--s:' . $s . ';--r:' . $r . ';--d:' . $dx . ';--o:' . $o . ';--k:' . $k . '"><svg viewBox="0 0 24 24"><use href="#sp-plane"/></svg></i>';
    $ch = '';
    foreach (str_split('Tartib') as $i => $c) $ch .= '<span class="sp-ch" style="--i:' . $i . '">' . $c . '</span>';
    $video = splash_video();

    return '<div id="tartibon-splash" class="sp" aria-hidden="true"><style>
#tartibon-splash{display:none}
html.sp-intro #tartibon-splash,html.sp-wait #tartibon-splash{display:block}
html.sp-intro body,html.sp-wait body{overflow:hidden}
.sp{position:fixed;inset:0;height:100vh;height:100dvh;z-index:2147483000;overflow:hidden;contain:strict;color:#fff;
 background:radial-gradient(120% 80% at 50% 38%,rgba(255,255,255,.16),transparent 60%),linear-gradient(165deg,#168FFF 0%,#0789F9 46%,#006DE5 100%);
 font:800 16px/1 "SF Pro Rounded",ui-rounded,"Nunito","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif;-webkit-font-smoothing:antialiased}
.sp *{box-sizing:border-box}
.sp-light,.sp-flash{position:absolute;left:50%;top:46%;width:150vmax;height:150vmax;margin:-75vmax 0 0 -75vmax;border-radius:50%;pointer-events:none}
.sp-light{background:radial-gradient(closest-side,rgba(255,255,255,.34),rgba(120,200,255,.12) 45%,transparent 70%);opacity:.8}
.sp-flash{background:radial-gradient(closest-side,rgba(215,255,248,.62),rgba(80,230,220,.2) 38%,transparent 66%);opacity:0}
.sp-pl{position:absolute;width:var(--s);height:var(--s);opacity:var(--o);transform:rotate(var(--r)) translate3d(var(--d),0,0)}
.sp-pl svg{display:block;width:100%;height:100%;fill:#fff}
.sp-stage{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
 padding:env(safe-area-inset-top,0) max(16px,env(safe-area-inset-right,0)) env(safe-area-inset-bottom,0) max(16px,env(safe-area-inset-left,0))}
.sp-logo{position:relative;display:flex;align-items:baseline;font-size:clamp(46px,16vw,124px);letter-spacing:-.025em;white-space:nowrap}
.sp-ch{display:inline-block;text-shadow:0 .04em .14em rgba(0,40,120,.28)}
.sp-on{position:relative;display:inline-flex;align-items:baseline;margin-left:.03em}
.sp-glow{position:absolute;left:-.35em;right:-.35em;top:-.45em;bottom:-.55em;z-index:-1;border-radius:50%;
 background:radial-gradient(closest-side,rgba(60,245,200,.5),rgba(16,207,224,.22) 55%,transparent);pointer-events:none}
.sp-btn{position:relative;display:inline-block;width:.84em;height:.84em;margin:0 .03em 0 .01em;transform:translateY(.055em)}
.sp-press{position:absolute;inset:0}
.sp-press svg{display:block;width:100%;height:100%;overflow:visible;filter:drop-shadow(0 .02em .05em rgba(0,50,130,.28))}
.sp-disc{position:absolute;inset:-16%;border-radius:50%;opacity:0;
 background:radial-gradient(closest-side,rgba(255,255,255,.26),rgba(255,255,255,.1) 70%,rgba(255,255,255,0));box-shadow:inset 0 0 0 .02em rgba(255,255,255,.35),0 .05em .2em rgba(0,40,120,.25)}
.sp-ring{position:absolute;inset:-10%;border-radius:50%;border:.035em solid #5ff5d2;opacity:0;pointer-events:none}
.sp-ring.r2{border-color:#bff;border-width:.02em}
.sp-off{stroke:#e4f0ff;opacity:0}
.sp-onl{stroke:url(#sp-grad)}
.sp-bar{transform-box:view-box;transform-origin:50% 50%}
.sp-n{position:relative;color:rgba(255,255,255,.5)}
.sp-n::after{content:"n";position:absolute;left:0;top:0;color:transparent;background:linear-gradient(135deg,#6CF58A 0%,#22E3A8 45%,#12CFE2 100%);-webkit-background-clip:text;background-clip:text}
.sp-video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:none}
html.sp-video .sp-video{display:block}
html.sp-video .sp-stage,html.sp-video .sp-pl,html.sp-video .sp-flash{display:none}
html.sp-vout #tartibon-splash{opacity:0;transition:opacity .5s ease;pointer-events:none}
html.sp-wait .sp-stage,html.sp-wait .sp-pl,html.sp-wait .sp-flash{display:none}

/* ---- vaqt chizig\'i (jami ≈3.8 s) ---- */
html.sp-intro .sp-light{animation:sp-light .9s cubic-bezier(.2,.7,.2,1) .05s both}
html.sp-intro .sp-pl{animation:sp-in .8s ease-out var(--k) both,sp-drift 3.8s linear both}
html.sp-intro .sp-logo{animation:sp-logo 2.5s linear .5s both}
html.sp-intro .sp-ch,html.sp-intro .sp-btn{animation:sp-letter .55s cubic-bezier(.2,.9,.25,1.12) calc(.5s + var(--i) * .07s) both}
html.sp-intro .sp-press{animation:sp-press .9s linear 1.5s both}
html.sp-intro .sp-bar{animation:sp-bar .6s linear 1.75s both}
html.sp-intro .sp-disc{animation:sp-disc 2s linear .55s both}
html.sp-intro .sp-ring{animation:sp-ring .75s cubic-bezier(.15,.6,.3,1) 1.9s both}
html.sp-intro .sp-ring.r2{animation:sp-ring2 .95s cubic-bezier(.15,.6,.3,1) 2.05s both}
html.sp-intro .sp-off{animation:sp-out-o .45s ease 2s both}
html.sp-intro .sp-onl{animation:sp-in-o .45s ease 2s both}
html.sp-intro .sp-n::after{animation:sp-in-o .45s ease 2.1s both}
html.sp-intro .sp-glow{animation:sp-glow .6s ease-out 2.4s both}
html.sp-intro .sp-flash{animation:sp-flash .75s ease-out 2.45s both}
html.sp-intro .sp-stage{animation:sp-exit .5s cubic-bezier(.4,0,.2,1) 3.2s forwards}
html.sp-intro #tartibon-splash{animation:sp-hide .55s ease 3.25s forwards}
html.sp-intro body>:not(#tartibon-splash){animation:sp-page .55s cubic-bezier(.2,.8,.2,1) 3.3s both}
html.sp-video #tartibon-splash{animation:none}
html.sp-video body>:not(#tartibon-splash){animation:none;opacity:0}
html.sp-video.sp-vout body>:not(#tartibon-splash){animation:sp-page .5s cubic-bezier(.2,.8,.2,1) both}
html.sp-wait #tartibon-splash{animation:sp-hide .4s ease 8s forwards} /* zaxira: kirish so\'rovi osilib qolsa */

@keyframes sp-light{from{opacity:0;transform:scale(.4)}}
@keyframes sp-in{from{opacity:0}}
@keyframes sp-drift{from{transform:rotate(var(--r)) translate3d(0,0,0)}}
@keyframes sp-logo{0%{transform:scale(.94);animation-timing-function:cubic-bezier(.2,.8,.2,1)}40%,80%{transform:scale(1);animation-timing-function:cubic-bezier(.3,0,.3,1)}88%{transform:scale(1.035);animation-timing-function:cubic-bezier(.3,0,.3,1)}100%{transform:scale(1)}}
@keyframes sp-letter{from{opacity:0;transform:translate3d(0,.45em,0) scale(.86)}}
@keyframes sp-press{0%,11%{transform:none;animation-timing-function:cubic-bezier(.3,0,.3,1)}28%{transform:translate3d(0,-2%,0) scale(1.1);animation-timing-function:cubic-bezier(.5,0,.8,.4)}44%{transform:translate3d(0,7%,0) scale(.9);animation-timing-function:cubic-bezier(.2,.8,.3,1)}66%{transform:translate3d(0,-1%,0) scale(1.06);animation-timing-function:ease-in-out}100%{transform:none}}
@keyframes sp-bar{0%{transform:none;animation-timing-function:cubic-bezier(.5,0,.8,.4)}35%,50%{transform:translate3d(0,14px,0);animation-timing-function:cubic-bezier(.2,.8,.3,1)}80%{transform:translate3d(0,-3px,0)}100%{transform:none}}
@keyframes sp-disc{0%{opacity:0}20%,70%{opacity:1}100%{opacity:0}}
@keyframes sp-ring{from{opacity:.95;transform:scale(.7)}to{opacity:0;transform:scale(2.6)}}
@keyframes sp-ring2{from{opacity:.6;transform:scale(.8)}to{opacity:0;transform:scale(3.6)}}
@keyframes sp-out-o{from{opacity:1}to{opacity:0}}
@keyframes sp-in-o{from{opacity:0}}
@keyframes sp-glow{from{opacity:0;transform:scale(.6)}}
@keyframes sp-flash{0%{opacity:0;transform:scale(.45)}35%{opacity:1}100%{opacity:0;transform:scale(1.25)}}
@keyframes sp-exit{to{opacity:0;transform:translate3d(0,-7vh,0) scale(.84)}}
@keyframes sp-hide{0%{pointer-events:none}to{opacity:0;visibility:hidden;pointer-events:none}}
@keyframes sp-page{from{opacity:0;transform:translate3d(0,18px,0)}to{opacity:1;transform:none}}

@media (prefers-reduced-motion:reduce){
 #tartibon-splash,#tartibon-splash *,#tartibon-splash *::after{animation:none!important}
 html.sp-intro #tartibon-splash{animation:sp-hide .35s ease .8s forwards!important}
 html.sp-intro body>:not(#tartibon-splash){animation:sp-in-o .35s ease .8s both!important}
}
</style>'
    . '<svg width="0" height="0" style="position:absolute"><symbol id="sp-plane" viewBox="0 0 24 24"><path d="M21.4 3.3 2.9 10.5c-1 .4-.9 1.7.1 2l4.6 1.4 1.8 5.5c.3.8 1.3 1 1.9.4l2.6-2.6 4.8 3.5c.7.5 1.6.1 1.8-.7L23 4.6c.2-1-.7-1.7-1.6-1.3Zm-3.3 4L9.6 15l-.4 3.3-1.3-4.4 10.2-6.6Z"/></symbol></svg>'
    . '<div class="sp-light"></div>' . $planes
    . '<div class="sp-stage"><div class="sp-logo" role="img" aria-label="TartibOn">' . $ch
    . '<span class="sp-on"><span class="sp-glow"></span><span class="sp-btn" style="--i:6"><span class="sp-disc"></span><span class="sp-ring"></span><span class="sp-ring r2"></span><span class="sp-press">'
    . '<svg viewBox="0 0 100 100" fill="none" stroke-width="12" stroke-linecap="round"><defs><linearGradient id="sp-grad" gradientUnits="userSpaceOnUse" x1="10" y1="5" x2="90" y2="95"><stop offset="0" stop-color="#6CF58A"/><stop offset=".45" stop-color="#22E3A8"/><stop offset="1" stop-color="#12CFE2"/></linearGradient></defs>'
    . '<path class="sp-off" d="M73.1 22.4A36 36 0 1 1 26.9 22.4"/><path class="sp-onl" d="M73.1 22.4A36 36 0 1 1 26.9 22.4"/>'
    . '<g class="sp-bar"><path class="sp-off" d="M50 9v38"/><path class="sp-onl" d="M50 9v38"/></g></svg>'
    . '</span></span><span class="sp-ch sp-n" style="--i:7">n</span></span></div></div>'
    . '<div class="sp-flash"></div>'
    . ($video !== '' ? '<video class="sp-video" src="' . htmlspecialchars($video, ENT_QUOTES) . '" muted playsinline preload="auto"></video>' : '')
    . '<script>(function(){var d=document.documentElement,el=document.getElementById("tartibon-splash"),t;'
    . 'if(!d.classList.contains("sp-intro")){if(!d.classList.contains("sp-wait"))el.parentNode.removeChild(el);return}'
    . 'var rm=window.matchMedia&&matchMedia("(prefers-reduced-motion: reduce)").matches,v=el.querySelector("video");'
    // tugagach: splash DOMdan o'chadi, sahifa to'liq ishlaydi, Telegram ranglari qaytadi
    . 'function done(){clearTimeout(t);if(!el)return;d.classList.remove("sp-intro","sp-video","sp-vout");if(el.parentNode)el.parentNode.removeChild(el);el=v=null;try{var w=Telegram.WebApp;w.setHeaderColor("#000000");w.setBackgroundColor("#030603")}catch(e){}}'
    . 'function css(){clearTimeout(t);if(v){v.removeAttribute("src");if(v.parentNode)v.parentNode.removeChild(v);v=null}d.classList.remove("sp-video");t=setTimeout(done,rm?1200:3850)}'
    . 'function vout(){if(!v)return;v=null;d.classList.add("sp-vout");clearTimeout(t);t=setTimeout(done,520)}'
    . 'if(!v||!d.classList.contains("sp-video"))return css();'
    . 'var ok=0;v.addEventListener("playing",function(){ok=1});v.addEventListener("ended",vout);v.addEventListener("error",css);'
    . 'try{var pr=v.play();if(pr&&pr.catch)pr.catch(css)}catch(e){return css()}'
    . 't=setTimeout(function(){if(!ok)return css();t=setTimeout(vout,3000)},400)})();</script></div>';
}
