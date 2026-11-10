# TartibOn Hops — maket (100 Hops uslubi)

*Parkour Challenge: 100 Hops* mexanikasi asosida, TartibOn uchun moslashtirilgan
dizayn maketi. 1080×1920, Telegram Mini App ichida ishlashga mo'ljallangan.

| Fayl | Ekran |
|---|---|
| `01-oyin.png` | O'yin ekrani — kuch to'plash holati |
| `02-natija.png` | Yugurish tugagandan keyingi natija |
| `03-guruh-posti.png` | Guruh/kanalga tashlanadigan turnir yakuni |
| `04-qahramon.png` | Qahramon pozalari lavhasi |

---

## Qahramon — TartibOn Ninja

Qora siluet, qizil bog'ich va sharf, oq g'azabli ko'zlar.
Fon yorug' (kunduzgi osmon, oq binolar) — shuning uchun qora qahramon
har qanday ekranda aniq ko'rinadi, kichik telefonda ham yo'qolmaydi.

Pozalar `kod/ninja.js` ichida `POSE` jadvalida — bo'g'im nuqtalari
(son, tizza, oyoq, yelka, tirsak, qo'l) sifatida yozilgan, rasm emas.
Shuning uchun:

- hech qanday sprite fayl kerak emas — `index.php` hajmi oshmaydi;
- oraliq kadrlarni kod o'zi hisoblaydi (ikki poza orasini interpolyatsiya qiladi);
- rangni bitta joydan o'zgartirib, yangi "skin" chiqarish mumkin
  (masalan oltin bog'ich — 1000 sakrash mukofoti).

Bog'ich va sharf dumlari fizika bilan emas, oddiy sinus to'lqini bilan
chiziladi (`tailPts`) — tekin, lekin harakatda tirik ko'rinadi.

Pozalarni yaqindan ko'rish: `cd kod && node zoom.js` → `zoom.png`.

---

## Mexanika (bitta tugma)

```
BOSIB TURING  →  kuch to'planadi  →  QO'YIB YUBORING  →  sakraydi
```

- Ekranning istalgan joyini bosib turasiz — chapdagi ko'rsatkich to'ladi
  (qizil → oltin → yashil).
- Qo'yib yuborganda stickman keyingi tomga sakraydi. Punktir yoy —
  taxminiy traektoriya.
- Tomga tushsa — **+1 sakrash**, tom chetiga aniq tushsa — **toza qo'nish**
  (bonus ko'rsatkich).
- Pastga qulasa — yugurish tugaydi, darhol qaytadan boshlanadi.
- Har 100 sakrashda yangi shlyapa ochiladi (chapdagi "🎩 4-daraja" chipi).

### Nega aynan shu uslub sizga mos

1. **Bitta tugma** — boshqaruv tugmalari, joystick, swipe kerak emas.
   Telefon ham, kichik ekran ham, sekin internet ham muammo emas.
2. **Bitta raqam** — "47 sakrash". Taqqoslash oson, bahs ham oson.
3. **Darhol qayta boshlash** — "yana bir marta" halqasi. Viral bo'ladigan qism shu.
4. **Adolat tekin** — hamma bir xil urug' (seed) oladi, demak hammaga
   bir xil tomlar tushadi. Omad emas, mahorat hal qiladi.
5. **Arzon** — 3D yo'q, fizika dvigateli yo'q. Toza canvas 2D, `index.php`
   ichiga sig'adi.

---

## Turnir (guruh va kanal uchun alohida)

- Admin guruhga `/turnir 15` deb yozadi yoki ilovadan tugma bosadi.
- Bot guruhga post tashlaydi: **"🎮 O'ynash"** tugmasi `?startapp=` havolasi bilan.
- 15 daqiqa davomida har kim xohlagancha urinadi — **eng yaxshi natija** hisobga olinadi.
- Vaqt tugaganda bot o'sha postni tahrirlab yakuniy reytingni chiqaradi
  (`03-guruh-posti.png`).
- **Ikki xil reyting:**
  - *Guruh ichidagi* — a'zolar o'rtasida (postda ko'rinadigani).
  - *Guruhlar/kanallar o'rtasidagi* — guruh a'zolarining jami sakrashlari
    ("Guruh jami 2 847 sakrash · Kanallar reytingi 3-o'rin").

Shu ikkinchisi muhim: bitta odam emas, **butun guruh** raqobatga kiradi,
shuning uchun a'zolar bir-birini o'yinga chaqiradi.

---

## Anti-chit

O'yin yadrosi **deterministik**: urug' + bosish/qo'yib yuborish vaqtlari ketma-ketligi.
Mijoz shu ketma-ketlikni yuboradi, server PHP'da qayta hisoblaydi va natijani
solishtiradi. Arena uchun o'lchagan edik — 45 soniyalik o'yin ~1 ms oladi,
ya'ni serveringizga sezilarli yuk emas.

Qo'shimcha chegaralar:
- bitta sakrash eng kamida ~12 freym (bundan tez bo'lsa — rad).
- 15 daqiqada eng ko'pi bilan ~450 sakrash (odam cheklovi).

---

## Maketni qayta chizish

```bash
cd kod && node shot.js      # n1..n4.png
```

`bg.js` — osmon, bulutlar, binolar, tomlar, kuch ko‘rsatkichi.
`ninja.js` — qahramon: bo‘g‘imlar, pozalar, sharf va bog‘ich.
`page.html` — `scene()`, `res()`, `post()`, `sheet()` ekranlari.
