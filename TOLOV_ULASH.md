# Avto-to'lov: faqat karta SMS orqali (Humo · iPhone)

> Payme va Click bekor qilingan (ilovadan olib tashlangan).


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
