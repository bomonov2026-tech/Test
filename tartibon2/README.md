# TartibOn — Telegram Mini App (noldan qurilgan)

Eski `tartibon/index.php` (1.9 MB, 14 259 qator, bitta fayl) o'rniga
**bir xil dizayn uslubida** yangi, toza tuzilma.

## Tuzilma

```
tartibon2/
├─ public/                 ← nginx root (veb orqali faqat shu papka ochiq)
│  ├─ index.php            ← yagona kirish nuqtasi: HTML + JSON API
│  └─ assets/
│     ├─ app.css           ← dizayn tizimi (14 KB)
│     ├─ app.js            ← qobiq: router, sahifalar, pastki oyna (18 KB)
│     └─ icons.js          ← 36 ta chiziqli ikonka (4 KB)
├─ src/                    ← veb orqali ochilmaydi
│  ├─ Config.php           ← sozlamalar + demo rejimi
│  ├─ Db.php               ← PDO
│  ├─ Telegram.php         ← initData imzosini tekshirish
│  ├─ Catalog.php          ← bo'limlar va xizmatlar (yagona manba)
│  ├─ App.php              ← foydalanuvchi, statistika, hamyon
│  ├─ Api.php              ← JSON amallar
│  └─ View.php             ← HTML qobig'i
├─ sql/schema.sql          ← baza sxemasi
├─ config.example.php      ← nusxa olib config.php qiling
└─ README.md
```

## Ishga tushirish

```bash
cp config.example.php config.php    # token, baza, admin id
mysql tartibon < sql/schema.sql
```

nginx uchun root — **`tartibon2/public`**. `src/` va `config.php` papkadan
tashqarida, shuning uchun brauzer ularni hech qachon ko'rmaydi.

Sinab ko'rish uchun (config.php'siz ham ishlaydi — **namoyish rejimi**):

```bash
php -S 127.0.0.1:8080 -t public
```

## Dizayn tizimi

Uch rang — uch ma'no. Rang bezak emas, xizmat turini aytadi:

| Rang | Ma'nosi | Qayerda |
|---|---|---|
| 🔵 **ko'k** `#1C7DF3` | siz boshqarasiz | Kanallarim, Statistika, Testlar |
| 🟢 **yashil** `#22C55E` | ilova o'zi bajaradi (tumbler bor) | Namoz, Avto Post, Himoya |
| 🟣 **binafsha** `#8B5CF6` | sotib olinadi | Nakrutka, eSIM, Premium, Stars |

Xizmatning turi `src/Catalog.php` dagi `kind` va `toggle` maydonlaridan keladi,
CSS'da qo'lda yozilmaydi.

Sahifalar bir-birini takrorlamaydi:

- **Asosiy** — kataklar (ko'rgazmali). Yoniq xizmat katagi yashil + yashil nuqta.
  Tepada bitta qator: *"5 ta xizmat ishlab turibdi · Keyingisi 12:28 Peshin"*.
- **Xizmatlar** — Telegram sozlamalari uslubidagi guruhlangan ro'yxat.
  Yoqiladigan xizmat **joyida**, sahifaga kirmasdan yoqiladi (1 bosish).
- **Balans / Profil** — xuddi shu ro'yxat, tumbler o'rniga strelka.

Boshqa tokenlar `app.css` boshida: `--bg`, `--card`, `--line`, `--sh`, `--r-*`.
Qorong'i mavzu `html[data-theme="dark"]` — Telegram mavzusiga qarab avtomatik.
`!important` ishlatilmagan, bitta qatlam.

Asosiy bo'laklar: `.stat`, `.quick`, `.now`, `.sec`+`.tile`+`.chip`,
`.card`+`.row`+`.ic`+`.sw`, `.bal`, `.prof`, `.nav`, `.ov`/`.sheet`, `.toast`.

## Xizmat qo'shish

1. `src/Catalog.php` ga bitta qator qo'shing (`id`, `title`, `icon`, `sub`).
2. Ikonka kaliti `public/assets/icons.js` dagi 36 ta kalitdan biri bo'lsin.

Tayyor. Bo'lim ham bosh sahifada, ham Xizmatlar sahifasida chiqadi.

Xizmat katagi bosilganda o'z sahifangiz ochilishi uchun:

```js
TartibOn.service('stats', function () {
  TartibOn.sheet('Statistika', '<p class="note">...</p>');
});
```

Adminga xizmatni yashirish kerak bo'lsa — `settings` jadvalidagi
`hidden_services` ga id yozilsa kifoya: `["growth","esim"]`.

## API

Hammasi `POST /?api=<amal>`, tanasi `{"initData":"...","data":{...}}`.
Har bir so'rovda `initData` imzosi qaytadan tekshiriladi (HMAC-SHA256,
24 soat amal qiladi) — brauzerdan kelgan `user_id` ga ishonilmaydi.

| Amal | Qaytaradi |
|---|---|
| `me` | foydalanuvchi, statistika, hamyon, katalog, yoniqlar soni, keyingi amal, so'nggi amallar |
| `wallet` | hamyon holati |
| `topup_create` | to'ldirish arizasini yaratadi |
| `service_toggle` | xizmatni yoqadi/o'chiradi (tumbler) |

## Eski ilovadan nimasi boshqacha

- **Hajmi:** 1.9 MB → 36 KB (`public/` dagi hamma narsa).
- **Tuzilma:** bitta fayldagi 5 ta ustma-ust CSS qatlami o'rniga bitta qatlam,
  `!important` yo'q.
- **Telefon qotib qolmaydi:** scroll'ni to'suvchi (non-passive) tinglovchi yo'q,
  bitta `click` tinglovchi — `{ passive: true }`.
- **Tarmoq yiqilsa ham ochiladi:** shrift keyin yuklanadi, Telegram SDK
  yuklanmasa `app.js` zaxira obyekt bilan ishlaydi.
- **Xavfsizlik:** `initData` har bir so'rovda tekshiriladi, SQL — faqat
  tayyorlangan so'rovlar (prepared statements).
