# Crops UI cards (CSS px boxes, screenshots are 3x) used as floating 3D panels.
import json, sys
from PIL import Image, ImageDraw
S = 3
BOX = {  # name: (source, x, y, w, h) in CSS px
    'hero':     ('home_full', 14, 74, 362, 310),
    'cbu':      ('home_full', 14, 396, 362, 241),
    'banks':    ('home_full', 14, 650, 362, 440),
    'prayer_w': ('home_full', 14, 1092, 362, 480),
    'prayer_l': ('prayer_full', 14, 343, 362, 299),
    'channel':  ('channels_full', 14, 506, 362, 404),
    'guard':    ('guard_full', 14, 540, 362, 326),
    'guard2':   ('guard_full', 14, 872, 362, 222),
}
def rounded(im, r):
    m = Image.new('L', im.size, 0); ImageDraw.Draw(m).rounded_rectangle([0, 0, im.size[0] - 1, im.size[1] - 1], r, fill=255)
    im = im.convert('RGBA'); im.putalpha(m); return im
out = {}
for k, (src, x, y, w, h) in BOX.items():
    im = Image.open('screens/%s.png' % src).crop((x * S, y * S, (x + w) * S, (y + h) * S))
    rounded(im, 20 * S).save('crops/%s.png' % k); out[k] = [w, h]
json.dump(out, open('crops/sizes.json', 'w'))
