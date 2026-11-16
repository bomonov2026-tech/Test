# Dasturxon — Telegram Mini App restoran tizimi
## O‘rnatish, sozlash va test qilish qo‘llanmasi

Bu hujjat oddiy PHP + MySQL hostingda (cPanel) ishlaydigan to‘liq tizimni o‘rnatish uchun.
Framework yo‘q: PHP 8.2+, PDO, MySQL/MariaDB, HTML/CSS/JavaScript.

---

## 1. Loyiha tuzilmasi

```
restoran/                       ← cPanel: /home/CPUSER/restoran/  (public_html DAN TASHQARIDA)
├── config/
│   ├── config.sample.php       ← namuna (nusxa oling: config.php)
│   └── .htaccess               ← internetdan yopiq
├── includes/                   ← umumiy yadro
│   ├── bootstrap.php           ← konfiguratsiya, xavfsiz sarlavhalar, JSON yordamchilari
│   ├── db.php                  ← PDO (prepared statements, tranzaksiya)
│   ├── auth.php                ← Telegram initData, admin sessiya, CSRF, audit jurnal
│   ├── telegram.php            ← initData HMAC tekshiruvi, Bot API, xabarlar navbati
│   ├── orders.php              ← narx hisobi (serverda), promo, ish vaqti, buyurtma, statuslar
│   ├── validation.php          ← kiruvchi ma’lumotlarni tekshirish
│   ├── uploads.php             ← xavfsiz rasm yuklash (5 MB, MIME, GD qayta kodlash)
│   └── layout.php              ← mini app va admin HTML qismlari
├── database/
│   ├── schema.sql              ← jadvallar (MySQL 8.0 / MariaDB 10.4+)
│   └── seed.sql                ← demo ma’lumotlar
├── cron/
│   └── tasks.php               ← xabarlarni yuborish va tozalash (CLI)
├── tools/
│   ├── create_admin.php        ← admin/oshpaz hisobi yaratish (CLI)
│   └── setup_telegram.php      ← webhook, buyruqlar, menyu tugmasi (CLI)
└── public_html/                ← YAGONA web-ochiq papka (hujjatlar ildizi)
    ├── index.php, menu.php, product.php, cart.php, checkout.php, orders.php, profile.php
    ├── api/                    ← products, categories, cart, orders, profile, settings
    ├── bot/webhook.php         ← Telegram bot webhook
    ├── admin/                  ← admin panel: login, dashboard, orders, order, kitchen, products,
    │                             categories, banners, customers, reports, staff, settings, api
    ├── assets/css, js, images, vendor/lucide
    └── uploads/                ← products, categories, banners, logo (PHP ijrosi o‘chirilgan)
```

> Eslatma: talab qilingan `config/database.php` va `config/app.php` o‘rniga bitta `config/config.php`
> ishlatiladi (DB, bot, sessiya, yuklash sozlamalari bitta joyda). `includes/header.php`/`footer.php`
> vazifasini `layout.php` bajaradi.

---

## 2. Talablar

| Komponent | Talab |
|---|---|
| PHP | **8.2 yoki yuqori** |
| PHP kengaytmalari | `pdo_mysql`, `curl`, `gd` (rasm kichraytirish), `fileinfo`, `mbstring`, `json` |
| MySQL | 8.0 yoki MariaDB 10.4+ (`utf8mb4`) |
| Domen | **HTTPS** (SSL) majburiy — Telegram Mini App va webhook faqat HTTPS bilan ishlaydi |
| Cron | cPanel "Cron Jobs" |

---

## 3. cPanel’da o‘rnatish (bosqichma-bosqich)

### 3.1. Fayllarni joylashtirish
1. Loyihani zip qilib **File Manager** orqali `/home/CPUSER/` ichiga yuklang va oching.
   Natija: `/home/CPUSER/restoran/public_html/...`, `/home/CPUSER/restoran/includes/...` va hokazo.
2. `restoran/` papkasi `public_html` ning **ichida emas**, yonida bo‘lishi kerak.

### 3.2. Subdomen va Document Root
1. **Domains → Create A New Domain** (yoki **Subdomains**): `restoran.example.uz`.
2. **Document Root** maydoniga aniq yo‘l yozing:
   `/home/CPUSER/restoran/public_html`
3. **SSL/TLS → Let’s Encrypt (AutoSSL)** orqali sertifikat oling. `https://restoran.example.uz` ochilishi kerak.

### 3.3. PHP versiyasi
**Software → Select PHP Version** → loyiha uchun **8.2** yoki yuqori tanlang va quyidagi kengaytmalarni yoqing:
`curl`, `gd`, `fileinfo`, `mbstring`, `pdo_mysql`, `json`. Saqlang.

### 3.4. MySQL baza
1. **Databases → MySQL® Databases**:
   - Baza yarating: `cpuser_restoran`
   - Foydalanuvchi yarating: `cpuser_resto` (kuchli parol bilan)
   - Foydalanuvchini bazaga biriktiring va **ALL PRIVILEGES** bering.
2. **phpMyAdmin**da `cpuser_restoran` ni tanlang:
   - **Import** → `restoran/database/schema.sql` (kodirovka: utf-8)
   - So‘ng **Import** → `restoran/database/seed.sql`

### 3.5. Konfiguratsiya
1. `restoran/config/config.sample.php` ni nusxalang → `restoran/config/config.php`.
2. Quyidagilarni to‘ldiring:
   - `app.base_url` → `https://restoran.example.uz` (oxirida `/` bo‘lmasin)
   - `db.name`, `db.user`, `db.pass` → 3.4 dagi ma’lumotlar
   - `telegram.bot_token` → BotFather token (4-bo‘limga qarang)
   - `telegram.bot_username` → bot username (@siz)
   - `telegram.webhook_secret` → tasodifiy 32+ belgi. Masalan SSH orqali: `openssl rand -hex 32`
3. `config/config.php` ichidagi maxfiy ma’lumotlarni hech kimga bermang va gitga yubormang.

### 3.6. Papka huquqlari
- `restoran/cron/` va `restoran/public_html/uploads/` — PHP yoza olishi kerak (odatda 755 papka, 644 fayl yetarli).
- Agar yuklashda xatolik bo‘lsa: `uploads/products`, `uploads/categories`, `uploads/banners`, `uploads/logo` papkalari
  avtomatik yaratiladi — ota papka (`uploads`) yozish huquqiga ega bo‘lishi kerak.

### 3.7. Admin hisobi yaratish
SSH yoki **Terminal** (cPanel) orqali:
```bash
cd ~/restoran
php tools/create_admin.php
```
So‘raladigan maydonlar: login, ism, rol (`admin` yoki `kitchen`), Telegram ID (ixtiyoriy), parol (kamida 10 belgi, ekranga chiqmaydi).

- `admin` — to‘liq huquq (dashboard, buyurtmalar, mahsulotlar, sozlamalar, xodimlar).
- `kitchen` — faqat oshxona ekrani (`/admin/kitchen.php`).

> SSH/Terminal yo‘q bo‘lsa, hosting provayderidan Terminal yoqishni so‘rang. Tizim ataylab veb-orqali
> hisob yaratish sahifasini bermaydi (xavfsizlik uchun).

---

## 4. Telegram bot sozlash

### 4.1. Bot yaratish
1. Telegram’da **@BotFather** ga `/newbot` yozing, nom va username kiriting.
2. Olingan tokenni nusxalang (`123456789:AAE…`) va `config/config.php` dagi `telegram.bot_token` ga yozing.
3. Ixtiyoriy: `/setdescription`, `/setuserpic` (restoran logotipi), `/setcommands` — tool o‘zi buyruqlarni o‘rnatadi.

### 4.2. Admin Telegram ID
Xodimlar (adminlar) ga yangi buyurtma xabari kelishi uchun ularning Telegram ID sini bilish kerak:
- Telegram’da **@userinfobot** ga `/start` yozing → ID raqamingiz chiqadi.
- Uni **Admin panel → Sozlamalar → Admin Telegram ID lar** ga yozing (vergul bilan bir nechta bo‘lishi mumkin)
  yoki `tools/create_admin.php` da Telegram ID kiriting.

### 4.3. Webhook va menyu tugmasi (bir buyruq)
```bash
cd ~/restoran
php tools/setup_telegram.php
```
Bu quyidagilarni o‘rnatadi:
- **Webhook** → `https://restoran.example.uz/bot/webhook.php` (maxfiy token bilan)
- **Buyruqlar** → `/start`, `/help`
- **Menyu tugmasi** → Mini App ochiladigan “Menyu” tugmasi

Tekshirish:
```bash
php tools/setup_telegram.php --info
```
Natijada `url` to‘g‘ri bo‘lishi va `last_error_message` bo‘lmasligi kerak.

> Webhook manzili HTTPS bo‘lishi shart. `config.php` dagi `webhook_secret` o‘zgarsa, `setup_telegram.php` ni qayta ishga tushiring.

### 4.4. Mijoz uchun
Mijoz botda **/start** bosadi → “🍽 Buyurtma berish” tugmasi chiqadi → Mini App ochiladi.
> Telegram qoidasi: bot mijozga xabar yubora olishi uchun mijoz botni bir marta ishga tushirgan bo‘lishi kerak.
> Mini Appni ochgan, lekin `/start` bosmagan mijozlarga status xabarlari yetib bormaydi (tizim bunday xatoni
> “failed” deb belgilaydi).

---

## 5. Cron Job sozlash

cPanel → **Advanced → Cron Jobs** → yangi vazifa:

- **Common Settings:** Every 2 minutes (`*/2 * * * *`)
- **Command** (PHP yo‘lini `which php` yoki “Select PHP Version” sahifasidan oling, masalan `/opt/cpanel/ea-php82/root/usr/bin/php`):

```bash
/opt/cpanel/ea-php82/root/usr/bin/php /home/CPUSER/restoran/cron/tasks.php >> /home/CPUSER/restoran/cron/cron.log 2>&1
```

Cron nimani bajaradi:
- Navbatdagi Telegram xabarlarini yuboradi (buyurtma holati, admin bildirishnomasi).
  Xato bo‘lsa, qayta urinish 1, 4, 9, 16 daqiqadan keyin; 5 urinishdan keyin “failed”.
- 90 kundan eski yuborilgan xabarlarni va 365 kundan eski admin jurnallarini tozalaydi.

Jurnal: `restoran/cron/cron.log` (har bir ishlashda bir qator). Bir vaqtda ikki marta ishlamaydi (qulf bor).

---

## 6. Admin panelga kirish

- Manzil: **https://restoran.example.uz/admin/**
- Login va parol — 3.7 bo‘limda yaratilgan.
- 5 marta noto‘g‘ri parol kiritilsa, 15 daqiqaga bloklanadi.
- 2 soat harakatsizlikdan keyin sessiya tugaydi.

Bo‘limlar:

| Bo‘lim | Vazifa |
|---|---|
| Bosh sahifa | Bugungi/haftalik/oylik ko‘rsatkichlar, eng ko‘p sotilgan taomlar, oxirgi buyurtmalar |
| Buyurtmalar | Filtr (status, tur, to‘lov, sana, qidiruv); tafsilot: status o‘zgartirish, bekor qilish sababi, naqd to‘lov qayd etish, ichki izoh |
| Oshxona | Faol buyurtmalar, katta tugmalar: “Tayyorlashni boshlash”, “Tayyor bo‘ldi” (10 soniyada yangilanadi) |
| Mahsulotlar | Qo‘shish/tahrirlash, rasm (5 MB), narx, chegirma, o‘lchov, belgilar, qo‘shimchalar (guruh + narx), mavjudlik |
| Kategoriyalar | Qo‘shish, tahrirlash, rasm, tartib (▲▼), faollashtirish/yashirish, o‘chirish |
| Bannerlar | Reklama kartochkalari (sarlavha, rasm, bosilganda ochiladigan mahsulot) |
| Mijozlar | Telegram ID, ism, telefon, buyurtmalar soni, jami xarid, oxirgi buyurtma; bloklash |
| Hisobotlar | Sana oralig‘i bo‘yicha kunlik savdo, eng ko‘p sotilganlar, CSV yuklab olish |
| Xodimlar | Administrator / oshpaz hisoblari, bloklash, parolni almashtirish |
| Sozlamalar | Restoran ma’lumoti, logotip, ish vaqti, dam olish kunlari, yetkazib berish narxi, minimal summa, xizmatlarni yoqish/o‘chirish, admin Telegram ID lari |

**Ovozli bildirishnoma:** admin panelda yuqori o‘ngdagi “Ovoz o‘chiq” tugmasini bosing — brauzer ovozni faqat
foydalanuvchi bosgandan keyin ijro etadi. Har 15 soniyada yangi buyurtmalar tekshiriladi.

---

## 7. Biznes qoidalari (qisqacha)

- **Narxlar faqat serverda hisoblanadi** (MySQL dagi narx, chegirma, qo‘shimcha narxi). Mijoz yuborgan narx e’tiborga olinmaydi.
- **Buyurtma raqami** (`#1001` …) sozlamalardagi hisoblagich orqali qulflanib oshadi — takrorlanmaydi.
- **Buyurtma holatlari:** `new → accepted → preparing → ready → delivering → completed`, `cancelled` istalgan
  faol holatdan. Olib ketishda `ready → completed`; yetkazib berishda `ready → delivering → completed`.
- **Oshpaz** faqat `preparing` va `ready` statuslarini qo‘ya oladi.
- **Mijoz** faqat o‘zining `new` holatidagi buyurtmasini bekor qila oladi.
- Har bir status o‘zgarishi `order_status_history` ga yoziladi va mijozga Telegram xabari yuboriladi.
- Ish vaqti tashqarisida, dam olish kunlarida yoki buyurtma qabul qilish o‘chirilganda buyurtma qabul qilinmaydi.
- Bekor qilingan buyurtmaning promo-kod ishlatilishi qaytariladi.

---

## 8. Test qilish bo‘yicha yo‘riqnoma

Quyidagi qadamlarni Telegram’dagi haqiqiy bot orqali bajaring. Har bir qadamda kutilgan natija ko‘rsatilgan.

### 8.1. Asosiy oqim (mijoz)
| # | Qadam | Kutilgan natija |
|---|---|---|
| 1 | Botda `/start` | Salom xabari va “🍽 Buyurtma berish” tugmasi |
| 2 | Tugmani bosing | Mini App ochiladi; bosh sahifada banner, kategoriyalar, mashhur taomlar |
| 3 | Qidiruvga “shashlik” yozing | Mos mahsulotlar chiqadi |
| 4 | “Qo‘y go‘shtidan shashlik” ni oching, “Piyoz” va “Non” ni tanlang, miqdor 2 | Narx: (35 000 + 3 000) × 2 = **76 000 so‘m** |
| 5 | “Savatga qo‘shish” | Toast xabar, savat belgisida son |
| 6 | Savatda promo `WELCOME10` | Chegirma 10 % qo‘llanadi; jami yangilanadi |
| 7 | Savatda miqdorni +/− bosing, bitta mahsulotni o‘chiring | Qatorlar va jami darhol yangilanadi |
| 8 | Sahifalar orasida o‘ting (menyu → savat) | Savat saqlanib qoladi |
| 9 | “Buyurtma rasmiylashtirish” → ism, telefon, manzil → “Buyurtmani tasdiqlash” | Tasdiqlash oynasida barcha mahsulotlar, narxlar va yakuniy summa |
| 10 | “Tasdiqlash” | Buyurtma raqami (masalan **#1001**) chiqadi |
| 11 | Profil → “Buyurtmalar tarixi” | Yangi buyurtma “Yangi” holatida; ro‘yxat 15 soniyada avtomatik yangilanadi |
| 12 | Buyurtmani bekor qiling (faqat “Yangi” holatida) | “Bekor qilindi” holati, promo qaytariladi |
| 13 | “Takroriy buyurtma” (yakunlangan/bekor qilingan) | Mahsulotlar savatga qaytadi |

### 8.2. Admin va oshpaz oqimi
| # | Qadam | Kutilgan natija |
|---|---|---|
| 14 | Admin Telegram’ida (sozlangan bo‘lsa) | “🛎 YANGI BUYURTMA” xabari, pastida “📋 Buyurtmani ochish” tugmasi |
| 15 | Admin panelga kirib, yangi buyurtmani oching; holatni “Qabul qilindi” qiling | Mijozga “✅ Buyurtma qabul qilindi” xabari (cron yoki darhol) |
| 16 | Oshxona ekrani: “Tayyorlashni boshlash” | Holat “Tayyorlanmoqda”; mijozga xabar |
| 17 | Oshxona: “Tayyor bo‘ldi” | Holat “Tayyor”; mijozga xabar |
| 18 | Oshpaz “Yakunlandi” ni bosa olmasligini tekshiring | Ruxsat yo‘q xabari |
| 19 | Yetkazib berish: “Yo‘lda” → “Yakunlandi” | Mijozga “🛵 Kuryer yo‘lga chiqdi” va “🎉 yakunlandi” |
| 20 | Naqd to‘lovni belgilang (“Naqd to‘lov qabul qilindi”) | To‘lov holati “To‘langan” |
| 21 | Bekor qilish sababi bilan bekor qiling | Mijozga sabab bilan xabar |

### 8.3. Chegara holatlari
| # | Qadam | Kutilgan natija |
|---|---|---|
| 22 | Sozlamalarda ish vaqtini hozirgi vaqtdan oldinroq qilib qo‘ying | Mini appda “Restoran hozir yopiq”; buyurtma rad etiladi |
| 23 | Dam olish kunini bugungi kunga belgilang | Buyurtma qabul qilinmaydi |
| 24 | Minimal summadan kam buyurtma | “Minimal buyurtma summasi” xatosi |
| 25 | Mahsulotni “Tugagan” qiling | Mini appda “Tugagan” belgisi, savatga qo‘shib bo‘lmaydi |
| 26 | Majburiy tanlovni tanlamasdan qo‘shing (“Ko‘k choy” — Shakar) | “Shakar bo‘yicha tanlov qiling” |
| 27 | Mijozni bloklang (Mijozlar bo‘limi) | Mini appda “Hisobingiz bloklangan” |
| 28 | 6 MB rasm yuklang | “Rasm hajmi 5 MB dan oshmasligi kerak” |
| 29 | `.php` faylni `.jpg` deb nomlab yuklang | Rad etiladi (MIME tekshiruvi) |
| 30 | Noto‘g‘ri parol bilan 5 marta kiring | 15 daqiqalik blok |
| 31 | Oshpaz hisobi bilan `/admin/dashboard.php` ga kiring | Oshxona sahifasiga yo‘naltiriladi |

### 8.4. Xavfsizlik tekshiruvi
| # | Tekshiruv | Kutilgan natija |
|---|---|---|
| 32 | `https://restoran.example.uz/config/config.php` | 403 / 404 (fayl web-ochiq emas) |
| 33 | `https://restoran.example.uz/cron/tasks.php` | Ochilmaydi (faqat CLI) |
| 34 | `https://restoran.example.uz/bot/webhook.php` ni brauzerda ochish | 405 (GET qabul qilinmaydi) |
| 35 | Webhook ga noto‘g‘ri `X-Telegram-Bot-Api-Secret-Token` bilan so‘rov | 403 |
| 36 | Mini app API ga `X-Init-Data` siz so‘rov (`/api/profile.php`) | 401 |
| 37 | `X-Init-Data` ni o‘zgartirib yuborish | 401 (HMAC tekshiruvi) |
| 38 | Oshpaz hisobi bilan `admin/api.php` ga CSRF tokensiz POST | 419 |

### 8.5. Cron tekshiruvi
```bash
cd ~/restoran && php cron/tasks.php
```
Natija: `yuborildi=N xato=M …` qatori. `cron/cron.log` da yangi yozuvlar paydo bo‘ladi.

---

## 9. Muammolarni bartaraf etish

| Belgi | Sabab va yechim |
|---|---|
| Mini app “Telegram orqali oching” deydi | Sahifa brauzerda ochilgan. Botdagi tugma orqali oching. |
| Mini app bo‘sh / “Internet aloqasi yo‘q” | `base_url` noto‘g‘ri yoki SSL yo‘q. `api/settings.php` brauzerda ochilishini tekshiring. |
| Status xabarlari kelmayapti | Mijoz botda `/start` bosmagan; yoki bot tokeni noto‘g‘ri; `cron` ishlamayapti. `notifications` jadvalidagi `last_error` ni ko‘ring. |
| Admin xabar olmayapti | Telegram ID noto‘g‘ri yoki admin botda `/start` bosmagan. |
| Webhook xato | `php tools/setup_telegram.php --info`; `webhook_secret` ikkala joyda bir xil bo‘lishi shart (config va webhook o‘rnatilganda). |
| 500 xato | cPanel → **Errors** yoki `error_log` faylini tekshiring; PHP versiyasi 8.2+ ekanini tasdiqlang. |
| Rasm yuklanmayapti | PHP `upload_max_filesize` va `post_max_size` ≥ 6M bo‘lsin; `gd` kengaytmasi yoqilgan bo‘lsin. |
| Sahifalar 404 | Document Root `restoran/public_html` ga qo‘yilganini tekshiring. |
| Ikonkalar ko‘rinmaydi | Ikonkalar o‘zimizda (`assets/vendor/lucide`), tashqi CDN kerak emas. Brauzer keshini tozalang. |

---

## 10. Xavfsizlik bo‘yicha tavsiyalar

1. `config/config.php` faqat serverda bo‘lsin; zaxira nusxalarni xavfsiz joyda saqlang.
2. Bot tokenini hech qayerga (chat, skrinshot, gitga) yubormang. Shubha bo‘lsa, BotFather’da `/revoke` qiling va
   `config.php` ni yangilab, `setup_telegram.php` ni qayta ishga tushiring.
3. Har bir xodim uchun alohida hisob yarating; ishdan ketganlarni bloklang.
4. PHP va MySQL versiyalarini yangilab turing; cPanel’da avtomatik yangilanishlarni yoqing.
5. Muhim jadvallarni (`orders`, `order_items`, `admin_logs`) kunlik zaxiralang (cPanel → Backup).
6. Admin jurnali (`admin_logs`) orqali kim nima o‘zgartirganini kuzating (Sozlamalar sahifasining pastki qismi).

---

## 11. Kelajak uchun: QR stol buyurtmasi

Hozirgi arxitektura QR stol uchun tayyor:
- `orders.order_type` ENUM ga `dine_in` va `table_id` ustunini qo‘shish;
- `includes/orders.php` dagi `create_order()` ga stol bo‘yicha tekshiruv qo‘shish;
- mijoz Mini App ni `product.php?table=…` orqali ochishi mumkin.
Birinchi versiyada bu funksiya ataylab qo‘shilmagan.

---

## 12. Fayl va jadval xaritasi (qisqacha)

- Jadvallar (15 talab + 2 qo‘shimcha): `users`, `admins`, `categories`, `products`, `product_options`,
  `product_option_values`, `orders`, `order_items`, `order_item_options`, `order_status_history`, `addresses`,
  `promo_codes`, `settings`, `notifications`, `admin_logs` + `banners`, `favorites`.
- Har bir jadvalda `PRIMARY KEY`, `FOREIGN KEY`, indekslar, `created_at`, `updated_at` bor.
- Buyurtma narxlari `order_items` va `order_item_options` da **snapshot** sifatida saqlanadi — keyinchalik narx
  o‘zgarsa ham eski buyurtma summasi o‘zgarmaydi.
