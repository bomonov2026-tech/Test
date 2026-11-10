# Ramazon taqvimi — kanal va guruhga avtomatik post

«Tez kunda» yozuvi olib tashlandi, funksiya to'liq yozildi.

**Yangilandi:** post **kuniga ikki marta** yuboriladi — saharlikdan 1 soat oldin
va iftordan 1 soat oldin. Admin panelda yoqish/o'chirish kaliti qo'shildi.

- `php -l` — sintaksis xatosi yo'q
- `CHI` massivi Node bilan tekshirildi — 7 ta qator, to'g'ri
- **Rasm generatori haqiqatan ishga tushirilib sinaldi** — namuna post rasmi
  birga yuborildi

---

## Foydalanuvchi nima ko'radi

Kanal yoki guruh sozlamalarida, «Avtomatik xabarlar» ro'yxatida yangi qator:

> 🌙 **Ramazon taqvimi**
> Faqat Ramazon oyida — har kuni saharlik va iftor jadvali

Kalitni yoqadi, tamom. Ramazon boshlanganda post o'zi chiqadi, Ramazon
tugagach o'z-o'zidan to'xtaydi.

**Kuniga ikki marta:**

| Qachon | Post sarlavhasi |
|---|---|
| Saharlikdan 1 soat oldin | «7-kun · Saharlikka 1 soat» |
| Iftordan 1 soat oldin | «7-kun · Iftorga 1 soat» |

Ikkalasida ham 12 viloyatning to'liq jadvali — saharlik va iftor yonma-yon.
Faqat sarlavha va urg'u rangi farq qiladi (saharlikda ko'k, iftorda yashil).

Vaqt Toshkent bo'yicha hisoblanadi: bomdod va shom vaqtidan 1 soat ayriladi.

## Post qanday ko'rinadi

12 viloyat uchun saharlik va iftor vaqtlari, Ramazonning nechanchi kuni ekani
va TartibOn pechati — mavjud namoz/ob-havo postlari bilan bir xil uslubda.

Matn (caption):

```
🌙 Saharlikka 1 soat qoldi
Ramazon — 7-kun · 09.02.2027

🤲 Ro'zangiz qabul bo'lsin!
#Ramazon #TartibOn
```

```
🌇 Iftorga 1 soat qoldi
Ramazon — 7-kun · 09.02.2027

🤲 Ro'zangiz qabul bo'lsin!
#Ramazon #TartibOn
```

---

## Qo'shilgan kod

| Nima | Vazifasi |
|---|---|
| `rz_home()` | 12 viloyat uchun saharlik (bomdod) va iftor (shom) vaqtlarini yig'adi |
| `png_ramazon($h, $kind)` | Post rasmini chizadi (1000×1488); `$kind`: `s` yoki `i` |
| `rz_cap($h, $kind)` | Post matni |
| `rz_lead_txt($m)` | «60» → «1 soat», «45» → «45 daqiqa» |
| `cron_ramazon()` | Kuniga ikki marta yuboradi |
| `RZ_WIN_MIN` | Yuborish oynasi (20 daqiqa) |

### Ma'lumotlar bazasi

```sql
ALTER TABLE chats ADD COLUMN rz_on TINYINT NOT NULL DEFAULT 0;
ALTER TABLE chats ADD COLUMN rz_sa DATE NULL;   -- saharlik posti yuborilgan kun
ALTER TABLE chats ADD COLUMN rz_if DATE NULL;   -- iftor posti yuborilgan kun
```

Migratsiya avtomatik (`mig_rz2`). Har bir ustun alohida `try/catch` ichida —
agar ustun allaqachon bo'lsa, xato bermaydi.

### O'zgartirilgan joylar

- `cron_minute()` ga `'Ramazon posti'` vazifasi qo'shildi
- `chat_set` API endi `rz_on` ni qabul qiladi va saqlaydi
- `CHI` massiviga haqiqiy qator qo'shildi
- Soxta «Tez kunda» elementi olib tashlandi
- Ikonkalar ro'yxatiga oy+yulduz (`rz`) qo'shildi

---

## Himoya choralari (mavjud kod uslubida)

| Holat | Nima bo'ladi |
|---|---|
| Ramazon emas | `cron_ramazon()` darhol chiqadi, hech narsa yubormaydi |
| Bir xil post takrorlanishi | `rz_sa` / `rz_if` ustunlari to'sadi (atomar `UPDATE … rowCount()===1`) |
| Admin o'chirib qo'ygan | `rz_all` sozlamasi — funksiya darhol chiqadi |
| Vaqtlar hali olinmagan | `kutilmoqda` — keyingi daqiqada qayta urinadi |
| Vaqtlar hisoblangan (rasmiy emas) | `rasmiy vaqt kutilmoqda` — yuborilmaydi |
| Telegram vaqtincha xato berdi | `dp_transient()` — tegishli ustun tozalanadi, qayta yuboriladi |
| Bot kanaldan chiqarilgan | `chat_lost()` — egasiga xabar beriladi |
| Ko'p chat | 100 tadan, orasida 0,2 soniya tanaffus |

Barcha yuborishlar `post_logs` ga yoziladi.

---

## Admin panelda sozlash

Admin panel → **«🌙 Ramazon sanasi»** kartasi. Unga ikkita yangi sozlama
qo'shildi:

| Sozlama | Nima qiladi |
|---|---|
| **Kanal va guruhlarga post** | Yoqish/o'chirish kaliti. O'chirilsa, hech kimga post bormaydi — chatlardagi kalitlar o'zgarishsiz qoladi |
| **Necha daqiqa oldin** | 15–180 oralig'ida, standart 60. Rasm va matnda avtomatik aks etadi («1 soat», «45 daqiqa») |

Shu yerda avvalgidek **Ramazon 1-kuni** va **Necha kun** maydonlari ham bor.
Bo'sh qoldirsangiz hijriy taqvimdan hisoblanadi (±1 kun xato bo'lishi mumkin),
shuning uchun rasmiy e'londan keyin aniq sanani kiriting.

---

## Tekshirish

Ramazon kelishini kutmasdan sinab ko'rish uchun:

1. Admin panelda «Ramazon 1-kuni» ga **bugungi sanani** qo'ying
2. «Kanal va guruhlarga post» kaliti yoqilganiga ishonch hosil qiling
3. Biror kanalda «Ramazon taqvimi» kalitini yoqing
4. «Necha daqiqa oldin» ni shunday tanlangki, hisoblangan vaqt hozirga yaqin
   bo'lsin (masalan, shom 18:14 bo'lsa va hozir 17:50 bo'lsa — 25 daqiqa qo'ying)
5. Bir-ikki daqiqa kuting — post chiqadi
6. Sinovdan keyin sana va daqiqani haqiqiy qiymatga qaytaring

---

## Vaqtlar: faqat rasmiy manbadan

**Qoida:** post va eslatmalar **faqat islom.uz rasmiy vaqtlari** bilan
yuboriladi. Bot o'zi hisoblagan vaqt bilan **hech qachon** yuborilmaydi.

| Holat | Nima bo'ladi |
|---|---|
| islom.uz javob berdi | Post yuboriladi, rasm tagida «islom.uz taqvimi asosida» |
| islom.uz javob bermadi | **Post yuborilmaydi**, keyingi daqiqada qayta urinadi |
| 12 viloyatdan bittasi ham hisoblangan bo'lsa | Post yuborilmaydi |

Cron jurnalida `Ramazon posti: rasmiy vaqt kutilmoqda` deb ko'rinadi.

Xuddi shu qoida **shaxsiy saharlik/iftor eslatmalariga** ham qo'llanildi
(`rz_run`): hisoblangan vaqt bilan eslatma ketmaydi.

### Oldindan yuklash

Rasmiy vaqt post vaqtida tayyor turishi uchun alohida vazifa qo'shildi:

```php
'Ramazon vaqtlari' => function () { return rz_warm(); }
```

`rz_warm()` faqat Ramazon davrida ishlaydi va yarim soatda bir marta 12
viloyat vaqtlarini islom.uz dan keshga oladi. Ramazon tugagach umuman
tegmaydi.

Shunda post vaqti kelganda vaqtlar allaqachon keshda bo'ladi va «rasmiy
vaqt kutilmoqda» holati deyarli uchramaydi.

### Agar islom.uz uzoq ishlamasa

Post o'tkazib yuboriladi — bu ataylab shunday. Ramazonda noto'g'ri vaqt
yuborilgandan ko'ra, post chiqmagani yaxshiroq.

Jurnalda ko'rinadi, shunda muammoni sezasiz.
