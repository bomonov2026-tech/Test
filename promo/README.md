# Usd Go24 — 3D promo video

A 42-second, 1920×1080, 30 fps cinematic promo for the **Usd Go24** Telegram Mini App (the KursPremium bot in `../kurspremium`). It is built from the app's real interface: screenshots of the running Mini App, the bot's real channel post images, and the real brand colours and "▶ Usd Go24" wordmark.

The soundtrack is fully synthesised: electronic music plus synced impacts, whooshes, UI clicks and notification sounds. It has **no voice-over or speech**.

## Storyboard
| Time | Scene | What happens |
|---|---|---|
| 0–6 s | Opening | Particles, light trails and chrome rings converge. The 3D "▶ Usd Go24" logo lands on a bass impact at 2.0 s, followed by a light sweep and "Valyuta kurslari". The camera then pushes through the logo. |
| 6–12 s | Product reveal | A phone rises and powers on with the real home screen, and a desktop browser window slides in. The phone scrolls to the bank rates and the 13-region prayer-time widget. |
| 12–26 s | Features | Real UI cards fly out of the phone in four segments: 01 Valyuta kurslari (refresh tap), 02 Namoz vaqtlari (with the bot's prayer-time post), 03 Avtomatik postlar (frame checkboxes, rate-card posts) and 04 Guruh himoyasi (protection toggles). |
| 26–34 s | Hero | The camera orbits the phone, which is ringed by floating UI panels, light rings and a glowing rate line, then pushes into a hero shot on the 32 s hit. |
| 34–42 s | Brand | The 3D logo, "Valyuta kurslari · Namoz vaqtlari · Guruh himoyasi" and "@UsdGo24" share the frame with the phone. The final hit is at 36 s, then the frame holds. |

## Pipeline
- `capture/capture.js` takes dark-theme screenshots at 3× scale of a running instance of the app. `capture/crops.py` cuts the UI cards used as floating panels.
- `scene/` is a Three.js scene with a deterministic `window.renderAt(t)`. It uses physical materials with a studio environment map, a real-time mirror floor, bloom, colour grade, vignette, chromatic aberration and grain.
- `render.js` drives headless Chromium and writes JPEG frames (it also accepts `--stills t1,t2`).
- `audio/score.py` (numpy + scipy) generates the music and sound design. Its cue times match `scene/main.js`.

```bash
npm install                      # three.js
npm run stills                   # quick preview frames → out/stills
npm run frames                   # all 1260 frames → out/frames (split with: node render.js out/frames 30 <from> <to>)
npm run audio                    # out/soundtrack.wav
npm run encode                   # out/usdgo24_promo_1080p.mp4 (H.264 High, CRF 14, AAC 320k)
```
Set `PW=/path/to/node_modules/playwright` if Playwright is not installed locally. Set `CHROME=/path/to/chromium` to use a specific browser.

Note: the rates in the captured screens come from a local test instance. To show live values, re-run `capture/capture.js` against the production app.
