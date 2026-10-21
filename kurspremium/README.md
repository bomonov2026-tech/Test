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

## Namoz vaqtlari va namoz xabarnomalari

### Nimalar qo'shilgan
- **Menyu → 🕌 Namoz vaqtlari**: hudud tanlanadi (viloyatlar bo'yicha guruhlangan), bugungi 6 ta vaqt ko'rsatiladi (Bomdod, Quyosh, Peshin, Asr, Shom, Xufton). Keyingi namoz ajratib ko'rsatiladi. Tanlangan hudud telefonda eslab qolinadi.
- **Admin panel → 🕌 Namoz xabarnomalari**: kanal yoki guruh qo'shish (nomi + chat_id), hudud tanlash, yoqish/o'chirish, kunlik jadval vaqti, har bir namoz uchun alohida yoqish va matn, 🧪 test, 📜 tarix, xatolar, 🗑 o'chirish, kesh oralig'i, cron buyrug'i.
- Kunlik jadval va namoz vaqti kirgandagi xabarlar bir-biridan mustaqil sozlanadi. Quyosh chiqishi xabari ixtiyoriy.
- Matnda o'zgaruvchilar ishlatiladi: `{hudud} {sana} {hijriy} {bomdod} {quyosh} {peshin} {asr} {shom} {xufton}`, alohida xabarda yana `{namoz} {vaqt} {belgi}`. Xabarlar oddiy matn bo'lib yuboriladi (HTML ishlatilmaydi).

### API manbasi: islomapi.uz
- Ochiq kodi: https://github.com/zero8d/namozvatqiapi — ma'lumotlar islom.uz taqvimidan olingan. Kalit kerak emas.
- Ishlatiladigan so'rovlar: `GET /api/daily?region=…&month=…&day=…` (asosiy), `GET /api/present/day?region=…` (zaxira).
- Javobdagi maydonlar: `times.tong_saharlik` (Bomdod), `quyosh`, `peshin`, `asr`, `shom_iftor` (Shom), `hufton` (Xufton), `date`, `hijri_date`.
- **Cheklovlar:**
  - Bu islom.uz ning rasmiy API'si emas, uchinchi tomon xizmati. Kafolat yo'q, vaqti-vaqti bilan ishlamay qolishi mumkin.
  - Ro'yxatda har bir viloyatning faqat katta shaharlari va tuman markazlari bor (jami 46 ta, hammasi API'da mavjud). API'da yo'q hududlar uchun vaqt hisoblanmaydi va to'qib chiqarilmaydi (masalan, Sirdaryodan faqat Guliston bor).
  - Javobdagi sana so'ralgan sana bilan solishtiriladi. Boshqa yil yoki boshqa kun ma'lumoti qabul qilinmaydi.
- **Zaxira hisob:** API ishlamasa (server o'chiq, hosting ulana olmaydi, 5xx yoki noto'g'ri javob), vaqtlar botning o'zida hisoblanadi. Hisob islom.uz uslubida: Bomdod va Xufton 15,5°, Asr Hanafiy, Shom = quyosh botishi + 4 daqiqa. Toshkent uchun 01.10.2026 va 20.10.2026 dagi rasmiy jadval bilan 6 ta vaqt daqiqasigacha mos keldi; boshqa shaharlarda 1–2 daqiqa farq bo'lishi mumkin. Bunday holatda sahifada «hisoblangan» deb yoziladi. API tiklangach, 15 daqiqa ichida bot o'zi yana API ma'lumotiga qaytadi.
- Kesh: `prayer_cache` jadvali, standart holatda 360 daqiqa saqlanadi (admin panelda 15–1440 oralig'ida o'zgartiriladi). API xato bersa, o'sha hududga 15 daqiqadan keyin qayta uriniladi. Shu orada oxirgi saqlangan ma'lumot ⚠️ belgisi bilan ko'rsatiladi.

### Cron (myxvest panel → Cron, har daqiqada)
```
php /home/USER/…/KursPremium/index.php prayer
```
yoki URL orqali (kalit admin paneldagi "Namoz xabarnomalari → Sozlamalar" bo'limida ko'rinadi):
```
wget -q -O /dev/null "https://DOMEN/KursPremium/?prayer_cron=KALIT"
```
- Mavjud `?cron=…&job=watch` cron ham namoz xabarlarini tekshiradi. Faqat u ishlasa, xabar 5 daqiqagacha kechikishi mumkin.
- Ikkita cron bir vaqtda ishga tushsa, `GET_LOCK` va har bir xabarning noyob `uid` qiymati (`dest:namoz:sana`) xabarning ikki marta ketishiga yo'l qo'ymaydi.
- Telegram xato bersa, xabar 2 daqiqadan keyin qayta yuboriladi, ko'pi bilan 3 marta. Bu faqat namoz vaqtidan keyingi 30 daqiqa ichida (kunlik jadval uchun 60 daqiqa ichida) bo'ladi.
- Cron ishlamasa ham Web App va valyuta kurslari ishlayveradi.

### Botni kanal yoki guruhga qo'shish
1. Kanal → Изменить → Администраторы → Добавить администратора → `@KursPremiumBot` → **«Публикация сообщений»** huquqini yoqing.
2. Guruh → botni a'zo qilib qo'shing (yaxshisi administrator qiling).
3. chat_id ni bilish: ochiq kanal bo'lsa `@username` yozsa bo'ladi. Yopiq kanal bo'lsa, kanaldagi postni @userinfobot / @getidsbot ga forward qiling va `-100…` raqamini oling.
4. Admin panel → 🕌 Namoz xabarnomalari → nom + chat_id + hudud → Qo'shish. Bot huquqi yetmasa, ogohlantirish chiqadi.
5. Avval 🧪 Test tugmasini bosing.

### Jadvallar (avtomatik yaratiladi, mavjud jadvallarga tegilmaydi)
`?setup=KALIT` ochilganda yoki Web App/cron birinchi marta ishlaganda (`mig_pr`) `CREATE TABLE IF NOT EXISTS` bilan yaratiladi:
```sql
CREATE TABLE IF NOT EXISTS prayer_settings (k VARCHAR(60) PRIMARY KEY, v TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prayer_destinations (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(80) NOT NULL, chat_id BIGINT NOT NULL, chat_type VARCHAR(12) NOT NULL DEFAULT '', region VARCHAR(40) NOT NULL, active TINYINT NOT NULL DEFAULT 1, last_ok_at DATETIME NULL, last_err VARCHAR(255) NULL, last_err_at DATETIME NULL, created_at DATETIME NOT NULL, UNIQUE KEY u (chat_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prayer_schedules (id INT AUTO_INCREMENT PRIMARY KEY, dest_id INT NOT NULL, kind VARCHAR(10) NOT NULL, enabled TINYINT NOT NULL DEFAULT 0, at_time CHAR(5) NULL, body TEXT NULL, UNIQUE KEY u (dest_id,kind)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prayer_sent_log (id INT AUTO_INCREMENT PRIMARY KEY, uid VARCHAR(80) NOT NULL, dest_id INT NOT NULL, kind VARCHAR(10) NOT NULL, day DATE NOT NULL, status VARCHAR(10) NOT NULL, tries INT NOT NULL DEFAULT 1, msg_id BIGINT NULL, err VARCHAR(255) NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE KEY u (uid), KEY d (dest_id,id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS prayer_cache (region VARCHAR(40) NOT NULL, day DATE NOT NULL, times TEXT NULL, hijri VARCHAR(60) NULL, source VARCHAR(255) NULL, fetched_at DATETIME NULL, tried_at DATETIME NULL, err VARCHAR(255) NULL, PRIMARY KEY (region,day)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tekshirish
`?diag=KALIT` sahifasida endi `Namoz API: OK …` yoki xato matni ko'rinadi. Shu bilan API hostingdan ochilishini tekshirasiz.

## Guruh himoyasi (🛡)

Menyu → **🛡 Guruh himoyasi**: guruh egasi o'z guruhlarida himoyani va har bir qoidani ✅/⬜ qiladi. Admin panel → **🛡 Guruh himoyasi**: barcha guruhlar, statistika va qo'shimcha so'kinish so'zlari.

| Qoida | Nima o'chiriladi |
|---|---|
| 🖇 Reklama havolalari | `http`, `www`, `t.me` havolalari, yashirin havola (text_link), kanaldan forward, tugmali yoki inline-bot xabarlari |
| 🔞 So'kingan xabarlar | o'zbekcha va ruscha so'kinish ildizlari (52 ta). Kirill harflar lotinga o'giriladi, `h`/`x` bir xil o'qiladi, takrorlangan harflar qisqartiriladi. Ro'yxatni admin panelda kengaytirish mumkin |
| 🔊 Ovozli xabarlar | **barcha** ovozli va dumaloq video xabarlar. Bot ovozni tinglay olmaydi, shuning uchun faqat so'kinganlarini ajratib o'chirib bo'lmaydi |
| 🇸🇦 Arabcha xabarlar | harflarining kamida 30% arab yozuvida bo'lgan xabarlar (kamida 4 ta arab harfi) |
| 🚫 Spam | 10 soniyada 6 tadan ortiq xabar, 2 daqiqada bir xil matn 3 marta, bitta xabarda 5 ta va undan ko'p @mention. Albomlar spam hisoblanmaydi |
| 🧹 Arabcha reklamalar | arabcha matn + havola, @mention yoki telefon raqami |
| 🗑 Kirdi-chiqdilar | «… qo'shildi» va «… chiqib ketdi» xizmat xabarlari |

- Guruh adminlari, anonim admin va bog'langan kanal postlariga tegilmaydi. Adminlar ro'yxati 10 daqiqa keshlanadi.
- Tahrirlangan xabarlar ham tekshiriladi.
- Bot guruhga qo'shilganda yoki guruhda `/himoya` (yoki `/start`) yozilganda vazifalar ro'yxatini yuboradi. Bu xabar daqiqasiga ko'pi bilan bir marta chiqadi.
- **Talab:** guruh «Guruh qo'shish» orqali ro'yxatda bo'lishi kerak. Bot guruhda **administrator** bo'lib, unda **«Удаление сообщений» (Xabarlarni o'chirish)** huquqi bo'lishi kerak. Huquq yetmasa, panelda qizil ogohlantirish chiqadi.
- Hammasi mavjud webhook orqali ishlaydi, alohida cron kerak emas.
- Jadvallar avtomatik yaratiladi: `group_guard`, `guard_log` (30 kun saqlanadi), `guard_admins`, `guard_seen`.
