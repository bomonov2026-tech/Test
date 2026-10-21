# KursPremium — ertalabki post (myxvest.ru)

`index.php` — bitta faylli bot (PHP + MySQL). Sozlamalar (`BOT_TOKEN`, `DB_*`, `APP_URL`, `ADMIN_IDS`) repoda bo'sh — serverdagi faylda to'ldiring.

## Ertalabki post (haftaning 7 kuni — 7 xil matn + 7 xil video)
Video hostingga yuklanmaydi: baza kanaldagi post `copyMessage` bilan nusxalanadi (hajm cheklovi yo'q, "переслано" yozuvi chiqmaydi).

1. Yopiq **video baza kanal** oching, botni unga **administrator** qiling.
2. 7 ta videoni joylang. Har biridan: post → *Копировать ссылку* (`https://t.me/c/1234567890/45`).
3. Mini App → Admin panel → **🌅 Ertalabki post**: yoqing, vaqt `06:00`, har kunga havola + matn → *Saqlash*. *🧪 Menga yuborish* bilan tekshiring.
   Matnda `{kun}` — hafta kuni nomi; `<b>`, `<i>`, `<a href="...">` ishlaydi.
4. Kanallar ro'yxatida kerakli kanalga **🌅** tugmasini bosing (yoki kanal egasi "Kanallarim"da belgilaydi).

## Cron (myxvest panel → Cron)
Mavjud cron yetarli — har 5 daqiqada (aniqroq bo'lishi uchun har 1 daqiqada):
```
curl -s "https://DOMEN/KursPremium/?cron=KALIT&job=watch"
```
06:00 dan keyingi birinchi chaqiruvda yuboradi (3 soat oynasi ichida), har kanalga kuniga bir marta.
