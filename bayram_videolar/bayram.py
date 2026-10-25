#!/usr/bin/env python3
"""20 soniyali bayram tabrik videolari generatori (1080x1920, 30fps)."""
import math, os, subprocess, sys, wave
import numpy as np
from PIL import Image, ImageDraw, ImageFilter, ImageFont

W, H, FPS, DUR = 1080, 1920, 30, 20.0
NF = int(FPS * DUR)
SR = 44100
FD = "/usr/share/fonts/truetype/"
F_TITLE = FD + "dejavu/DejaVuSerif-Bold.ttf"
F_BODY = FD + "dejavu/DejaVuSans.ttf"
F_FINAL = FD + "liberation/LiberationSerif-BoldItalic.ttf"
F_SUB = FD + "dejavu/DejaVuSans-Bold.ttf"

GOLD = (245, 205, 115)
CREAM = (250, 244, 232)
FLAG_BLUE, FLAG_GREEN, FLAG_RED = (0, 153, 181), (30, 181, 58), (206, 17, 38)


# ---------------------------------------------------------------- helpers
def clamp(x, a=0.0, b=1.0):
    return max(a, min(b, x))


def ease_out(x):
    x = clamp(x)
    return 1 - (1 - x) ** 3


def ease_back(x):
    x = clamp(x)
    c = 1.70158
    return 1 + (c + 1) * (x - 1) ** 3 + c * (x - 1) ** 2


def with_alpha(img, a):
    if a >= 0.999:
        return img
    r = img.copy()
    lut = [int(v * a) for v in range(256)]
    r.putalpha(img.getchannel("A").point(lut))
    return r


def put(frame, img, cx, cy, a=1.0):
    if a <= 0.003:
        return
    im = with_alpha(img, a)
    x, y = int(cx - im.width / 2), int(cy - im.height / 2)
    # crop to frame bounds
    sx, sy = max(0, -x), max(0, -y)
    ex, ey = min(im.width, W - x), min(im.height, H - y)
    if ex <= sx or ey <= sy:
        return
    if (sx, sy, ex, ey) != (0, 0, im.width, im.height):
        im = im.crop((sx, sy, ex, ey))
    frame.alpha_composite(im, (x + sx, y + sy))


def hi_canvas(w, h, k=2):
    img = Image.new("RGBA", (w * k, h * k), (0, 0, 0, 0))
    return img, ImageDraw.Draw(img)


def down(img, k=2):
    return img.resize((img.width // k, img.height // k), Image.LANCZOS)


def add_glow(img, color, radius, strength=1.0, pad=None):
    pad = pad if pad is not None else radius * 2
    big = Image.new("RGBA", (img.width + 2 * pad, img.height + 2 * pad), (0, 0, 0, 0))
    big.alpha_composite(img, (pad, pad))
    a = big.getchannel("A").filter(ImageFilter.GaussianBlur(radius))
    a = a.point([min(255, int(v * strength)) for v in range(256)])
    glow = Image.new("RGBA", big.size, color + (0,))
    glow.putalpha(a)
    glow.alpha_composite(big)
    return glow


def star_pts(cx, cy, R, r=None, n=5, rot=-90):
    r = r if r is not None else R * 0.42
    pts = []
    for i in range(n * 2):
        ang = math.radians(rot + i * 180 / n)
        rr = R if i % 2 == 0 else r
        pts.append((cx + rr * math.cos(ang), cy + rr * math.sin(ang)))
    return pts


def glow_sprite(size, color, peak=255):
    y, x = np.mgrid[0:size, 0:size].astype(np.float32)
    c = (size - 1) / 2
    d = np.sqrt((x - c) ** 2 + (y - c) ** 2) / c
    a = np.clip(np.exp(-d * d * 4.0) - 0.02, 0, 1) * peak
    arr = np.zeros((size, size, 4), np.uint8)
    arr[..., :3] = color
    arr[..., 3] = a.astype(np.uint8)
    return Image.fromarray(arr, "RGBA")


def font_fit(path, text, size, maxw):
    f = ImageFont.truetype(path, size)
    while f.getlength(text) > maxw and size > 20:
        size -= 2
        f = ImageFont.truetype(path, size)
    return f


def text_img(text, path, size, fill, glow=None, maxw=940, gradient=None, shadow=True):
    f = font_fit(path, text, size, maxw)
    bb = f.getbbox(text)
    tw, th = bb[2] - bb[0], bb[3] - bb[1]
    pad = 10
    mask = Image.new("L", (tw + pad * 2, th + pad * 2), 0)
    ImageDraw.Draw(mask).text((pad - bb[0], pad - bb[1]), text, font=f, fill=255)
    if gradient:
        top, bot = gradient
        g = np.linspace(0, 1, mask.height)[:, None, None]
        col = (np.array(top) * (1 - g) + np.array(bot) * g)
        col = np.repeat(col, mask.width, axis=1).astype(np.uint8)
        layer = Image.fromarray(np.dstack([col, np.zeros(col.shape[:2], np.uint8)]), "RGBA")
    else:
        layer = Image.new("RGBA", mask.size, fill + (0,))
    layer.putalpha(mask)
    out = layer
    if shadow:
        sh = Image.new("RGBA", mask.size, (0, 0, 0, 0))
        s = Image.new("RGBA", mask.size, (0, 0, 0, 255))
        s.putalpha(mask.point(lambda v: int(v * 0.6)))
        sh.alpha_composite(s, (0, 0))
        base = Image.new("RGBA", (mask.width + 8, mask.height + 8), (0, 0, 0, 0))
        base.alpha_composite(sh, (5, 6))
        base = base.filter(ImageFilter.GaussianBlur(3))
        base.alpha_composite(layer, (0, 0))
        out = base
    if glow:
        out = add_glow(out, glow, 14, 0.9)
    return out


# ---------------------------------------------------------------- backgrounds
def gradient_bg(top, bot, glow_col, glow_y=430, glow_x=None):
    gx = W / 2 if glow_x is None else glow_x
    y = np.linspace(0, 1, H)[:, None, None]
    col = np.array(top) * (1 - y) + np.array(bot) * y
    col = np.repeat(col, W, axis=1).astype(np.float32)
    yy, xx = np.mgrid[0:H, 0:W].astype(np.float32)
    d = np.sqrt((xx - gx) ** 2 + ((yy - glow_y) * 0.85) ** 2)
    g = np.exp(-(d / 520) ** 2)[..., None] * 0.55
    col = col * (1 - g) + np.array(glow_col) * g * 0.75 + col * g * 0.25
    # vignette
    v = np.sqrt(((xx - W / 2) / (W * 0.75)) ** 2 + ((yy - H / 2) / (H * 0.7)) ** 2)
    col *= np.clip(1.15 - v * 0.55, 0.35, 1)[..., None]
    rgba = np.dstack([np.clip(col, 0, 255).astype(np.uint8), np.full((H, W), 255, np.uint8)])
    return Image.fromarray(rgba, "RGBA")


def geometric_pattern(alpha=22, color=GOLD):
    img, d = hi_canvas(W, H, 1)
    step = 135
    for gy in range(-step, H + step, step):
        for gx in range(-step, W + step, step):
            cx, cy = gx + (step / 2 if (gy // step) % 2 else 0), gy
            r = step * 0.38
            sq1 = [(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a))) for a in (0, 90, 180, 270)]
            sq2 = [(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a))) for a in (45, 135, 225, 315)]
            d.polygon(sq1, outline=color + (alpha,), width=2)
            d.polygon(sq2, outline=color + (alpha,), width=2)
            d.ellipse((cx - 6, cy - 6, cx + 6, cy + 6), outline=color + (alpha,), width=2)
    return img


def ornament_frame(color=GOLD):
    img, d = hi_canvas(W, H, 2)
    k = 2
    for inset, wdt, a in ((34, 3, 200), (48, 1, 140)):
        d.rounded_rectangle((inset * k, inset * k, (W - inset) * k, (H - inset) * k),
                            radius=26 * k, outline=color + (a,), width=wdt * k)
    for cx, cy in ((41, 41), (W - 41, 41), (41, H - 41), (W - 41, H - 41)):
        cx, cy = cx * k, cy * k
        for i in range(8):
            ang = math.radians(i * 45)
            px, py = cx + 22 * k * math.cos(ang), cy + 22 * k * math.sin(ang)
            d.ellipse((px - 7 * k, py - 7 * k, px + 7 * k, py + 7 * k), fill=color + (210,))
        d.ellipse((cx - 13 * k, cy - 13 * k, cx + 13 * k, cy + 13 * k), fill=color + (255,))
    # top/bottom centre lozenges
    for cy in (34, H - 34):
        cx = W / 2
        d.polygon([((cx - 40) * k, cy * k), (cx * k, (cy - 14) * k), ((cx + 40) * k, cy * k), (cx * k, (cy + 14) * k)],
                  fill=color + (230,))
    return down(img, 2)


def mosque_silhouette(col, win=(255, 200, 110), cx=540, mo=350):
    top = H - 380
    img, d = hi_canvas(W, H - top, 2)
    k = 2
    h = (H - top) * k
    c = col + (255,)

    def dome(cx, base, r, hgt):
        pts = []
        for i in range(41):
            t = i / 40
            ang = math.pi * t
            x = cx - r * math.cos(ang)
            y = base - hgt * math.sin(ang) ** 0.8
            pts.append((x, y))
        pts.append((cx, base - hgt - r * 0.35))
        pts = pts[:21] + [(cx, base - hgt * 1.25)] + pts[21:-1]
        d.polygon([(x * k, y * k) for x, y in pts], fill=c)
        d.line([(cx * k, (base - hgt * 1.25) * k), (cx * k, (base - hgt * 1.25 - 26) * k)], fill=c, width=4 * k)
        d.ellipse(((cx - 6) * k, (base - hgt * 1.25 - 40) * k, (cx + 6) * k, (base - hgt * 1.25 - 28) * k), fill=c)

    hb = H - top
    d.rectangle((0, (hb - 90) * k, W * k, h), fill=c)
    d.rectangle(((cx - 240) * k, (hb - 170) * k, (cx + 240) * k, h), fill=c)
    dome(cx, hb - 165, 185, 150)
    dome(cx - 210, hb - 88, 95, 80)
    dome(cx + 210, hb - 88, 95, 80)
    for mx in (cx - mo, cx + mo):
        d.rectangle(((mx - 22) * k, (hb - 300) * k, (mx + 22) * k, h), fill=c)
        d.rectangle(((mx - 32) * k, (hb - 230) * k, (mx + 32) * k, (hb - 218) * k), fill=c)
        d.polygon([((mx - 24) * k, (hb - 300) * k), (mx * k, (hb - 360) * k), ((mx + 24) * k, (hb - 300) * k)], fill=c)
    # windows
    for wx in range(cx - 180, cx + 200, 70):
        d.rounded_rectangle(((wx - 14) * k, (hb - 130) * k, (wx + 14) * k, (hb - 70) * k), radius=14 * k, fill=win + (200,))
    for wx in (cx - 430, cx - 290, cx + 290, cx + 430):
        d.rounded_rectangle(((wx - 10) * k, (hb - 65) * k, (wx + 10) * k, (hb - 25) * k), radius=10 * k, fill=win + (170,))
    out = down(img, 2)
    full = Image.new("RGBA", (W, H), (0, 0, 0, 0))
    full.alpha_composite(add_glow(out, win, 18, 0.25, pad=0), (0, top))
    return full


def lantern(scale=1.0, body=(250, 190, 90)):
    w, h = int(120 * scale), int(230 * scale)
    img, d = hi_canvas(w, h, 2)
    k = 2 * scale
    cx = w  # centre in hi-res
    gold = GOLD + (255,)
    d.polygon([(cx - 30 * k, 40 * k), (cx, 10 * k), (cx + 30 * k, 40 * k)], fill=gold)
    d.ellipse((cx - 8 * k, 0, cx + 8 * k, 16 * k), outline=gold, width=int(3 * k))
    d.rectangle((cx - 38 * k, 40 * k, cx + 38 * k, 50 * k), fill=gold)
    body_poly = [(cx - 36 * k, 50 * k), (cx + 36 * k, 50 * k), (cx + 48 * k, 110 * k), (cx + 36 * k, 170 * k),
                 (cx - 36 * k, 170 * k), (cx - 48 * k, 110 * k)]
    d.polygon(body_poly, fill=body + (235,), outline=gold, width=int(4 * k))
    d.line([(cx, 50 * k), (cx, 170 * k)], fill=gold, width=int(3 * k))
    d.line([(cx - 48 * k, 110 * k), (cx + 48 * k, 110 * k)], fill=gold, width=int(3 * k))
    d.polygon([(cx - 30 * k, 170 * k), (cx + 30 * k, 170 * k), (cx, 205 * k)], fill=gold)
    d.line([(cx, 205 * k), (cx, 228 * k)], fill=gold, width=int(3 * k))
    return add_glow(down(img, 2), body, 22, 0.9)


# ---------------------------------------------------------------- emblems (static RGBA ~520px)
S = 520


def em_crescent(color=GOLD):
    img, d = hi_canvas(S, S)
    k = 2
    d.ellipse(((S * .14) * k, (S * .14) * k, (S * .86) * k, (S * .86) * k), fill=color + (255,))
    d.ellipse(((S * .30) * k, (S * .06) * k, (S * .96) * k, (S * .72) * k), fill=(0, 0, 0, 0))
    d.polygon([(x * k, y * k) for x, y in star_pts(S * .70, S * .47, S * .12)], fill=color + (255,))
    return add_glow(down(img), color, 28, 1.2)


def em_tree():
    img, d = hi_canvas(S, S)
    k = 2
    greens = [(20, 110, 60), (25, 130, 70), (35, 150, 80)]
    tiers = [(470, 300, 230), (360, 240, 175), (260, 180, 120)]
    for (by, hw, hgt), g in zip(tiers, greens):
        d.polygon([((S / 2 - hw / 1.3) * k, by * k), ((S / 2 + hw / 1.3) * k, by * k), (S / 2 * k, (by - hgt) * k)], fill=g + (255,))
    d.rectangle(((S / 2 - 22) * k, 470 * k, (S / 2 + 22) * k, 510 * k), fill=(110, 70, 40, 255))
    # garlands
    for by, hw in ((420, 150), (320, 110), (225, 70)):
        d.arc(((S / 2 - hw) * k, (by - 50) * k, (S / 2 + hw) * k, (by + 25) * k), 20, 160, fill=GOLD + (255,), width=5 * k)
    rng = np.random.default_rng(3)
    cols = [(230, 50, 60), (250, 200, 80), (80, 170, 250), (240, 240, 255), (240, 120, 200)]
    for _ in range(26):
        t = rng.uniform(0.15, 1)
        y = 140 + t * 320
        hw = t * 165
        x = S / 2 + rng.uniform(-hw, hw)
        c = cols[rng.integers(len(cols))]
        d.ellipse(((x - 10) * k, (y - 10) * k, (x + 10) * k, (y + 10) * k), fill=c + (255,))
        d.ellipse(((x - 4) * k, (y - 6) * k, (x + 0) * k, (y - 2) * k), fill=(255, 255, 255, 200))
    d.polygon([(x * k, y * k) for x, y in star_pts(S / 2, 72, 50)], fill=GOLD + (255,))
    return add_glow(down(img), (255, 230, 150), 24, 0.8)


def em_shield():
    img, d = hi_canvas(S, S)
    k = 2
    shield = [(130, 90), (390, 90), (395, 260), (370, 360), (260, 470), (150, 360), (125, 260)]
    sp = [(x * k, y * k) for x, y in shield]
    bands = Image.new("RGBA", img.size, (0, 0, 0, 0))
    bd = ImageDraw.Draw(bands)
    bd.rectangle((0, 0, S * k, 205 * k), fill=FLAG_BLUE + (255,))
    bd.rectangle((0, 205 * k, S * k, 315 * k), fill=(250, 250, 250, 255))
    bd.rectangle((0, 315 * k, S * k, S * k), fill=FLAG_GREEN + (255,))
    bd.rectangle((0, 199 * k, S * k, 211 * k), fill=FLAG_RED + (255,))
    bd.rectangle((0, 309 * k, S * k, 321 * k), fill=FLAG_RED + (255,))
    m = Image.new("L", img.size, 0)
    ImageDraw.Draw(m).polygon(sp, fill=255)
    img.paste(bands, (0, 0), m)
    d.polygon(sp, outline=GOLD + (255,), width=12 * k)
    d.polygon([(x * k, y * k) for x, y in star_pts(260, 260, 62)], fill=GOLD + (255,), outline=(120, 80, 20, 255))
    # laurels
    for side in (-1, 1):
        for i in range(9):
            t = i / 8
            ang = math.radians(200 - t * 120) if side < 0 else math.radians(-20 + t * 120)
            r = 215
            x, y = 260 + r * math.cos(ang) * (1 if side > 0 else 1), 300 + r * math.sin(ang) * 0.95
            if side < 0:
                x = 520 - (260 + r * math.cos(math.radians(-20 + t * 120)))
                y = 300 + r * math.sin(math.radians(-20 + t * 120)) * 0.95
            x = x if side > 0 else x
            x2, y2 = x, y
            d.ellipse(((x2 - 16) * k, (y2 - 9) * k, (x2 + 16) * k, (y2 + 9) * k), fill=GOLD + (255,))
    return add_glow(down(img), GOLD, 26, 0.9)


def tulip(d, k, x0, y0, x1, y1, color, size=1.0):
    """stem from (x0,y0) to flower at (x1,y1)"""
    g = (40, 140, 60, 255)
    d.line([(x0 * k, y0 * k), (x1 * k, y1 * k)], fill=g, width=int(7 * k))
    mx, my = (x0 + x1) / 2, (y0 + y1) / 2
    d.polygon([(mx * k, my * k), ((mx - 40 * size) * k, (my - 50 * size) * k), ((mx - 5) * k, (my - 20) * k)], fill=g)
    s = 42 * size
    c = color + (255,)
    dark = tuple(int(v * 0.75) for v in color) + (255,)
    d.ellipse(((x1 - s) * k, (y1 - s * 1.1) * k, (x1 + s) * k, (y1 + s * 0.8) * k), fill=c)
    d.polygon([((x1 - s) * k, (y1 - s * 0.3) * k), ((x1 - s * 0.9) * k, (y1 - s * 1.7) * k), ((x1 - s * 0.2) * k, (y1 - s * 0.9) * k)], fill=c)
    d.polygon([((x1 + s) * k, (y1 - s * 0.3) * k), ((x1 + s * 0.9) * k, (y1 - s * 1.7) * k), ((x1 + s * 0.2) * k, (y1 - s * 0.9) * k)], fill=c)
    d.polygon([((x1 - s * 0.45) * k, (y1 - s * 0.6) * k), (x1 * k, (y1 - s * 1.8) * k), ((x1 + s * 0.45) * k, (y1 - s * 0.6) * k)], fill=dark)


def em_bouquet():
    img, d = hi_canvas(S, S)
    k = 2
    # gold "8" behind
    f = ImageFont.truetype(F_TITLE, 560 * k)
    d.text((S / 2 * k, 250 * k), "8", font=f, anchor="mm", fill=(0, 0, 0, 0), stroke_width=8 * k, stroke_fill=GOLD + (200,))
    base = (260, 480)
    heads = [(130, 210, (235, 70, 120), 0.9), (390, 210, (235, 70, 120), 0.9), (195, 150, (250, 120, 160), 1.0),
             (325, 150, (250, 120, 160), 1.0), (260, 110, (220, 30, 80), 1.1)]
    for x, y, c, s in heads:
        tulip(d, k, base[0], base[1], x, y, c, s)
    # ribbon
    d.polygon([(225 * k, 400 * k), (295 * k, 400 * k), (310 * k, 430 * k), (210 * k, 430 * k)], fill=GOLD + (255,))
    d.polygon([(250 * k, 425 * k), (205 * k, 495 * k), (235 * k, 490 * k), (262 * k, 430 * k)], fill=GOLD + (255,))
    d.polygon([(270 * k, 425 * k), (315 * k, 495 * k), (285 * k, 490 * k), (258 * k, 430 * k)], fill=GOLD + (255,))
    return add_glow(down(img), (255, 160, 200), 26, 0.8)


def em_navruz():
    img, d = hi_canvas(S, S)
    k = 2
    cx, cy = 260, 210
    for i in range(18):
        a = math.radians(i * 20)
        a1, a2 = a - 0.09, a + 0.09
        d.polygon([((cx + 120 * math.cos(a1)) * k, (cy + 120 * math.sin(a1)) * k),
                   ((cx + 195 * math.cos(a)) * k, (cy + 195 * math.sin(a)) * k),
                   ((cx + 120 * math.cos(a2)) * k, (cy + 120 * math.sin(a2)) * k)], fill=(255, 200, 70, 255))
    d.ellipse(((cx - 115) * k, (cy - 115) * k, (cx + 115) * k, (cy + 115) * k), fill=(255, 210, 90, 255))
    d.ellipse(((cx - 90) * k, (cy - 90) * k, (cx + 90) * k, (cy + 90) * k), fill=(255, 225, 130, 255))
    # sprouts (ko'kat)
    rng = np.random.default_rng(5)
    for _ in range(70):
        x = rng.uniform(140, 380)
        top = 330 - rng.uniform(20, 110) * (1 - abs(x - 260) / 200)
        bend = rng.uniform(-18, 18)
        g = (int(rng.uniform(60, 110)), int(rng.uniform(170, 220)), int(rng.uniform(60, 100)), 255)
        d.line([(x * k, 400 * k), ((x + bend / 2) * k, ((400 + top) / 2) * k), ((x + bend) * k, top * k)], fill=g, width=int(4 * k), joint="curve")
    # bowl
    d.chord((110 * k, 300 * k, 410 * k, 500 * k), 0, 180, fill=(190, 90, 50, 255))
    d.rectangle((100 * k, 388 * k, 420 * k, 404 * k), fill=GOLD + (255,))
    for x in range(150, 380, 40):
        d.polygon([(x * k, 425 * k), ((x + 14) * k, 440 * k), (x * k, 455 * k), ((x - 14) * k, 440 * k)], fill=GOLD + (255,))
    return add_glow(down(img), (255, 220, 120), 30, 0.9)


def em_flame_base():
    img, d = hi_canvas(S, S)
    k = 2
    # bronze bowl + star
    d.polygon([(150 * k, 380 * k), (370 * k, 380 * k), (330 * k, 440 * k), (190 * k, 440 * k)], fill=(170, 120, 60, 255))
    d.rectangle((140 * k, 370 * k, 380 * k, 385 * k), fill=GOLD + (255,))
    d.rectangle((225 * k, 440 * k, 295 * k, 480 * k), fill=(140, 95, 45, 255))
    d.rectangle((180 * k, 478 * k, 340 * k, 495 * k), fill=GOLD + (255,))
    # tulips on sides (red)
    for x0, x1, y1 in ((95, 60, 330), (130, 110, 290), (425, 460, 330), (390, 410, 290)):
        tulip(d, k, x0, 500, x1, y1, (200, 20, 35), 0.65)
    return down(img)


def draw_flame(t):
    w, h = 300, 330
    img, d = hi_canvas(w, h)
    k = 2
    layers = [((230, 60, 20), 1.0), ((255, 140, 30), 0.72), ((255, 225, 120), 0.42)]
    for (col, s) in layers:
        pts = []
        for i in range(48):
            a = i / 48 * 2 * math.pi
            # teardrop: wide at bottom, pointed top
            yv = -math.cos(a)  # -1 bottom .. 1 top
            rw = math.sin(a) * (0.55 * (1 - yv) ** 0.9) * 95 * s
            wob = 10 * s * math.sin(t * 9 + yv * 4 + s * 3) * (yv + 1) / 2
            x = w / 2 + rw + wob
            y = h - 40 - (yv + 1) / 2 * (250 * s * (1 + 0.06 * math.sin(t * 13 + s * 5)))
            pts.append((x * k, y * k))
        d.polygon(pts, fill=col + (235,))
    return add_glow(down(img), (255, 120, 40), 22, 1.0)


def make_flag(w=600, h=300):
    img, d = hi_canvas(w, h)
    k = 2
    d.rectangle((0, 0, w * k, h / 3 * k), fill=FLAG_BLUE + (255,))
    d.rectangle((0, h / 3 * k, w * k, 2 * h / 3 * k), fill=(250, 250, 250, 255))
    d.rectangle((0, 2 * h / 3 * k, w * k, h * k), fill=FLAG_GREEN + (255,))
    for yy in (h / 3, 2 * h / 3):
        d.rectangle((0, (yy - 4) * k, w * k, (yy + 4) * k), fill=FLAG_RED + (255,))
    # crescent
    cx, cy, r = 70, 50, 34
    d.ellipse(((cx - r) * k, (cy - r) * k, (cx + r) * k, (cy + r) * k), fill=(255, 255, 255, 255))
    d.ellipse(((cx - r + 13) * k, (cy - r - 2) * k, (cx + r + 13) * k, (cy + r - 2) * k), fill=FLAG_BLUE + (255,))
    # 12 stars: rows of 3,4,5
    for row, n in enumerate((3, 4, 5)):
        for i in range(n):
            sx = 210 - (n - 1 - i) * 28 + (5 - n) * 0
            sx = 105 + (5 - n) * 28 + i * 28
            sy = 22 + row * 28
            d.polygon([(x * k, y * k) for x, y in star_pts(sx, sy, 10)], fill=(255, 255, 255, 255))
    return down(img)


def wave_flag(flag, t):
    arr = np.asarray(flag).astype(np.float32)
    h, w = arr.shape[:2]
    A = 18
    out = np.zeros((h + 2 * A, w, 4), np.float32)
    xs = np.arange(w)
    ph = 2 * math.pi * xs / 260 - t * 4.5
    amp = A * (xs / w) ** 0.8
    off = (amp * np.sin(ph)).astype(int) + A
    shade = 1 + 0.18 * np.cos(ph) * (xs / w)
    for x in range(w):
        col = arr[:, x, :].copy()
        col[:, :3] *= shade[x]
        out[off[x]:off[x] + h, x, :] = col
    out = np.clip(out, 0, 255).astype(np.uint8)
    flg = Image.fromarray(out, "RGBA")
    canvas = Image.new("RGBA", (w + 40, h + 2 * A + 160), (0, 0, 0, 0))
    cd = ImageDraw.Draw(canvas)
    cd.rounded_rectangle((8, 0, 22, canvas.height), radius=6, fill=GOLD + (255,))
    cd.ellipse((2, -6, 28, 20), fill=GOLD + (255,))
    canvas.alpha_composite(flg, (24, 14))
    return canvas


def em_book_open():
    img, d = hi_canvas(S, S)
    k = 2
    # light rays
    for i in range(9):
        a = math.radians(-150 + i * 15)
        a1, a2 = a - 0.05, a + 0.05
        d.polygon([(260 * k, 330 * k), ((260 + 300 * math.cos(a1)) * k, (330 + 300 * math.sin(a1)) * k),
                   ((260 + 300 * math.cos(a2)) * k, (330 + 300 * math.sin(a2)) * k)], fill=(255, 225, 140, 70))
    # cover
    d.polygon([(40 * k, 330 * k), (260 * k, 370 * k), (480 * k, 330 * k), (480 * k, 360 * k), (260 * k, 400 * k), (40 * k, 360 * k)], fill=(120, 40, 50, 255))
    for side in (-1, 1):
        pts = []
        for i in range(21):
            t = i / 20
            x = 260 + side * t * 210
            y = 360 - 40 * math.sin(t * math.pi * 0.9) + 0 * t
            pts.append((x, y - 20))
        pts += [(260 + side * 210, 330), (260, 370)]
        poly = [(x * k, y * k) for x, y in pts[:21]] + [((260 + side * 210) * k, 345 * k), (260 * k, 380 * k)]
        top = [(x, y - 150) for x, y in pts[:21]]
        page = [(x * k, y * k) for x, y in top] + list(reversed([(x * k, y * k) for x, y in pts[:21]]))
        d.polygon(page, fill=(252, 246, 230, 255), outline=(200, 170, 120, 255))
        for j in range(6):
            line = [(x * k, (y + 25 + j * 20) * k) for x, y in top[3:19]]
            d.line(line, fill=(170, 160, 140, 255), width=int(3 * k))
    d.line([(260 * k, 190 * k), (260 * k, 360 * k)], fill=(200, 170, 120, 255), width=4 * k)
    # sparkles
    for x, y, r in ((150, 120, 18), (380, 100, 22), (260, 60, 26), (90, 200, 12), (440, 190, 14)):
        d.polygon([(px * k, py * k) for px, py in star_pts(x, y, r, r * 0.25, 4)], fill=(255, 240, 180, 255))
    return add_glow(down(img), (255, 220, 140), 26, 0.8)


def em_constitution():
    img, d = hi_canvas(S, S)
    k = 2
    x0, y0, x1, y1 = 140, 50, 400, 450
    d.rounded_rectangle(((x0 - 22) * k, (y0 + 8) * k, (x0 + 20) * k, (y1 + 8) * k), radius=10 * k, fill=(10, 40, 90, 255))
    d.rounded_rectangle((x0 * k, y0 * k, x1 * k, y1 * k), radius=12 * k, fill=(20, 70, 140, 255))
    d.rounded_rectangle(((x0 + 18) * k, (y0 + 18) * k, (x1 - 18) * k, (y1 - 18) * k), radius=8 * k, outline=GOLD + (255,), width=4 * k)
    cx, cy = (x0 + x1) / 2, 190
    for i in range(16):
        a = math.radians(i * 22.5)
        d.line([((cx + 50 * math.cos(a)) * k, (cy + 50 * math.sin(a)) * k), ((cx + 72 * math.cos(a)) * k, (cy + 72 * math.sin(a)) * k)], fill=GOLD + (255,), width=4 * k)
    d.ellipse(((cx - 45) * k, (cy - 45) * k, (cx + 45) * k, (cy + 45) * k), outline=GOLD + (255,), width=5 * k)
    d.polygon([(x * k, y * k) for x, y in star_pts(cx, cy, 30)], fill=GOLD + (255,))
    f = ImageFont.truetype(F_SUB, 24 * k)
    d.text((cx * k, 310 * k), "KONSTITUTSIYA", font=f, anchor="mm", fill=GOLD + (255,))
    f2 = ImageFont.truetype(F_BODY, 17 * k)
    d.text((cx * k, 345 * k), "O‘ZBEKISTON RESPUBLIKASI", font=f2, anchor="mm", fill=GOLD + (230,))
    # ribbon bookmark (flag colours)
    for i, c in enumerate((FLAG_BLUE, (250, 250, 250), FLAG_GREEN)):
        d.rectangle(((330 + i * 12) * k, (y1 - 10) * k, (342 + i * 12) * k, (y1 + 50) * k), fill=c + (255,))
    d.polygon([(330 * k, (y1 + 50) * k), (366 * k, (y1 + 50) * k), (348 * k, (y1 + 35) * k)], fill=(0, 0, 0, 0))
    return add_glow(down(img), GOLD, 26, 0.8)


# ---------------------------------------------------------------- particles
class Particles:
    def __init__(self, kinds, palette, seed):
        self.kinds = kinds
        self.rng = np.random.default_rng(seed)
        r = self.rng
        self.palette = palette
        self.snow = [(r.uniform(0, W), r.uniform(0, H), r.uniform(50, 140), r.uniform(2, 6), r.uniform(0, 6.28), r.uniform(0.5, 1.5)) for _ in range(170)]
        self.petals = [(r.uniform(0, W), r.uniform(0, H), r.uniform(60, 130), r.uniform(12, 24), r.uniform(0, 6.28), r.uniform(1, 3),
                        palette[r.integers(len(palette))]) for _ in range(45)]
        self.stars = [(r.uniform(60, W - 60), r.uniform(60, H * 0.62), r.uniform(1, 3.2), r.uniform(0, 6.28), r.uniform(1, 3)) for _ in range(130)]
        self.sparks = [(r.uniform(60, W - 60), r.uniform(0, 1), r.uniform(0.04, 0.09), r.uniform(4, 9), r.uniform(0, 6.28)) for _ in range(70)]
        self.confetti = [(r.uniform(0, W), r.uniform(0, H), r.uniform(120, 230), r.uniform(10, 18), r.uniform(0, 6.28), r.uniform(2, 5),
                          palette[r.integers(len(palette))]) for _ in range(90)]
        self.bokeh = [(r.uniform(0, W), r.uniform(0, H), r.uniform(80, 200), r.uniform(-15, 15), r.uniform(-25, -5)) for _ in range(14)]
        bcol = palette[0]
        self.bokeh_sprites = {s: glow_sprite(s, bcol, 55) for s in (80, 120, 160, 200)}
        self.spark_sprite = glow_sprite(36, palette[0], 255)
        self.bursts = []
        times = kinds.get("fireworks", [])
        for i, t0 in enumerate(times):
            n = 70
            ang = r.uniform(0, 6.28, n)
            spd = r.uniform(180, 330, n)
            self.bursts.append((t0, r.uniform(200, W - 200), r.uniform(180, 760), palette[i % len(palette)], ang, spd))
        self.flash = glow_sprite(260, (255, 240, 210), 160)

    def draw(self, frame, t):
        ov = Image.new("RGBA", (W, H), (0, 0, 0, 0))
        d = ImageDraw.Draw(ov)
        k = self.kinds
        if "bokeh" in k:
            for x, y, s, vx, vy in self.bokeh:
                sz = min(self.bokeh_sprites, key=lambda q: abs(q - s))
                put(ov, self.bokeh_sprites[sz], (x + vx * t) % W, (y + vy * t) % H)
        if "stars" in k:
            for x, y, s, ph, f in self.stars:
                a = 0.35 + 0.65 * math.sin(t * f + ph) ** 2
                c = (255, 248, 220, int(255 * a))
                d.ellipse((x - s, y - s, x + s, y + s), fill=c)
                if s > 2.6:
                    L = s * 5 * a
                    d.line([(x - L, y), (x + L, y)], fill=c, width=1)
                    d.line([(x, y - L), (x, y + L)], fill=c, width=1)
            for t0, x0, y0 in ((3.0, 250, 150), (9.5, 900, 260), (15.5, 400, 120)):
                dt = t - t0
                if 0 <= dt < 0.9:
                    p = dt / 0.9
                    x, y = x0 + 520 * p * (-1 if x0 > 540 else 1), y0 + 260 * p
                    for j in range(14):
                        q = j / 14
                        xx = x - 60 * q * (-1 if x0 > 540 else 1) * 2
                        yy = y - 30 * q * 2
                        al = int(255 * (1 - q) * (1 - p))
                        d.ellipse((xx - 3 * (1 - q), yy - 3 * (1 - q), xx + 3 * (1 - q), yy + 3 * (1 - q)), fill=(255, 250, 230, al))
        if "snow" in k:
            for x, y, v, s, ph, f in self.snow:
                yy = (y + v * t) % (H + 20) - 10
                xx = x + 30 * math.sin(t * f + ph)
                d.ellipse((xx - s, yy - s, xx + s, yy + s), fill=(255, 255, 255, 210))
        if "petals" in k:
            for x, y, v, s, ph, f, c in self.petals:
                yy = (y + v * t) % (H + 40) - 20
                xx = (x + 40 * t * 0.6 + 50 * math.sin(t * 0.8 + ph)) % (W + 40) - 20
                wv = s * (0.25 + 0.75 * abs(math.cos(t * f + ph)))
                d.ellipse((xx - wv, yy - s * 0.6, xx + wv, yy + s * 0.6), fill=c + (220,))
        if "confetti" in k:
            for x, y, v, s, ph, f, c in self.confetti:
                yy = (y + v * t) % (H + 40) - 20
                xx = x + 35 * math.sin(t * 1.3 + ph)
                wv = s * abs(math.cos(t * f + ph)) + 1
                d.polygon([(xx - wv, yy - s * 0.4), (xx + wv, yy - s * 0.2), (xx + wv, yy + s * 0.4), (xx - wv, yy + s * 0.2)], fill=c + (235,))
        if "sparks" in k:
            for x, p0, v, s, ph in self.sparks:
                p = (p0 + v * t) % 1.0
                yy = H + 20 - p * (H + 40)
                xx = x + 25 * math.sin(t * 1.5 + ph)
                a = math.sin(p * math.pi) * (0.6 + 0.4 * math.sin(t * 6 + ph))
                put(ov, with_alpha(self.spark_sprite, max(0, a)), xx, yy)
                d.ellipse((xx - s / 4, yy - s / 4, xx + s / 4, yy + s / 4), fill=(255, 250, 230, int(220 * max(0, a))))
        for t0, cx, cy, c, ang, spd in self.bursts:
            dt = t - t0
            if dt < -0.6 or dt > 2.2:
                continue
            if dt < 0:  # rocket rising
                p = (dt + 0.6) / 0.6
                ry = H - 200 - (H - 200 - cy) * ease_out(p)
                d.ellipse((cx - 4, ry - 4, cx + 4, ry + 4), fill=(255, 240, 200, 255))
                d.line([(cx, ry), (cx, ry + 70)], fill=(255, 220, 160, 120), width=3)
                continue
            if dt < 0.25:
                put(ov, self.flash, cx, cy, 1 - dt / 0.25)
            rr = (1 - math.exp(-2.6 * dt)) / 2.6
            fall = 70 * dt * dt
            a = (1 - dt / 2.2) ** 1.4
            for an, sp in zip(ang, spd):
                x = cx + math.cos(an) * sp * rr * 2.2
                y = cy + math.sin(an) * sp * rr * 2.2 + fall
                tw = 0.6 + 0.4 * math.sin(dt * 30 + an * 7)
                al = int(255 * a * tw)
                d.ellipse((x - 7, y - 7, x + 7, y + 7), fill=c + (int(al * 0.25),))
                d.ellipse((x - 3, y - 3, x + 3, y + 3), fill=tuple(min(255, v + 80) for v in c) + (al,))
        frame.alpha_composite(ov)


# ---------------------------------------------------------------- audio
def note(freq, dur, sr=SR, kind="bell"):
    t = np.arange(int(dur * sr)) / sr
    if kind == "bell":
        env = np.exp(-t * 3.2) * np.minimum(1, t * 200)
        s = (np.sin(2 * np.pi * freq * t) + 0.35 * np.sin(2 * np.pi * freq * 2.01 * t) * np.exp(-t * 5)
             + 0.15 * np.sin(2 * np.pi * freq * 3.98 * t) * np.exp(-t * 8))
    elif kind == "piano":
        env = np.exp(-t * 2.2) * np.minimum(1, t * 300)
        s = sum((0.6 ** h) * np.sin(2 * np.pi * freq * (h + 1) * t) for h in range(5))
    return s * env


def pad(freqs, dur, sr=SR):
    t = np.arange(int(dur * sr)) / sr
    s = np.zeros_like(t)
    for f in freqs:
        for det in (-0.6, 0, 0.7):
            s += np.sin(2 * np.pi * (f + det) * t + det)
    att = np.minimum(1, t / 0.8) * np.minimum(1, (dur - t) / 0.8)
    return s * att / (len(freqs) * 3)


def midi(m):
    return 440 * 2 ** ((m - 69) / 12)


def synth_music(cfg, boom_times=()):
    n = int(DUR * SR)
    L, R = np.zeros(n), np.zeros(n)
    root = cfg["root"]
    minor = cfg.get("minor", False)
    prog = cfg.get("prog") or ([0, 8, 3, 10] if minor else [0, 7, 9, 5])
    chord_len = cfg.get("chord", 2.5)
    beat = 60 / cfg["bpm"]
    kind = cfg.get("inst", "bell")
    i = 0
    tpos = 0.0
    while tpos < DUR - 0.5:
        deg = prog[i % len(prog)]
        r = root + deg
        is_minor = (deg in (9, 2, 4)) if not minor else (deg in (0, 5, 7))
        third = 3 if is_minor else 4
        tri = [r, r + third, r + 7]
        p = pad([midi(m - 12) for m in tri] + [midi(r - 24)], chord_len + 0.4) * 0.22
        s0 = int(tpos * SR)
        e0 = min(n, s0 + len(p))
        L[s0:e0] += p[:e0 - s0]
        R[s0:e0] += p[:e0 - s0]
        # arpeggio
        pattern = [tri[0] + 12, tri[1] + 12, tri[2] + 12, tri[1] + 24, tri[2] + 12, tri[1] + 12]
        nt = int(chord_len / (beat / 2))
        for j in range(nt):
            if tpos < 1.0 and j % 2:
                continue
            m = pattern[j % len(pattern)]
            sn = note(midi(m), 1.6, kind=kind) * (0.16 if j % 2 == 0 else 0.11)
            st = int((tpos + j * beat / 2) * SR)
            en = min(n, st + len(sn))
            pan = 0.5 + 0.3 * math.sin(j)
            L[st:en] += sn[:en - st] * (1 - pan) * 1.4
            R[st:en] += sn[:en - st] * pan * 1.4
        tpos += chord_len
        i += 1
    rng = np.random.default_rng(1)
    if cfg.get("jingle"):
        for b in np.arange(1.0, DUR - 1, beat):
            nn = int(0.12 * SR)
            ns = rng.normal(0, 1, nn) * np.exp(-np.arange(nn) / SR * 30)
            ns = np.diff(np.concatenate([[0], ns]))  # brighten
            st = int(b * SR)
            L[st:st + nn] += ns * 0.05
            R[st:st + nn] += ns * 0.05
    for bt in boom_times:
        nn = int(1.4 * SR)
        tt = np.arange(nn) / SR
        boom = np.sin(2 * np.pi * (55 + 30 * np.exp(-tt * 8)) * tt) * np.exp(-tt * 4) * 0.5
        crack = rng.normal(0, 1, nn) * np.exp(-tt * 2.5) * (rng.random(nn) > 0.995) * 0.6
        st = int(bt * SR)
        en = min(n, st + nn)
        L[st:en] += (boom + crack)[:en - st]
        R[st:en] += (boom + crack * 0.7)[:en - st]
    # reverb
    ir_len = int(1.6 * SR)
    tt = np.arange(ir_len) / SR
    out = []
    for ch, seed in ((L, 7), (R, 8)):
        ir = np.random.default_rng(seed).normal(0, 1, ir_len) * np.exp(-tt * 3.5)
        ir[0] = 0
        nfft = 1 << int(np.ceil(np.log2(n + ir_len)))
        wet = np.fft.irfft(np.fft.rfft(ch, nfft) * np.fft.rfft(ir, nfft), nfft)[:n]
        wet /= np.max(np.abs(wet)) + 1e-9
        dry = ch / (np.max(np.abs(ch)) + 1e-9)
        out.append(dry * 0.75 + wet * 0.35)
    st = np.stack(out, 1)
    tt = np.arange(n) / SR
    env = np.minimum(1, tt / 1.0) * np.minimum(1, (DUR - tt) / 1.8)
    st *= env[:, None]
    st /= np.max(np.abs(st)) + 1e-9
    return (st * 0.85 * 32767).astype(np.int16)


def write_wav(path, data):
    with wave.open(path, "wb") as w:
        w.setnchannels(2)
        w.setsampwidth(2)
        w.setframerate(SR)
        w.writeframes(data.tobytes())


# ---------------------------------------------------------------- themes
ISLAMIC = dict(pattern=True, mosque=True, music=None)
THEMES = {
    "01_juma": dict(ISLAMIC, bg=((8, 52, 48), (2, 16, 20), (255, 210, 120)), emblem="crescent",
                   title=["Juma muborak!"], sub="Muborak juma ayyomi",
                   body=["Alloh duolaringizni ijobat,", "qalbingizni iymon nuriga,", "xonadoningizni baraka va", "tinchlikka to‘ldirsin!"],
                   final="Juma ayyomingiz muborak bo‘lsin!", particles={"stars": 1, "bokeh": 1, "sparks": 1}, palette=[GOLD]),
    "02_yangi_yil": dict(bg=((14, 30, 80), (4, 6, 26), (130, 170, 255)), emblem="tree",
                         title=["Yangi yil", "muborak!"], sub="1-yanvar",
                         body=["Yangi yil xonadoningizga", "baxt, sog‘lik va omad", "olib kelsin. Barcha ezgu", "orzularingiz ushalsin!"],
                         final="Yangi yilingiz bilan!", particles={"snow": 1, "bokeh": 1, "fireworks": [13.4, 14.4, 15.3, 16.2, 17.0]},
                         palette=[(255, 210, 120), (255, 90, 110), (120, 200, 255), (180, 255, 160)],
                         music=dict(root=72, bpm=112, jingle=True)),
    "03_vatan_himoyachilari": dict(bg=((28, 52, 38), (6, 14, 12), (255, 220, 140)), emblem="shield",
                                   title=["Vatan", "himoyachilari kuni"], sub="14-yanvar",
                                   body=["Tinchligimiz posbonlari —", "mard va jasur himoyachilarga", "mustahkam sog‘lik, sharaf", "va zafarlar tilaymiz!"],
                                   final="Bayramingiz muborak, qahramonlar!", particles={"sparks": 1, "bokeh": 1, "fireworks": [14.0, 15.0, 16.0]},
                                   palette=[GOLD, FLAG_BLUE, (255, 255, 255), FLAG_GREEN],
                                   music=dict(root=67, bpm=96, prog=[0, 5, 7, 0], chord=2.5)),
    "04_8_mart": dict(bg=((130, 24, 78), (40, 6, 32), (255, 150, 200)), emblem="bouquet",
                      title=["8-Mart"], sub="Xotin-qizlar kuni",
                      body=["Aziz onalar, opa-singillar,", "mehribon ayollar! Hayotingiz", "gullar kabi go‘zal, qalbingiz", "doimo bahor bo‘lsin!"],
                      final="Bayramingiz muborak!", particles={"petals": 1, "bokeh": 1},
                      palette=[(255, 170, 200), (255, 120, 170), (255, 220, 230), (250, 90, 140)],
                      music=dict(root=74, bpm=100)),
    "05_navruz": dict(bg=((26, 104, 70), (6, 38, 34), (255, 225, 130)), emblem="navruz",
                      title=["Navro‘z", "muborak!"], sub="21-mart · Bahor bayrami",
                      body=["Bahor ayyomi dasturxoningizga", "to‘kinlik, xonadoningizga", "fayz va baraka, qalbingizga", "quvonch olib kelsin!"],
                      final="Navro‘zi olam muborak!", particles={"petals": 1, "bokeh": 1},
                      palette=[(255, 250, 245), (255, 205, 220), (255, 235, 240)],
                      music=dict(root=69, bpm=108)),
    "06_xotira_va_qadrlash": dict(bg=((44, 14, 12), (6, 3, 3), (255, 120, 60)), emblem="flame",
                                  title=["Xotira va", "qadrlash kuni"], sub="9-may",
                                  body=["Vatan uchun jon fido qilganlar", "xotirasi qalbimizda abadiy.", "Keksalarimizni qadrlaylik,", "duolari bilan yashaylik."],
                                  final="Xotira — abadiy, qadr — muqaddas", particles={"sparks": 1},
                                  palette=[(255, 140, 50)], music=dict(root=62, bpm=70, minor=True, inst="piano", chord=2.5)),
    "07_mustaqillik": dict(bg=((0, 72, 120), (0, 14, 42), (110, 210, 255)), emblem="flag",
                           title=["Mustaqillik", "kuni muborak!"], sub="1-sentabr",
                           body=["Ozod va obod Vatanimiz", "bundan-da gullab-yashnasin!", "Yurtimiz tinch, osmonimiz", "doimo musaffo bo‘lsin!"],
                           final="Bayramingiz muborak, vatandoshlar!", particles={"confetti": 1, "fireworks": [1.5, 2.6, 12.8, 13.8, 14.8, 15.8, 16.8]},
                           palette=[FLAG_BLUE, (255, 255, 255), FLAG_GREEN, GOLD, (240, 70, 80)],
                           music=dict(root=67, bpm=116)),
    "08_oqituvchilar": dict(bg=((60, 30, 96), (16, 8, 34), (255, 205, 130)), emblem="book",
                            title=["O‘qituvchi va", "murabbiylar kuni"], sub="1-oktabr",
                            body=["Qalbimizga ilm nurini sochgan", "aziz ustozlarimiz! Mashaqqatli", "mehnatingiz uchun chuqur", "minnatdorchilik bildiramiz!"],
                            final="Bayramingiz muborak, ustozlar!", particles={"sparks": 1, "stars": 1, "bokeh": 1},
                            palette=[GOLD], music=dict(root=65, bpm=96)),
    "09_konstitutsiya": dict(bg=((12, 36, 92), (3, 10, 32), (255, 215, 130)), emblem="constitution",
                             title=["Konstitutsiya", "kuni"], sub="8-dekabr",
                             body=["Asosiy Qomusimiz — erkinlik,", "adolat va farovonlik kafolati.", "Yurtimiz tinch, xalqimiz", "farovon bo‘lsin!"],
                             final="Bayramingiz muborak!", particles={"confetti": 1, "sparks": 1, "fireworks": [14.5, 15.6, 16.6]},
                             palette=[FLAG_BLUE, (255, 255, 255), FLAG_GREEN, GOLD],
                             music=dict(root=70, bpm=100, prog=[0, 5, 9, 7])),
    "10_qurbon_hayiti": dict(ISLAMIC, bg=((10, 60, 62), (2, 16, 24), (255, 215, 130)), emblem="crescent", lanterns=True,
                             title=["Qurbon hayiti", "muborak!"], sub="Iyd al-Adho",
                             body=["Qilgan ibodatlaringiz va", "qurbonliklaringiz qabul bo‘lsin.", "Xonadoningizdan fayz va", "baraka arimasin!"],
                             final="Hayit ayyomingiz muborak!", particles={"stars": 1, "sparks": 1}, palette=[GOLD]),
    "11_ramazon_hayiti": dict(ISLAMIC, bg=((40, 20, 78), (8, 5, 26), (255, 205, 130)), emblem="crescent", lanterns=True,
                              title=["Ramazon hayiti", "muborak!"], sub="Iyd al-Fitr",
                              body=["Tutgan ro‘zalaringiz va", "qilgan duolaringiz qabul bo‘lsin.", "Qalbingiz iymon nuri bilan", "doimo munavvar bo‘lsin!"],
                              final="Hayit ayyomingiz muborak!", particles={"stars": 1, "sparks": 1}, palette=[GOLD]),
}

EMBLEMS = {"crescent": em_crescent, "tree": em_tree, "shield": em_shield, "bouquet": em_bouquet, "navruz": em_navruz,
           "flame": em_flame_base, "book": em_book_open, "constitution": em_constitution}


def divider(color=GOLD, w=560):
    img, d = hi_canvas(w, 40)
    k = 2
    d.line([(0, 20 * k), ((w / 2 - 30) * k, 20 * k)], fill=color + (220,), width=2 * k)
    d.line([((w / 2 + 30) * k, 20 * k), (w * k, 20 * k)], fill=color + (220,), width=2 * k)
    cx = w / 2
    d.polygon([((cx - 18) * k, 20 * k), (cx * k, 6 * k), ((cx + 18) * k, 20 * k), (cx * k, 34 * k)], fill=color + (255,))
    for s in (-1, 1):
        d.ellipse(((cx + s * 30 - 4) * k, 16 * k, (cx + s * 30 + 4) * k, 24 * k), fill=color + (255,))
    return down(img)


def render(name, out_dir, tmp_dir):
    th = THEMES[name]
    top, bot, gl = th["bg"]
    wide = W > H
    EX, TX = (520, 1250) if wide else (W / 2, W / 2)
    em_y = (340 if th.get("mosque") else 420) if wide else 430
    bg = gradient_bg(top, bot, gl, glow_y=em_y, glow_x=EX)
    if th.get("pattern"):
        bg.alpha_composite(geometric_pattern())
    if th.get("mosque"):
        mcol = tuple(max(0, int(v * 0.45)) for v in bot)
        bg.alpha_composite(mosque_silhouette(mcol, cx=480, mo=300) if wide else mosque_silhouette(mcol))
    if th.get("lanterns"):
        lx = (90, 200) if wide else (150, 250)
        for x, length, s in ((lx[0], 120, 0.9), (lx[1], 40, 0.7), (W - lx[0], 120, 0.9), (W - lx[1], 40, 0.7)):
            ImageDraw.Draw(bg).line([(x, 48), (x, 48 + length)], fill=GOLD + (200,), width=3)
            ln = lantern(s)
            bg.alpha_composite(ln, (int(x - ln.width / 2), int(48 + length - 24 * s)))
    bg.alpha_composite(ornament_frame())

    em_kind = th["emblem"]
    emblem = EMBLEMS[em_kind]() if em_kind in EMBLEMS else None
    flag = make_flag() if em_kind == "flag" else None

    # text layers
    ts, ss, bs, fs, mw = (92, 42, 42, 58, 800) if wide else (104, 48, 52, 66, 900)
    title_imgs = [text_img(t, F_TITLE, ts, GOLD, glow=(255, 190, 90), gradient=((255, 236, 170), (220, 160, 60)), maxw=mw - 20) for t in th["title"]]
    sub_img = text_img(th["sub"], F_SUB, ss, CREAM, maxw=mw - 40)
    body_imgs = [text_img(t, F_BODY, bs, CREAM, maxw=mw) for t in th["body"]]
    final_img = text_img(th["final"], F_FINAL, fs, GOLD, glow=(255, 200, 100), gradient=((255, 240, 180), (235, 175, 70)), maxw=mw)
    div = divider()
    shimmer_mask = None

    parts = Particles(th["particles"], th["palette"], seed=sum(map(ord, name)))

    # layout
    if wide:
        ty0 = 190 if len(title_imgs) == 2 else 240
        title_ys = [ty0 + i * 100 for i in range(len(title_imgs))]
        sub_y = title_ys[-1] + 88
        div_y = sub_y + 58
        body_y0 = div_y + 68
        body_ys = [body_y0 + i * 64 for i in range(len(body_imgs))]
        final_y = body_ys[-1] + 108
    else:
        ty0 = 760 if len(title_imgs) == 2 else 800
        title_ys = [ty0 + i * 112 for i in range(len(title_imgs))]
        sub_y = title_ys[-1] + 100
        div_y = sub_y + 70
        body_y0 = div_y + 85
        body_ys = [body_y0 + i * 80 for i in range(len(body_imgs))]
        final_y = body_ys[-1] + 140
        if th.get("mosque"):
            final_y = min(final_y, 1510)

    # audio
    wav = os.path.join(tmp_dir, name + ".wav")
    mus = th.get("music")
    if mus:
        write_wav(wav, synth_music(mus, boom_times=th["particles"].get("fireworks", [])))
    else:
        write_wav(wav, np.zeros((int(DUR * SR), 2), np.int16))

    out = os.path.join(out_dir, name + ".mp4")
    ff = subprocess.Popen(["ffmpeg", "-y", "-loglevel", "error", "-f", "rawvideo", "-pix_fmt", "rgb24", "-s", f"{W}x{H}",
                           "-r", str(FPS), "-i", "-", "-i", wav, "-c:v", "libx264", "-preset", "medium", "-crf", "19",
                           "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "160k", "-shortest", "-movflags", "+faststart", out],
                          stdin=subprocess.PIPE)
    preview_ts = {int(FPS * x) for x in (3, 10, 16.5)}
    for fi in range(NF):
        t = fi / FPS
        frame = bg.copy()
        parts.draw(frame, t)
        # emblem
        p = (t - 0.5) / 1.4
        if p > 0:
            a = ease_out(p)
            sc = 0.55 + 0.45 * ease_back(p)
            fy = em_y + 8 * math.sin(t * 1.6)
            if flag is not None:
                im = wave_flag(flag, t)
                cx = EX + 10
            elif em_kind == "flame":
                im = emblem.copy()
                fl = draw_flame(t)
                im.alpha_composite(fl, (int(S / 2 - fl.width / 2), int(380 - fl.height + 25)))
                cx = EX
            else:
                im = emblem
                cx = EX
            if sc < 0.999:
                im = im.resize((max(1, int(im.width * sc)), max(1, int(im.height * sc))), Image.BILINEAR)
            # pulse glow halo
            put(frame, im, cx, fy, a)
        # title
        for i, (ti, ty) in enumerate(zip(title_imgs, title_ys)):
            st = 2.0 + i * 0.35
            p = (t - st) / 0.9
            if p > 0:
                img = ti
                # shimmer sweep
                for s0 in (4.0, 15.0):
                    sp = (t - s0 - i * 0.15) / 1.0
                    if 0 < sp < 1:
                        img = shimmer(ti, sp)
                put(frame, img, TX, ty + (1 - ease_out(p)) * 50, ease_out(p))
        p = (t - 3.2) / 0.8
        if p > 0:
            put(frame, sub_img, TX, sub_y + (1 - ease_out(p)) * 30, ease_out(p) * 0.95)
        p = (t - 3.8) / 1.0
        if p > 0:
            dv = div.resize((max(1, int(div.width * ease_out(p))), div.height), Image.BILINEAR)
            put(frame, dv, TX, div_y, 1)
        for i, (bi, by) in enumerate(zip(body_imgs, body_ys)):
            st = 5.2 + i * 1.3
            p = (t - st) / 1.0
            if p > 0:
                put(frame, bi, TX + (1 - ease_out(p)) * (-60 if i % 2 == 0 else 60), by, ease_out(p))
        p = (t - 11.5) / 1.2
        if p > 0:
            sc = 0.8 + 0.2 * ease_back(p)
            pulse = 1 + 0.025 * math.sin((t - 11.5) * 3) if p >= 1 else 1
            sc *= pulse
            fi_img = final_img
            sp = (t - 17.0) / 1.0
            if 0 < sp < 1:
                fi_img = shimmer(final_img, sp)
            fi_img = fi_img.resize((int(fi_img.width * sc), int(fi_img.height * sc)), Image.BILINEAR)
            put(frame, fi_img, TX, final_y, ease_out(p))
        # global fades
        fade = min(1, t / 0.7, (DUR - t) / 0.8)
        rgb = frame.convert("RGB")
        if fade < 1:
            rgb = Image.blend(Image.new("RGB", (W, H), (0, 0, 0)), rgb, max(0, fade))
        if fi in preview_ts:
            rgb.save(os.path.join(tmp_dir, f"{name}_{fi:03d}.jpg"), quality=80)
        ff.stdin.write(rgb.tobytes())
    ff.stdin.close()
    ff.wait()
    print("done", out, flush=True)


def shimmer(img, p):
    arr = np.asarray(img).astype(np.float32)
    h, w = arr.shape[:2]
    xs = np.arange(w)[None, :] + np.arange(h)[:, None] * 0.4
    center = -0.3 * w + p * 1.6 * w
    band = np.exp(-((xs - center) / (w * 0.06)) ** 2)[..., None]
    arr[..., :3] = arr[..., :3] + (255 - arr[..., :3]) * band * 0.75
    return Image.fromarray(arr.astype(np.uint8), "RGBA")


if __name__ == "__main__":
    args = sys.argv[1:]
    if "--16x9" in args:
        args.remove("--16x9")
        W, H = 1920, 1080
    out_dir, tmp_dir = args[0], args[1]
    names = args[2:] or list(THEMES)
    for n in names:
        render(n, out_dir, tmp_dir)
