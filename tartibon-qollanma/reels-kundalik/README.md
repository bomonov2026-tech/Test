# TartibOn — «Kundalik xabarlar» reklama Reels

Bosh sahifadagi «Kundalik xabarlar» ramkasidagi oltita katak uchun qisqa reklama roliklari
(+ bitta umumiy rolik). Uslub oldingi to'plamlar bilan bir xil.

| № | Reels | Kimga | Vaqt | Video | Diktor matni | ElevenLabs |
|---|---|---|---|---|---|---|
| 01 | **Kundalik xabarlar** | Kanal va guruh egalari | 52s | [`video/01-umumiy.mp4`](video/01-umumiy.mp4) | [`01-umumiy.md`](diktor-matni/01-umumiy.md) | [`../elevenlabs/reels-kundalik/01-umumiy.txt`](../elevenlabs/reels-kundalik/01-umumiy.txt) |
| 02 | **Namoz vaqtlari** | Diniy va mahalla kanallari | 32s | [`video/02-namoz-vaqtlari.mp4`](video/02-namoz-vaqtlari.mp4) | [`02-namoz-vaqtlari.md`](diktor-matni/02-namoz-vaqtlari.md) | [`../elevenlabs/reels-kundalik/02-namoz-vaqtlari.txt`](../elevenlabs/reels-kundalik/02-namoz-vaqtlari.txt) |
| 03 | **Dollar kursi** | Yangiliklar va biznes kanallari | 33s | [`video/03-dollar-kursi.mp4`](video/03-dollar-kursi.mp4) | [`03-dollar-kursi.md`](diktor-matni/03-dollar-kursi.md) | [`../elevenlabs/reels-kundalik/03-dollar-kursi.txt`](../elevenlabs/reels-kundalik/03-dollar-kursi.txt) |
| 04 | **Ob-havo** | Mahalla va yangilik kanallari | 29s | [`video/04-ob-havo.mp4`](video/04-ob-havo.mp4) | [`04-ob-havo.md`](diktor-matni/04-ob-havo.md) | [`../elevenlabs/reels-kundalik/04-ob-havo.txt`](../elevenlabs/reels-kundalik/04-ob-havo.txt) |
| 05 | **Tongi xabar** | Barcha kanal egalari | 33s | [`video/05-tongi-xabar.mp4`](video/05-tongi-xabar.mp4) | [`05-tongi-xabar.md`](diktor-matni/05-tongi-xabar.md) | [`../elevenlabs/reels-kundalik/05-tongi-xabar.txt`](../elevenlabs/reels-kundalik/05-tongi-xabar.txt) |
| 06 | **Tungi xabar** | Barcha kanal egalari | 29s | [`video/06-tungi-xabar.mp4`](video/06-tungi-xabar.mp4) | [`06-tungi-xabar.md`](diktor-matni/06-tungi-xabar.md) | [`../elevenlabs/reels-kundalik/06-tungi-xabar.txt`](../elevenlabs/reels-kundalik/06-tungi-xabar.txt) |
| 07 | **Bayram tabriklari** | Barcha kanal egalari | 31s | [`video/07-bayram-tabriklari.mp4`](video/07-bayram-tabriklari.mp4) | [`07-bayram-tabriklari.md`](diktor-matni/07-bayram-tabriklari.md) | [`../elevenlabs/reels-kundalik/07-bayram-tabriklari.txt`](../elevenlabs/reels-kundalik/07-bayram-tabriklari.txt) |

**Jami: 7 ta reels, 3 daqiqa 59 soniya.**

---

## Muhim

- **Videoda subtitr yo'q** — ekranda faqat qisqa sarlavha.
- **Namoz vaqtlari, Ob-havo va Dollar kursi postlari — haqiqiy.** Ular `index.php` ichidagi
  PHP rasm generatorlari bilan chizilgan: `png_prayer()`, `png_weather()`, `png_card()`.
  Matnlar ham haqiqiy: `DEF_CAP_C`, `DEF_WX_HEAD/FOOT`, `DEF_CAP`, `DEF_EV` konstantalaridan.
- Tongi, tungi va bayram postlari video yuboradi, shuning uchun ular Telegram post
  ko'rinishidagi namuna qilib chizilgan (video o'rniga pleyer belgisi).

## Qaysi birini birinchi joylash kerak

1. **01-umumiy** — bu to'plamning eng kuchlisi: oltita post bir rolikda ko'rsatiladi.
2. **02-namoz-vaqtlari** — diniy va mahalla kanallari uchun eng keng auditoriya.
3. **05-tongi-xabar** va **06-tungi-xabar** — «kuniga 2 post» argumenti.
4. **03-dollar-kursi** — yangiliklar va biznes kanallari uchun.
