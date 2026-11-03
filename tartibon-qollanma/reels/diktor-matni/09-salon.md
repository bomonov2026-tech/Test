# Reels 09 — Salon va klinika

**Instagram Reels · 9:16 (1080×1920) · 25 soniya**

**Kimga:** Go'zallik salonlari, klinikalar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Salon yoki klinikangiz bormi?»*

Go'zallik saloni yoki klinikangiz bormi?

**`00:03`**  ·  ekranda: *«Narxlarni har kuni so'rashadimi?»*

Mijozlar har kuni bir xil savolni berib, narxlarni so'rayveradimi?

**`00:07`**  ·  ekranda: *«Narxlar ro'yxati»*

Barcha xizmatlar va narxlarni bitta rasmga joylaysiz — savollar o'z-o'zidan kamayadi.

**`00:13`**  ·  ekranda: *«Aksiyani qo'shing»*

Vaqtinchalik aksiyani ham qo'shasiz — bu yangi mijoz keltiradi.

**`00:18`**  ·  ekranda: *««Navbatga yozilish»»*

Mijoz «Navbatga yozilish» tugmasini bosadi va sizga yoziladi.

**`00:22`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 25 soniya
- **Format:** 9:16, 1080×1920, 30 fps — Reels, TikTok va YouTube Shorts uchun mos.
- **Ovoz:** video ovozsiz. Diktor ovozini yozib, shunday ulaysiz:

```bash
ffmpeg -i video/09-salon.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

- Ovoz o'rniga trend musiqa qo'ysangiz ham bo'ladi — unda `subtitr/09-salon.srt` ni Instagramga
  izoh (caption) yoki avtomatik subtitr sifatida ishlating.
