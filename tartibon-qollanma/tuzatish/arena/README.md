# Stickman Arena — holat hisoboti

## Eng muhimi: arxitektura to'sig'i

Loyihangizni tekshirdim:

| Nima | Holat |
|---|---|
| Arxitektura | Bitta PHP fayl (2 MB), PHP 7.4 + MySQL |
| Jarayon | PHP-FPM, `set_time_limit(60…180)` |
| WebSocket | Kutubxona **yo'q** (Swoole, Ratchet, ReactPHP, Workerman) |
| Fon ishlari | Daqiqada bir marta cron |

**Siz so'ragan onlayn qism — WebSocket, serverda 30 Hz jang sikli, 100
ulanishli xona, snapshot interpolation — bu stekda ishlay olmaydi.**
PHP-FPM da xona holatini ushlab turadigan doimiy jarayon yo'q: har so'rov
tugashi bilan xotira tozalanadi.

Shuning uchun Socket.IO kodi yozilsa, hostingingizda ishga tushmas edi.
Men spetsifikatsiyaning o'zi aytgan tartibda ketdim: *«avval bepul
stickman va musht bilan to'liq ishlaydigan duelni yakunla»*.

---

## Nima TAYYOR va ishlaydi

### Jang yadrosi (`sa-core.js`)
Deterministik, DOM'siz, faqat matematika — **serverga o'zgarishsiz
ko'chiriladi**. Klient faqat input beradi, holatni yadro hisoblaydi.

- 5 personaj: Oddiy, Ninja, Ritsar, Samuray, Berserk
- 6 qurol: musht, xanjar, qilich, nayza, bolta, bolg'a
- Hitbox, qurol yetish masofasi, zarba tanaffusi, blok (zarar ×0.25),
  orqaga surilish, stun
- 3-2-1 sanoq, 45 soniyalik duel, vaqt tugasa joni ko'p g'olib,
  teng bo'lsa **oltin zarba** (birinchi tekkan zarba hal qiladi)
- Holatlar: `countdown` → `fighting` → `suddenDeath` → `finished`

### Muvozanat — o'lchangan, taxmin emas
`balance.js` tasodifiy xarakterli botlarni (tajovuzkorlik, reaksiya
kechikishi, blok odati, xato ehtimoli) o'ynatadi.

**Bepul jangchi (Oddiy + musht) qurolli raqiblarga qarshi, 240 jangdan:**

```
vs oddiy+xanjar    38%     vs ninja+qilich     45%
vs oddiy+qilich    52%     vs ritsar+bolta     50%
vs oddiy+nayza     50%     vs samuray+qilich   43%
vs oddiy+bolta     57%     vs berserk+bolgha   46%
vs oddiy+bolgha    51%
```

Talab bajarildi: bepul jangchi tezligi va qisqa tanaffusi hisobiga
qurolli raqibni yenga oladi. Qurollar o'zaro 38–69%, personajlar 40–66%.

Birinchi versiyada bepul jangchi **0–9%** yutardi va Ritsar hammani
yengardi — buni faqat simulyatsiya ko'rsatdi.

### Ko'rinish (`sa-draw.js`)
Biriktirgan rasmingizga yaqin: to'q fon, quyosh botishi, tosh arena,
qizil bayroqli ustunlar, moviy/qizil sharfli stickmanlar, oltin sanoq,
jon chiziqlari, taymer.

### Uchta ekran
1. **Tayyorgarlik** — personaj, uning xususiyatlari (tezlik/kuch/himoya/jon),
   qurol, tangalar, kunlik tanga hisobi
2. **Arena** — jang, katta sensorli tugmalar (bir vaqtda bir nechta barmoq),
   klaviatura boshqaruvi (← → ↑/probel, J, K)
3. **Reyting** — g'alabalar, ketma-ket g'alaba, eng yaxshilar ro'yxati

Tugmalar `touch-action:none` va `preventDefault` bilan — sahifa aylanmaydi.

### Backend (PHP, mavjud arxitekturaga mos)
- `sa_u` — tanga, g'alaba, duel, ketma-ket, jihoz, kunlik tanga chegarasi
- `sa_own` — egalik, `PRIMARY KEY (uid,kind,k)`
- `sa_get` / `sa_equip` / `sa_buy` / `sa_result`
- Jihozni serverda tekshiradi: sizda yo'q personajni kiya olmaysiz
- Mashq uchun tanga: g'alaba 25, mag'lubiyat 8, kuniga 400 gacha

**Takroriy xarid himoyasi — SQLite da haqiqatan ishlatib sinaldi:**

```
1) qilich (700) sotib olish   -> QABUL    tanga=300  buyum=1
2) QAYTA qilich               -> already  tanga=300  buyum=1
3) yana QAYTA                 -> already  tanga=300  buyum=1
4) ninja (600), tanga yetmaydi-> poor     tanga=300  buyum=1
```

Pul ikkinchi marta yechilmaydi, buyum ikki marta berilmaydi.
Tranzaksiya + PRIMARY KEY ikkalasi birga ishlaydi.

---

## Nima TAYYOR EMAS

Buni ochiq aytaman — bular yozilmadi:

| Nima | Nega |
|---|---|
| Onlayn 1v1 | WebSocket serveri kerak, PHP-FPM da ishlamaydi |
| 100 kishilik xona, navbat, tomoshabinlar | Yuqoridagi bilan birga |
| Snapshot interpolation | Onlayn bo'lmagani uchun kerak emas |
| Qayta ulanish, texnik mag'lubiyat | Onlayn qismning bo'lagi |
| Xona taklif havolasi | Onlayn qismning bo'lagi |
| 100 ulanishli yuklama testi | Sinaydigan server yo'q — **sig'imni kafolatlamayman** |
| Kosmetikalar | Do'kon tuzilmasi tayyor, kosmetika qo'shilmadi |
| «Arena qiroli» belgisi (10 g'alaba) | Ketma-ket g'alaba sanaladi, belgi hali ko'rsatilmaydi |

Hozirgi Arena — **mashq rejimi**: siz bot bilan jang qilasiz. Spetsifikatsiya
«mashq rejimi navbat o'rnini yo'qotmasdan ishlasin» deydi — navbat yo'q
bo'lgani uchun bu hozir mavzuga kirmaydi.

Interfeysda soxta online son yoki ishlamaydigan tugma **yo'q** — faqat
rostdan ishlaydigani qo'yilgan.

---

## Onlayn qismni qanday qo'shamiz

Ikki yo'l bor, ikkalasi ham haqiqiy:

**A. Node.js serveri (tavsiya).** PHP qoladi (auth, baza, do'kon, reyting),
yoniga kichik Node + WebSocket serveri qo'shiladi: xonalar, navbat,
30 Hz sikl, hammasi server tomonida. `sa-core.js` o'zgarishsiz o'sha yerda
ishlaydi — men uni aynan shu maqsadda DOM'siz yozdim.

Kerak: Node ishlaydigan joy (VPS, Railway, Fly.io, Render) va `wss://`
uchun domen/TLS.

**B. Faqat PHP — raundli duel.** Hozirgi hostingda bugun ishlaydi. Jang
real-time emas: har raundda ikkala o'yinchi zarba/blok/chetlanish tanlaydi,
server natijani hisoblaydi, ~1 soniyada yangilanadi. Navbat, 1v1, g'olib
qoladi, ketma-ket g'alaba — hammasi bo'ladi, lekin janr boshqa.

Qaysi biri? Node ishlatadigan serveringiz bor-yo'qligini ayting —
shunga qarab davom etaman.

---

## O'zgargan fayllar va migratsiyalar

| Nima | Qayerda |
|---|---|
| Butun o'yin | `index.php` (bitta fayl arxitekturasi saqlandi) |
| Yangi jadval | `sa_u`, `sa_own` — `mig_sa1` migratsiyasi, o'zi yaratiladi |
| Yangi API | `sa_get`, `sa_equip`, `sa_buy`, `sa_result` |
| Yangi sahifa | `p-sa`, bosh sahifada «Stickman Arena» katagi |
| Havola | `t.me/TartibOnBot?startapp=s_sa` |
| Manba fayllar | `tuzatish/arena/` — yadro, chizish, muvozanat skripti |

Alohida SQL ishga tushirish, build yoki konfiguratsiya **kerak emas** —
fayl almashtirilsa bo'ldi. Hech qanday token yoki sir frontendga yozilmadi.

Eski 40 ta bo'lim havolasi qayta tekshirildi — buzilmagan.
