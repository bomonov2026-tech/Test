# Reels — Pul ishlash (hamkor dasturi)

> **Ikki variant bor, matni bir xil, vaqtlari ham bir xil:**
> - `01-hamkor-dasturi.mp4` — slayd uslubi (5,1 MB)
> - `02-hamkor-dasturi-3d.mp4` — **3D motion-grafika** (10,3 MB)
>
> Quyidagi vaqt belgilari **ikkalasiga ham** to'g'ri keladi.

**Instagram Reels va Telegram uchun · 9:16 (1080×1920) · 66 soniya**

**Kimga:** Telegramda ko'p vaqt o'tkazadigan har kim

> Videoda subtitr yo'q. Quyidagi matnni ovoz bilan o'qiysiz.
> Ohang: savol bilan boshlanadi, o'rtasida ishonchli, 8–9-sahnada **halol va tinch**
> («birdan boyib ketmaysiz» — bu joyni shoshmasdan ayting).

---

**`00:00`**  ·  ekranda: *«Telegramda kuniga necha soat o'tkazasiz?»*

Telegramda kuniga necha soat o'tkazasiz?

**`00:03`**  ·  ekranda: *«3 soat»*

O'rtacha uch soat. Bu oyiga to'qson soat. Yiliga qirq besh kun — uxlamasdan.

**`00:09`**  ·  ekranda: *«Bu vaqt sizga nima berdi?»*

Savol: shu vaqt sizga nima berdi? Hech narsa.

**`00:13`**  ·  ekranda: *«Shu vaqtning 5 daqiqasi yetarli»*

Endi o'sha vaqtning atigi besh daqiqasi pul ishlashi mumkin.

**`00:18`**  ·  ekranda: *«Har bir do'st uchun 500 so'm»*

TartibOn ilovasida shaxsiy havolangiz bor. Havolangiz orqali kelgan har bir yangi foydalanuvchi uchun besh yuz so'm olasiz. Avtomatik.

**`00:27`**  ·  ekranda: *«Uch qadam»*

Uch qadam. Havolani nusxalaysiz, do'stlaringizga tashlaysiz, pul o'zi tushadi.

**`00:32`**  ·  ekranda: *«Bir martalik emas»*

Eng muhimi: bu bir martalik emas. Taklif qilgan odamingiz xizmat sotib olsa — har safar sizga uch foiz ulush tushadi.

**`00:41`**  ·  ekranda: *«Birdan boyib ketmaysiz»*

Ochiq aytamiz: bundan birdan boyib ketmaysiz.

**`00:44`**  ·  ekranda: *«Kam-kam, lekin doimiy»*

Lekin kam-kam yig'ilaveradi va to'xtamaydi. Kam-kam xo'ram — hamisha xo'ram.

**`00:50`**  ·  ekranda: *«Pulni qanday olasiz»*

Yig'ilgan pulni asosiy balansga o'tkazasiz. Keyin xizmatlarga sarflaysiz yoki kartangizga yechasiz.

**`00:55`**  ·  ekranda: *«Kanalingiz bormi?»*

Agar kanalingiz yoki guruhingiz bo'lsa, bitta post yetarli — havolani yuzlab odam ko'radi.

**`01:02`**  ·  ekranda: *«TartibOn»*

TartibOn. Telegramda qidiring va havolangizni bugunoq oling.

---

### Rolikdagi raqamlar — kodga mos

Hammasi `index.php` dan tekshirildi:

| Raqam | Kodda | Izoh |
|---|---|---|
| **500 so'm** | `ref_reward()`, standart `500` | Admin panelda o'zgartirsa bo'ladi |
| **3%** | `pt_cfg()['pct']`, standart `3` | 0–20% oralig'ida sozlanadi |
| **Eng kam 500 so'm** | `ref_min()`, standart `500` | Asosiy balansga o'tkazish chegarasi |

Muhim shartlar (rolikda aytilgan):

- Mukofot **faqat yangi foydalanuvchi** uchun beriladi (`ref_credit()` da `$isNew`)
- Pul avval **bonus balansga** tushadi
- Asosiy balansga **admin tasdig'i bilan** o'tadi
- Keyin xizmatlarga sarflanadi yoki kartaga yechiladi

### 3D variant haqida

Ikkinchi variant slayd emas — har kadr alohida hisoblanadi (30 fps, 1 986 kadr):

| Element | Harakat |
|---|---|
| Kamera | Doimiy 3D uchish — `rotateX/Y`, `translateZ` |
| Matn | So'zlar ketma-ket, 3D chuqurlikdan uchib kiradi |
| Kartalar | `rotateY` bilan uchib kiradi, keyin sekin tebranadi |
| Fon | Uchta parallaks shar, har biri o'z sur'atida |
| Raqamlar | «45» noldan sanaydi; «3» va «500 so'm» overshoot bilan |
| Chuqurlik | Perspektiva 1500px, vinyetka, yorug'lik halqasi |

Hammasi `render(t)` funksiyasida vaqtdan hisoblanadi — tasodifiy element yo'q,
shuning uchun diktor vaqtlari aniq mos keladi.

### Nega «birdan boyib ketmaysiz» deb aytdim

Rolik 500 so'mni katta pul qilib ko'rsatmaydi. Sababi oddiy: ko'rsatsa,
odam ishonib kelib, keyin hafsalasi pir bo'ladi va ilovani tashlab ketadi.

Siz aytgan **«kam-kam xo'ram, hamisha xo'ram»** — aynan to'g'ri ohang.
Va'da kichik, lekin bajariladi. Bunday reklama uzoq muddatda ko'proq
foydalanuvchi olib keladi.
