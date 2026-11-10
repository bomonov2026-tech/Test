# Obby Parkour — Roblox uslubidagi maket

Uchta ekran: `1-oyin.png`, `2-turnir.png`, `3-natija.png` · hammasi — `maket.png`

## Nega obby turnirga yaxshiroq mos keladi

Yuguruvchi o'yinda «hamma uchun bir xil trassa» qoidasini **alohida o'ylab
topish** kerak edi. Obbyda bu tabiiy: trassa bitta, hamma o'sha trassani o'tadi.

Rekord ham aniqroq: **necha bosqich** va **qancha vaqtda**. «14-bosqich · 2:41»
— buni taqqoslash oson, bahs ham shundan chiqadi.

## Texnik tanlov

| Yo'l | Ko'rinish | Narxi |
|---|---|---|
| Three.js (haqiqiy 3D) | Roblox'dek, erkin kamera | +150 KB, bitta fayl buziladi, arzon telefonda sekin |
| **Qo'zg'almas burchakli 3D** | Bloklar hajmli | Qo'shimcha kod yo'q |
| Yondan 2D | Oddiy platformer | Roblox'ga o'xshamaydi |

Ikkinchisi tanlandi — **Crossy Road** qanday ishlasa, shunday: kamera
qimirlamaydi, qahramon katakdan katakka sakraydi.

Bu nafaqat arzon, balki **telefonda aniqroq**: erkin kameralı 3D da
o'yinchi chuqurlikni chamalay olmay tez-tez yiqiladi, bu esa jahlini
chiqaradi. Qo'zg'almas burchakda bunday muammo yo'q.

## 1-ekran: o'yin

- Osmonda suzuvchi rangli bloklar, pastda bulutlar va bo'shliq
- Blokli qahramon (Roblox uslubida)
- Qizil **aylanuvchi to'siq**, yashil/sariq/qizil **checkpoint bayroqlari**
- Yuqorida: bosqich (9/20), vaqt, o'rin, turnir taymeri
- Pastda: yetakchi — «🥇 AZIZ — 14-bosqich · 2:41»
- Boshqaruv: chap / o'ng / sakrash

## 2-ekran: turnir

Guruh nomi, taymer, jonli reyting (bosqich bo'yicha), «birinchi o'ringacha
5 bosqich».

## 3-ekran: natija

G'olib, raqamlar, yakuniy jadval (bosqich · vaqt), tangalar, guruhlar
ligasi, «Guruhga tashlash».

## Bu rasm chizilgan emas

1-ekranni **kodning o'zi chizgan**: bloklarning proyeksiyasi, yon
tomonlarning soyasi, chuqurlik bo'yicha tartiblash — hammasi hisoblangan.
Yasaganda aynan shunday chiqadi.

## Keyingi qadam

Trassa generatori: har kuni yangi 20 bosqichli obby, lekin **o'tsa
bo'ladigan**. Buni bot bilan mingta kunni oldindan o'ynatib tekshiraman —
bitta o'tib bo'lmaydigan sakrash chiqsa, o'sha kun yo'qoladi.
