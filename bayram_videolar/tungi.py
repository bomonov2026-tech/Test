#!/usr/bin/env python3
"""20 soniyali "Xayrli tun" tabrik videosi, 16:9 (1920x1080). bayram.py yordamchilaridan foydalanadi."""
import math, os, subprocess, sys
import numpy as np
from PIL import Image, ImageDraw, ImageFilter

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import bayram as B

W, H = B.W, B.H = 1920, 1080
FPS, DUR, NF = B.FPS, B.DUR, B.NF
MOON = (255, 242, 205)
MOON_X, MOON_Y = 500, 400


def night_bg():
    y = np.linspace(0, 1, H)[:, None, None]
    col = np.array((8, 14, 48)) * (1 - y) + np.array((30, 30, 82)) * y
    col = np.repeat(col, W, axis=1).astype(np.float32)
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    d = np.sqrt((xx - MOON_X) ** 2 + (yy - MOON_Y) ** 2)
    g = np.exp(-(d / 480) ** 2)[..., None] * 0.45
    col = col * (1 - g) + np.array((150, 160, 220)) * g
    v = np.sqrt(((xx - W / 2) / (W * 0.7)) ** 2 + ((yy - H / 2) / (H * 0.8)) ** 2)
    col *= np.clip(1.15 - v * 0.5, 0.4, 1)[..., None]
    rgba = np.dstack([np.clip(col, 0, 255).astype(np.uint8), np.full((H, W), 255, np.uint8)])
    return Image.fromarray(rgba, "RGBA")


def hills():
    img, d = B.hi_canvas(W, H)
    k = 2
    layers = [((22, 28, 70), 820, 60, 0.004, 1.0), ((12, 16, 44), 900, 45, 0.006, 2.3), ((5, 8, 24), 975, 35, 0.009, 4.1)]
    for col, base, amp, f, ph in layers:
        pts = [(x * k, (base - amp * math.sin(x * f + ph) - amp * 0.5 * math.sin(x * f * 2.7 + ph * 2)) * k) for x in range(0, W + 20, 20)]
        d.polygon(pts + [(W * k, H * k), (0, H * k)], fill=col + (255,))
    # trees on the front hill
    rng = np.random.default_rng(4)
    for x in list(rng.uniform(40, 700, 7)) + list(rng.uniform(1400, 1880, 6)):
        base = 975 - 35 * math.sin(x * 0.009 + 4.1) - 17 * math.sin(x * 0.009 * 2.7 + 8.2)
        h = rng.uniform(50, 95)
        d.polygon([((x - h * 0.28) * k, (base + 6) * k), (x * k, (base - h) * k), ((x + h * 0.28) * k, (base + 6) * k)], fill=(4, 6, 18, 255))
    # small house with a warm window
    hx = 1560
    hb = 975 - 35 * math.sin(hx * 0.009 + 4.1) - 17 * math.sin(hx * 0.009 * 2.7 + 8.2)
    d.rectangle(((hx - 45) * k, (hb - 60) * k, (hx + 45) * k, (hb + 10) * k), fill=(4, 6, 18, 255))
    d.polygon([((hx - 58) * k, (hb - 58) * k), (hx * k, (hb - 105) * k), ((hx + 58) * k, (hb - 58) * k)], fill=(4, 6, 18, 255))
    win = Image.new("RGBA", img.size, (0, 0, 0, 0))
    wd = ImageDraw.Draw(win)
    for wx in (hx - 22, hx + 10):
        wd.rectangle((wx * k, (hb - 42) * k, (wx + 14) * k, (hb - 26) * k), fill=(255, 200, 110, 255))
    out = B.down(img)
    w = B.down(win)
    out.alpha_composite(B.add_glow(w, (255, 190, 100), 10, 1.2, pad=0))
    return out


def moon():
    s = 380
    img, d = B.hi_canvas(s, s)
    k = 2
    d.ellipse((30 * k, 30 * k, (s - 30) * k, (s - 30) * k), fill=MOON + (255,))
    # soft craters
    for cx, cy, r in ((150, 130, 28), (110, 220, 18), (170, 270, 22), (95, 165, 12)):
        d.ellipse(((cx - r) * k, (cy - r) * k, (cx + r) * k, (cy + r) * k), fill=(235, 220, 180, 255))
    d.ellipse((115 * k, 5 * k, (s + 50) * k, (s - 40) * k), fill=(0, 0, 0, 0))
    m = B.down(img)
    return B.add_glow(m, (200, 210, 255), 30, 1.0)


def cloud(seed, w=520, h=170):
    rng = np.random.default_rng(seed)
    img = Image.new("L", (w, h), 0)
    d = ImageDraw.Draw(img)
    for _ in range(9):
        cx, cy = rng.uniform(90, w - 90), rng.uniform(h * 0.45, h * 0.7)
        r = rng.uniform(35, 70)
        d.ellipse((cx - r * 1.3, cy - r, cx + r * 1.3, cy + r * 0.7), fill=255)
    img = img.filter(ImageFilter.GaussianBlur(14)).point(lambda v: int(v * 0.32))
    c = Image.new("RGBA", (w, h), (190, 200, 240, 0))
    c.putalpha(img)
    return c


def render(out_path, tmp_dir):
    bg = night_bg()
    stars = B.Particles({"stars": 1}, [B.GOLD], seed=21)
    bg_static = bg.copy()
    halo = B.glow_sprite(760, (170, 180, 255), 70)
    mn = moon()
    hl = hills()
    clouds = [(cloud(1), 200, 260, 14), (cloud(2), 1100, 160, 9), (cloud(3), 1500, 330, 12), (cloud(4), 700, 120, 7)]
    frame_deco = B.ornament_frame((215, 220, 255))
    fsp = B.glow_sprite(34, (220, 255, 140), 255)
    rng = np.random.default_rng(9)
    flies = [(rng.uniform(80, W - 80), rng.uniform(860, 1030), rng.uniform(0, 6.28), rng.uniform(0.5, 1.4)) for _ in range(28)]

    tx = 1270
    title = B.text_img("Xayrli tun!", B.F_TITLE, 130, MOON, glow=(160, 170, 255),
                       gradient=((255, 250, 225), (230, 200, 140)), maxw=900)
    sub = B.text_img("Shirin tushlar ko‘ring", B.F_SUB, 46, (210, 220, 255), maxw=860)
    body = [B.text_img(t, B.F_BODY, 40, B.CREAM, maxw=880) for t in
            ("Kunning barcha tashvishlari ortda qolsin,", "tuningiz osuda, uyqungiz shirin bo‘lsin.",
             "Ertangi kuningiz yanada go‘zal bo‘lsin!")]
    final = B.text_img("Yaxshi dam oling!", B.F_FINAL, 64, MOON, glow=(160, 170, 255),
                       gradient=((255, 250, 225), (235, 205, 150)), maxw=880)
    div = B.divider((215, 220, 255), 520)

    wav = os.path.join(tmp_dir, "tungi.wav")
    B.write_wav(wav, B.synth_music(dict(root=65, bpm=66, chord=2.5, prog=[0, 9, 5, 7])))
    ff = subprocess.Popen(["ffmpeg", "-y", "-loglevel", "error", "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{W}x{H}",
                           "-r", str(FPS), "-i", "-", "-i", wav, "-c:v", "libx264", "-preset", "medium", "-crf", "19",
                           "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "160k", "-shortest", "-movflags", "+faststart", out_path],
                          stdin=subprocess.PIPE)
    preview_ts = {int(FPS * x) for x in (4, 10, 16.5)}
    for fi in range(NF):
        t = fi / FPS
        frame = bg_static.copy()
        stars.draw(frame, t)
        p = (t - 0.3) / 2.0
        if p > 0:
            a = B.ease_out(p)
            B.put(frame, halo, MOON_X, MOON_Y, a * (0.85 + 0.15 * math.sin(t * 1.2)))
            B.put(frame, mn, MOON_X, MOON_Y + (1 - a) * 60 + 6 * math.sin(t * 0.8), a)
        for img, x0, y0, v in clouds:
            B.put(frame, img, (x0 + v * t) % (W + 600) - 300, y0)
        frame.alpha_composite(hl)
        for x, y, ph, f in flies:
            xx = x + 40 * math.sin(t * 0.5 * f + ph)
            yy = y + 18 * math.sin(t * 0.9 * f + ph * 2)
            blink = max(0.0, math.sin(t * 1.7 * f + ph)) ** 2
            B.put(frame, fsp, xx, yy, blink)
        p = (t - 1.8) / 1.0
        if p > 0:
            ti = title
            for s0 in (3.5, 15.5):
                sp = (t - s0) / 1.1
                if 0 < sp < 1:
                    ti = B.shimmer(title, sp)
            B.put(frame, ti, tx, 250 + (1 - B.ease_out(p)) * 50, B.ease_out(p))
        p = (t - 3.0) / 0.9
        if p > 0:
            B.put(frame, sub, tx, 365 + (1 - B.ease_out(p)) * 30, B.ease_out(p))
        p = (t - 3.6) / 1.0
        if p > 0:
            dv = div.resize((max(1, int(div.width * B.ease_out(p))), div.height), Image.BILINEAR)
            B.put(frame, dv, tx, 435)
        for i, bi in enumerate(body):
            p = (t - 5.0 - i * 1.5) / 1.1
            if p > 0:
                B.put(frame, bi, tx + (1 - B.ease_out(p)) * 60, 510 + i * 66, B.ease_out(p))
        p = (t - 10.5) / 1.3
        if p > 0:
            sc = (0.8 + 0.2 * B.ease_back(p)) * (1 + 0.02 * math.sin((t - 10.5) * 2.5) if p >= 1 else 1)
            fi_img = final
            sp = (t - 17.0) / 1.0
            if 0 < sp < 1:
                fi_img = B.shimmer(final, sp)
            fi_img = fi_img.resize((int(fi_img.width * sc), int(fi_img.height * sc)), Image.BILINEAR)
            B.put(frame, fi_img, tx, 760, B.ease_out(p))
        frame.alpha_composite(frame_deco)
        fade = min(1, t / 0.8, (DUR - t) / 1.0)
        rgb = frame.convert("RGB")
        if fade < 1:
            rgb = Image.blend(Image.new("RGB", (W, H), (0, 0, 0)), rgb, max(0, fade))
        if fi in preview_ts:
            rgb.save(os.path.join(tmp_dir, f"tungi_{fi:03d}.jpg"), quality=80)
        ff.stdin.write(rgb.tobytes())
    ff.stdin.close()
    ff.wait()
    print("done", out_path)


if __name__ == "__main__":
    render(sys.argv[1], sys.argv[2])
