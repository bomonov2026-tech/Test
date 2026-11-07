# Reels 02 — Reklama Post Tayorlash

**Instagram Reels · 9:16 (1080×1920) · 45 soniya**

**Kimga:** Barcha kichik biznes egalari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Biznesingiz bormi?»*

Biznesingiz bormi? Lekin e'lonlaringiz oddiy va ko'zga tashlanmaydimi?

**`00:04`**  ·  ekranda: *«Dizayner kerak emas»*

Dizayner yollash ham, grafik dastur o'rganish ham kerak emas.

**`00:09`**  ·  ekranda: *«Mahsulot sotasizmi?»*

Mahsulot sotasizmi — Savdogar bo'limi.

**`00:12`**  ·  ekranda: *«Mashina sotasizmi?»*

Mashina sotasizmi — Avto savdo.

**`00:15`**  ·  ekranda: *«Uy sotasizmi?»*

Uy yoki kvartira — Ko'chmas mulk.

**`00:18`**  ·  ekranda: *«Odam qidiryapsizmi?»*

Ishga odam qidiryapsizmi — Vakansiya.

**`00:21`**  ·  ekranda: *«Kursga qabul?»*

Kursga qabul e'lon qilyapsizmi — O'quv markazi.

**`00:25`**  ·  ekranda: *«Kafe yoki restoran?»*

Kafe yoki restoran bo'lsa — menyu va aksiya posti.

**`00:30`**  ·  ekranda: *«Salon yoki klinika?»*

Salon yoki klinika — narxlar ro'yxati bilan.

**`00:34`**  ·  ekranda: *«Hammasi 1 daqiqada»*

Hammasi bir xil ishlaydi: rasm va bir nechta maydon — tayyor post.

**`00:40`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring — bepul sinab ko'ring.

---

### Instagram uchun

- **Davomiyligi:** 45 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/02-umumiy.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/02-umumiy.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
