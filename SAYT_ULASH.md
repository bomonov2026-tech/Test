# TartibOn ni sayt.uz hostingiga ulash (ISPmanager 6)

Fayllar: `tartibon_sayt.zip` (index.php, config.php.example, .htaccess, kalit.php, server_check.php, pv_seed/).
**Bu qo'llanmani (SAYT_ULASH.md) hostingga yuklamang** — faqat zip.

## 0. Tayyorgarlik (5 daqiqa)
- [ ] **Yangi bot tokeni:** Telegram → @BotFather → `/mybots` → botingiz → API Token → **Revoke current token**. Yangisini nusxalab qo'ying (eskisi chatda ko'ringan edi).
- [ ] **Telegram ID** (admin): @userinfobot ga /start yozing.
- [ ] **Domen:** HTTPS bilan ishlaydigan domen yoki subdomen kerak (masalan `app.sizning-domen.uz`). Domenning DNS A-yozuvi sayt.uz hosting IP siga yo'naltirilgan bo'lsin (hosting IP sini sayt.uz kabinetida/panel «Основная информация» da ko'rasiz).

## 1. Sayt yaratish — «Сайты» → «Создать сайт»
1. Домен: o'z domeningiz (yoki subdomen).
2. **Версия PHP: 8.2 yoki 8.3**, режим **PHP-FPM** (yoki «PHP как FastCGI/PHP-FPM»).
3. **SSL-сертификат → «Let's Encrypt»** ni yoqing (bepul). Telegram faqat HTTPS bilan ishlaydi.
4. Yarating. Sayt papkasi odatda `www/DOMEN/` bo'ladi.

## 2. Baza — «Базы данных» → «Создать базу данных»
- Nomi, foydalanuvchi va **yangi kuchli parol** kiriting (eskisini ishlatmang). Uchalasini yozib qo'ying.
- Server: `localhost`.

## 3. Fayllarni joylash — «Файлы» (yoki FTP)
1. `www/DOMEN/` papkasiga **tartibon_sayt.zip** ni yuklang → «Извлечь» (arxivdan chiqarish). Fayllar **to'g'ridan-to'g'ri** `www/DOMEN/` ichida bo'lsin (`index.php` shu yerda).
2. `config.php.example` → **`config.php`** deb nomini o'zgartiring va ochib to'ldiring:
   - `BOT_TOKEN` — yangi token
   - `ADMIN_IDS` — sizning Telegram ID
   - `APP_URL` — `https://DOMEN/index.php` (https bilan, `index.php` gacha)
   - `DB_NAME`, `DB_USER`, `DB_PASS` — 2-qadamdagilar (`DB_HOST` = `localhost`)
3. Fayl huquqi: `config.php` → **640**.
4. Ixtiyoriy: `pv_seed/` — faqat Prava savollarini noldan qayta yuklash kerak bo'lsa. Eski bazani ko'chirsangiz kerak emas.

## 4A. Yangi (bo'sh) o'rnatish
Brauzerda: `https://DOMEN/index.php?install`
Ko'rinishi kerak: `Baza: OK`, `Bot: @TartibOnBot — OK`, `Webhook: OK`. Pastda cron buyruqlari chiqadi (5-qadamda ularning **CLI** varianti qulayroq).

## 4B. Eski ma'lumotlar bilan (foydalanuvchilar, balans, kanallar saqlansin)
1. Eski hostingda: «Базы данных» → phpMyAdmin → bazani tanlang → **Export (SQL)** → faylni saqlang.
   (Eski panelga kira olmasangiz, myxvest ta'minotchisidan baza nusxasini so'rang — hosting №9335.)
2. Yangi hostingda: phpMyAdmin → yangi baza → **Import** → shu `.sql` fayl. (Katta bo'lsa ISPmanager «Базы данных → Импорт».)
3. Brauzerda: `https://DOMEN/kalit.php` → u sizga tayyor `?setup=…` havolasini va cron buyruqlarini ko'rsatadi va **o'zini o'chiradi**.
4. Ko'rsatilgan `?setup=…` havolasini oching: `Baza: OK`, `Webhook: OK`, `Menyu: OK`.

## 5. Cron — «Планировщик» (Cron) → «Создать»
Ikkita vazifa (php yo'li panelda ko'rsatilganiga moslang; `kalit.php` yo'lni o'zi yozib beradi):
| Qachon | Buyruq |
|---|---|
| **Har daqiqada** (hamma maydon `*`) | `/usr/bin/php /var/www/FOYDALANUVCHI/data/www/DOMEN/index.php prayer` |
| **Har 5 daqiqada** (Минуты `*/5`) | `/usr/bin/php /var/www/FOYDALANUVCHI/data/www/DOMEN/index.php watch` |

Daqiqalik cron bo'lmasa: namoz, rejalashtirilgan postlar, nakrutka holati va konkurslar ishlamaydi. Agar panel har daqiqada cronni bermasa, sayt.uz yordamiga yozing yoki `?prayer_cron=…` havolasini tashqi cron xizmatiga (cron-job.org) ulang.

## 6. Tekshirish
1. Telegramda botga `/start` → javob keladi.
2. Ilovani oching (menyu tugmasi «TartibOn»): sahifalar ochiladi.
3. Admin sifatida: Admin panel → 🤖 Bot holati — cron «yashil» (1–2 daqiqada).
4. `https://DOMEN/server_check.php?k=TARTIBON` — hamma qator ✅ bo'lsin (ayniqsa Telegram API, curl, gd). So'ng **`server_check.php` ni o'chiring**.
5. Nakrutka: ilovada Nakrutka → ⚙️ Admin → API kalit → «🔌 Tekshirish».
6. BotFather'da asosiy Mini App havolasi bo'lsa (Bot Settings → Configure Mini App / Menu Button) — URL ni yangi `https://DOMEN/index.php` ga o'zgartiring.

## 7. Eski hostingni o'chirish
Webhook yangi serverga o'tgan (setup/install shuni qiladi). Eski hostingdagi **cronlarni to'xtating** (ikki joydan bir xil post ketmasin), eski hostingni 2–3 kun saqlab turing, so'ng yoping.

## Muammolar
| Belgi | Sabab / yechim |
|---|---|
| 500 xato | Panel → «Журналы» (error_log). PHP 8.2/8.3 ekanini, `config.php` to'g'ri to'ldirilganini tekshiring. |
| `Baza: XATO` | `DB_NAME/DB_USER/DB_PASS` noto'g'ri yoki foydalanuvchi bazaga biriktirilmagan. |
| `Bot: XATO` | `BOT_TOKEN` noto'g'ri (yangi token, bo'sh joysiz). |
| `Webhook: XATO` | `APP_URL` `https://` bilan boshlanib `index.php` gacha yozilmagan yoki SSL yoqilmagan. |
| Bot javob bermaydi | `?setup=…` ni qayta oching; `server_check.php` da `api.telegram.org` ✅ ekanini tekshiring. |
| Postlar/namoz ketmaydi | Daqiqalik cron qo'yilmagan. |
| Rasmlar chiqmaydi | PHP kengaytma `gd` yoqilmagan (PHP sozlamalarida yoqing). |

## Xavfsizlik
- `config.php`, bot tokeni, baza paroli, API kalitlarni chatga/skrinshotga tashlamang.
- `server_check.php` va `kalit.php` ishlatilgach **hostingda qolmasin** (kalit.php o'zi o'chadi).
- `.htaccess` `config.php` va `.log/.sql` fayllarni tashqaridan yopadi.
