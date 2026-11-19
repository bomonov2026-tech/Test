"""Procedural soundtrack for the PO'LATHON promo (40 s, 48 kHz stereo).

Layers: cinematic pad + pulse, fire crackle, grill sizzle, tandoor rumble,
cinematic bass impacts at scene cuts, logo riser/reveal and final impact.
If build/voice.wav exists (ElevenLabs voiceover), it is mixed on top with
music ducking.
Output: build/mix.wav
"""
import os
import wave
import numpy as np

SR = 48000
DUR = 40.0
N = int(SR * DUR)
rng = np.random.default_rng(7)
t = np.arange(N) / SR


def env_lin(n, a, d, s=1.0):
    """Attack/decay envelope of length n samples."""
    e = np.zeros(n)
    na = max(1, int(a * SR)); nd = max(1, int(d * SR))
    e[:na] = np.linspace(0, 1, na)
    e[na:na + nd] = np.linspace(1, 0, nd) ** 2 * s
    return e[:n] if len(e) >= n else np.pad(e, (0, n - len(e)))


def place(buf, sig, t0):
    i0 = int(t0 * SR)
    if i0 >= len(buf):
        return
    n = min(len(sig), len(buf) - i0)
    buf[i0:i0 + n] += sig[:n]


def lowpass(x, cutoff):
    # one-pole smoothing (fast enough for 48 kHz / 40 s)
    a = np.exp(-2 * np.pi * cutoff / SR)
    out = np.zeros_like(x)
    acc = 0.0
    for i in range(len(x)):
        acc = (1 - a) * x[i] + a * acc
        out[i] = acc
    return out


def highpass(x, cutoff):
    return x - lowpass(x, cutoff)


def kick(t0, amp=0.9):
    n = int(0.6 * SR)
    tt = np.arange(n) / SR
    f = 45 + 90 * np.exp(-tt * 30)
    phase = 2 * np.pi * np.cumsum(f) / SR
    return amp * np.sin(phase) * np.exp(-tt * 6)


def boom(t0, amp=1.0, length=2.2):
    n = int(length * SR)
    tt = np.arange(n) / SR
    f = 110 * np.exp(-tt * 1.6) + 28
    body = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-tt * 1.9)
    noise = highpass(rng.standard_normal(n), 800) * np.exp(-tt * 18) * 0.35
    return amp * (body + noise)


def riser(t0, length, amp=0.35):
    n = int(length * SR)
    tt = np.arange(n) / SR
    k = tt / length
    f = 200 + 1800 * k ** 2.2
    sig = np.sin(2 * np.pi * np.cumsum(f) / SR) * 0.6 + highpass(rng.standard_normal(n), 2000) * 0.4
    return amp * sig * (k ** 1.5)


def chime(t0, freqs, amp=0.18, length=2.5):
    n = int(length * SR)
    tt = np.arange(n) / SR
    out = np.zeros(n)
    for i, f in enumerate(freqs):
        out += np.sin(2 * np.pi * f * tt) * np.exp(-tt * (1.4 + 0.3 * i)) / (i + 1)
    return amp * out


def crackle_track(t_start, t_end, density, amp):
    """Sparse fire pops: random short noise bursts."""
    n = int((t_end - t_start) * SR)
    out = np.zeros(n)
    count = int(density * (t_end - t_start))
    for _ in range(count):
        i = rng.integers(0, max(1, n - 800))
        L = rng.integers(60, 500)
        burst = highpass(rng.standard_normal(L), 1200) * np.exp(-np.arange(L) / (L / 4))
        out[i:i + L] += burst * amp * rng.uniform(0.3, 1.0)
    return out


# ---------- music bed ----------
def note(f):
    return 440.0 * 2 ** ((f - 69) / 12)


progression = [  # chord roots (MIDI) with minor/major voicings, 5 s each
    [(45, 0, 3, 7), (45, 0, 4, 7)],  # Am
    [(41, 0, 4, 7), (41, 0, 4, 7)],  # F
    [(48, 0, 4, 7), (48, 0, 4, 7)],  # C
    [(43, 0, 4, 7), (43, 0, 4, 7)],  # G
]
music = np.zeros(N)
for sec in range(0, 40, 4):
    ch = progression[(sec // 4) % len(progression)][0]
    root = ch[0]
    seg = np.arange(int(4 * SR)) / SR
    pad = np.zeros(len(seg))
    for semi in (0, 3, 7, 12):
        f = note(root + semi - 12 + (0 if semi != 3 else 0))
        for det in (-0.4, 0.4):
            pad += 0.35 * (np.sin(2 * np.pi * f * (1 + det / 1000) * seg) +
                           0.5 * np.sin(2 * np.pi * 2 * f * seg) + 0.25 * np.sin(2 * np.pi * 3 * f * seg))
    pad /= 9.0
    s0 = int(sec * SR)
    music[s0:s0 + len(pad)] += pad[:N - s0]
music = lowpass(music, 1400)

# swell curve: quiet intro, full from 5 s, cut low before the reveal, lift for the end
swell = np.interp(t, [0, 1.5, 5, 24, 25, 31, 33, 39.5, 40], [0.05, 0.25, 0.55, 0.6, 0.25, 0.3, 0.55, 0.55, 0.0])
music *= swell * 0.8

# rhythmic pulse from 5 s to 38 s (~96 BPM), kick + soft hats
beat = 60 / 96
for k in range(int((38 - 5) / beat)):
    tb = 5 + k * beat
    if tb > 38:
        break
    place(music, kick(tb, 0.7) * np.interp(tb, [5, 6, 38], [0.3, 1, 0.7]), tb)
    hat = highpass(rng.standard_normal(int(0.05 * SR)), 7000) * np.exp(-np.arange(int(0.05 * SR)) / (0.01 * SR))
    place(music, hat * 0.05, tb + beat / 2)

# ---------- scene sound design ----------
sfx = np.zeros(N)
# 0-5 s: fire ignition — crackle + rising roar
place(sfx, crackle_track(0, 5.0, density=28, amp=0.22), 0.0)
place(sfx, highpass(rng.standard_normal(int(5 * SR)), 400) * env_lin(int(5 * SR), 2.0, 3.0) * 0.05, 0.0)
# 5-12 s: grill — crackle, meat sizzle (band-passed noise, pulsing)
g_n = int(7 * SR)
sizzle = highpass(rng.standard_normal(g_n), 3000) * (0.6 + 0.4 * np.sin(np.arange(g_n) / SR * 2 * np.pi * 1.7))
sizzle = lowpass(sizzle, 7500) * 0.12
place(sfx, sizzle, 5.0)
place(sfx, crackle_track(5, 12, density=18, amp=0.18), 5.0)
# 12-18 s: tandoor ambience — low rumble + sparse embers
t_n = int(6 * SR)
rumble = lowpass(rng.standard_normal(t_n), 180) * 6.0
rumble *= 0.6 + 0.2 * np.sin(np.arange(t_n) / SR * 2 * np.pi * 0.4)
place(sfx, rumble * 0.12, 12.0)
place(sfx, crackle_track(12, 18, density=8, amp=0.10), 12.0)
# 18-24 s: table — soft glass clinks and light air
for tc in (18.8, 20.3, 21.7, 22.9):
    place(sfx, chime(tc, [2400, 3600], amp=0.05, length=0.9), tc)
# 24-32 s: restaurant exterior — street hum + riser into the logo reveal
hum = lowpass(rng.standard_normal(int(8 * SR)), 260) * 3.0
place(sfx, hum * 0.06, 24.0)
place(sfx, riser(23.2, 1.6, amp=0.3), 23.2)
place(sfx, chime(26.2, [523.25, 659.25, 783.99, 1046.5], amp=0.22, length=3.2), 26.2)
# 32-40 s: CTA shimmer + final hit
place(sfx, chime(33.3, [659.25, 783.99, 987.77], amp=0.14, length=2.4), 33.3)
place(sfx, chime(34.8, [587.33, 739.99, 880.0], amp=0.12, length=2.4), 34.8)

# cinematic impacts on scene cuts and the finale
for tb, a in [(0.0, 0.5), (5.0, 0.95), (12.0, 0.9), (18.0, 0.9), (24.0, 1.0), (32.0, 1.0), (39.35, 1.2)]:
    place(sfx, boom(tb, a), tb)

# ---------- mix ----------
mix = music + sfx
# gentle stereo spread on fx and a slow pan for the pad
L = mix.copy()
R = mix.copy()
R[: N // 2 * 0 + N] = mix * 1.0
# subtle decorrelation from a short delay on the right channel
delay = int(0.012 * SR)
R = np.concatenate([np.zeros(delay), R])[:N]
out = np.stack([L, R], axis=1)

# fade in/out
fade_in = np.clip(t / 0.6, 0, 1)
fade_out = np.clip((DUR - t) / 1.2, 0, 1)
out *= (fade_in * fade_out)[:, None]

# optional voiceover (ElevenLabs) — mixed over music with ducking
voice_path = os.path.join(os.path.dirname(__file__), '..', 'build', 'voice.wav')
if os.path.exists(voice_path):
    with wave.open(voice_path, 'rb') as w:
        vsr = w.getframerate(); vch = w.getnchannels(); raw = w.readframes(w.getnframes())
    v = np.frombuffer(raw, dtype=np.int16).astype(np.float64) / 32768.0
    if vch == 2:
        v = v.reshape(-1, 2).mean(axis=1)
    if vsr != SR:
        v = np.interp(np.linspace(0, len(v) - 1, int(len(v) * SR / vsr)), np.arange(len(v)), v)
    v = v[:N]
    vo = np.zeros((N, 2))
    vo[: len(v), 0] = v; vo[: len(v), 1] = v
    duck = 1 - 0.55 * np.clip(np.convolve(np.abs(vo[:, 0]) > 0.02, np.ones(int(0.25 * SR)) / (0.25 * SR), 'same') * 4, 0, 1)
    out = out * duck[:, None] + vo * 1.1
    print('voiceover mixed:', voice_path)

# soft limiter + normalize to about -1 dBFS
out = np.tanh(out * 1.1) / np.tanh(1.1)
peak = np.max(np.abs(out))
out = out / peak * 0.89

pcm = (out * 32767).astype('<i2')
os.makedirs(os.path.join(os.path.dirname(__file__), '..', 'build'), exist_ok=True)
dst = os.path.join(os.path.dirname(__file__), '..', 'build', 'mix.wav')
with wave.open(dst, 'wb') as w:
    w.setnchannels(2); w.setsampwidth(2); w.setframerate(SR)
    w.writeframes(pcm.tobytes())
print('wrote', dst, 'peak', float(peak))
