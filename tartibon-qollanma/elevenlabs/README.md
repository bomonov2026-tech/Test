# ElevenLabs uchun matnlar

Har bir video uchun ikkita fayl:

| Fayl | Nima uchun |
|---|---|
| `*.txt` | **Bir martada** butun matnni ElevenLabs'ga qo'yib, bitta audio olish uchun |
| `*.csv` | **Sahna bo'yicha** generatsiya qilish uchun: har qatorda matn va uning aniq vaqti |

---

## Reels (reklama roliklari)

| Fayl | Sahna | Video | Belgi |
|---|---|---|---|
| `reels/01-savdogar.txt` | 13 | 0:57 | 834 |
| `reels/02-umumiy.txt` | 11 | 0:44 | 549 |
| `reels/03-taksi.txt` | 7 | 0:32 | 470 |
| `reels/04-avto.txt` | 6 | 0:27 | 383 |
| `reels/05-kochmas-mulk.txt` | 6 | 0:27 | 408 |
| `reels/06-vakansiya.txt` | 6 | 0:25 | 375 |
| `reels/07-oquv-markazi.txt` | 6 | 0:26 | 378 |
| `reels/08-restoran.txt` | 6 | 0:26 | 365 |
| `reels/09-salon.txt` | 6 | 0:24 | 353 |
| `reels/10-sayohat.txt` | 7 | 0:31 | 446 |

## Video darslik

| Fayl | Sahna | Video | Belgi |
|---|---|---|---|
| `darslik/1-bosh-panel.txt` | 39 | 5:34 | 5120 |
| `darslik/2-adminlar-uchun.txt` | 79 | 9:47 | 8592 |
| `darslik/3-kundalik-xabarlar.txt` | 40 | 4:46 | 4129 |
| `darslik/4-xizmatlar.txt` | 76 | 8:22 | 7247 |
| `darslik/5-savdo-admin.txt` | 67 | 7:03 | 5972 |
| `darslik/6-ilova.txt` | 35 | 4:12 | 3748 |

---

## Matn qanday tayyorlangan

- **Raqamlar so'z bilan yozilgan** (`24 soat` → `yigirma to'rt soat`). Sababi: ElevenLabs raqamni
  boshqa tilda yoki noto'g'ri o'qib yuborishi mumkin.
- **«Qo'shtirnoqlar»** oddiy `"` ga almashtirildi — ular ovozda o'qilmaydi.
- Sahnalar orasida bo'sh qator qoldirilgan — ElevenLabs buni qisqa pauza sifatida o'qiydi.

## 1-usul: bitta audio (oson)

1. `reels/01-savdogar.txt` ni oching, matnni nusxalang.
2. ElevenLabs'ga qo'ying, ovozni tanlang va generatsiya qiling.
3. `ovoz.mp3` nomi bilan saqlang va videoga ulang:

```bash
ffmpeg -i video/01-savdogar.mp4 -i ovoz.mp3 \
  -c:v copy -map 0:v:0 -map 1:a:0 -shortest tayyor.mp4
```

Bu usulda ovoz kadrlarga **taxminan** mos tushadi — ElevenLabs o'qish tezligi mening
hisobimdan (daqiqasiga ~140 so'z) farq qilishi mumkin.

## 2-usul: sahna bo'yicha (aniq moslashadi)

1. `*.csv` dagi har bir qatorni alohida generatsiya qiling.
2. Fayllarni `001.mp3`, `002.mp3`, ... deb nomlab, bitta papkaga soling.
3. Shu papkani skriptga bering — u har bir sahnani **o'z vaqtiga qo'yib**, to'liq
   audio yo'lakni yig'adi va videoga ulaydi:

```bash
python3 ovoz-yigish.py \
  --csv reels/01-savdogar.csv \
  --audio ./mp3lar \
  --video ../reels/video/01-savdogar.mp4 \
  --out tayyor-01.mp4
```

Agar biror sahnaning ovozi o'z joyiga sig'masa, skript ogohlantiradi — o'sha
sahnaning matnini qisqartirsangiz bo'ladi.

## O'zbek tili haqida — muhim

ElevenLabs'ning o'zbek tilini qo'llashi model versiyasiga bog'liq va tez o'zgarib turadi,
shuning uchun ishonch bilan ayta olmayman — **ElevenLabs'ning o'zida tekshiring**:

1. Avval eng yangi modelni (v3) tanlab, qisqa bir qatorni sinab ko'ring.
2. Agar talaffuz yomon chiqsa, turkcha ovoz modelini sinang — o'zbek lotin yozuvi
   turkchaga yaqin, ba'zan qoniqarli natija beradi. Lekin `o'`, `g'`, `sh`, `ch`
   harflarini tekshiring.
3. Baribir yoqmasa — matn tayyor, o'zingiz yoki diktor o'qib bersa ham bo'ladi.

Eslatma: uzun darslik matnlari 10 000 belgidan oshadi. ElevenLabs'da bir martalik
generatsiya chegarasi bor (tarifga qarab), shuning uchun darsliklar uchun 2-usul qulayroq.
