# TartibOn Hops — o'ynaydigan fayl

`tartibon-hops.html` — bitta fayl, ~19 KB, hech qanday kutubxona yo'q.
Telefonda ham, kompyuterda ham, Telegram Mini App ichida ham ishlaydi.

**Ochish:** faylni brauzerda oching. Ekranni **bosib turing**, kuch
ko'rsatkichi to'ladi, **qo'yib yuboring** — ninja keyingi tomga sakraydi.
Kompyuterda **probel** tugmasi ham ishlaydi.

---

## Ichida nima bor

| | |
|---|---|
| Qahramon | Stickman ninja — qora siluet, qizil bog'ich va sharf, oq ko'zlar |
| Pozalar | turish, yugurish (2 kadr), kuch to'plash, sakrash, qulash, qo'nish |
| Fon | kunduzgi osmon, parallaks bulutlar, ikki qatlam bino, ko'cha soyasi |
| Mexanika | bosib turing → qo'yib yuboring; yoy qayerga tushishini ko'rsatadi |
| Hisob | sakrash soni, rekord (localStorage), toza qo'nish, seriya, shlyapa darajasi |
| Ekranlar | menyu → o'yin → natija → qayta |

## Nega bu kod "to'g'ri" yozilgan

**Deterministik.** O'yin qat'iy 1/60 qadam bilan yuradi va butun trassa
bitta urug'dan (`seed`) hosil bo'ladi. Bir xil urug' + bir xil bosish
ketma-ketligi = **har doim bir xil natija**. Sinovda uch marta bir xil
kirish berildi, uchalasi ham `51:24809:3600` — bir xil.

Buning ikki foydasi bor:
- **Adolat** — turnirda hammaga bir xil trassa tushadi, omad emas, mahorat hal qiladi.
- **Anti-chit** — mijoz faqat bosish vaqtlarini yuboradi, server PHP'da
  xuddi shu hisobni takrorlab, natijani tekshiradi.

**Trassa har doim yechiladi.** Har bir tom qo'yilayotganda fizika formulasi
bilan "VMAX quvvat bilan yetib boriladimi?" deb tekshiriladi
(`reach()` funksiyasi), va oraliq shu chegaraning 88 % idan oshmaydi.
Yuqoriga sakrash ham cheklangan: maksimal ko'tarilish balandligi
`v²sin²α / 2g` — undan baland tom umuman qo'yilmaydi.
Sinovda mukammal bot 300 soniya o'ynab bir marta ham qulamadi.

**Qiyinlik vaqt bilan oshadi.** Oraliq kengayadi, tomlar torayadi, va eng
muhimi — **kuch ko'rsatkichi tezroq to'la boshlaydi** (0.85 s → 0.38 s).
Ya'ni o'yinchining qo'li bir xil aniqlikda ishlasa ham, xato kattalashib boradi.
Shuning uchun har qanday o'yin ertami-kechmi tugaydi.

### O'lchangan natijalar (bot bilan, 40 urinish)

| Qo'l aniqligi | Eng kam | O'rtacha | Eng ko'p |
|---|---|---|---|
| ±20 ms | 163 | 187 | 247 |
| ±35 ms | 129 | 157 | 175 |
| ±55 ms | 105 | 126 | 153 |

Bu **masofani aniq biladigan** bot uchun. Odam masofani ham chamalaydi,
shuning uchun haqiqiy natijalar ancha past bo'ladi — 30–80 oralig'i odatiy,
100 dan oshgani esa chinakam yutuq. "100 Hops" nomi shundan.

---

## Ilovaga ulash

Fayl ichida ikkita joy ajratib qo'yilgan:

1. **Natijani yuborish** — `natijaYubor(h)` funksiyasi.
   Hozir `Telegram.WebApp.sendData()` ga yuboradi; `index.php` ichida
   `fetch('?api=hp_score', …)` ga almashtiriladi va yonida
   `seed` hamda bosish ketma-ketligi ham yuboriladi.

2. **Turnir urug'i** — `boshla(seed)`.
   Guruh turniri boshlanganda server bitta urug' beradi, shu turnirdagi
   hamma shu urug' bilan o'ynaydi.

Faylni `index.php` ga qo'shish oson: butun `<script>` blokini PHP ichida
satr sifatida chiqarish kifoya — tashqi fayl, rasm, shrift kerak emas.

---

## Sozlash (fayl boshidagi o'zgaruvchilar)

```js
var GRAV=2200;       // tortishish
var VMIN=420, VMAX=1150;  // eng kuchsiz va eng kuchli sakrash
var CHARGE=0.85, CHARGE_MIN=0.38;  // ko'rsatkich to'lish vaqti (boshida / oxirida)
var PERFECT=95;      // tom chetiga shuncha yaqin qo'nsa — "toza"
var HAT=10;          // nechta sakrashda bir shlyapa darajasi
```

`yangiTom()` funksiyasi — qiyinlik egri chizig'i. Oraliq, tom kengligi va
balandlik farqi shu yerda hisoblanadi.
