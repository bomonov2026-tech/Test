# Reels 07 — Testlar

**Instagram Reels · 9:16 (1080×1920) · 31 soniya**

**Kimga:** Guruhini jonlantirmoqchi bo'lganlar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Guruhingiz jim qoldimi?»*

Guruhingizda hech kim yozmay qo'ydimi?

**`00:03`**  ·  ekranda: *«Bot o'zi savol beradi»*

Bot belgilangan oraliqda o'zi savol berib turadi — to'rt variantli viktorina.

**`00:08`**  ·  ekranda: *«Maktab, imtihon yoki Prava»*

Mavzuni o'zingiz tanlaysiz: maktab va imtihon savollari yoki yo'l harakati qoidalari bo'yicha Prava testi.

**`00:15`**  ·  ekranda: *«Tez javobga ko'proq ochko»*

To'g'ri javobga ochko beriladi, tezroq javob berganga ko'proq. Oxirida eng yaxshi o'nta e'lon qilinadi.

**`00:22`**  ·  ekranda: *«Guruh jonlanadi»*

Bir necha kundan keyin a'zolarning o'zi savol chaqira boshlaydi — guruh jonlanadi.

**`00:28`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 31 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-adminlar/07-testlar.txt`

```bash
ffmpeg -i video/07-testlar.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
