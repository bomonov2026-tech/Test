# Nakrutka xizmati (prm4u.com, SMM API v2)

Ilovada: bosh sahifa → **Nakrutka**. Foydalanuvchi platforma (Telegram/Instagram/YouTube), xizmat, havola va miqdorni tanlaydi; pul balansdan yechiladi, buyurtma prm4u.com ga yuboriladi, holati avtomatik yangilanadi, bajarilmagan qism uchun pul avtomatik qaytadi.

## Sozlash (faqat admin, bir marta)
1. prm4u.com → hisob → **API** bo'limidan API kalitni oling (va hisobingizni to'ldiring).
2. Ilova → Nakrutka → pastdagi **⚙️ Admin** kartasi:
   - API manzil: `https://prm4u.com/api/v2` (tayyor)
   - API kalit (kiritganingizdan so'ng faqat oxirgi 4 belgi ko'rinadi)
   - Dollar kursi (bo'sh = bankdagi dollar kursi avtomatik), ustama % (70/80/90/100% tugmalari yoki o'zingiz yozing), kunlik chegara
   - **🔌 Tekshirish** — provayder balansi chiqsa, ulanish to'g'ri.
3. «➕ Provayderdan xizmat qo'shish»: platformani tanlang, qidiring (masalan `telegram members`), «➕ qo'shish». Nomi va ustamani xizmat kartasida tahrirlang.
4. Daqiqalik cron (`php index.php prayer`) ishlab turishi kerak — holat yangilash va qaytarishlar shu orqali.

Ixtiyoriy (config.php): `define('NK_API_KEY','...'); define('NK_API_URL','https://prm4u.com/api/v2');` — qo'yilsa, ilovadagi maydonlar o'chirilgan bo'ladi.

## Qoidalar
- Narx = provayder narxi ($/1000) × dollar kursi × (1 + ustama), 10 so'mgacha yuqoriga yaxlitlanadi.
- Faqat oddiy («Default» turidagi) xizmatlar qo'llanadi.
- Provayder buyurtmani aniq rad etsa — pul darhol qaytadi. Natija noaniq bo'lsa (tarmoq uzilishi) — pul ushlab turiladi, adminga xabar boradi; admin provayder panelida tekshirib, «↩️ Qaytarish» yoki «✔ Yuborilgan» ni bosadi.
- Provayder balansi 5$ dan kam bo'lsa, adminlarga ogohlantirish yuboriladi.
- Sinov: soxta provayder bilan 93 ta avtomatik tekshiruv + 17 ta interfeys tekshiruvi o'tgan.

## Provayder balansi (faqat admin panelda)
Admin panel (Profil → admin) → «💰 Provayder balanslari» ingichka kataklari: prm4u va 5sim balansi va holat nuqtasi (🟢 yetarli, 🔴 kam/ulanmadi). Bosilsa to'liq sahifa: so'mdagi qiymat, 7 kunlik sarf va «necha kunga yetadi», ogohlantirish chegarasi (standart $5), yangilash, provayder saytiga o'tish. Bosh sahifada ko'rinmaydi. 5sim virtual raqam xizmati qo'shilganda shu bo'limga qo'shiladi.

### 5sim balansi
Bo'lim sahifasida 5sim kartasi: API kalitni kiriting (5sim → Settings → «API key for 5SIM protocol», yangi API; «Deprecated» emas), balans valyutasini (RUB/USD) tanlang va 💾 bosing. Balans `GET https://5sim.net/v1/user/profile` (Authorization: Bearer) orqali olinadi. **5sim sandboxdan tekshirilmagan** (faqat soxta server bilan): birinchi marta balansni saytdagi bilan solishtiring, valyuta mos kelmasa RUB/USD ni almashtiring.

## Virtual raqam (5sim aktivatsiya) — bosh sahifa → «Virtual raqam»
- Foydalanuvchi tarmoqni (Telegram, WhatsApp…) tanlaydi → «Olish»: pul yechiladi, raqam ko'rsatiladi (nusxalash tugmasi), sahifa har 5 soniyada kodni tekshiradi, kod kelganda katta ko'rinadi va Telegram'ga xabar ketadi.
- **Kod kelmasa** (standart 15 daqiqa) yoki foydalanuvchi bekor qilsa (SMS kelmagan bo'lsa) pul avtomatik qaytadi. Kod kelgach pul qaytmaydi. Provayder buyurtmani rad etsa, pul darhol qaytadi. Natija noaniq (tarmoq uzilishi) bo'lsa, pul ushlab turiladi va adminga xabar ketadi (admin «Qaytarish» yoki «Yuborilgan»).
- **Admin** (sahifa pastida): 5sim kaliti va valyuta — Profil → Provayder balanslari → 5sim. «🔎 5sim narxini ko'rish» (davlat+tarmoq) → «➕ Taklif qo'shish» (nom, qat'iy narx so'mda). Har taklifda tannarx, qoldiq va foyda ko'rinadi.
- **Foyda himoyasi:** tannarx oshib, foyda chegarasidan (standart 15%) pastga tushsa, taklif avtomatik yashiriladi va adminga xabar ketadi (zararga sotilmaydi). Buning uchun 1 rubl (yoki USD) kursini kiriting.
- Chegaralar: bir vaqtda kutilayotgan raqamlar (3), kunlik buyurtmalar (30), takror bosishdan himoya, parallel so'rovda qo'sh yechish yo'q.
- Davlat/tarmoq nomlari 5sim dagidek: inglizcha, kichik harf (`indonesia`, `canada`, `usa`; `telegram`, `whatsapp`, `instagram`).
- **5sim bilan haqiqiy ulanish tekshirilmagan** (faqat 5sim ommaviy API v1 formatidagi soxta server bilan: 78 backend + 12 interfeys tekshiruvi). Birinchi marta eng arzon raqamni o'zingiz sotib olib sinab ko'ring (masalan Indoneziya, ~$0.2).
