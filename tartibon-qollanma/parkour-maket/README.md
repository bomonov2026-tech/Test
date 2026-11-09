# Parkour Run — dizayn maketi (Subway Surfers uslubi)

Uchta ekran: `1-oyin.png`, `2-turnir.png`, `3-natija.png`,
hammasi birga — `maket.png`.

## 3D haqida ochiq gap

Bu **perspektivali 3D** — canvas'da chiziladi, Three.js kabi kutubxona yo'q.

Nega shunday:

| | Haqiqiy 3D (Three.js) | Perspektivali 3D |
|---|---|---|
| Qo'shimcha kod | ~150 KB kutubxona | Yo'q |
| Bitta fayl tuzilmangiz | Buziladi | Saqlanadi |
| Arzon Android | Sekinlashadi | Ishlaydi |
| Ko'rinish | To'liq 3D | Rasmdagidek |

**Muhim:** maketdagi rasm chizilgan rasm emas — u **o'yin dvigatelining
o'zi** chizgan kadr. Ya'ni ko'rayotganingiz rostdan yasaladi, keyin
«bunday chiqmadi» degan gap bo'lmaydi.

Agar haqiqiy 3D (aylanadigan kamera, modellar, soyalar) kerak bo'lsa —
aytasiz, lekin unda alohida fayllar va og'irroq yuk bo'ladi.

## Uslub

Subway Surfers'ga yaqin: yorqin kunduz, moviy osmon va bulutlar, rangli
shahar, temir yo'l. Qorong'i variantdan voz kechildi.

| Element | Nima |
|---|---|
| Fon | Moviy osmon, oq bulutlar, binafsha binolar, daraxtlar |
| Yo'l | Temir yo'l: shpallar, relslar, qumli chekka |
| To'siqlar | **Poyezdlar** (qizil, moviy) va to'siq panjaralar |
| Tangalar | Yoy shaklida uchib turadi |
| Boshqaruv | **Ekranni surish** — tugmalar yo'q |

## 1-ekran: o'yin

- Orqadan ko'rinadigan stickman: sariq shapka, moviy sharf
- Uchta yo'lak, poyezd va panjara to'siqlar
- **Moviy chiziq** — hozirgi yetakchining masofasi. Yaqinlashganda
  «🥇 AZIZ · 1 840 m» yozuvi ko'rinadi, o'tib ketsangiz ekran yonadi
- Yuqorida: masofa, tanga, turnir taymeri
- Pastda katta tugmalar; ekranni surib ham boshqarsa bo'ladi

## 2-ekran: turnir lobbisi

- Guruh nomi va ishtirokchilar soni
- Katta taymer
- Jonli reyting, o'z o'rningiz ajratilgan
- «Birinchi o'ringacha 556 m» — aniq maqsad
- Trassa hamma uchun bir xil ekani alohida yozilgan

## 3-ekran: natija

- G'olib toj bilan
- Raqamlar: ishtirokchi, urinish, sizniki
- Yakuniy jadval, har kimga tanga
- Guruhlar ligasidagi o'rin
- «Guruhga tashlash» va «Kanalga ham yuborish»
- Pastda: natijani server tekshirgani yozilgan

## Rang va uslub

| | |
|---|---|
| Osmon / fon | Moviy, och ko'k |
| Asosiy tugma | Oltin-sariq |
| Yetakchi chizig'i | Moviy punktir |
| Jonli turnir belgisi | Qizil |
| Taymer | To'q zarg'aldoq |

## Keyingi qadam

Maket yoqsa, trassa generatoridan boshlayman: har kuni yangi, lekin
**o'tsa bo'ladigan**. Buni mingta kunni bot bilan oldindan o'ynatib
tekshiraman — jang muvozanatini shunday tekshirgandim.
