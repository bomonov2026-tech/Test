# Yangi avtomatik xabar g'oyalari

Avval kodni to'liq sanab chiqdim — `cron_minute()` da **43 ta vazifa** bor.
Quyidagilar takrorlanmaydi.

---

## ⚠️ Avvalgi maslahatimni tuzataman

O'tgan safar «yevro va rubl kursini qo'shish» deb yozgan edim. **Yarim
noto'g'ri edi.**

Kodni ochib ko'rsam, **2-ramka** (`png_list()`) allaqachon barcha valyutalarni
chiqarar ekan — Markaziy bankning rasmiy kursi bo'yicha, `USD, EUR, RUB`.

Lekin **1-ramka** (`png_card()`) — banklardagi **olish va sotish** narxi —
faqat dollar uchun. `card_rows()` da shunday filtr bor:

```php
foreach (rates_data() as $c) if ($c['code'] === 'USD') ...
```

Ya'ni haqiqiy bo'shliq shu: **banklar rublni va yevroni qanday narxda olib-sotishi
ko'rsatilmaydi.** Rasmiy kurs bor, lekin odam bankka borganda boshqa narxni
ko'radi.

---

## Hozir nima bor (takrorlamaslik uchun)

Kanal/guruhga: kurs posti (banklar, USD), Markaziy bank kurslari, tongi va
tungi xabar, ob-havo va ogohlantirish, bayram tabriklari, Kun kanali,
a'zolar marrasi, Ramazon taqvimi, rejalashtirilgan postlar, konkurs,
testlar/krossvord, haftalik TOP-10, RSS (saytdan avtopost), sanoq posti,
Juma sukuti, tungi rejim, ochiq byudjet.

Egasiga: haftalik hisobot, kunlik hisobot, monitoring.

---

## 🥇 Birinchi uchlik — shuni tavsiya qilaman

### 1. Kunlik so'rovnoma (Telegram poll)

> Bot har kuni guruhga tayyor so'rovnoma tashlaydi:
> *«Bugun qaysi mavzuda post kerak?»* · *«Kayfiyatingiz qanday?»*

**Nega eng yaxshisi:** Telegram'ning o'z so'rovnomasi — a'zo bir bosadi,
natija hammaga ko'rinadi. Hech qanday tashqi manba, hech qanday ma'lumot
bazasi kerak emas. Faqat 100–200 ta tayyor savol.

Guruhni jonlantirishning **eng arzon** usuli: odam yozmasa ham, bosadi.
Bosganlar esa keyin yozishni ham boshlaydi.

**Mehnat:** kam. `sendPoll` Telegram API'sida tayyor, savollar jadvali statik.

---

### 2. Tug'ilgan kunlar tabrigi

> *«🎂 Bugun guruhimizda 3 kishining tug'ilgan kuni — Dilnoza, Javohir,
> Malika. Tabriklaymiz!»*

**Nega:** ma'lumot **allaqachon bazada**. Ilovada tug'ilgan kun bonusi
funksiyasi bor — foydalanuvchi sanasini kiritadi.

Guruhda eng ko'p reaksiya oladigan post turi. Tabriklanganlar guruhga
bog'lanib qoladi.

**Mehnat:** kam. Kuniga bir so'rov + tayyor shablon.

---

### 3. Banklarda yevro va rubl

> 1-ramkaga yevro va rubl qo'shish — olish va sotish narxi bilan.

**Nega:** Rossiyada ishlaydiganlar va ularning oilalari uchun rubl kursi
dollardan kam muhim emas. Hozir ular faqat rasmiy kursni ko'radi, bankdagi
haqiqiy narxni emas.

**Mehnat:** kam. Ma'lumot `rates_data()` da allaqachon bor, rasm generatori
(`png_card`) tayyor — faqat filtrni ochish va rasmga ustun qo'shish.

---

## Qolgan g'oyalar

### Tashqi manba kerak emas (statik baza + shablon)

| G'oya | Kimga | Mehnat |
|---|---|---|
| **Kunlik hikmat / maqol** | Hammaga, ayniqsa ma'naviy kanallar | kam |
| **Kunlik ingliz so'zi** (so'z + tarjima + misol) | O'quv kanallari, yoshlar | kam |
| **Tarixda bugun** | Yangilik va ta'lim kanallari | o'rtacha (366 kunlik baza) |
| **Kunlik retsept** | Ayollar guruhlari — Savdogar auditoriyasiga mos | o'rtacha |
| **Salomatlik maslahati** | Keng auditoriya | kam |

### Ilovadagi ma'lumotdan (yangi manba kerak emas)

| G'oya | Izoh | Mehnat |
|---|---|---|
| **Haftalik guruh hisoboti kanalga** | Hozir hisobot faqat **egasiga** boradi. A'zolarga ko'rsatish — «shu hafta 1 240 xabar, 38 yangi a'zo, eng faol — Dilnoza» | o'rtacha |
| **Haftalik kurs xulosasi** | «Shu hafta dollar 120 so'mga oshdi» + mini grafik. Ma'lumot allaqachon yig'ilgan | o'rtacha |

### Tashqi manba kerak

| G'oya | Muammo |
|---|---|
| **Futbol natijalari** | Qiziqish yuqori, lekin ishonchli va bepul API topish kerak |
| **Benzin / avtogaz narxlari** | Rasmiy manba yo'q, qo'lda yangilash kerak bo'ladi |
| **Kino premyeralari** | Manba beqaror |

---

## Muhim: son emas, sifat

Hozir bitta kanalga **8 tagacha** avtomatik xabar yoqilishi mumkin. Yana
beshtasini qo'shsangiz, obunachi kuniga 10+ avtomatik post oladi va obunadan
chiqa boshlaydi.

### Taklif: «Kunlik yig'ma post»

Alohida-alohida post o'rniga **bitta ertalabki post**: ob-havo + kurs +
hikmat + tug'ilgan kunlar — hammasi bir rasmda yoki bir xabarda.

**Afzalligi:** kanalda kuniga 5 ta post o'rniga 1 ta, lekin ma'lumot o'sha.
Obunachi bezmaydi, admin esa hammasini bitta kalit bilan boshqaradi.

Buni yangi funksiya qo'shishdan **oldin** o'ylab ko'rishni tavsiya qilaman —
aks holda har bir yangi xabar kanalni yana bir pog'ona shovqinli qiladi.

---

## Qaysidan boshlash

Agar bittasini tanlasangiz — **kunlik so'rovnoma**. Sababi: mehnat eng kam,
tashqi manbaga bog'liq emas, va u post qo'shmaydi, balki guruhdagi **suhbatni**
ko'paytiradi. Qolgan g'oyalarning hammasi kanalga yana bir post qo'shadi,
so'rovnoma esa a'zolarni gapirtiradi.

Qaysi birini yozishimni aytsangiz — qilaman.
