# Reels 06 — Vakansiya

**Instagram Reels · 9:16 (1080×1920) · 26 soniya**

**Kimga:** Ish beruvchilar, HR

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Ishga odam qidiryapsizmi?»*

Ishga odam qidiryapsizmi?

**`00:02`**  ·  ekranda: *«Quruq matn posti ishlamaydi»*

Quruq matn ko'rinishidagi e'lonlarni odamlar oxirigacha o'qimaydi.

**`00:06`**  ·  ekranda: *«Lavozim, maosh, talablar»*

Lavozim, maosh, ish vaqti va talablar — hammasi bitta chiroyli rasmda.

**`00:11`**  ·  ekranda: *«Maoshni yozing»*

Maosh raqamini albatta yozing — maoshi ko'rsatilgan e'lonlar bir necha barobar ko'p javob oladi.

**`00:18`**  ·  ekranda: *««Rezyume yuborish»»*

Nomzod «Rezyume yuborish» tugmasini bosadi va to'g'ridan-to'g'ri sizga yoziladi.

**`00:23`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 26 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/06-vakansiya.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/06-vakansiya.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
