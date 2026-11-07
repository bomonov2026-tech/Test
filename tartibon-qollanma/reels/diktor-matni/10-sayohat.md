# Reels 10 — Sayohat e'loni

**Instagram Reels · 9:16 (1080×1920) · 31 soniya**

**Kimga:** Turagentliklar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Turagentlikmisiz?»*

Turagentlik bilan shug'ullanasizmi?

**`00:02`**  ·  ekranda: *«Tur paket posti kerakmi?»*

Tur paket, qaynoq tur yoki Umra uchun chiroyli post kerakmi?

**`00:07`**  ·  ekranda: *«Yo'nalish, sana, narx»*

Yo'nalish, sana, kecha soni va narxni kiritasiz — davlat bayrog'i o'zi qo'yiladi.

**`00:13`**  ·  ekranda: *«Narxga nima kiradi?»*

Eng muhimi: narxga nima kirishini alohida ko'rsatasiz — aviabilet, mehmonxona, transfer va ovqatlanish.

**`00:19`**  ·  ekranda: *«Savollar kamayadi»*

Shunda mijozlarning savollari ikki barobar kamayadi — chunki hammasi rasmda ko'rinadi.

**`00:25`**  ·  ekranda: *««Bron qilish» tugmasi»*

Post tagida «Bron qilish» tugmasi turadi.

**`00:29`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 31 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/10-sayohat.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/10-sayohat.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
