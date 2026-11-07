# Reels 08 — Restoran va kafe

**Instagram Reels · 9:16 (1080×1920) · 27 soniya**

**Kimga:** Kafe, restoran, yetkazib berish

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Kafe yoki restoraningiz bormi?»*

Kafe yoki restoraningiz bormi?

**`00:02`**  ·  ekranda: *«Menyu rasmi notekis chiqyaptimi?»*

Menyu va aksiya rasmlaringiz har safar boshqacha, notekis chiqyaptimi?

**`00:07`**  ·  ekranda: *«Kombo va narxlar»*

Kombo taomlar va narxlar — bitta tartibli, chiroyli rasmda.

**`00:12`**  ·  ekranda: *«Aksiyani ko'rsating»*

Aksiya va chegirmani ko'rsatsangiz, u rasmda yorqin belgida chiqadi.

**`00:17`**  ·  ekranda: *«Tushlikdan oldin yuboring»*

Maslahat: postni soat o'n birda, tushlikdan oldin yuboring — buyurtma aynan shu payt ko'p bo'ladi.

**`00:24`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 27 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/08-restoran.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/08-restoran.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
