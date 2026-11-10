# Reels 05 — Tongi xabar

**Instagram Reels · 9:16 (1080×1920) · 33 soniya**

**Kimga:** Barcha kanal egalari

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Obunachilaringiz ertalab nimani ko'radi?»*

Obunachilaringiz ertalab telefonni ochganda kanalingizda nima turadi?

**`00:03`**  ·  ekranda: *«Har kuni 04:30 da video bilan xabar»*

Tongi xabar har kuni soat to'rt yarimda video bilan yuboriladi — ular uyg'onguncha post allaqachon tayyor turadi.

**`00:11`**  ·  ekranda: *«Kun, sana va hijriy sana»*

Matnda bugun haftaning qaysi kuni ekani, sana va hijriy sana avtomatik yoziladi. Siz hech narsa yozmaysiz.

**`00:19`**  ·  ekranda: *«Qaysi kunlar — o'zingiz tanlaysiz»*

Haftaning qaysi kunlarida yuborilishini o'zingiz belgilaysiz — masalan, faqat ish kunlari.

**`00:25`**  ·  ekranda: *«Eng yuqori ko'rish shu postda»*

Amaliyot shuni ko'rsatadi: ertalabki post eng ko'p ko'riladigan post bo'ladi.

**`00:30`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 33 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-kundalik/05-tongi-xabar.txt`

```bash
ffmpeg -i video/05-tongi-xabar.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
