# Reels 02 — Namoz vaqtlari

**Instagram Reels · 9:16 (1080×1920) · 32 soniya**

**Kimga:** Diniy va mahalla kanallari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Namoz vaqtlarini qo'lda yozasizmi?»*

Kanalingizga namoz vaqtlarini har kuni qo'lda yozib chiqasizmi?

**`00:04`**  ·  ekranda: *«Bot o'zi chiroyli post qiladi»*

Bot buni o'zi qiladi: o'n ikki viloyatning vaqti bitta chiroyli rasmga joylanadi.

**`00:10`**  ·  ekranda: *«Har namozdan 30 daqiqa keyin»*

Xabar azon vaqtida emas, har namozdan o'ttiz daqiqa keyin yuboriladi — odamlar namozni o'qib bo'lgach.

**`00:17`**  ·  ekranda: *«Hududni o'zingiz tanlaysiz»*

Hududni o'zingiz tanlaysiz — viloyat va uning ichidagi shaharlar ro'yxatidan.

**`00:22`**  ·  ekranda: *«Manba — islom.uz taqvimi»*

Vaqtlar islom.uz taqvimi asosida. Manba ishlamay qolsa, bot vaqtlarni o'zi hisoblab, xabarni baribir yuboradi.

**`00:29`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 32 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-kundalik/02-namoz-vaqtlari.txt`

```bash
ffmpeg -i video/02-namoz-vaqtlari.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
