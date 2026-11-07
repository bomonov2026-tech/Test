# Reels 09 — Reklama birjasi

**Instagram Reels · 9:16 (1080×1920) · 36 soniya**

**Kimga:** Kanal egalari va reklama beruvchilar

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: tez, ishonchli, do'stona. Har qator — bitta kadr.

---

**`00:00`**  ·  ekranda: *«Reklama berdingiz — pul ketdi, natija yo'q?»*

Reklama uchun pul to'ladingiz, lekin post bir soatdan keyin o'chirib yuborildimi?

**`00:05`**  ·  ekranda: *«Bu yerda unday bo'lmaydi»*

Reklama birjasida bunday bo'lmaydi. Chunki pul kafolat ostida turadi.

**`00:10`**  ·  ekranda: *«Pul darhol sotuvchiga o'tmaydi»*

To'lagan pulingiz darhol sotuvchiga o'tmaydi. Reklama kelishilgan vaqt — yigirma to'rt yoki qirq sakkiz soat — turgandan keyin o'zi o'tadi.

**`00:18`**  ·  ekranda: *«O'chirib yuborilsa?»*

Agar reklama muddatidan oldin o'chirilsa, nizo ochasiz va qarorni admin chiqaradi: pul sizga qaytadi yoki sotuvchiga beriladi.

**`00:26`**  ·  ekranda: *«Sotuvchi bo'lsangiz»*

Kanal egasi bo'lsangiz, narx va formatni o'zingiz belgilaysiz — buyurtma kelganda Joylash tugmasini bosasiz.

**`00:33`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring.

---

### Instagram uchun

- **Davomiyligi:** 36 soniya
- **Ovoz:** video ovozsiz. ElevenLabs matni: `elevenlabs/reels-xizmatlar/09-reklama-birjasi.txt`

```bash
ffmpeg -i video/09-reklama-birjasi.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```
