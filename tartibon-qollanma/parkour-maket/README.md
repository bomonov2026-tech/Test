# Parkour Run — dizayn maketi

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

## 1-ekran: o'yin

- Orqadan ko'rinadigan stickman, uchta yo'lak
- Uzoqqa ketuvchi ko'cha, quyosh botishi, shahar siluети
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
| Fon | To'q siyohrang → binafsha |
| Asosiy tugma | Oltin-sariq |
| O'zingiz / yetakchi chizig'i | Moviy |
| Ogohlantirish / jonli | Qizil |

## Keyingi qadam

Maket yoqsa, trassa generatoridan boshlayman: har kuni yangi, lekin
**o'tsa bo'ladigan**. Buni mingta kunni bot bilan oldindan o'ynatib
tekshiraman — jang muvozanatini shunday tekshirgandim.
