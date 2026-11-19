# PO'LATHON — 40 s cinematic 3D promo (9:16, 1080x1920, 30 fps)

Pipeline (all open-source, runs headless in this container):

| Step | Tool | File |
|---|---|---|
| 3D scenes, camera, lights, FX, text | Three.js r160 in headless Chromium (WebGL/SwiftShader) | `src/scene.js`, `render.html` |
| Brand font (Uzbek ‘ apostrophe) | three.js `helvetiker_bold` typeface | `public/fonts/brand-bold.json` |
| Frame capture | Playwright, deterministic `renderFrame(frame)` | `tools/render_all.cjs` |
| Soundtrack (music, fire, sizzle, tandoor, impacts) | numpy synthesis | `tools/audio.py` → `build/mix.wav` |
| Voiceover (optional) | ElevenLabs API, key from env only | `tools/voiceover.py` → `build/voice.wav` |
| Encode | FFmpeg (H.264 + AAC, grade, grain) | `tools/encode.sh` → `out/polathon_promo_1080x1920.mp4` |

## Run
```bash
npm install
python3 -m http.server 8123 --bind 127.0.0.1 &   # serve the folder
node tools/render_all.cjs 3 1200                  # frames -> build/frames
python3 tools/audio.py                            # -> build/mix.wav
# optional voiceover:
ELEVENLABS_API_KEY=... ELEVEN_VOICE_ID=... python3 tools/voiceover.py && python3 tools/audio.py
bash tools/encode.sh
```
