# Tekshiruv natijalari

## Mahalliy tekshirildi

- PHP 7.4 grammatikasi: yangilangan `index.php`, `stick-ops/bridge.php` va `ops-config.example.php` php-parser bilan xatosiz parse qilindi.
- Mavjud PHP sahifadagi uchta inline JavaScript blokining sintaksisi tekshirildi; PHP echo joylari faqat sintaksis testi uchun qiymat bilan almashtirildi.
- Qo‘shilgan JavaScript modullarida Node syntax check o‘tdi.
- 18 ta avtomatlashtirilgan test: 12 ta jang dvigateli va 6 ta autentifikatsiya/WebSocket testi o‘tdi.
- Simulyatsiyada 10 botli to‘liq o‘yin yakunlandi; harakat, devorlar, qurol, o‘q almashtirish, bomba va raund hisoblari tekshirildi.
- Haqiqiy loopback WebSocket ulanishlarida 10 klientli 5v5 xona, 11-odam limiti, jamoa/mehmon/mezbon huquqlari, qayta ulanish va qayta o‘yin tekshirildi.
- Chromium’da parent/iframe sessiya ko‘prigi, server sozlanmagan holat, botlar bilan mashq, xarid oynasi, mobil va desktop joylashuv, xona yaratish va chiqish sinovdan o‘tdi.
- Chromium’da bir brauzer o‘yinchisi + 9 WebSocket klienti bilan online jang ochildi. Klaviaturadagi yurish va bir vaqtning o‘zida ikki barmoq bilan yurish/otish inputlari servergacha yetgani tekshirildi; barmoqlar qo‘yib yuborilganda inputlar to‘xtadi.
- Brauzer tekshiruvida JavaScript runtime xatosi qayd etilmadi.

`lobby-desktop.png`, `practice-desktop.png`, `practice-mobile.png` va `online-mobile.png` — ishlayotgan koddan olingan mahalliy skrinshotlar.

## Ushbu muhitda tekshirilmagan

- PHP interpreter ostida butun TartibOn ilovasi, haqiqiy `config.php`, MySQL va Telegram initData sessiyasi: bu komponentlar taqdim etilmagan. PHP uchun bajarilgan tekshiruv — grammatik parse; to‘liq PHP runtime testi emas.
- Jamoat internetidagi TLS/WSS domeni, tashqi reverse proxy, haqiqiy Telegram Android/iOS WebView va foydalanuvchilar hostingi.
- 1000 ulanish/100 xona yuklamasi, yuqori kechikishli mobil tarmoq, uzoq davom etadigan ish va bir nechta instansiya. Serverdagi xona limitlari bunday yuklama ko‘tara olishi isboti sifatida qabul qilinmasin.
- Jonli saytga deploy bajarilmadi. Foydalanuvchi hostingining konfiguratsiyasi va alohida Node xizmatini ulash qoladi.
