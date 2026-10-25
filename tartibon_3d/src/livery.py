# Kuzov yon tomonlari uchun livery atlas: yuqori yarmi - chap tomon, pastki yarmi - o'ng tomon.
import sys
from PIL import Image, ImageDraw, ImageFont

W, H = 4096, 2048          # har bir tomon: 4096 x 1024
XF, XR = 2.20, -2.20       # kuzov old va orqa chegarasi (m)
ZB, ZT = 0.25, 1.15        # tekstura qamraydigan balandlik (m)
FONT = "/usr/share/fonts/opentype/inter/Inter-Bold.otf"
FONT2 = "/usr/share/fonts/opentype/inter/Inter-SemiBold.otf"
BLUE, TEAL, GREEN, NAVY, SILL = (31, 95, 214), (25, 190, 170), (60, 205, 120), (30, 70, 150), (24, 27, 32)

def side(flip):
    img = Image.new("RGB", (W, H // 2), (244, 245, 247))
    d = ImageDraw.Draw(img)
    def P(x, z):
        u = (x - XR) / (XF - XR) if flip else (XF - x) / (XF - XR)
        return (u * W, (ZT - z) / (ZT - ZB) * (H // 2))
    # Diagonal chiziqlar: old-pastdan orqa-tepaga ko'tariladi
    bands = [(0.55, 0.00, BLUE), (0.30, 0.10, TEAL), (0.05, 0.16, BLUE), (-0.25, 0.10, GREEN), (-0.50, 0.07, TEAL)]
    for x0, w, col in bands:
        for k in range(1):
            pts = [(x0, 0.44), (x0 - 0.22 - w, 0.44), (x0 - 1.70 - w, 1.06), (x0 - 1.48, 1.06)]
            d.polygon([P(*p) for p in pts], fill=col)
    # Ostona (sill) va teal chiziq
    d.rectangle([P(XF, 0.43), P(XR, ZB)] if not flip else [P(XR, 0.43), P(XF, ZB)], fill=SILL)
    a, b = P(XF, 0.455), P(XR, 0.435)
    d.rectangle([min(a[0], b[0]), a[1], max(a[0], b[0]), b[1]], fill=TEAL)
    # Eshik choklari va tutqichlar
    for x in (0.86, -0.22, -1.20):
        (x1, y1), (x2, y2) = P(x, 1.07), P(x, 0.46)
        d.line([(x1, y1), (x2, y2)], fill=(150, 156, 165), width=5)
    for x in (0.10, -0.92):
        (x1, y1), (x2, y2) = P(x, 1.00), P(x - 0.20, 0.97)
        d.rounded_rectangle([min(x1, x2), y1, max(x1, x2), y2], 6, fill=(170, 175, 182))
    # Logotip: "Tartib" + quvvat belgisi (O) + "n"
    f = ImageFont.truetype(FONT, 190)
    cx, cy = P(0.33, 0.76)
    tw = d.textlength("Tartib", font=f); nw = d.textlength("n", font=f); r = 72; gap = 14
    total = tw + gap + 2 * r + gap + nw
    x = cx - total / 2
    d.text((x, cy), "Tartib", font=f, fill=NAVY, anchor="ls")
    ox, oy = x + tw + gap + r, cy - r - 4
    d.arc([ox - r, oy - r, ox + r, oy + r], start=-50, end=230, fill=GREEN, width=26)
    d.rounded_rectangle([ox - 13, oy - r - 20, ox + 13, oy + 8], 12, fill=GREEN)
    d.text((ox + r + gap, cy), "n", font=f, fill=GREEN, anchor="ls")
    f2 = ImageFont.truetype(FONT2, 62)
    d.text((cx, cy + 75), "YO‘L QOIDASI — XAVFSIZ HAYOT", font=f2, fill=(55, 60, 70), anchor="mm")
    return img

atlas = Image.new("RGB", (W, H))
atlas.paste(side(False), (0, 0))
atlas.paste(side(True), (0, H // 2))
atlas.save(sys.argv[1])
