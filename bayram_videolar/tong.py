#!/usr/bin/env python3
"""Haftaning 7 kuni uchun 20 soniyali "Xayrli tong" videolari, 16:9 (1920x1080)."""
import math, os, subprocess, sys
import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import bayram as B

W, H = B.W, B.H = 1920, 1080
FPS, DUR, NF, SR = B.FPS, B.DUR, B.NF, B.SR
SUN_X = 480
TX = 1260
CARD = (850, 120, 1670, 860)

DAYS = {
    "1_dushanba": dict(day="DUSHANBA", sky=((70, 140, 225), (255, 196, 150)), sun=(255, 214, 120),
                       hills=((120, 170, 120), (70, 140, 90), (40, 100, 70)), sub="Yangi hafta muborak!",
                       body=["Yangi hafta yangi imkoniyatlar,", "yangi marralar va omadlar", "olib kelsin!"],
                       final="Kuningiz barakali o‘tsin!", music=dict(root=67, bpm=104), extra="birds"),
    "2_seshanba": dict(day="SESHANBA", sky=((112, 108, 210), (255, 176, 170)), sun=(255, 200, 140),
                       hills=((150, 140, 190), (110, 100, 160), (70, 65, 120)), sub="Seshanba kuningiz xayrli bo‘lsin",
                       body=["Bugun har bir ishingiz", "o‘z vaqtida, tartib bilan", "va muvaffaqiyatli bitsin!"],
                       final="Omad yor bo‘lsin!", music=dict(root=69, bpm=100, prog=[0, 5, 9, 7]), extra="birds"),
    "3_chorshanba": dict(day="CHORSHANBA", sky=((40, 150, 195), (255, 218, 140)), sun=(255, 225, 120),
                         hills=((110, 180, 140), (60, 150, 120), (30, 110, 95)), sub="Haftaning o‘rtasi — g‘ayrat vaqti",
                         body=["Kayfiyatingiz quyoshdek charog‘on,", "qalbingiz quvonchga to‘la,", "kuningiz unumli bo‘lsin!"],
                         final="Xayrli kun tilaymiz!", music=dict(root=72, bpm=108), extra="sparkle"),
    "4_payshanba": dict(day="PAYSHANBA", sky=((52, 92, 185), (255, 165, 95)), sun=(255, 190, 90),
                        hills=((150, 120, 110), (110, 85, 90), (70, 55, 70)), sub="Payshanba tongi muborak",
                        body=["Bugungi kuningiz yaxshi", "xabarlar va shirin lahzalarga", "boy bo‘lsin!"],
                        final="Tabassumingiz so‘nmasin!", music=dict(root=65, bpm=98), extra="birds"),
    "5_juma": dict(day="JUMA", sky=((30, 112, 125), (255, 212, 150)), sun=(255, 220, 140),
                   hills=((90, 160, 120), (50, 125, 100), (25, 85, 75)), sub="Muborak juma tongi",
                   body=["Alloh kuningizni barakaga,", "xonadoningizni tinchlik va", "fayzga to‘ldirsin!"],
                   final="Juma ayyomingiz muborak!", music=None, extra="sparkle", mosque=True),
    "6_shanba": dict(day="SHANBA", sky=((80, 165, 235), (255, 230, 140)), sun=(255, 230, 110),
                     hills=((140, 195, 110), (95, 165, 85), (55, 125, 65)), sub="Dam olish kunlari boshlandi!",
                     body=["Yaqinlaringiz davrasida", "maroqli hordiq va unutilmas", "damlar tilaymiz!"],
                     final="Ajoyib shanba tilaymiz!", music=dict(root=67, bpm=112), extra="petals"),
    "7_yakshanba": dict(day="YAKSHANBA", sky=((120, 140, 220), (255, 180, 150)), sun=(255, 205, 130),
                        hills=((170, 150, 170), (125, 110, 150), (85, 75, 120)), sub="Yakshanba tongi muborak",
                        body=["Bugun yaxshi dam oling,", "kuch to‘plang — yangi hafta", "yangi marralar bilan kutmoqda!"],
                        final="Xayrli yakshanba!", music=dict(root=70, bpm=96, prog=[0, 9, 5, 7]), extra="petals"),
}


def sky_bg(top, bot):
    y = np.linspace(0, 1, H)[:, None, None] ** 0.9
    col = np.array(top) * (1 - y) + np.array(bot) * y
    col = np.repeat(col, W, axis=1).astype(np.float32)
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    d = np.sqrt((xx - SUN_X) ** 2 + ((yy - 640) * 1.2) ** 2)
    g = np.exp(-(d / 620) ** 2)[..., None] * 0.55
    col = col * (1 - g) + np.array((255, 236, 200)) * g
    rgba = np.dstack([np.clip(col, 0, 255).astype(np.uint8), np.full((H, W), 255, np.uint8)])
    return Image.fromarray(rgba, "RGBA")


def hills(cols, mosque=False):
    img, d = B.hi_canvas(W, H)
    k = 2
    specs = [(820, 60, 0.004, 1.0), (900, 45, 0.006, 2.3), (975, 35, 0.009, 4.1)]
    hy = lambda x, s: s[0] - s[1] * math.sin(x * s[2] + s[3]) - s[1] * 0.5 * math.sin(x * s[2] * 2.7 + s[3] * 2)
    for i, (col, s) in enumerate(zip(cols, specs)):
        if mosque and i == 1:
            # distant mosque on the middle hill
            mx, base = 1765, hy(1765, s) + 10
            c = col + (255,)
            d.rectangle(((mx - 70) * k, (base - 60) * k, (mx + 70) * k, base * k), fill=c)
            d.chord(((mx - 55) * k, (base - 130) * k, (mx + 55) * k, (base - 20) * k), 180, 360, fill=c)
            d.line([(mx * k, (base - 130) * k), (mx * k, (base - 150) * k)], fill=c, width=3 * k)
            for m in (mx - 95, mx + 95):
                d.rectangle(((m - 8) * k, (base - 150) * k, (m + 8) * k, base * k), fill=c)
                d.polygon([((m - 9) * k, (base - 150) * k), (m * k, (base - 175) * k), ((m + 9) * k, (base - 150) * k)], fill=c)
        pts = [(x * k, hy(x, s) * k) for x in range(0, W + 20, 20)]
        d.polygon(pts + [(W * k, H * k), (0, H * k)], fill=col + (255,))
    rng = np.random.default_rng(4)
    front = specs[2]
    tree = tuple(int(v * 0.7) for v in cols[2]) + (255,)
    for x in list(rng.uniform(40, 760, 7)) + list(rng.uniform(1100, 1500, 4)):
        base = hy(x, front)
        h = rng.uniform(50, 95)
        d.ellipse(((x - h * 0.3) * k, (base - h) * k, (x + h * 0.3) * k, (base - h * 0.25) * k), fill=tree)
        d.rectangle(((x - 4) * k, (base - h * 0.3) * k, (x + 4) * k, (base + 6) * k), fill=tree)
    return B.down(img)


def rays(color, size=1300):
    img = Image.new("RGBA", (size, size), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    c = size / 2
    for i in range(16):
        a = math.radians(i * 22.5)
        a1, a2 = a - 0.06, a + 0.06
        d.polygon([(c, c), (c + c * math.cos(a1), c + c * math.sin(a1)), (c + c * math.cos(a2), c + c * math.sin(a2))],
                  fill=color + (46,))
    img = img.filter(ImageFilter.GaussianBlur(6))
    m = B.glow_sprite(size, (255, 255, 255), 255).getchannel("A")
    img.putalpha(Image.fromarray((np.asarray(img.getchannel("A")).astype(np.float32) * np.asarray(m) / 255).astype(np.uint8)))
    return img


def sun(color, r=120):
    s = r * 2 + 40
    img, d = B.hi_canvas(s, s)
    k = 2
    d.ellipse((20 * k, 20 * k, (s - 20) * k, (s - 20) * k), fill=color + (255,))
    d.ellipse((50 * k, 50 * k, (s - 50) * k, (s - 50) * k), fill=(255, 245, 210, 255))
    return B.add_glow(B.down(img), color, 40, 1.3)


def cloud(seed, w=560, h=180):
    rng = np.random.default_rng(seed)
    img = Image.new("L", (w, h), 0)
    d = ImageDraw.Draw(img)
    for _ in range(9):
        cx, cy = rng.uniform(90, w - 90), rng.uniform(h * 0.45, h * 0.7)
        r = rng.uniform(35, 70)
        d.ellipse((cx - r * 1.3, cy - r, cx + r * 1.3, cy + r * 0.7), fill=255)
    img = img.filter(ImageFilter.GaussianBlur(10)).point(lambda v: int(v * 0.75))
    c = Image.new("RGBA", (w, h), (255, 250, 245, 0))
    c.putalpha(img)
    return c


def card(bg):
    x0, y0, x1, y1 = CARD
    reg = bg.crop(CARD).filter(ImageFilter.GaussianBlur(18))
    tint = Image.new("RGBA", reg.size, (14, 22, 52, 150))
    reg.alpha_composite(tint)
    m = Image.new("L", (reg.width * 2, reg.height * 2), 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, m.width - 1, m.height - 1), radius=64, fill=255)
    m = m.resize(reg.size, Image.LANCZOS)
    reg.putalpha(m)
    border, bd = B.hi_canvas(reg.width, reg.height)
    bd.rounded_rectangle((2, 2, border.width - 3, border.height - 3), radius=64, outline=(255, 255, 255, 90), width=4)
    reg.alpha_composite(B.down(border))
    out = Image.new("RGBA", (reg.width + 80, reg.height + 80), (0, 0, 0, 0))
    sh = Image.new("RGBA", reg.size, (0, 0, 0, 255))
    sh.putalpha(m.point(lambda v: int(v * 0.35)))
    out.alpha_composite(sh, (40, 52))
    out = out.filter(ImageFilter.GaussianBlur(16))
    out.alpha_composite(reg, (40, 40))
    return out


def badge(text, color):
    f = ImageFont.truetype(B.F_SUB, 30 * 2)
    tw = f.getlength(text)
    img, d = B.hi_canvas(int(tw / 2) + 70, 56)
    d.rounded_rectangle((0, 0, img.width - 1, img.height - 1), radius=56, fill=color + (255,))
    d.text((img.width / 2, img.height / 2), text, font=f, fill=(40, 30, 20, 255), anchor="mm")
    return B.down(img)


def bird(t, ph, size):
    """simple flapping bird (V shape)"""
    img = Image.new("RGBA", (int(size * 2.4), int(size * 1.6)), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    cx, cy = img.width / 2, img.height / 2
    a = math.sin(t * 9 + ph) * 0.6
    wy = -size * 0.55 * math.cos(a * 2) + size * 0.2
    col = (40, 40, 60, 220)
    d.line([(cx - size, cy + wy), (cx - size * 0.4, cy - wy * 0.4 + size * 0.05), (cx, cy + size * 0.15)], fill=col, width=4, joint="curve")
    d.line([(cx + size, cy + wy), (cx + size * 0.4, cy - wy * 0.4 + size * 0.05), (cx, cy + size * 0.15)], fill=col, width=4, joint="curve")
    return img


def birdsong(seed=3):
    n = int(DUR * SR)
    rng = np.random.default_rng(seed)
    L, R = np.zeros(n), np.zeros(n)
    t0 = 0.6
    while t0 < DUR - 1.5:
        f0 = rng.uniform(2600, 4200)
        for j in range(int(rng.integers(2, 6))):
            dur = rng.uniform(0.05, 0.12)
            m = int(dur * SR)
            tt = np.arange(m) / SR
            sweep = f0 * (1 + rng.uniform(-0.35, 0.35) * tt / dur)
            ph = 2 * np.pi * np.cumsum(sweep) / SR
            s = np.sin(ph + 0.6 * np.sin(2 * np.pi * 45 * tt)) * np.sin(np.pi * tt / dur) ** 2
            st = int((t0 + j * rng.uniform(0.09, 0.16)) * SR)
            pan = rng.uniform(0.15, 0.85)
            L[st:st + m] += s * (1 - pan)
            R[st:st + m] += s * pan
        t0 += rng.uniform(0.7, 1.8)
    return np.stack([L, R], 1)


def audio(cfg, path):
    birds = birdsong()
    birds /= np.max(np.abs(birds)) + 1e-9
    tt = np.arange(len(birds)) / SR
    env = np.minimum(1, tt / 1.0) * np.minimum(1, (DUR - tt) / 1.8)
    if cfg:
        mus = B.synth_music(cfg).astype(np.float32) / 32767
        mix = mus * 0.85 + birds * 0.22 * env[:, None]
    else:
        mix = birds * 0.6 * env[:, None]
    mix /= max(1.0, np.max(np.abs(mix)) / 0.9)
    B.write_wav(path, (mix * 32767).astype(np.int16))


def render(name, out_dir, tmp_dir):
    th = DAYS[name]
    bg = sky_bg(*th["sky"])
    sun_img = sun(th["sun"])
    ray_img = rays(th["sun"])
    halo = B.glow_sprite(900, (255, 240, 200), 110)
    hl = hills(th["hills"], th.get("mosque"))
    clouds = [(cloud(1), 150, 200, 16), (cloud(2), 1000, 110, 10), (cloud(3), 1500, 260, 13), (cloud(5), 600, 330, 8)]
    static_card = None  # built after first frame composition of static layers
    deco = B.ornament_frame((255, 240, 205))
    extra = th["extra"]
    parts = None
    if extra == "petals":
        parts = B.Particles({"petals": 1}, [(255, 255, 255), (255, 210, 225), (255, 235, 240)], seed=11)
    elif extra == "sparkle":
        parts = B.Particles({"sparks": 1}, [(255, 245, 210)], seed=12)
    rng = np.random.default_rng(sum(map(ord, name)))
    flock = [(rng.uniform(-200, -40), rng.uniform(150, 420), rng.uniform(70, 120), rng.uniform(0, 6.28), rng.uniform(16, 26), rng.uniform(0, 4))
             for _ in range(6)]

    gold = ((255, 238, 175), (255, 190, 90))
    title = B.text_img("Xayrli tong!", B.F_TITLE, 118, (255, 230, 150), glow=(255, 180, 80), gradient=gold, maxw=760)
    bdg = badge(th["day"], (255, 214, 120))
    sub = B.text_img(th["sub"], B.F_SUB, 40, (255, 250, 238), maxw=740)
    body = [B.text_img(t, B.F_BODY, 40, B.CREAM, maxw=760) for t in th["body"]]
    final = B.text_img(th["final"], B.F_FINAL, 60, (255, 230, 150), glow=(255, 180, 80), gradient=gold, maxw=740)
    div = B.divider((255, 225, 150), 480)

    # glass card from the static scene (sky + sun at final position + hills)
    base = bg.copy()
    B.put(base, halo, SUN_X, 600)
    B.put(base, sun_img, SUN_X, 600)
    base.alpha_composite(hl)
    static_card = card(base)

    wav = os.path.join(tmp_dir, name + ".wav")
    audio(th["music"], wav)
    out = os.path.join(out_dir, f"tong_{name}.mp4")
    ff = subprocess.Popen(["ffmpeg", "-y", "-loglevel", "error", "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{W}x{H}",
                           "-r", str(FPS), "-i", "-", "-i", wav, "-c:v", "libx264", "-preset", "medium", "-crf", "19",
                           "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "160k", "-shortest", "-movflags", "+faststart", out],
                          stdin=subprocess.PIPE)
    for fi in range(NF):
        t = fi / FPS
        frame = bg.copy()
        rise = B.ease_out(t / 3.5)
        sy = 860 - 260 * rise
        B.put(frame, halo, SUN_X, sy, 0.4 + 0.6 * rise)
        if rise > 0.3:
            B.put(frame, ray_img.rotate(t * 4, resample=Image.BILINEAR), SUN_X, sy, (rise - 0.3) / 0.7 * (0.8 + 0.2 * math.sin(t * 1.5)))
        B.put(frame, sun_img, SUN_X, sy)
        for img, x0, y0, v in clouds:
            B.put(frame, img, (x0 + v * t) % (W + 600) - 300, y0)
        if extra == "birds" or extra == "sparkle":
            for x0, y0, v, ph, sz, delay in flock:
                if t > delay:
                    x = x0 + v * (t - delay)
                    if x < W + 100:
                        B.put(frame, bird(t, ph, sz), x, y0 + 15 * math.sin(t * 0.8 + ph))
        frame.alpha_composite(hl)
        if parts:
            parts.draw(frame, t)
        # card + text
        p = (t - 1.2) / 1.0
        if p > 0:
            B.put(frame, static_card, (CARD[0] + CARD[2]) / 2, (CARD[1] + CARD[3]) / 2 + 6 + (1 - B.ease_out(p)) * 40, B.ease_out(p))
        p = (t - 1.8) / 0.7
        if p > 0:
            B.put(frame, bdg, TX, 200, B.ease_out(p))
        p = (t - 2.2) / 0.9
        if p > 0:
            ti = title
            for s0 in (3.6, 15.5):
                sp = (t - s0) / 1.1
                if 0 < sp < 1:
                    ti = B.shimmer(title, sp)
            B.put(frame, ti, TX, 305 + (1 - B.ease_out(p)) * 40, B.ease_out(p))
        p = (t - 3.2) / 0.8
        if p > 0:
            B.put(frame, sub, TX, 405 + (1 - B.ease_out(p)) * 25, B.ease_out(p))
        p = (t - 3.8) / 1.0
        if p > 0:
            dv = div.resize((max(1, int(div.width * B.ease_out(p))), div.height), Image.BILINEAR)
            B.put(frame, dv, TX, 465)
        for i, bi in enumerate(body):
            p = (t - 5.0 - i * 1.4) / 1.0
            if p > 0:
                B.put(frame, bi, TX + (1 - B.ease_out(p)) * 50, 540 + i * 64, B.ease_out(p))
        p = (t - 10.0) / 1.2
        if p > 0:
            sc = (0.8 + 0.2 * B.ease_back(p)) * (1 + 0.02 * math.sin((t - 10.0) * 2.5) if p >= 1 else 1)
            fi_img = final
            sp = (t - 17.0) / 1.0
            if 0 < sp < 1:
                fi_img = B.shimmer(final, sp)
            fi_img = fi_img.resize((int(fi_img.width * sc), int(fi_img.height * sc)), Image.BILINEAR)
            B.put(frame, fi_img, TX, 770, B.ease_out(p))
        frame.alpha_composite(deco)
        fade = min(1, t / 0.7, (DUR - t) / 0.9)
        rgb = frame.convert("RGB")
        if fade < 1:
            rgb = Image.blend(Image.new("RGB", (W, H), (0, 0, 0)), rgb, max(0, fade))
        if fi == int(FPS * 16):
            rgb.save(os.path.join(tmp_dir, f"tong_{name}.jpg"), quality=80)
        ff.stdin.write(rgb.tobytes())
    ff.stdin.close()
    ff.wait()
    print("done", out, flush=True)


if __name__ == "__main__":
    out_dir, tmp_dir = sys.argv[1], sys.argv[2]
    for n in sys.argv[3:] or list(DAYS):
        render(n, out_dir, tmp_dir)
