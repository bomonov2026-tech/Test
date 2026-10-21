"""Usd Go24 promo soundtrack: electronic score + synchronised sound design (no voice).
Everything is synthesised; cue times match scene/main.js. Usage: python3 score.py out.wav"""
import sys
import numpy as np
from scipy import signal

SR = 48000
DUR = 42.0
N = int(SR * DUR)
BPM = 120
BEAT = 60 / BPM
rng = np.random.default_rng(2024)


def mtof(m):
    return 440.0 * 2 ** ((m - 69) / 12)


def buf():
    return np.zeros((2, N))


def place(bus, x, t0, gain=1.0, pan=0.0):
    """Add mono or stereo signal x at time t0 (seconds) with equal-power pan."""
    i0 = int(round(t0 * SR))
    if x.ndim == 1:
        a = (pan + 1) * np.pi / 4
        x = np.vstack([x * np.cos(a), x * np.sin(a)]) * np.sqrt(2)
    if i0 < 0:
        x = x[:, -i0:]
        i0 = 0
    n = min(x.shape[1], N - i0)
    if n > 0:
        bus[:, i0:i0 + n] += x[:, :n] * gain


def env_adsr(n, a, d, s, r, sr=SR):
    """Attack/decay/sustain envelope of n samples with a release fade at the end."""
    t = np.arange(n) / sr
    e = np.where(t < a, t / max(a, 1e-9), s + (1 - s) * np.exp(-(t - a) / max(d, 1e-9) * 3))
    rn = min(int(r * sr), n)
    if rn:
        e[n - rn:] *= np.linspace(1, 0, rn)
    return e


def saw(freq, n, phase=0.0, maxf=11000):
    """Band-limited sawtooth by additive synthesis (freq may be an array for glides)."""
    t = np.arange(n) / SR
    ph = 2 * np.pi * (np.cumsum(np.broadcast_to(freq, (n,))) / SR) + phase
    f0 = float(np.max(freq))
    out = np.zeros(n)
    for k in range(1, max(2, int(maxf / f0)) + 1):
        out += np.sin(k * ph) / k
    return out * (2 / np.pi), t


def lowpass(x, fc, order=2, kind='low'):
    sos = signal.butter(order, np.clip(fc, 20, SR / 2 - 100), kind, fs=SR, output='sos')
    return signal.sosfilt(sos, x, axis=-1)


def bandpass(x, lo, hi, order=2):
    sos = signal.butter(order, [lo, hi], 'band', fs=SR, output='sos')
    return signal.sosfilt(sos, x, axis=-1)


def tv_lowpass(x, fc, block=256):
    """Time-varying 2-pole lowpass; fc is an array (Hz) the length of x."""
    out = np.zeros_like(x)
    zi = None
    for s in range(0, len(x), block):
        f = float(np.clip(fc[min(s + block // 2, len(fc) - 1)], 30, SR / 2 - 200))
        sos = signal.butter(2, f, 'low', fs=SR, output='sos')
        if zi is None:
            zi = signal.sosfilt_zi(sos) * 0
        out[s:s + block], zi = signal.sosfilt(sos, x[s:s + block], zi=zi)
    return out


def reverb_ir(seconds=2.6, damp=3.2, pre=0.012):
    n = int(seconds * SR)
    t = np.arange(n) / SR
    ir = rng.standard_normal((2, n)) * np.exp(-damp * t)
    ir = lowpass(ir, 6500)
    ir[:, :int(pre * SR)] = 0
    return ir / np.sqrt(np.sum(ir ** 2) / 2)


def convolve(x, ir):
    return np.vstack([signal.fftconvolve(x[c], ir[c])[:N] for c in range(2)])


def delay(x, secs, fb=0.35, taps=5, pingpong=True):
    out = x.copy()
    d = int(secs * SR)
    for k in range(1, taps + 1):
        g = fb ** k
        sh = np.zeros_like(x)
        sh[:, d * k:] = x[:, :-d * k] * g
        if pingpong and k % 2:
            sh = sh[::-1]
        out += sh
    return out


def ramp(points):
    """Piecewise-linear automation over the whole timeline: [(t, v), ...]."""
    ts, vs = zip(*points)
    return np.interp(np.arange(N) / SR, ts, vs)


# ---------------------------------------------------------------- harmony
# D minor: Dm – Bb – F – C, one chord per bar (2 s)
CHORDS = [(50, [62, 65, 69, 74]), (46, [58, 62, 65, 70]), (53, [60, 65, 69, 72]), (48, [60, 64, 67, 72])]


def chord_at(bar):
    return CHORDS[bar % 4]


# ---------------------------------------------------------------- music
def pad():
    bus = buf()
    for bar in range(0, 21):
        root, notes = chord_at(bar)
        if bar >= 18:   # outro: hold D minor add9
            root, notes = 50, [62, 65, 69, 76]
        t0 = bar * 2.0
        dur = 2.0 + 1.2 if bar < 18 else (DUR - t0 if bar == 18 else 0)
        if dur <= 0:
            continue
        n = int(dur * SR)
        for j, m in enumerate(notes):
            for v, det in enumerate([-0.11, -0.05, 0.0, 0.05, 0.11]):
                x, _ = saw(mtof(m + det), n, phase=rng.uniform(0, 6.28), maxf=6000)
                e = env_adsr(n, 0.55, 0.3, 0.85, 1.2 if bar < 18 else 3.5)
                place(bus, x * e * 0.05, t0, pan=(-0.7 + 1.4 * ((v + j) % 5) / 4))
    cut = ramp([(0, 380), (2, 900), (6, 1400), (11.9, 2400), (12, 1700), (26, 2600), (30, 3600), (32, 2800), (34, 2200), (36, 3000), (42, 900)])
    bus = np.vstack([tv_lowpass(bus[c], cut) for c in range(2)])
    return lowpass(bus, 150, kind='high')   # keep low end for bass/kick


def drone():
    bus = buf()
    n = int(12 * SR)
    t = np.arange(n) / SR
    x = np.sin(2 * np.pi * mtof(38) * t) * 0.6 + np.sin(2 * np.pi * mtof(50) * t + np.sin(t * 0.7)) * 0.25
    e = np.minimum(t / 1.5, 1) * np.clip((12 - t) / 4, 0, 1)
    place(bus, x * e * 0.16, 0.0)
    return bus


def bass():
    bus = buf()
    for bar in list(range(6, 13)) + [16]:
        root, _ = chord_at(bar)
        for b in range(4):
            t0 = bar * 2.0 + b * BEAT + BEAT / 2   # offbeat 8ths (pumping bass)
            if True:
                n = int(BEAT * 0.48 * SR)
                f = mtof(root - 12)
                x, t = saw(f, n, maxf=4000)
                x = lowpass(x, 700) * 0.8 + np.sin(2 * np.pi * f * t) * 0.6
                e = env_adsr(n, 0.004, 0.12, 0.6, 0.05)
                place(bus, x * e * 0.32, t0)
    return bus


def arp():
    bus = buf()
    pattern = [0, 2, 1, 3, 2, 1, 3, 2]
    for bar in range(1, 19):
        root, notes = chord_at(bar)
        tones = [notes[0], notes[1], notes[2], notes[3]] + [n + 12 for n in notes]
        for s16 in range(16):
            t0 = bar * 2.0 + s16 * BEAT / 4
            if t0 < 2.0 or t0 > 37.0:
                continue
            m = tones[pattern[s16 % 8] + (4 if s16 % 16 >= 8 and bar % 2 else 0)] + 12
            n = int(0.2 * SR)
            x, t = saw(mtof(m), n, maxf=9000)
            x = x * 0.6 + np.sign(np.sin(2 * np.pi * mtof(m) * t)) * 0.12
            e = np.exp(-t * 22)
            place(bus, x * e * 0.09, t0, pan=0.35 if s16 % 2 else -0.35)
    cut = ramp([(0, 500), (2, 900), (6, 1600), (11.9, 3200), (12, 2600), (26, 1800), (31.9, 4200), (32, 3600), (34, 2200), (38, 900), (42, 500)])
    bus = np.vstack([tv_lowpass(bus[c], cut) for c in range(2)])
    bus = delay(bus, BEAT * 0.75, fb=0.32, taps=4)
    lvl = ramp([(0, 0), (2, 0.35), (6, 0.6), (12, 1.0), (26, 0.75), (32, 1.0), (34, 0.7), (36, 0.5), (39, 0.0), (42, 0)])
    return bus * lvl


def kick(level=1.0):
    n = int(0.42 * SR)
    t = np.arange(n) / SR
    f = 46 + 110 * np.exp(-t * 32)
    x = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 7.5)
    x += lowpass(rng.standard_normal(n), 3000) * np.exp(-t * 180) * 0.25
    return np.tanh(x * 1.6) * level


def clap():
    n = int(0.3 * SR)
    t = np.arange(n) / SR
    nz = bandpass(rng.standard_normal(n), 900, 4200)
    e = np.zeros(n)
    for k, d in enumerate([0, 0.011, 0.022]):
        i = int(d * SR)
        e[i:] += np.exp(-(t[:n - i]) * (90 if k < 2 else 20))
    return nz * e * 0.5


def hat(open_=False):
    n = int((0.22 if open_ else 0.05) * SR)
    t = np.arange(n) / SR
    x = lowpass(rng.standard_normal(n), 7000, kind='high')
    return x * np.exp(-t * (14 if open_ else 70)) * 0.22


def tom(f0):
    n = int(0.5 * SR)
    t = np.arange(n) / SR
    f = f0 * (1 + 0.6 * np.exp(-t * 20))
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 6) * 0.5


def drums():
    bus = buf()
    kicks = []
    for i in range(int(DUR / BEAT)):
        t0 = i * BEAT
        main = 12 <= t0 < 26 or 32 <= t0 < 34
        build = 8 <= t0 < 12
        if main or (build and i % 1 == 0):
            place(bus, kick(0.9 if main else 0.55), t0)
            kicks.append(t0)
        if main and i % 2 == 1:
            place(bus, clap(), t0, gain=0.8)
        if main or (10 <= t0 < 12):
            for h in range(4 if main else 2):
                th = t0 + h * BEAT / (4 if main else 2)
                acc = 1.0 if h % 2 else 0.55
                place(bus, hat(open_=(main and h == 2 and i % 4 == 3)), th, gain=acc, pan=0.25)
    # hero section: tom pulse + snare roll into 32
    for i in range(int(26 / BEAT), int(30 / BEAT)):
        place(bus, tom(110 if i % 2 else 82), i * BEAT, gain=0.5)
    for k in range(32):
        t0 = 30.0 + k * (2.0 / 32)
        place(bus, clap() * (0.2 + 0.8 * k / 32), t0, gain=0.55, pan=0.1 * np.sin(k))
    return bus, kicks


def sidechain(kicks, depth=0.55):
    g = np.ones(N)
    n = int(0.32 * SR)
    shape = 1 - depth * np.exp(-np.arange(n) / SR * 11)
    for t0 in kicks:
        i = int(t0 * SR)
        m = min(n, N - i)
        g[i:i + m] = np.minimum(g[i:i + m], shape[:m])
    return g


# ---------------------------------------------------------------- sound design
def impact(size=1.0):
    n = int(3.2 * SR)
    t = np.arange(n) / SR
    f = 30 + 60 * np.exp(-t * 6)
    sub = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * (1.4 / size))
    body = np.tanh(lowpass(rng.standard_normal(n), 900) * np.exp(-t * 9) * 3) * 0.5
    crack = lowpass(rng.standard_normal(n), 2500, kind='high') * np.exp(-t * 28) * 0.25
    return (sub * 0.9 + body + crack) * size


def whoosh(dur=1.0, up=True, bright=1.0):
    n = int(dur * SR)
    t = np.linspace(0, 1, n)
    nz = rng.standard_normal(n)
    fc = (300 + 5200 * bright * (t if up else 1 - t) ** 1.5) if up else 300 + 5200 * bright * (1 - t) ** 1.2
    x = tv_lowpass(nz, fc) - tv_lowpass(nz, fc * 0.18)
    e = np.sin(np.pi * t) ** 2 * (t if up else 1)
    e = e / (e.max() + 1e-9)
    pan = np.linspace(-0.6, 0.6, n) if up else np.linspace(0.6, -0.6, n)
    a = (pan + 1) * np.pi / 4
    return np.vstack([x * e * np.cos(a), x * e * np.sin(a)]) * np.sqrt(2) * 0.5


def swish(dur=0.45):
    n = int(dur * SR)
    t = np.linspace(0, 1, n)
    x = bandpass(rng.standard_normal(n), 1200, 7000)
    return x * np.sin(np.pi * t ** 0.6) ** 3 * 0.3


def riser(dur):
    n = int(dur * SR)
    t = np.linspace(0, 1, n)
    nz = tv_lowpass(rng.standard_normal(n), 300 + 9000 * t ** 2)
    f = 180 + 1100 * t ** 2
    tone = np.sin(2 * np.pi * np.cumsum(f) / SR) * 0.25 + np.sin(2 * np.pi * np.cumsum(f * 1.5) / SR) * 0.12
    return (nz * 0.6 + tone) * t ** 2.2 * 0.6


def reverse_swell(dur):
    x = impact(0.6)[: int(dur * SR)][::-1]
    t = np.linspace(0, 1, len(x))
    return lowpass(x, 3000) * t ** 1.5 + riser(dur) * 0.5


def click(f=3200, level=1.0):
    n = int(0.05 * SR)
    t = np.arange(n) / SR
    return (np.sin(2 * np.pi * f * t) * np.exp(-t * 260) + lowpass(rng.standard_normal(n), 4000, kind='high') * np.exp(-t * 900) * 0.4) * 0.35 * level


def pop():
    n = int(0.22 * SR)
    t = np.arange(n) / SR
    f = 420 + 380 * (1 - np.exp(-t * 40))
    body = np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t * 26) * 0.35
    thunk = np.sin(2 * np.pi * 95 * t) * np.exp(-t * 30) * 0.4
    mech = bandpass(rng.standard_normal(n), 2000, 6000) * np.exp(-t * 120) * 0.12
    return body + thunk + mech


def bell(m, dur=1.6, level=1.0):
    n = int(dur * SR)
    t = np.arange(n) / SR
    f = mtof(m)
    x = sum(a * np.sin(2 * np.pi * f * r * t) * np.exp(-t * d) for r, a, d in [(1, 1, 3.2), (2.0, 0.45, 5), (3.01, 0.25, 7), (4.2, 0.12, 9)])
    return x * np.minimum(t / 0.004, 1) * 0.22 * level


def notify():
    return np.concatenate([bell(88, 0.13, 0.9), bell(93, 0.9, 0.9)])


def shimmer(dur=1.2, base=86):
    out = np.zeros(int(dur * SR) + int(0.8 * SR))
    for k, m in enumerate([base, base + 3, base + 7, base + 10, base + 14]):
        b = bell(m, 0.8, 0.35)
        i = int(k * 0.06 * SR)
        out[i:i + len(b)] += b
    return out[: int(dur * SR) + int(0.8 * SR)]


def power_on():
    n = int(1.1 * SR)
    t = np.arange(n) / SR
    out = np.zeros(n)
    for m in (62, 69, 74, 81):
        f = mtof(m) * (0.985 + 0.015 * np.minimum(t / 0.25, 1))
        out += np.sin(2 * np.pi * np.cumsum(f) / SR) * np.minimum(t / 0.06, 1) * np.exp(-t * 3.2) * 0.12
    c = click(2600, 0.8)
    out[:len(c)] += c
    return out


def ticks(t0, t1, rate=14, level=0.25):
    bus = buf()
    k = 0
    for tt in np.arange(t0, t1, 1 / rate):
        place(bus, click(5200 + 300 * (k % 3), level), tt, pan=0.3 * np.sin(k))
        k += 1
    return bus


def sfx():
    dry, wet = buf(), buf()

    def add(x, t0, g=1.0, pan=0.0, send=0.25):
        place(dry, x, t0, g, pan)
        place(wet, x, t0, g * send, pan)

    # S1 opening
    add(whoosh(1.8, True, 0.8), 0.15, 0.55, send=0.4)
    add(whoosh(0.8, False, 0.9), 1.0, 0.35, send=0.3)
    add(reverse_swell(1.15), 0.85, 0.5, send=0.3)
    add(impact(1.25), 2.0, 0.95, send=0.55)
    add(shimmer(1.4, 86), 2.15, 0.6, send=0.7)
    add(shimmer(1.0, 81), 3.0, 0.35, send=0.7)
    add(whoosh(1.25, True, 1.0), 4.8, 0.6, send=0.35)
    add(riser(1.1), 4.9, 0.5, send=0.3)
    add(impact(0.75), 6.0, 0.7, send=0.45)
    # S2 product reveal
    add(whoosh(1.6, False, 0.7), 6.05, 0.45, send=0.3)
    add(power_on(), 7.35, 0.75, send=0.5)
    add(whoosh(1.2, True, 0.8), 7.85, 0.5, pan=0.4, send=0.3)
    add(pop(), 9.2, 0.55, pan=0.3, send=0.2)
    place(dry, ticks(9.65, 10.55, 16, 0.18), 0)
    place(dry, ticks(11.05, 11.8, 16, 0.18), 0)
    add(riser(2.0), 10.0, 0.5, send=0.25)
    add(whoosh(0.5, True, 1.0), 11.55, 0.55, send=0.3)
    add(impact(0.9), 12.0, 0.8, send=0.45)
    # S3 features
    for tf, tl in [(12.35, 12.9), (12.85, 13.4), (15.85, 16.4), (16.75, 17.3), (19.35, 19.9), (21.2, 21.7), (21.6, 22.1), (22.85, 23.4), (23.3, 23.85)]:
        add(swish(0.5), tf, 0.5, pan=-0.3, send=0.2)
        add(pop(), tl, 0.5, pan=-0.2, send=0.25)
    for ts in (12.3, 15.8, 19.3, 22.8):
        add(shimmer(0.6, 93), ts, 0.22, pan=-0.5, send=0.6)
    for ts in (15.4, 18.95, 22.45):
        add(whoosh(0.55, True, 0.9), ts - 0.1, 0.45, send=0.3)
    add(click(2800, 1.1), 14.4, 0.8, pan=-0.3, send=0.2)
    add(bell(84, 0.6, 0.6), 14.45, 0.5, pan=-0.3, send=0.5)
    add(notify(), 17.05, 0.55, pan=0.3, send=0.45)
    for tc in (20.0, 20.35, 20.7, 21.05):
        add(click(3000, 0.9), tc, 0.7, pan=-0.35, send=0.15)
    add(notify(), 21.55, 0.5, pan=0.35, send=0.45)
    add(notify(), 21.95, 0.4, pan=0.45, send=0.45)
    for tc in (23.7, 24.1, 24.5, 24.9):
        add(click(2200, 0.8), tc, 0.65, pan=-0.3, send=0.15)
    add(whoosh(0.45, True, 1.0), 25.6, 0.6, send=0.35)
    add(impact(0.85), 26.0, 0.75, send=0.5)
    # S4 hero
    add(whoosh(3.5, True, 0.5), 26.3, 0.35, send=0.5)
    add(riser(2.0), 30.0, 0.6, send=0.35)
    add(whoosh(1.4, True, 0.9), 31.3, 0.45, send=0.35)
    add(impact(1.3), 32.0, 0.95, send=0.55)
    add(shimmer(1.2, 81), 32.1, 0.4, send=0.7)
    add(whoosh(0.5, True, 1.0), 33.55, 0.55, send=0.3)
    # S5 final brand
    add(impact(0.7), 34.0, 0.55, send=0.45)
    add(whoosh(1.4, False, 0.7), 34.05, 0.4, send=0.35)
    add(reverse_swell(0.95), 35.05, 0.6, send=0.35)
    add(impact(1.5), 36.0, 1.0, send=0.65)
    add(shimmer(2.2, 74), 36.02, 0.8, send=0.8)
    add(shimmer(1.6, 86), 36.2, 0.45, send=0.8)
    add(shimmer(0.6, 93), 36.55, 0.2, send=0.6)
    add(shimmer(0.6, 98), 37.05, 0.18, send=0.6)
    return dry, wet


# ---------------------------------------------------------------- mix
def main(out):
    ir = reverb_ir()
    p, dr, b, a = pad(), drone(), bass(), arp()
    d, kicks = drums()
    sc = sidechain(kicks)
    music = p * sc * 0.9 + dr + b * sc * 0.9 + a * (0.6 + 0.4 * sc) + d
    music_wet = convolve(p * 0.35 + a * 0.4 + d * 0.06, ir)
    music = music + music_wet * 0.45
    fx_dry, fx_wet = sfx()
    fx = fx_dry + convolve(fx_wet, reverb_ir(3.4, 2.2, 0.02)) * 0.55
    # duck music slightly under big impacts so they read clearly
    duck = np.ones(N)
    for t0 in (2.0, 12.0, 26.0, 32.0, 36.0):
        i = int(t0 * SR)
        n = int(1.2 * SR)
        duck[i:i + n] = np.minimum(duck[i:i + n], 1 - 0.35 * np.exp(-np.arange(min(n, N - i)) / SR * 3))
    mix = music * duck * 0.8 + fx * 0.72
    mix = lowpass(mix, 28, kind='high')   # remove DC / sub rumble
    # glue compression (RMS envelope, 3:1 above threshold)
    rms = np.sqrt(signal.sosfilt(signal.butter(1, 8, fs=SR, output='sos'), np.mean(mix ** 2, axis=0)))
    thr = 0.18
    gain = np.where(rms > thr, (thr + (rms - thr) / 3) / np.maximum(rms, 1e-9), 1.0)
    mix *= gain
    # fades: no abrupt start/end
    fade = np.ones(N)
    fade[: int(0.02 * SR)] = np.linspace(0, 1, int(0.02 * SR))
    tail = int(2.6 * SR)
    fade[-tail:] = np.cos(np.linspace(0, np.pi / 2, tail)) ** 2
    mix *= fade
    mix = np.tanh(mix * 1.15) / np.tanh(1.15)          # soft clip
    mix *= 10 ** (-1.0 / 20) / np.max(np.abs(mix))       # peak -1 dBFS
    from scipy.io import wavfile
    wavfile.write(out, SR, (mix.T * 32767).astype(np.int16))
    print('wrote', out, 'peak', np.max(np.abs(mix)), 'rms dBFS', 20 * np.log10(np.sqrt(np.mean(mix ** 2))))


if __name__ == '__main__':
    main(sys.argv[1] if len(sys.argv) > 1 else 'soundtrack.wav')
