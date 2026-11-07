# Bajarilgan o'zgarishlar — `index.php`

Fayl: `154f82c7-index.php` (siz yuborgan hozirgi versiya) → `index.php` (tuzatilgan)

- Hajm: 1 917 899 → 1 916 347 belgi
- `php -l` — **sintaksis xatosi yo'q**
- O'zgartirilgan JavaScript massivlari Node bilan alohida tekshirildi — **to'g'ri**

---

## 1. Kurs posti — har 7 soatda

```diff
- define('KURS_SLOT', 4 * 3600);   /* kurs posti har 4 soatda bir marta ... */
+ define('KURS_SLOT', 7 * 3600);   /* kurs posti har 7 soatda bir marta; 24 soat 7 ga
+                                     bo'linmagani uchun yuborilish vaqti har kuni
+                                     3 soatga suriladi */
```

Yuborish sharti shu konstantadan hisoblangani uchun boshqa joy tegilmadi.

**Esda tuting:** post vaqti har kuni 3 soatga suriladi — 1-kuni 00:00, 07:00,
14:00, 21:00; 2-kuni 04:00, 11:00, 18:00 va hokazo. Doimiy vaqt kerak bo'lsa,
`7` o'rniga `8` qo'ying (00:00, 08:00, 16:00).

---

## 2. Namoz vaqtlari — ilovadan olib tashlandi

### ⚠️ Avvalgi maslahatim noto'g'ri edi — tuzatdim

Oldingi hujjatda «crontab'dan `?prayer_cron=` qatorini olib tashlang» deb
yozgan edim. **Bu xato bo'lardi.** Kodni ochib ko'rsam, o'sha endpoint
`cron_minute()` ni chaqirar ekan, u esa **45 ta vazifani** bajaradi: tongi
xabar, tungi xabar, ob-havo, postlar, kapcha, statistika, konkurs, nakrutka,
eSIM, pullik obuna va hokazo.

Ya'ni o'sha qatorni o'chirsangiz, **butun ilova to'xtab qolardi**.

To'g'ri yo'l — `cron_minute()` ichidan faqat namozga tegishli uchta vazifani
olib tashlash. Shuni qildim.

### Yuborish to'xtatildi (3 ta o'zgarish)

`cron_minute()` ro'yxatidan olib tashlandi:

| Olib tashlangan vazifa | Nima qilardi |
|---|---|
| `'Namoz' => cron_prayer()` | Namoz vaqti xabarlarini yuborardi |
| `'Namoz rasmi' => pimg_run(40)` | Namoz rasmini tayyorlardi |
| `'Bosh sahifa' => pr_home()` | Bosh sahifa uchun vaqtlarni oldindan olardi |

Qolgan 42 ta vazifa tegilmadi.

### Interfeysdan olib tashlandi (9 ta o'zgarish)

| # | Qayerdan |
|---|---|
| 4 | Bosh sahifadagi «🕌 Namoz vaqtlari» katagi |
| 5 | Chat sozlamalaridagi «Avtomatik xabarlar» qatori |
| 6 | «Hammasiga yoqish» kalitlari xaritasi (`flag_all`) |
| 7 | Menyudagi «Namoz vaqtlari» qatori |
| 8 | Sahifa nomlari ro'yxati (`HLN`) |
| 9 | Kanal ulanganda keladigan xabardagi qator |
| 10 | «Ilova haqida» dagi namoz bo'limi |
| 11 | Bot buyruqlari menyusidagi `/namoz` (2 joyda) |
| 12 | `/namoz` buyrug'ini qabul qilish (`cmd_handle`) |

Tekshirildi: `go('pnm')` va `go('pr')` chaqiruvlari **qolmadi** — ya'ni
foydalanuvchi namoz sahifalariga hech qayerdan o'ta olmaydi.

### Nima **atayin** qoldirildi

`pr_*` funksiyalari (22 ta), `png_prayer()`, `PR_*` konstantalari va
`prayer_destinations` jadvali **o'chirilmadi**.

**Sababi:** sizda «Ramazon taqvimi — Tez kunda» rejasi bor. Saharlik va
iftorlik vaqtlari aynan shu kod orqali hisoblanadi (`pr_fetch`, `pr_calc`,
hudud tanlash). Ularni o'chirsam, Ramazon taqvimini noldan yozishga to'g'ri
kelardi.

Bu kod endi **hech qayerdan chaqirilmaydi** — foydalanuvchiga ko'rinmaydi,
xabar yubormaydi, resurs sarflamaydi. Shunchaki turadi.

Agar Ramazon taqvimi rejangiz bekor bo'lsa, ayting — o'shanda butunlay
tozalab beraman va `DROP TABLE prayer_destinations;` ni ham yozib beraman.

### Admin panelda qoldi

Admin paneldagi «Namoz xabarnomalari» bo'limi o'chirilmadi — u faqat sizga
ko'rinadi va kelajakda qayta yoqish kerak bo'lsa asqatadi. Xohlasangiz uni
ham olib tashlayman.

---

## O'rnatishdan oldin

1. Hozirgi `index.php` ning **zaxira nusxasini** oling.
2. Yangi faylni yuklang.
3. Botga `/start` yozib, bosh sahifada namoz katagi yo'qligini tekshiring.
4. Biror kanal sozlamasini oching — «Avtomatik xabarlar» ro'yxatida namoz
   qatori bo'lmasligi kerak.
5. Kurs posti keyingi 7 soatlik oraliqda chiqadi.

**Eslatma:** bot buyruqlari menyusi (`/kurs`, `/namoz`) Telegram tomonida
keshlanadi. `/namoz` ro'yxatdan darhol yo'qolmasligi mumkin — bot
buyruqlarini qayta o'rnatganingizda yangilanadi.
