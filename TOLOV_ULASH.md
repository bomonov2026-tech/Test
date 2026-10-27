# Balansni karta bilan avtomatik to'ldirish (Payme · Click)

Ilovada: Balans → «Balansni to'ldirish» → **⚡ Karta bilan (avtomatik)**. Foydalanuvchi summani tanlaydi, «Payme/Click orqali» ni bosadi, to'lov sahifasida to'laydi — summa balansga darrov tushadi (chek va admin tasdig'i kerak emas). Qo'lda (chek bilan) usul saqlanib qolgan.

## Sizdan kerak (bir martalik)
Ikkala tizim ham **merchant shartnomasi** talab qiladi (yuridik shaxs yoki YaTT):
- **Payme:** Payme Business (business.payme.uz) → ro'yxatdan o'tish → kassa yaratish → **Merchant ID** va **Maxfiy kalit (Key)**.
- **Click:** Click Merchant (merchant.click.uz) → ro'yxatdan o'tish → xizmat yaratish → **Merchant ID**, **Service ID**, **Maxfiy kalit (Secret key)**.

## Sozlash (ilovada)
Admin → Profil → **💳 Avto-to'lov (Payme · Click)**:
1. Payme: Merchant ID, kalit, (sinov uchun «🧪 Sinov rejimi») → Saqlash.
2. Click: Merchant ID, Service ID, kalit → Saqlash.
3. Sahifada ko'rsatilgan **endpoint manzillarini** nusxalab, ularning kabinetiga qo'ying:
   - Payme → Kassa → Parametrlar → **Endpoint URL**: `https://DOMEN/index.php?pay=payme`
   - Click → Xizmat sozlamalari → **Prepare URL** va **Complete URL** (ikkalasi ham): `https://DOMEN/index.php?pay=click`
4. Summa chegarasi (standart 10 000 – 3 000 000 so'm).
5. Avval Payme **test kassada** (checkout.test.paycom.uz) sinab, so'ng sinov rejimini o'chiring.

## Shartlar
- Manzil **HTTPS** bo'lishi shart (`APP_URL` https:// bilan).
- Hostingda `Authorization` sarlavhasi PHP ga yetib borishi kerak (paketdagi `.htaccess` buni qiladi). Payme «ruxsat yo'q (-32504)» desa — sayt.uz yordamiga shuni ayting.
- Tranzaksiyalar va provayderdan kelgan so'rovlar jurnali admin sahifasida ko'rinadi.
- Payme to'lovni bekor qilsa (qaytarish), summa foydalanuvchi balansidan yechiladi; balans yetmasa (pul sarflangan) qaytarish rad etiladi va adminga xabar ketadi.

## Sinov holati
Payme (JSON-RPC: CheckPerform/Create/Perform/Cancel/Check/GetStatement) va Click (Prepare/Complete) protokollari imzolangan soxta so'rovlar bilan sinalgan (81 tekshiruv). **Haqiqiy Payme/Click bilan ulanish tekshirilmagan** — ularning o'z sinov (sandbox) tekshiruvidan o'tish kerak bo'ladi; birinchi marta kichik summa bilan sinab ko'ring.

---

# Karta SMS orqali avto-tasdiq (Humo · iPhone) — Merchant shartnomasisiz

Mijoz sizning kartangizga o'tkazadi → bank SMS yuboradi → iPhone «Buyruqlar» SMS ni ilovaga uzatadi → balans avtomatik to'ldiriladi.

## Sozlash
1. Admin → **Karta va narxlar**: karta raqami va egasini kiriting.
2. Admin → **Avto-to'lov** → «Karta (Humo SMS)»: yoqing, karta oxirgi 4 raqamini (masalan 6662) kiriting, **Saqlash**. Ilova maxfiy manzil beradi: `https://DOMEN/index.php?sms=TOKEN`.
3. iPhone → **Buyruqlar (Shortcuts)** → **Avtomatlashtirish** → **+** → **Xabar (Message)**:
   - «Xabar tarkibi» (Message contains): `HUMOCARD`
   - **Darhol ishga tushirish (Run Immediately)**, «Ishga tushishdan oldin so'rash» o'chiq.
   - Amal: **URL mazmunini olish (Get Contents of URL)** → URL: yuqoridagi manzil → Usul: **POST** → So'rov tanasi: **Forma** → maydon `text` = **Qabul qilingan xabar (Shortcut Input / Message)**.
4. Sinov: 1 000 so'm o'tkazib ko'ring (min. summa sozlamasiga qarang).

## Qanday ishlaydi
- Foydalanuvchi «Kartaga o'tkazish» bosadi → **noyob summa** oladi (masalan 100 037) va 40 daqiqa ichida aynan shuncha o'tkazishi kerak.
- SMS: karta oxiri, summa, vaqt, «Dostupno» qoldig'i tekshiriladi; bir xil SMS ikki marta hisoblanmaydi; qoldiq zanjiri buzilsa yoki limitdan katta/eski/kech kelgan to'lov **«Ko'rib chiqish»**ga tushadi — admin qo'lda tasdiqlaydi.

## Ogohlantirishlar (halol)
- Bank shaxsiy kartaga ko'p P2P o'tkazma kelsa kartani **vaqtincha bloklashi** mumkin; soliq/yuridik jihatlarni o'zingiz hal qiling.
- Telefon yoqiq va internetda bo'lishi shart; iPhone avtomatlashtirishi ba'zan kechikadi.
- Maxfiy manzilni hech kimga bermang (soxta SMS yuborilishi mumkin); chalkashsa — «yangi token» qiling.
- Haqiqiy bank SMS bilan faqat siz bergan namunada sinalgan; birinchi marta kichik summa bilan tekshiring.
