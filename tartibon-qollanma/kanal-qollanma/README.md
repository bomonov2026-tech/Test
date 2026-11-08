# TartibOn — Telegram kanal uchun bo'lim qo'llanmalari

Bosh sahifadagi **har bir katak uchun alohida video**: o'sha bo'lim nima vazifa bajaradi va undan qanday foydalaniladi. Jami **42 ta video**, umumiy uzunligi **50 daqiqa 27 soniya**.

Har bir video oxirida o'sha bo'limning **to'g'ridan-to'g'ri havolasi** ko'rsatiladi — tomoshabin bosishi bilan ilova aynan o'sha bo'limda ochiladi.

## Papkalar

| Papka | Nima bor |
|---|---|
| `video/` | 41 ta tayyor mp4 — 1080×1920 (9:16), ovozsiz |
| `diktor-matni/` | Har bir videoning diktor matni, oddiy matn fayl |
| `kanal-postlari/` | Kanalga qo'yiladigan tayyor post matnlari (video tagiga) |
| `subtitr/` | SRT fayllar — kerak bo'lsa subtitr sifatida ulash uchun |
| `../elevenlabs/kanal-qollanma/` | ElevenLabs uchun sahna-sahna matn va CSV |
| `matnli-postlar/` | O'sha 42 ta bo'lim **matn ko'rinishida** — video o'rniga yoki u bilan birga |

## Videolar ovozsiz — nega?

Bu muhitda ovoz sintezi yo'q, shuning uchun videolar **ovozsiz** chiqdi va diktor
matni alohida berildi. Ovoz qo'shish uchun:

1. `../elevenlabs/kanal-qollanma/<video>.txt` dagi sahnalarni ElevenLabs'da birma-bir
   yarating va `001.mp3`, `002.mp3` … deb saqlang.
2. Keyin ulang:

```bash
python3 ../elevenlabs/ovoz-yigish.py \
  --csv   ../elevenlabs/kanal-qollanma/01-kanallarim.csv \
  --audio ./mp3lar/01 \
  --video video/01-kanallarim.mp4 \
  --out   tayyor/01-kanallarim.mp4
```

Skript har sahnaning ovozini CSV dagi o'z vaqtiga qo'yadi, shuning uchun oxirgi
sahna ham joyida turadi. Ovoz uzunroq chiqsa, skript ogohlantiradi.

> ElevenLabs o'zbek tilini qanchalik yaxshi o'qishini **oldin bitta qisqa video
> ustida sinab ko'ring** — hammasiga kredit sarflashdan oldin.

## Matnli postlar

Videolarning o'rniga yoki ular bilan birga ishlatish uchun, har bir bo'lim
matn ko'rinishida ham tayyorlandi: `matnli-postlar/`.

| Papka | Nima |
|---|---|
| `oddiy/` | Belgisiz matn — nusxa olib qo'yasiz |
| `html/` | `parse_mode=HTML` bilan yuboriladigan (sarlavhalar qalin) |
| `00-mundarija.txt` | 42 tasining ro'yxati, har biri havola bilan |
| `HAMMASI.md` | Hammasi bitta faylda |

Eng uzun post 1 998 belgi, mundarija 3 319 — Telegram cheki 4096 dan past.

Matnli post videodan ikki joyda ustun: qidiruvda topiladi va odam kerakli
joyini tez topib o'qiydi. Video esa yaxshiroq tushuntiradi. Ikkalasini birga
qo'ysangiz — avval video, keyin matn — eng yaxshi natija beradi.

Mundarija postini kanalga **mahkamlab** qo'ying.

## Kanalga qanday joylash

Videoni yuklang, tagiga `kanal-postlari/<video>.txt` dagi matnni qo'ying.
Matn HTML emas, oddiy — to'g'ridan-to'g'ri nusxa olib qo'yish mumkin.
`kanal-postlari/HAMMASI.md` da hammasi bitta faylda.

Kuniga 1–2 tadan joylasangiz, 41 ta video ~3–4 haftaga yetadi va kanal
doimiy kontentli bo'ladi.

## Ro'yxat


### Adminlar uchun (9 ta)

| № | Bo'lim | Uzunligi | Havola |
|---|---|---|---|
| 01 | Kanallarim | 1:30 | `https://t.me/TartibOnBot?startapp=s_mykanal` |
| 02 | Guruhlarim | 0:55 | `https://t.me/TartibOnBot?startapp=s_myguruh` |
| 03 | Guruh himoyasi | 1:50 | `https://t.me/TartibOnBot?startapp=s_gd` |
| 04 | Rejalashtirilgan postlar | 1:25 | `https://t.me/TartibOnBot?startapp=s_po` |
| 05 | Taklif havolalari | 0:53 | `https://t.me/TartibOnBot?startapp=s_rf` |
| 06 | Statistika | 0:57 | `https://t.me/TartibOnBot?startapp=s_st` |
| 07 | Testlar | 1:22 | `https://t.me/TartibOnBot?startapp=s_psj` |
| 08 | Pullik kanallar | 1:25 | `https://t.me/TartibOnBot?startapp=s_pc` |
| 09 | Konkurs | 1:11 | `https://t.me/TartibOnBot?startapp=s_gw` |

### Kundalik xabarlar (7 ta)

| № | Bo'lim | Uzunligi | Havola |
|---|---|---|---|
| 10 | Dollar kursi | 1:03 | `https://t.me/TartibOnBot?startapp=s_pcb` |
| 11 | Ob-havo | 0:59 | `https://t.me/TartibOnBot?startapp=s_pwx` |
| 12 | Tongi xabar | 0:53 | `https://t.me/TartibOnBot?startapp=s_pdp` |
| 13 | Tungi xabar | 0:51 | `https://t.me/TartibOnBot?startapp=s_pev` |
| 14 | Bayram tabriklari | 0:48 | `https://t.me/TartibOnBot?startapp=s_bt` |
| 15 | Ramazon taqvimi | 1:39 | `https://t.me/TartibOnBot?startapp=s_rz` |
| 42 | Namoz vaqtlari | 1:08 | `https://t.me/TartibOnBot?startapp=s_pnm` |

### Xizmatlar (9 ta)

| № | Bo'lim | Uzunligi | Havola |
|---|---|---|---|
| 16 | Nakrutka | 1:24 | `https://t.me/TartibOnBot?startapp=s_nk` |
| 17 | eSIM | 1:25 | `https://t.me/TartibOnBot?startapp=s_vs` |
| 18 | Prava | 1:18 | `https://t.me/TartibOnBot?startapp=s_dv` |
| 19 | O'zaro reklama | 0:45 | `https://t.me/TartibOnBot?startapp=s_vz` |
| 20 | Kanal Savdo — Telegram | 0:57 | `https://t.me/TartibOnBot?startapp=s_sale` |
| 21 | Kanal Savdo — YouTube | 1:04 | `https://t.me/TartibOnBot?startapp=s_ksyt` |
| 22 | Kanal Savdo — Instagram | 0:46 | `https://t.me/TartibOnBot?startapp=s_ksig` |
| 23 | Reklama birjasi | 1:25 | `https://t.me/TartibOnBot?startapp=s_auc` |
| 24 | Kanallar reytingi | 0:58 | `https://t.me/TartibOnBot?startapp=s_rt` |

### Reklama posti (12 ta)

| № | Bo'lim | Uzunligi | Havola |
|---|---|---|---|
| 25 | Savdogar | 1:15 | `https://t.me/TartibOnBot?startapp=s_adp` |
| 26 | Taksi e'loni | 1:33 | `https://t.me/TartibOnBot?startapp=s_txp` |
| 27 | Avto savdo | 1:17 | `https://t.me/TartibOnBot?startapp=s_avt` |
| 28 | Sayohat e'loni | 1:18 | `https://t.me/TartibOnBot?startapp=s_trv` |
| 29 | O'quv markazi | 1:20 | `https://t.me/TartibOnBot?startapp=s_edu` |
| 30 | Ko'chmas mulk | 1:18 | `https://t.me/TartibOnBot?startapp=s_rlt` |
| 31 | Vakansiya | 1:15 | `https://t.me/TartibOnBot?startapp=s_vak` |
| 32 | Restoran va kafe | 1:17 | `https://t.me/TartibOnBot?startapp=s_rst` |
| 33 | Salon va klinika | 1:18 | `https://t.me/TartibOnBot?startapp=s_sln` |
| 34 | Ochiq byudjet | 1:29 | `https://t.me/TartibOnBot?startapp=s_obd` |
| 35 | Statistikam | 0:49 | `https://t.me/TartibOnBot?startapp=s_ast` |
| 36 | Brend to'plami | 0:51 | `https://t.me/TartibOnBot?startapp=s_brand` |

### Ilova (5 ta)

| № | Bo'lim | Uzunligi | Havola |
|---|---|---|---|
| 37 | Balans | 1:39 | `https://t.me/TartibOnBot?startapp=s_bal` |
| 38 | Bot yaratish | 0:53 | `https://t.me/TartibOnBot?startapp=s_bty` |
| 39 | Savol va takliflar | 0:54 | `https://t.me/TartibOnBot?startapp=s_sup` |
| 40 | Ilova haqida | 1:07 | `https://t.me/TartibOnBot?startapp=s_ab` |
| 41 | Reklama berish | 1:45 | `https://t.me/TartibOnBot?startapp=s_ads` |
