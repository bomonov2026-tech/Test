# Avtomatik xabarlar: ikkita o'zgarish + yangi xabar g'oyalari

> **Diqqat.** Menda turgan `index.php` — **eski versiya**. Sizning ekraningizda
> «A'zolar marrasi» bor, mening faylimda u yo'q. Shuning uchun quyida tayyor
> fayl emas, **aniq ko'rsatma** berdim. Hozirgi `index.php` ni yuborsangiz,
> o'zgartirilgan faylni to'g'ridan-to'g'ri qilib beraman.

---

## 1. Kurs posti — har 7 soatda

Hozir kurs posti **har 4 soatda** yuboriladi. Buni bitta qator boshqaradi:

```php
define('KURS_SLOT', 4 * 3600);   /* kurs posti har 4 soatda bir marta */
```

Tuzatish:

```php
define('KURS_SLOT', 7 * 3600);   /* kurs posti har 7 soatda bir marta */
```

Boshqa hech narsa o'zgartirish shart emas — yuborish mantig'i shu konstantadan
hisoblaydi:

```sql
FLOOR((UNIX_TIMESTAMP(last_post_at) + KURS_OFF) / KURS_SLOT)
  < FLOOR((UNIX_TIMESTAMP() + KURS_OFF) / KURS_SLOT)
```

### ⚠️ 7 soatda vaqt har kuni suriladi

Bu muhim, oldindan bilib qo'ying. Sutka 24 soat, 24 esa 7 ga bo'linmaydi.
Shuning uchun post vaqti **har kuni 3 soatga oldinga suriladi**:

| Kun | Yuborilish vaqtlari (Toshkent) |
|---|---|
| 1-kun | 00:00 · 07:00 · 14:00 · 21:00 |
| 2-kun | 04:00 · 11:00 · 18:00 |
| 3-kun | 01:00 · 08:00 · 15:00 · 22:00 |
| 4-kun | 05:00 · 12:00 · 19:00 |

Ya'ni ba'zi kunlari post tunda (01:00, 04:00) chiqadi va obunachi har kuni
boshqa vaqtda ko'radi.

**Agar vaqt doimiy bo'lishini xohlasangiz** — 24 ga bo'linadigan sonni oling:

| Qiymat | Yuborilish vaqtlari | Kuniga |
|---|---|---|
| `6 * 3600` | 00:00 · 06:00 · 12:00 · 18:00 | 4 marta |
| `8 * 3600` | 00:00 · 08:00 · 16:00 | 3 marta |
| `12 * 3600` | 00:00 · 12:00 | 2 marta |

Bular har kuni **bir xil vaqtda** chiqadi. Men **8 soat**ni tavsiya qilaman:
kuniga uch marta, vaqtlar doimiy, tunda ortiqcha post yo'q.

Qaroringizni ayting — 7 ham, 8 ham bir qatorlik o'zgarish.

---

## 2. Namoz vaqtlarini o'chirish

### Avval holatni ko'ring

Namoz vaqtlari kodga chuqur singgan:

| Nima | Nechta |
|---|---|
| `pr_*` funksiyalari | 22 ta |
| `png_prayer()` rasm generatori | 1 ta |
| `PR_*` konstantalari | 12 ta |
| `prayer_destinations` jadvali | alohida jadval |
| Kodda «namoz» so'zi | 96 marta |
| Tashqi API (`islomapi.uz`) | 17 joyda |

Hammasini o'chirish — katta ish va xatolik xavfi bor. Lekin **yaxshi xabar**:
namoz xabarlari **alohida cron** orqali yuboriladi, asosiy crondan ajratilgan:

```
?prayer_cron=PR_KEY        (yoki: php index.php prayer)
```

Shuning uchun uni **xavfsiz va darhol** to'xtatish mumkin.

### Tavsiya qilingan yo'l — 3 qadam

**1-qadam. Yuborishni to'xtatish (kodga tegmasdan)**

Serverdagi crontab'dan namoz qatorini olib tashlang:

```bash
crontab -e
# shu qatorni o'chiring yoki boshiga # qo'ying:
# * * * * * curl -s "https://.../?prayer_cron=..."
```

Shu zahoti hech kimga namoz xabari bormaydi. Kod tegilmaydi, orqaga qaytarish
ham oson.

**2-qadam. Bosh sahifadan katakni yashirish**

Kodga tegmasdan: ilovada admin sifatida bosh sahifani **«✎ Tahrirlash»**
rejimiga o'tkazib, «Namoz vaqtlari» katagini yashiring. Bu sozlama
`hx_hide` da saqlanadi.

**3-qadam. «Avtomatik xabarlar» ro'yxatidan olib tashlash**

Chat sozlamalari ro'yxatidagi «Namoz vaqtlari» qatorini chiqaradigan joyni
toping va o'sha bitta qatorni o'chiring (yoki `if (false)` ga o'rang).

### To'liq o'chirish (keyinroq, xohlasangiz)

Agar kodni butunlay tozalamoqchi bo'lsangiz, tartib shunday:

1. `?prayer_cron=` bloki va `php index.php prayer` CLI tarmog'i
2. 22 ta `pr_*` funksiyasi va `png_prayer()`
3. 12 ta `PR_*` konstantasi (`PR_API`, `PR_KEY`, `PR_TPL_*` va b.)
4. Admin paneldagi «Namoz xabarnomalari» bo'limi (`pra()`)
5. Bosh sahifadagi katak HTML'i va unga bog'langan sahifa
6. «Ilova haqida» dagi namoz bo'limi matni
7. `DEF_CAP_C` konstantasi (namoz posti sarlavhasi)
8. Oxirida: `DROP TABLE prayer_destinations;`

**Eslatma:** «Ramazon taqvimi — Tez kunda» rejangiz namoz vaqtlari kodining bir
qismidan (`pr_fetch`, `pr_calc`, hudud tanlash) foydalanishi mumkin. Agar shu
reja kuchda bo'lsa, **4–8-qadamlarni bajarmang** — faqat 1–3-qadam bilan
cheklaning, kod turaversin.

---

## 3. O'rniga qanday xabar qo'shsak bo'ladi — 6 ta g'oya

Tartib: eng kam mehnat va eng katta foyda birinchi.

### 🥇 1. Tug'ilgan kunlar tabrigi

> *«🎂 Bugun guruhimizda 3 kishining tug'ilgan kuni — Dilnoza, Javohir, Malika.
> Tabriklaymiz!»*

**Nega eng yaxshisi:** ma'lumot **allaqachon sizda bor**. Ilovada «Tug'ilgan
kun» funksiyasi bor — foydalanuvchi sanasini kiritadi va shu kuni balansiga
bonus tushadi. Ya'ni yangi API ham, yangi jadval ham kerak emas.

**Nega ishlaydi:** guruhda eng ko'p reaksiya oladigan post turi. Odamlar
tabriklaydi, suhbat boshlanadi, guruh jonlanadi. Tabriklanganlar guruhga
bog'lanib qoladi.

**Mehnat:** kam. Kuniga bir marta so'rov + tayyor shablon.

---

### 🥈 2. Yevro va rubl kursi

> Hozirgi dollar kursi postiga yevro va rubl qo'shish.

**Nega:** kurs ma'lumotlari allaqachon yig'iladi (kodda `rates_data()` barcha
valyutalar bilan ishlaydi, `card_rows()` esa faqat `USD` ni filtrlaydi).
Rasm generatori (`png_card`) ham tayyor.

**Nega ishlaydi:** O'zbekistonda rubl kursi dollardan kam qiziqtirmaydi —
Rossiyada ishlaydiganlar va ularning oilalari uchun. Yevro esa sayohat va
o'qish uchun.

**Mehnat:** kam. `card_rows()` dagi filtrni o'zgartirish va rasmga qator qo'shish.

---

### 🥉 3. Haftalik guruh hisoboti

> *«📊 Shu hafta: 1 240 xabar · 38 yangi a'zo · eng faol — Dilnoza (214 xabar)»*

**Nega:** statistika va TOP-10 ham allaqachon bor (`Statistika` bo'limi,
haftalik TOP-10 funksiyasi).

**Nega ishlaydi:** adminlar uchun qimmatli, a'zolar uchun qiziq. Haftada bir
marta chiqqani uchun bezdirmaydi.

**Mehnat:** o'rtacha. Mavjud ma'lumotlarni bir postga yig'ish.

---

### 4. Kunlik hikmatli so'z

> *«Ilm — boylikdan afzal. Boylikni sen qo'riqlaysan, ilm esa seni qo'riqlaydi.»*

**Nega:** tashqi API umuman kerak emas — 365 ta iqtibosdan iborat jadval
yetarli. `px_tools` rasm chizish vositasi tayyor, chiroyli karta qilib
chiqarish oson.

**Nega ishlaydi:** O'zbek Telegram kanallarida eng ko'p ulashiladigan kontent
turi. Namoz vaqtlari o'rniga **ma'naviy bo'shliqni to'ldiradi**, lekin hech
qanday tashqi manbaga bog'liq emas.

**Mehnat:** kam (asosiy ish — iqtiboslar to'plash).

---

### 5. Tarixda bugun

> *«📅 Bugun — 4-noyabr. 1991-yilda shu kuni…»*

**Nega ishlaydi:** o'qiladi va ulashiladi, kanalga "foydali" tus beradi.

**Mehnat:** o'rtacha — 366 kunlik ma'lumot bazasini tayyorlash kerak.
Lekin bir marta qilinadi va keyin abadiy ishlaydi.

---

### 6. Kunlik ingliz so'zi

> *«📖 So'z: *reliable* — ishonchli. "He is a reliable friend."»*

**Nega ishlaydi:** o'quv va yoshlar kanallari uchun. Obunachi har kuni
qaytib keladi.

**Mehnat:** kam (so'zlar ro'yxati + shablon).

---

## Mening tavsiyam

Namoz vaqtlari o'rniga **ikkitasini** qo'ying, ko'p emas:

1. **Tug'ilgan kunlar** — ma'lumot tayyor, guruhlarda eng kuchli ta'sir
2. **Kunlik hikmatli so'z** — tashqi manbaga bog'liq emas, hech qachon
   «xabar olinmadi» xatosi chiqmaydi

Keyin kuzating: qaysi biri ko'proq ko'rilsa, o'shani rivojlantiring.

> **Nega ko'p emas?** Hozir ro'yxatda 8 ta avtomatik xabar bor. Yana beshtasini
> qo'shsangiz, kanal obunachisi kuniga 10+ avtomatik post oladi va obunadan
> chiqa boshlaydi. Kam, lekin sifatli — yaxshiroq.
