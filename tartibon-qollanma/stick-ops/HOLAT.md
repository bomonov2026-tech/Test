# STICK OPS — joylash hisoboti

## Nima tekshirdim

Kodni ilovangizga qo'shishdan oldin o'qib chiqdim — bu 1057 ta foydalanuvchingizning
brauzerida ishlaydigan kod.

| Tekshiruv | Natija |
|---|---|
| Xavfli naqshlar (`eval`, `new Function`, `atob`, `document.cookie`) | **Topilmadi** |
| Tashqi manzillar | Faqat sizning mavjud kodingizdagilar (banklar, Telegram, ob-havo) |
| Node bog'liqliklari | Bitta — `ws`, standart WebSocket kutubxonasi |
| `bridge.php` | Toza: 120 soniyalik HMAC chipta beradi, kalit serverda qoladi |
| `integration.js` | Iframe bir xil originda, `postMessage` da origin va manba tekshiriladi |
| Node testlari | **18/18 o'tdi** |
| Server tirik ishga tushirildi | `/health` javob berdi, boshqa yo'llar 404 |
| Mashq rejimi brauzerda | **Ishladi** — canvas, raund taymeri, qurol do'koni, 9 bot |
| Eski 40 ta bo'lim havolasi | Buzilmagan |
| `php -l` | Toza |

Paket **mening oxirgi ishlarim ustiga** qurilgan: deep link, `chat_rm`,
Premium/Stars, yugurish o'yini, kunlik faollik xabari — hammasi joyida.

## Bitta narsa o'chirildi

Paketdagi `index.php` da **Stickman Arena** (`sa_*`) yo'q. Siz undan
kechganingiz uchun shunday qoldirdim. Agar keyin kerak bo'lsa, menda
zaxirasi bor — qaytarib qo'shaman.

## O'yin nima ekan

Jangchi o'yin emas — **taktik 5v5 otishma**. Qurol do'koni (pichoq, pistolet,
SMG, avtomat, drobovik, snayper), raund iqtisodi, bomba qo'yish va
zararsizlantirish, A/B nuqtalar, 90 soniyalik raund. Interfeys o'zbekcha.

## Joylash

### 1-qism: PHP hosting — bugun ishlaydi

`stick-ops-hosting.zip` ichidagilarni saytingizga yuklang:

- `index.php` — hozirgisining **o'rniga** (avval zaxira oling)
- `stick-ops/` papkasi — `index.php` yoniga

Shu bilan **botlar bilan mashq** rejimi ishlaydi. Node serveri kerak emas.

### 2-qism: onlayn 5v5 — Node serveri kerak

`stick-ops-server.zip` — Node.js 22+ ishlaydigan joyga. Keyin `ops-config.php`
yaratib, ikkala tomonga **bir xil maxfiy kalit** yozasiz.

Kalitni shu buyruq bilan yarating va **menga yubormang**:

```sh
node -e "console.log(require('node:crypto').randomBytes(32).toString('hex'))"
```

Node serveri sozlanmaguncha onlayn bo'lim sababini ko'rsatadi,
mashq rejimi esa ishlayveradi — soxta tugma yo'q.

## Ochiq aytadigan narsalar

**1. Onlayn qism uchun baribir pullik server kerak.** Oldin aytganim
o'zgarmadi: Node doimiy ishlashi kerak, bepul tariflar uxlaydi. Hetzner
~€4/oy. Paket README'sida ham shu yozilgan.

**2. Server qayta ishga tushsa, faol janglar tugaydi.** Xonalar operativ
xotirada. README buni ochiq yozgan — bu boshlang'ich versiya uchun ongli
cheklov. Instance sonini **1** qilish kerak.

**3. Men sinamagan narsalar:**
- Haqiqiy 10 kishilik onlayn jang (menda 10 ta Telegram akkaunt yo'q)
- Sizning hostingingizda `.js` fayllari to'g'ri MIME turi bilan berilishi
- 100 ulanishli yuklama
- Telegram ichidagi haqiqiy ko'rinish

Birinchi marta o'zingiz mashq rejimini ochib ko'ring, keyin ikki telefondan
xona sinab ko'ring.

**4. Kodni men yozmadim.** O'qib chiqdim, sinadim, xavfli narsa topmadim —
lekin har bir qatorini tekshirib chiqqanim yo'q (30 ming qator). Testlari
o'tadi va mantiqan to'g'ri yozilgan.

---

# YANGILANISH: hammasi bitta `index.php` ichiga kiritildi

Siz so'raganingizdek, alohida `stick-ops/` papkasi **endi kerak emas**.
Faqat `index.php` ni almashtirasiz — sizning odatdagi tartibingiz.

## Nima qilindi

| Avval (alohida fayl) | Endi |
|---|---|
| `stick-ops/bridge.php` | `index.php` ichida funksiya |
| `stick-ops/integration.css` | sahifada inline `<style>` |
| `stick-ops/integration.js` | sahifada inline `<script>` |
| `stick-ops/client/index.html` + `style.css` + `app.js` + `render.js` + `shared/game.js` | `index.php?ops=client` manzili bo'yicha beriladi |

O'yin baribir **iframe ichida** qoladi — bu ataylab shunday. Iframe o'yinning
CSS va JS'ini ilovangizdan ajratib turadi; qo'shib yuborilsa, nomlar
to'qnashib ikkala tomon ham buzilishi mumkin edi.

## Yo'lda topilgan xato

Uchta JS modulni oddiygina ketma-ket qo'shganimda **`clamp` nomi ikki faylda
takrorlandi** va o'yin umuman ishga tushmadi. Alohida modul bo'lganda muammo
yo'q edi, birlashtirgach to'qnashdi.

Tuzatish: har bir modul o'z doirasiga (IIFE) o'raldi, eksportlar obyekt
orqali uzatiladi. Ya'ni modul ajratilishi saqlanib qoldi.

## Kesh

73 KB o'yin sahifasi har safar qaytadan yuklanmasligi uchun `ETag` va
`Cache-Control: max-age=86400` qo'yildi. Tekshirildi:

```
brauzer ETag yubormasa   -> 200 (to'liq sahifa)
to'g'ri ETag bilan       -> 304 (keshdan)
eski versiya ETag        -> 200 (to'liq sahifa)
```

Kodni o'zgartirsangiz, versiya avtomatik o'zgaradi va kesh yangilanadi.

## Tekshirildi

| Nima | Natija |
|---|---|
| `php -l` | Toza |
| `index.php?ops=client` chiqishi | Kutilgan sahifa bilan **bayt-bayt bir xil** (75 373 bayt) |
| JS to'plami sintaksisi | `node --check` toza |
| Brauzerda to'liq zanjir | iframe `?ops=client&v=…` ni yukladi, **mashq rejimi ishladi** |
| ETag / 304 | To'rt holatda to'g'ri |
| Eski 40 ta bo'lim havolasi | Buzilmagan |

## Yuklash

1. Hozirgi `index.php` ning **zaxirasini oling**
2. `stick-ops-bitta-fayl.zip` ichidagi `index.php` ni yuklang
3. Tamom — mashq rejimi ishlaydi

Onlayn 5v5 uchun qo'shimcha: `ops-config.php` yaratasiz va Node serverini
joylaysiz (o'zgarmadi).

## Hali ham sinalmagan

- Haqiqiy onlayn jang (10 ta akkaunt kerak)
- Sizning hostingda `?ops=client` to'g'ri ishlashi — ba'zi hostinglar
  `index.php?...` so'rovlarini qayta yo'naltirishi mumkin
- Telegram ichidagi haqiqiy ko'rinish

Fayl 2,13 MB bo'ldi (ilgari 2,08 MB). PHP uni har so'rovda o'qiydi —
sezilarli farq bo'lmasligi kerak, lekin sayt sekinlashsa ayting.
