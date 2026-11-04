# Ramazon taqvimi — kanal va guruhga avtomatik post

«Tez kunda» yozuvi olib tashlandi, funksiya to'liq yozildi.

- `php -l` — sintaksis xatosi yo'q
- `CHI` massivi Node bilan tekshirildi — 7 ta qator, to'g'ri
- **Rasm generatori haqiqatan ishga tushirilib sinaldi** — namuna post rasmi
  birga yuborildi

---

## Foydalanuvchi nima ko'radi

Kanal yoki guruh sozlamalarida, «Avtomatik xabarlar» ro'yxatida yangi qator:

> 🌙 **Ramazon taqvimi**
> Faqat Ramazon oyida — har kuni saharlik va iftor jadvali

Kalitni yoqadi, tamom. Ramazon boshlanganda har kuni post o'zi chiqadi,
Ramazon tugagach o'z-o'zidan to'xtaydi.

## Post qanday ko'rinadi

12 viloyat uchun saharlik va iftor vaqtlari, Ramazonning nechanchi kuni ekani
va TartibOn pechati — mavjud namoz/ob-havo postlari bilan bir xil uslubda.

Matn (caption):

```
🌙 Ramazon — 7-kun · 09.02.2027
Saharlik va iftorlik vaqtlari

🤲 Ro'zangiz qabul bo'lsin!
#Ramazon #TartibOn
```

---

## Qo'shilgan kod

| Nima | Vazifasi |
|---|---|
| `rz_home()` | 12 viloyat uchun saharlik (bomdod) va iftor (shom) vaqtlarini yig'adi, Ramazonning nechanchi kuni ekanini hisoblaydi |
| `png_ramazon($h)` | Post rasmini chizadi (1000×1488) |
| `rz_cap($h)` | Post matni |
| `cron_ramazon()` | Yoqilgan chatlarga kuniga bir marta yuboradi |
| `RZ_WIN_H` | Yuborish oynasi (3 soat) |

### Ma'lumotlar bazasi

```sql
ALTER TABLE chats ADD COLUMN rz_on TINYINT NOT NULL DEFAULT 0,
                  ADD COLUMN rz_date DATE NULL;
```

Migratsiya avtomatik bajariladi (`mig_rzchat`) — qo'lda hech narsa qilish
shart emas.

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
| Kuniga bir martadan ortiq | `rz_date` ustuni to'sadi (atomar `UPDATE … rowCount()===1`) |
| Vaqtlar hali olinmagan | `kutilmoqda` qaytaradi, keyingi daqiqada qayta urinadi |
| Telegram vaqtincha xato berdi | `dp_transient()` — `rz_date` tozalanadi, qayta yuboriladi |
| Bot kanaldan chiqarilgan | `chat_lost()` — egasiga xabar beriladi |
| Ko'p chat | 100 tadan, orasida 0,2 soniya tanaffus |

Barcha yuborishlar `post_logs` ga yoziladi.

---

## Sozlash

**Yuborish vaqti** — `rz_time` sozlamasi, standart `04:00` (saharlikdan oldin).
O'zgartirish: ma'lumotlar bazasida `settings` jadvalidagi `rz_time` qiymati.

**Ramazon sanasi** — admin paneldagi «Ramazon 1-kuni» va «Necha kun» maydonlari.
Bo'sh qoldirsangiz hijriy taqvimdan hisoblanadi (±1 kun xato bo'lishi mumkin),
shuning uchun rasmiy e'londan keyin aniq sanani kiriting.

---

## Tekshirish

Ramazon kelishini kutmasdan sinab ko'rish uchun admin panelda «Ramazon 1-kuni»
ga **bugungi sanani** qo'ying. Keyin:

1. Biror kanalda «Ramazon taqvimi» kalitini yoqing
2. `rz_time` ni hozirgi vaqtga yaqin qilib qo'ying
3. Bir-ikki daqiqa kuting — post chiqadi
4. Sinovdan keyin sanani haqiqiy qiymatga qaytaring

---

## Eslatma: vaqtlar qayerdan olinadi

Namoz vaqtlari cron'i o'chirilgani uchun islom.uz dan avtomatik yuklash yo'q.
`pr_get()` ichidagi `pr_calc()` zaxirasi vaqtlarni o'zi hisoblaydi — post
baribir chiqadi, rasm tagida «Hisoblangan vaqtlar» deb yoziladi.

Rasmiy islom.uz vaqtlarini xohlasangiz, ayting — cron'ga kichik qator
qo'shaman, u faqat Ramazon davrida kuniga bir marta yangilaydi.
