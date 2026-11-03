#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ElevenLabs'dan olingan sahna-sahna audio fayllarni videoga aniq moslab ulaydi.

Har bir sahnaning ovozi CSV dagi o'z vaqtiga qo'yiladi, oradagi jim joylar
avtomatik to'ldiriladi. Shuning uchun oxirgi sahna ham joyida turadi —
ovoz asta-sekin siljib ketmaydi.

Ishlatish:
  python3 ovoz-yigish.py --csv reels/01-savdogar.csv \
                         --audio ./mp3lar \
                         --video ../reels/video/01-savdogar.mp4 \
                         --out tayyor-01.mp4

Audio fayllar nomi: 001.mp3, 002.mp3, ... (CSV dagi "sahna" ustuni bilan bir xil).
mp3, wav, m4a, ogg, flac — hammasi bo'ladi.
"""
import argparse, csv, os, subprocess, sys, shutil

EXT = ('.mp3', '.wav', '.m4a', '.ogg', '.flac', '.aac')


def need(prog):
    if not shutil.which(prog):
        sys.exit("Xato: '%s' topilmadi. ffmpeg o'rnating." % prog)


def dur(path):
    r = subprocess.run(['ffprobe', '-v', 'error', '-show_entries', 'format=duration',
                        '-of', 'csv=p=0', path], capture_output=True, text=True)
    try:
        return float(r.stdout.strip())
    except ValueError:
        sys.exit("Xato: '%s' uzunligini o'qib bo'lmadi." % path)


def find(folder, num):
    for e in EXT:
        p = os.path.join(folder, num + e)
        if os.path.exists(p):
            return p
    return None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--csv', required=True, help="sahna matnlari CSV fayli")
    ap.add_argument('--audio', required=True, help="001.mp3, 002.mp3 ... turgan papka")
    ap.add_argument('--video', required=True, help="ovozsiz mp4")
    ap.add_argument('--out', required=True, help="natija mp4")
    ap.add_argument('--audio-only', action='store_true', help="faqat audio yo'lak (mp3) chiqarish")
    a = ap.parse_args()

    need('ffmpeg'); need('ffprobe')
    for f in (a.csv, a.video):
        if not os.path.exists(f):
            sys.exit("Xato: '%s' topilmadi." % f)

    with open(a.csv, encoding='utf-8') as f:
        rows = list(csv.DictReader(f, delimiter=';'))
    if not rows:
        sys.exit("Xato: CSV bo'sh.")

    vlen = dur(a.video)
    inputs, filters, labels, warn, miss = [], [], [], [], []

    for i, r in enumerate(rows):
        num = r['sahna']
        start = float(r['boshlanish_s'])
        slot = float(r['davomiylik_s'])
        p = find(a.audio, num)
        if not p:
            miss.append(num)
            continue
        d = dur(p)
        if d > slot + 0.12:
            warn.append((num, d, slot, r['matn'][:58]))
        k = len(labels)   # nechanchi -i ekani (har bir fayl bitta kirish)
        inputs += ['-i', p]
        ms = int(round(start * 1000))
        filters.append('[%d:a]aresample=48000,adelay=%d|%d[a%d]' % (k, ms, ms, k))
        labels.append('[a%d]' % k)

    if miss:
        print("Topilmadi (%d ta): %s" % (len(miss), ', '.join(miss[:12]) + ('…' if len(miss) > 12 else '')))
    if not labels:
        sys.exit("Xato: birorta ham audio fayl topilmadi. Nomlar 001.mp3 ko'rinishida bo'lsin.")

    fc = ';'.join(filters) + ';' + ''.join(labels) + \
         'amix=inputs=%d:normalize=0:dropout_transition=0[mix]' % len(labels)

    tmp = os.path.splitext(a.out)[0] + '-ovoz.m4a'
    cmd = ['ffmpeg', '-y', '-loglevel', 'error'] + inputs + \
          ['-filter_complex', fc, '-map', '[mix]', '-t', '%.3f' % vlen,
           '-c:a', 'aac', '-b:a', '192k', tmp]
    subprocess.run(cmd, check=True)
    print("✓ audio yo'lak: %s" % tmp)

    if not a.audio_only:
        subprocess.run(['ffmpeg', '-y', '-loglevel', 'error', '-i', a.video, '-i', tmp,
                        '-c:v', 'copy', '-map', '0:v:0', '-map', '1:a:0',
                        '-c:a', 'aac', '-b:a', '192k', '-shortest',
                        '-movflags', '+faststart', a.out], check=True)
        print("✓ tayyor video: %s  (%.1f s)" % (a.out, vlen))

    if warn:
        print("\nOgohlantirish — bu sahnalarning ovozi o'z joyiga sig'madi")
        print("(keyingi sahnaga ozgina kirib ketadi; matnni qisqartirsangiz tuzaladi):")
        for num, d, slot, txt in warn:
            print("  %s  %.1fs > %.1fs   %s…" % (num, d, slot, txt))


if __name__ == '__main__':
    main()
