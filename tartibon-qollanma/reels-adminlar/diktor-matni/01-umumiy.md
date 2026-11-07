# Reels 01 — Adminlar uchun

**Instagram Reels · 9:16 (1080×1920) · 41 soniya**

**Kimga:** Kanal yoki guruh egalari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Kanalingiz bormi?»*

Telegramda kanalingiz yoki guruhingiz bormi?

**`00:03`**  ·  ekranda: *«Hammasini qo'lda qilyapsizmi?»*

Postni qo'lda yuborasiz, spamni qo'lda o'chirasiz, obunachilarni qo'lda sanaysiz.

**`00:07`**  ·  ekranda: *«Barcha kanallar bir joyda»*

Barcha kanal va guruhlaringiz bitta ro'yxatda — har biri alohida sozlanadi.

**`00:13`**  ·  ekranda: *«Spamni bot o'zi o'chiradi»*

Reklama va spamni bot o'zi o'chiradi. Siz hatto ko'rmaysiz ham.

**`00:18`**  ·  ekranda: *«Postni oldindan qo'yasiz»*

Postni oldindan tayyorlab, kerakli kunga qo'yasiz — bot o'zi chiqaradi.

**`00:23`**  ·  ekranda: *«O'sishni ko'rib turasiz»*

O'sish grafigini ko'rib turasiz va hisobotni Excelga yuklab olasiz.

**`00:28`**  ·  ekranda: *«Kanaldan daromad»*

Yopiq kanalingizga pullik obuna sotasiz — pul to'g'ridan-to'g'ri balansingizga tushadi.

**`00:33`**  ·  ekranda: *«To'qqizta vosita»*

Jami to'qqizta vosita. Hammasi Telegramning ichida — hech narsa o'rnatmaysiz.

**`00:39`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 41 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-adminlar/01-umumiy.txt`

```bash
ffmpeg -i video/01-umumiy.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
