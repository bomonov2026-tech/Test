# Yuk sinovi natijalari (TartibOn)

Sinov **mahalliy kompyuterda** (4 yadro, PHP 8 ichki serveri 8 jarayon + MariaDB) o'tkazildi, internet va haqiqiy hosting hisobga olinmagan. Maqsad: kodning o'zida tor joylarni topish. Hosting (sayt.uz) tarifi boshqacha bo'lishi mumkin, shuning uchun raqamlarni **taqqoslash uchun** ishlating, aniq kafolat deb emas.

Har foydalanuvchi 5 ta so'rov yuboradi: `me`, `wallet`, `nk_list`, `vs_list`, `home_status` (ilovani ochganda ketadigan asosiy so'rovlar).

| Holat | Foydalanuvchi | Parallel | So'rov/s | p50 | p95 | Xato |
|---|---|---|---|---|---|---|
| Birinchi o'lchov | 500 | 50 | 490 | 88–93 ms | 160–407 ms | yo'q |
| Tezlashtirishdan keyin | 500 | 50 | 741 | 56–59 ms | 102–290 ms | yo'q |
| Tezlashtirishdan keyin | 900 | 150 | 870 | 149–162 ms | 208–327 ms | yo'q |

## Topilgan tor joy va tuzatish
Har so'rovga o'rtacha **69 ta** bazaga murojaat ketayotgan edi: migratsiya (jadval yangilanishi) bayroqlari 48 marta alohida o'qilardi. Endi hammasi **bitta** so'rov bilan o'qiladi: o'rtacha **23 ta**. O'tkazuvchanlik ~50% oshdi.

## Hozirgi holat
- 900 ta foydalanuvchi bir necha soniya ichida kirsa ham xato bo'lmadi.
- Qolgan 23 ta so'rov asosan sozlamalarni (settings) o'qish. Keyingi tezlashtirish: ko'p ishlatiladigan sozlamalarni so'rov ichida eslab qolish (kesh).
- Hostingda PHP-FPM jarayonlari soni va MySQL ulanish chegarasi (`max_connections`) asosiy cheklov bo'ladi. Birinchi haftada admin → Hisobotlar → «Sekin so'rovlar» bo'limini kuzating.

## Qayta o'tkazish
`php -S` ni `PHP_CLI_SERVER_WORKERS=8` bilan ishga tushirib, `e2e/load_test.mjs 500 50` ni yuguriting (sinov skripti repoda emas, ish papkasida saqlangan).
