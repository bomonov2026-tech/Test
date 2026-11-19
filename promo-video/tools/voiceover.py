"""Generates the Uzbek male voiceover with ElevenLabs and writes build/voice.wav.

Requires environment variables (never hard-code keys in the repo):
  ELEVENLABS_API_KEY   your ElevenLabs API key
  ELEVEN_VOICE_ID      a deep, confident male voice id from your ElevenLabs library
Optional:
  ELEVEN_MODEL         default eleven_multilingual_v2
"""
import json
import os
import sys
import urllib.request
import wave

KEY = os.environ.get('ELEVENLABS_API_KEY')
VOICE = os.environ.get('ELEVEN_VOICE_ID')
MODEL = os.environ.get('ELEVEN_MODEL', 'eleven_multilingual_v2')
if not KEY or not VOICE:
    sys.exit('Set ELEVENLABS_API_KEY and ELEVEN_VOICE_ID to generate the voiceover. Skipping (music-only mix).')

TEXT = (
    "Har bir lazzatning o‘z manzili bor… "
    "Endi esa haqiqiy ta’m manzili — Eshonguzarda! "
    "Olovda pishgan, shirali va xushbo‘y shashliklar… "
    "Tandirda yopilgan, tillarang va mazali somsalar… "
    "Barchasi bir manzilda! "
    "PO‘LATHON! "
    "Tez kunda eshiklarimiz siz uchun ochiladi! "
    "Eshonguzar, Po‘lathon — lazzatni his qilishga tayyor bo‘ling!"
)

body = json.dumps({
    'text': TEXT,
    'model_id': MODEL,
    'voice_settings': {'stability': 0.55, 'similarity_boost': 0.8, 'style': 0.35, 'use_speaker_boost': True},
}).encode('utf-8')
url = f'https://api.elevenlabs.io/v1/text-to-speech/{VOICE}?output_format=pcm_44100'
req = urllib.request.Request(url, data=body, method='POST', headers={
    'xi-api-key': KEY, 'Content-Type': 'application/json', 'Accept': 'audio/pcm'})
with urllib.request.urlopen(req, timeout=300) as r:
    pcm = r.read()

dst = os.path.join(os.path.dirname(__file__), '..', 'build', 'voice.wav')
with wave.open(dst, 'wb') as w:
    w.setnchannels(1); w.setsampwidth(2); w.setframerate(44100)
    w.writeframes(pcm)
print('wrote', dst, len(pcm) / 2 / 44100, 's')
