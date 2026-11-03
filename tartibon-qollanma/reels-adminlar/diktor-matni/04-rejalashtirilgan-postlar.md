# Reels 04 — Rejalashtirilgan postlar

**Instagram Reels · 9:16 (1080×1920) · 30 soniya**

**Kimga:** Muntazam post qo'yadigan adminlar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Har kuni bir vaqtda post qo'yasizmi?»*

Har kuni belgilangan vaqtda post qo'yish uchun telefon qarab o'tirasizmi?

**`00:05`**  ·  ekranda: *«Oldindan tayyorlab qo'ying»*

Postni oldindan tayyorlab, kerakli kunga va soatga qo'yasiz. Belgilangan vaqtda bot o'zi chiqaradi.

**`00:11`**  ·  ekranda: *«Bir marta, har kuni yoki har hafta»*

Takrorlanishni o'zingiz tanlaysiz: bir marta, har kuni yoki har hafta.

**`00:16`**  ·  ekranda: *«Qanday ko'rinishini oldindan ko'rasiz»*

Post Telegramda aynan qanday ko'rinishi ekranda darhol ko'rinib turadi — yuborishdan oldin.

**`00:22`**  ·  ekranda: *««Menga yuborish»»*

Ishonchingiz komil bo'lmasa, avval faqat o'zingizga yuborib tekshirasiz.

**`00:27`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 30 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-adminlar/04-rejalashtirilgan-postlar.txt`

```bash
ffmpeg -i video/04-rejalashtirilgan-postlar.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
