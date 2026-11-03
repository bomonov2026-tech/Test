# TartibOn — video qo'llanma

Bosh sahifadagi **har bir bo'lim uchun alohida video** (9:16, 1080×1920, 30 fps).

| № | Dars | Mavzu | Davomiyligi | Video | Diktor matni | Subtitr |
|---|---|---|---|---|---|---|
| 1 | **Bosh panel** | Statistika, balans va birinchi ulanish | 5:43 | [`video/1-bosh-panel.mp4`](video/1-bosh-panel.mp4) | [`1-bosh-panel.md`](diktor-matni/1-bosh-panel.md) | [`1-bosh-panel.srt`](subtitr/1-bosh-panel.srt) |
| 2 | **Adminlar uchun** | Kanal va guruhni boshqarish | 9:47 | [`video/2-adminlar-uchun.mp4`](video/2-adminlar-uchun.mp4) | [`2-adminlar-uchun.md`](diktor-matni/2-adminlar-uchun.md) | [`2-adminlar-uchun.srt`](subtitr/2-adminlar-uchun.srt) |
| 3 | **Kundalik xabarlar** | Har kuni o'zi ishlaydigan postlar | 4:46 | [`video/3-kundalik-xabarlar.mp4`](video/3-kundalik-xabarlar.mp4) | [`3-kundalik-xabarlar.md`](diktor-matni/3-kundalik-xabarlar.md) | [`3-kundalik-xabarlar.srt`](subtitr/3-kundalik-xabarlar.srt) |
| 4 | **Xizmatlar** | Nakrutka, eSIM, savdo va reklama | 8:22 | [`video/4-xizmatlar.mp4`](video/4-xizmatlar.mp4) | [`4-xizmatlar.md`](diktor-matni/4-xizmatlar.md) | [`4-xizmatlar.srt`](subtitr/4-xizmatlar.srt) |
| 5 | **Savdo admin** | Biznes uchun tayyor post shablonlari | 7:03 | [`video/5-savdo-admin.mp4`](video/5-savdo-admin.mp4) | [`5-savdo-admin.md`](diktor-matni/5-savdo-admin.md) | [`5-savdo-admin.srt`](subtitr/5-savdo-admin.srt) |
| 6 | **Ilova** | Balans, bot yaratish va yordam | 4:12 | [`video/6-ilova.mp4`](video/6-ilova.mp4) | [`6-ilova.md`](diktor-matni/6-ilova.md) | [`6-ilova.srt`](subtitr/6-ilova.srt) |

**Jami: 6 ta video, 39 daqiqa 56 soniya.**

---

## Nima qilish kerak

1. `diktor-matni/` ichidagi `.md` faylni oching — har bir sahna uchun matn va **vaqt belgisi** bor.
2. Shu matnni mikrofonga o'qing (taxminan 140 so'z/daqiqa).
3. Ovozni videoga ulang:

```bash
ffmpeg -i video/1-bosh-panel.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor-1.mp4
```

## Texnik ma'lumot

- **O'lcham:** 1080×1920 (9:16) — Telegram, Instagram Reels, YouTube Shorts, TikTok uchun.
- **Kadr:** 30 fps, H.264 (yuv420p), `+faststart`.
- **Ovoz:** bo'sh AAC yo'lak (diktor ovozini ustiga yozish uchun).
- **Ikonkalar:** sizning skrinshotlaringizdan olingan haqiqiy 3D ikonkalar.
- **Matn manbasi:** `index.php` ichidagi «Ilova haqida» qo'llanma matnlari va kod mantig'i.

## Ekrandagi belgilar

| Belgi | Ma'nosi |
|---|---|
| 🔵 raqam | Ketma-ket bajariladigan **qadam** |
| 🟢 ✓ | Bo'limning **imkoniyati** |
| 🟠 ! | **Muhim** shart yoki ogohlantirish |
| 🟣 ★ | Amaliy **maslahat** |
| ⚪ • | Qo'shimcha **izoh** |
