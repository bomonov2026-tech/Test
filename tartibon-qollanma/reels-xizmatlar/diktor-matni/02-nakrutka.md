# Reels 02 — Nakrutka

**Instagram Reels · 9:16 (1080×1920) · 25 soniya**

**Kimga:** Kanal va sahifa egalari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Kanalingiz sekin o'syaptimi?»*

Kanalingiz juda sekin o'syaptimi?

**`00:02`**  ·  ekranda: *«Obunachi, layk, ko'rish buyurtma qiling»*

Nakrutka bo'limida obunachi, layk, izoh va ko'rish buyurtma qilasiz. Narx darrov ko'rinadi.

**`00:08`**  ·  ekranda: *«Telegram, Instagram, YouTube, TikTok»*

Telegram, Instagram, YouTube, TikTok — tarmoqni o'zingiz tanlaysiz.

**`00:13`**  ·  ekranda: *«Bajarilmasa — pul qaytadi»*

Buyurtma bajarilmasa pul to'liq qaytariladi. Qisman bajarilsa, bajarilmagan qismi qaytadi.

**`00:18`**  ·  ekranda: *«Ko'p miqdorga chegirma»*

Katta miqdorga chegirma bor. Avvalgi buyurtmani bir bosishda takrorlaysiz.

**`00:23`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 25 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-xizmatlar/02-nakrutka.txt`

```bash
ffmpeg -i video/02-nakrutka.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
