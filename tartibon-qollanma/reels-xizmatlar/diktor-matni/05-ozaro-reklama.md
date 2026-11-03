# Reels 05 — O'zaro reklama

**Instagram Reels · 9:16 (1080×1920) · 25 soniya**

**Kimga:** Kanal egalari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Reklamaga pul yo'qmi?»*

Kanalingizni o'stirmoqchisiz, lekin reklamaga pul yo'qmi?

**`00:03`**  ·  ekranda: *«O'zaro reklama bepul ishlaydi»*

Unda o'zaro reklama qiling: siz uning kanalini, u sizning kanalingizni e'lon qiladi. Bepul.

**`00:09`**  ·  ekranda: *«Katalogdan mos kanalni toping»*

Kanalingizni kategoriya bilan katalogga qo'shasiz. Boshqalar sizni topadi, siz ham qidirib taklif yuborasiz.

**`00:16`**  ·  ekranda: *«Auditoriyangizga yaqin kanal tanlang»*

Maslahat: mavzuingizga yaqin kanalni tanlang — natija bir necha barobar kuchli bo'ladi.

**`00:22`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 25 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-xizmatlar/05-ozaro-reklama.txt`

```bash
ffmpeg -i video/05-ozaro-reklama.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
