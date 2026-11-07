# Reels 02 — Kanallarim

**Instagram Reels · 9:16 (1080×1920) · 33 soniya**

**Kimga:** Bir nechta kanali bor adminlar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Bir nechta kanalingiz bormi?»*

Bir nechta kanalingiz bormi? Har birini alohida ochib yurasizmi?

**`00:04`**  ·  ekranda: *«Hammasi bitta ro'yxatda»*

Endi hammasi bitta ro'yxatda. Har bir kanal yonida obunachilar soni va bugungi o'sish turadi.

**`00:11`**  ·  ekranda: *«Har kanalga o'z sozlamasi»*

Kanalni ochsangiz, unga qaysi avtomatik xabarlar yoqilganini belgilaysiz — bittasiga namoz vaqtlari, boshqasiga faqat dollar kursi.

**`00:19`**  ·  ekranda: *«Ta'tilga chiqdingizmi?»*

Ta'tilga chiqsangiz, bitta kalitni bosasiz — barcha avtomatik xabarlar to'xtaydi.

**`00:24`**  ·  ekranda: *«Bot chiqarib yuborildimi?»*

Agar botni kanaldan chiqarib yuborishsa, qizil belgi darhol chiqadi va tuzatish yo'lini ko'rsatadi.

**`00:31`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 33 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-adminlar/02-kanallarim.txt`

```bash
ffmpeg -i video/02-kanallarim.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
