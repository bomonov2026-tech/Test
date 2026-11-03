# Reels 08 — Pullik kanallar

**Instagram Reels · 9:16 (1080×1920) · 34 soniya**

**Kimga:** Kontentdan daromad olmoqchi bo'lganlar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Kontentingizdan daromad olasizmi?»*

Kanalingizga mehnat qilasiz, lekin daromad yo'qmi?

**`00:03`**  ·  ekranda: *«Yopiq kanalga pullik obuna»*

Yopiq kanalingizga pullik obuna soting. Pul to'g'ridan-to'g'ri balansingizga tushadi.

**`00:08`**  ·  ekranda: *«Paketlarni o'zingiz belgilaysiz»*

Paketlar: bir, uch, olti va o'n ikki oylik. Narxni va uzoq muddatga chegirmani o'zingiz qo'yasiz.

**`00:15`**  ·  ekranda: *«Bepul sinov»*

Bepul sinov muddatini yoqsangiz, har bir odam bir marta bepul kirib ko'radi — shundan keyin ko'pchilik qoladi.

**`00:23`**  ·  ekranda: *«Hammasi avtomatik»*

Qolgani avtomatik: kirish havolasi o'zi boradi, muddat tugashiga uch kun qolganda eslatma ketadi, tugaganda bot o'zi chiqaradi.

**`00:31`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 34 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-adminlar/08-pullik-kanallar.txt`

```bash
ffmpeg -i video/08-pullik-kanallar.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
