# Nakrutka xizmati (prm4u.com, SMM API v2)

Ilovada: bosh sahifa → **Nakrutka**. Foydalanuvchi platforma (Telegram/Instagram/YouTube), xizmat, havola va miqdorni tanlaydi; pul balansdan yechiladi, buyurtma prm4u.com ga yuboriladi, holati avtomatik yangilanadi, bajarilmagan qism uchun pul avtomatik qaytadi.

## Sozlash (faqat admin, bir marta)
1. prm4u.com → hisob → **API** bo'limidan API kalitni oling (va hisobingizni to'ldiring).
2. Ilova → Nakrutka → pastdagi **⚙️ Admin** kartasi:
   - API manzil: `https://prm4u.com/api/v2` (tayyor)
   - API kalit (kiritganingizdan so'ng faqat oxirgi 4 belgi ko'rinadi)
   - Dollar kursi (bo'sh = bankdagi dollar kursi avtomatik), ustama % (masalan 40), kunlik chegara
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
