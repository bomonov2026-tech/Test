# O'yin bo'limi — stickman yugurish

Bosh sahifada **«O'yin»** katagi. Havolasi: `t.me/TartibOnBot?startapp=s_gm`

## O'yinning o'zi

2D stickman yugurish, **bitta bosish** bilan o'ynaladi:

- Ekranni bosasiz — stickman sakraydi
- Ikki marta bossangiz — qo'sh sakrash
- Vaqt o'tgan sari tezlashadi va to'siqlar og'irlashadi
- Ball = masofa (sekundiga 20 ball)

Hammasi canvas'da chizilgan, hech qanday tashqi kutubxona yo'q —
ilovaning bitta fayl tuzilishi buzilmadi. Rasm yuklanmaydi, shuning uchun
sekin internetda ham darhol ochiladi.

## Qiyinlik qanday o'sadi

To'siqlar orasidagi masofa **pikselda emas, kadrda** o'lchanadi. Shuning
uchun tezlik oshganda ham imkonsiz holat chiqmaydi — faqat reaksiya vaqti
qisqaradi:

| O'yin vaqti | Tezlik | Reaksiya oynasi | Og'ir to'siqlar |
|---|---|---|---|
| boshida | 6,2 | 0,70 s | — |
| 15 s | 8,3 | 0,60 s | +17% |
| 37 s | 11,3 | 0,46 s | +42% |
| 65 s | 15,2 | 0,32 s | +42% |

Buni tekshirish uchun **mukammal reaksiyali bot yozdim** va o'ynatib
ko'rdim: bot 47–53 soniya chidaydi, ball 944–1061. Ya'ni o'yin cheksiz
emas, lekin odam uchun ham juda qiyin. Tirik odam odatda 100–400 ball
oladi.

## Reyting

- **Bugun** va **Umumiy** — ikkita jadval, har birida top-10
- Rekordingiz, o'rningiz va o'yinlar soni tepada
- `gm_best` jadvali: har foydalanuvchiga bitta qator (umumiy rekord +
  bugungi rekord), shuning uchun jadval kichik va tez ishlaydi

## Kanal va guruhga tashlash

O'yin sahifasining pastida kanal/guruh tanlab **«Yuborish»**. Post shunday
ketadi:

```
🎮 TartibOn o'yini — reyting

🥇 @dilnoza_uz — 980
🥈 Akmal — 744
🥉 @shoh — 520
4. @men — 312
…

👥 Jami 143 kishi o'ynagan

        [ 🎮 O'ynash ]
```

Tugma bosilganda ilova to'g'ridan-to'g'ri o'yin bo'limida ochiladi.
Bitta kanalga **soatiga bir marta** joylash mumkin — spam bo'lmasligi uchun.

## Cheat himoyasi

Ball brauzerdan keladi, shuning uchun tekshiriladi:

| Tekshiruv | Nima qiladi |
|---|---|
| `score <= 1500` | Erishib bo'lmaydigan qiymatni rad etadi |
| `score <= soniya × 20 × 1.25` | Ball o'yin davomiyligiga mos kelishi kerak |
| `ms >= 500` | Bir zumda yuborilgan natijani rad etadi |

Sinovdan o'tkazildi — halol natijalar o'tadi, 11 xil cheat urinishi rad
etiladi:

```
halol: 50 s, 1000 ball        -> QABUL
CHEAT: 10 s, 251 ball         -> rad: vaqtga mos emas
CHEAT: 1 s, 900 ball          -> rad: vaqtga mos emas
CHEAT: 60 s, 5000 ball        -> rad: chegara
```

**Ochig'ini aytaman:** bu mutlaq himoya emas. Brauzer o'yinida ball
har doim mijoz tomonidan yuboriladi. Sabrli odam JS ni o'zgartirib,
50 soniya kutib, 1000 ball yuborishi mumkin — buni to'xtatish uchun
o'yinni serverda qayta hisoblash kerak bo'lardi, bu esa butunlay
boshqa hajmdagi ish. Hozirgi himoya **ahmoqona cheat**ni to'xtatadi.

Agar reyting jiddiy bo'lib qolsa (sovrin va hokazo), shunda qo'shimcha
chora kerak bo'ladi — ayting, o'ylaymiz.

## Nega Telegram'ning o'z «Games» tizimidan foydalanmadim

Telegram'da `sendGame` degan tayyor tizim bor — rekord jadvali xabarning
o'zida ko'rinadi. Lekin:

- Uning **kanalda ishlashini tasdiqlay olmadim** — rasmiy hujjatda aniq
  yozilmagan, topa olmadim
- BotFather'da alohida o'yin yaratish, alohida URL kerak
- Jadval ko'rinishini o'zgartirib bo'lmaydi

Ilova ichidagi variant kanalda ham, guruhda ham ishlaydi va jadval
ko'rinishi butunlay sizning qo'lingizda.

## Nima tekshirildi

| Nima | Qanday |
|---|---|
| O'yin ishlashi | Brauzerda: sakrash balandligi, traektoriya, to'qnashuv |
| Qiyinlik | Bot o'ynadi, 4 marta — 944–1061 ball, 47–53 s |
| Sahifa | Brauzerda: reyting, tablar, ulashish kartasi |
| Cheat himoyasi | PHP da 11 xil holat |
| `s_gm` havolasi | Brauzerda: `p-gm` ochiladi |
| Eski 40 ta havola | Qayta sinaldi, buzilmagan |

Yo'lda ikkita kamchilik topildi: sakrash juda baland edi (118 px,
suzib yurgandek) — 97 px ga tushirildi; va to'siqlar oralig'i pikselda
o'lchanar edi, tezlik oshganda imkonsiz holat chiqishi mumkin edi.

## Nima tekshirilmadi

Haqiqiy bazada ishga tushirmadim. `gm_best` jadvali avtomatik yaratiladi
(`mig_gm1`). Birinchi marta o'zingiz o'ynab ko'ring va kanalga tashlang.
