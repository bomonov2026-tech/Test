"""Cuts the approved voiceover into phrases, cleans them, and places each phrase on the
video timeline (aligned to scene beats). Output: build/voice.wav (48 kHz mono, 40 s).

Input: build/voice_src.mp3 (ElevenLabs export). Phrase boundaries come from silence detection.
"""
import os, subprocess, sys
ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..')
BUILD = os.path.join(ROOT, 'build')
SRC = os.path.join(BUILD, 'voice_src.mp3')
TMP = os.path.join(BUILD, 'voice_parts'); os.makedirs(TMP, exist_ok=True)
DUR = 40.0

# (src_start, src_end) in seconds from the export, and the timeline start for each phrase
PHRASES = [
    ((0.00, 2.02), 0.40),  # Har bir lazzatning o‘z manzili bor…          -> intro
    ((2.53, 4.70), 2.60),  # Endi esa haqiqiy ta’m manzili — Eshonguzarda! -> intro
    ((5.08, 5.96), 5.30),  # Olovda pishgan, shirali va xushbo‘y shashliklar… -> grill
    ((6.40, 9.32), 12.20), # Tandirda yopilgan, tillarang va mazali somsalar… -> tandoor
    ((9.94, 11.00), 18.30),# Barchasi bir manzilda!                        -> table
    ((11.14, 13.03), 25.60),# PO‘LATHON!                                    -> brand reveal
    ((13.34, 14.93), 28.40),# Tez kunda eshiklarimiz siz uchun ochiladi!    -> brand / CTA
    ((15.23, 22.39), 32.30),# Eshonguzar, Po‘lathon — lazzatni his qilishga tayyor bo‘ling! -> finale
]

CLEAN = ('highpass=f=90,'
         'equalizer=f=3200:t=q:w=1.1:g=1.5,'     # presence
         'equalizer=f=7500:t=q:w=2.0:g=-2.0,'    # tame sibilance
         'acompressor=threshold=-22dB:ratio=3:attack=4:release=90:makeup=2,'
         'afade=t=in:st=0:d=0.02')

def run(cmd):
    subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)

parts = []
for i, ((s, e), _) in enumerate(PHRASES):
    dst = os.path.join(TMP, f'p{i}.wav')
    d = e - s + 0.06
    run(['ffmpeg', '-y', '-ss', f'{max(0, s - 0.03):.3f}', '-t', f'{d:.3f}', '-i', SRC,
         '-af', CLEAN + f',afade=t=out:st={d - 0.06:.3f}:d=0.06',
         '-ar', '48000', '-ac', '1', dst])
    parts.append(dst)

# Timeline mix: delay each phrase, add light room reverb, then loudness-normalize.
inputs, filt, labels = [], [], []
for i, (p, (_, start)) in enumerate(zip(parts, PHRASES)):
    inputs += ['-i', p]
    ms = int(start * 1000)
    filt.append(f'[{i}:a]adelay={ms}|{ms}[d{i}]')
    labels.append(f'[d{i}]')
n = len(parts)
filt.append(''.join(labels) + f'amix=inputs={n}:normalize=0:duration=longest[m]')
filt.append('[m]aecho=0.8:0.6:45:0.14,'
            'apad=whole_dur=40,atrim=0:40,'
            'loudnorm=I=-15:TP=-1.5:LRA=7[out]')
dst = os.path.join(BUILD, 'voice.wav')
run(['ffmpeg', '-y', *inputs, '-filter_complex', ';'.join(filt), '-map', '[out]',
     '-ar', '48000', '-ac', '1', '-c:a', 'pcm_s16le', dst])
print('wrote', dst)
