# Reels 03 — Taksi e'loni

**Instagram Reels · 9:16 (1080×1920) · 33 soniya**

**Kimga:** Haydovchilar, yo'nalish guruhlari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Haydovchimisiz?»*

Haydovchimisiz? Har kuni guruhga bir xil matn yozib charchadingizmi?

**`00:04`**  ·  ekranda: *«Yo'nalishni yozing»*

Yo'nalishni kiritasiz: qayerdan va qayerga.

**`00:07`**  ·  ekranda: *«Sana, vaqt, joy soni»*

Jo'nash sanasi, vaqti, bo'sh joylar soni va mashina modeli.

**`00:12`**  ·  ekranda: *«Tayyor post»*

Va mana — tayyor post. Telefon raqamingiz va aloqa manzilingiz bilan.

**`00:18`**  ·  ekranda: *«Yo'lovchi bir bosadi»*

Yo'lovchi post tagidagi «Haydovchiga yozish» tugmasini bosadi va to'g'ridan-to'g'ri sizga yoziladi.

**`00:23`**  ·  ekranda: *«Har kuni qayta yozmang»*

Har kuni qaytadan yozishning hojati yo'q — eski postni nusxalab, faqat sanani o'zgartirasiz.

**`00:30`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 33 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/03-taksi.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/03-taksi.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
