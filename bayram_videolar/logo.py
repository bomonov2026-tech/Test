#!/usr/bin/env python3
"""TartibOn logotipini yaratadi va videolarning o'ng pastki burchagiga qo'yadi.

Foydalanish: python3 logo.py video1.mp4 [video2.mp4 ...]
Haqiqiy logotip fayli bo'lsa: LOGO=/yo'l/logo.png python3 logo.py ...
"""
import json, os, subprocess, sys, tempfile
from PIL import Image, ImageDraw, ImageFilter, ImageFont

FD = "/usr/share/fonts/truetype/dejavu/"
GOLD_TOP, GOLD_BOT = (255, 226, 150), (222, 160, 60)
CREAM = (255, 250, 238)


def make_logo(height):
    """Ikonka + "TartibOn" yozuvi, berilgan balandlikda (shaffof fon)."""
    k = 4
    h = height * k
    f = ImageFont.truetype(FD + "DejaVuSans-Bold.ttf", int(h * 0.62))
    icon = h
    gap = int(h * 0.22)
    w1, w2 = f.getlength("Tartib"), f.getlength("On")
    w = int(icon + gap + w1 + w2) + 4 * k
    img = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(img)
    # icon: gold rounded square with three "ordered" bars and a check mark
    grad = Image.new("RGBA", (icon, icon))
    gd = ImageDraw.Draw(grad)
    for y in range(icon):
        t = y / icon
        gd.line([(0, y), (icon, y)], fill=tuple(int(a * (1 - t) + b * t) for a, b in zip(GOLD_TOP, GOLD_BOT)) + (255,))
    m = Image.new("L", (icon, icon), 0)
    ImageDraw.Draw(m).rounded_rectangle((0, 0, icon - 1, icon - 1), radius=int(icon * 0.24), fill=255)
    img.paste(grad, (0, 0), m)
    dark = (40, 28, 10, 255)
    lw = int(icon * 0.085)
    for i, (x0, x1) in enumerate(((0.40, 0.80), (0.40, 0.80), (0.40, 0.68))):
        y = icon * (0.30 + i * 0.20)
        d.rounded_rectangle((icon * x0, y - lw / 2, icon * x1, y + lw / 2), radius=lw // 2, fill=dark)
        d.ellipse((icon * 0.20 - lw * 0.7, y - lw * 0.7, icon * 0.20 + lw * 0.7, y + lw * 0.7), fill=dark)
    # text
    ty = h / 2
    x = icon + gap
    d.text((x, ty), "Tartib", font=f, fill=CREAM + (255,), anchor="lm")
    tm = Image.new("L", img.size, 0)
    ImageDraw.Draw(tm).text((x + w1, ty), "On", font=f, fill=255, anchor="lm")
    gold = Image.new("RGBA", img.size)
    gg = ImageDraw.Draw(gold)
    for y in range(h):
        t = y / h
        gg.line([(0, y), (w, y)], fill=tuple(int(a * (1 - t) + b * t) for a, b in zip(GOLD_TOP, GOLD_BOT)) + (255,))
    img.paste(gold, (0, 0), tm)
    img = img.resize((w // k, height), Image.LANCZOS)
    # soft dark shadow so it stays readable on any background
    pad = 14
    out = Image.new("RGBA", (img.width + pad * 2, img.height + pad * 2), (0, 0, 0, 0))
    sh = Image.new("RGBA", img.size, (0, 0, 0, 255))
    sh.putalpha(img.getchannel("A").point(lambda v: int(v * 0.65)))
    out.alpha_composite(sh, (pad + 2, pad + 3))
    out = out.filter(ImageFilter.GaussianBlur(5))
    out.alpha_composite(img, (pad, pad))
    return out


def probe(path):
    r = subprocess.run(["ffprobe", "-v", "error", "-select_streams", "v:0", "-show_entries",
                        "stream=width,height:format=duration", "-of", "json", path], capture_output=True, text=True)
    j = json.loads(r.stdout)
    return j["streams"][0]["width"], j["streams"][0]["height"], float(j["format"]["duration"])


def brand(path, tmp):
    w, h, dur = probe(path)
    # ideal o'lcham: logotip eni qisqa tomonning ~27% i (1080p da ~290px), chetdan ~6.5%
    margin = round(min(w, h) * 0.065)
    custom = os.environ.get("LOGO") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "tartibon_logo.png")
    if os.path.exists(custom):
        src = Image.open(custom).convert("RGBA")
        lw = round(min(w, h) * 0.27)
        src = src.resize((lw, round(src.height * lw / src.width)), Image.LANCZOS)
        pad = 14
        logo = Image.new("RGBA", (src.width + pad * 2, src.height + pad * 2), (0, 0, 0, 0))
        sh = Image.new("RGBA", src.size, (0, 0, 0, 255))
        sh.putalpha(src.getchannel("A").point(lambda v: int(v * 0.7)))
        logo.alpha_composite(sh, (pad + 2, pad + 3))
        logo = logo.filter(ImageFilter.GaussianBlur(5))
        logo.alpha_composite(src, (pad, pad))
    else:
        logo = make_logo(round(min(w, h) * 0.055))
    png = os.path.join(tmp, "logo.png")
    logo.save(png)
    out = path + ".tmp.mp4"
    flt = (f"[1]format=rgba,colorchannelmixer=aa=0.95,fade=in:st=0.8:d=0.8:alpha=1,"
           f"fade=out:st={dur - 1.0:.2f}:d=0.9:alpha=1[l];[0][l]overlay=W-w-{margin - 14}:H-h-{margin - 14}:shortest=1")
    subprocess.run(["ffmpeg", "-y", "-loglevel", "error", "-i", path, "-loop", "1", "-framerate", "30", "-i", png,
                    "-filter_complex", flt, "-c:v", "libx264", "-preset", "medium", "-crf", "19", "-pix_fmt", "yuv420p",
                    "-c:a", "copy", "-movflags", "+faststart", out], check=True)
    os.replace(out, path)
    print("logo:", path, f"{logo.width}x{logo.height}")


if __name__ == "__main__":
    with tempfile.TemporaryDirectory() as tmp:
        for p in sys.argv[1:]:
            brand(p, tmp)
