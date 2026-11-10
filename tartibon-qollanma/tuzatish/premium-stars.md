# Premium va Stars bo'limi

Telegram Premium va Telegram Stars sotish uchun yangi bo'lim.

## Avval muhim bir narsa

Telegram qoidalarini tekshirdim. Holat shunday:

**Premium** — botlar `giftPremiumSubscription` orqali Premium sovg'a qila oladi
(3, 6 yoki 12 oy; narxi 1000, 1500, 2500 Stars). **Lekin** bot o'z hisobida
shuncha Stars ushlab turishi kerak, Stars esa botga faqat foydalanuvchilar
to'lovidan tushadi. Ya'ni birinchi kuni bot hisobida 0 Stars bo'ladi va
avtomatik sovg'a qilish ishlamaydi.

**Stars** — botning oddiy foydalanuvchiga to'g'ridan-to'g'ri Stars o'tkazish
imkoni yo'q.

Shuning uchun bo'lim **qo'lda bajariladigan** qilib yozildi: foydalanuvchi
buyurtma beradi, pul balansidan yechiladi, siz Fragment yoki Telegram ilovasi
orqali bajarasiz va admin panelidan «Bajarildi» deb belgilaysiz. Bu Nakrutka
va eSIM bo'limlari bilan bir xil tartib — ilovada allaqachon ishlab turgan usul.

Keyinchalik botda Stars yig'ilsa, avtomatik sovg'a qilishni qo'shish mumkin.

## Foydalanuvchi tomoni

Bosh sahifada **«Premium va Stars»** katagi (Xizmatlar ichida, eSIM yonida).
Havolasi: `t.me/TartibOnBot?startapp=s_ts`

Sahifada:
- Balans va «To'ldirish» tugmasi
- Ikkita bo'lim: ⭐️ Premium (3 / 6 / 12 oy) va ✨ Stars (50 … 2500)
- **Kimga?** — bo'sh qoldirsa o'ziga, yoki boshqa odamning username'i
- «Buyurtmalarim» — holati bilan: ⏳ kutilmoqda · ✅ bajarildi · ❌ rad etildi

Narxi 0 bo'lgan paket **umuman ko'rsatilmaydi**. Hammasi 0 bo'lsa —
«Narxlar hali belgilanmagan».

## Admin tomoni

Admin panel → **«Premium va Stars»**:
- Bo'limni yoqish/o'chirish
- Izoh matni (foydalanuvchiga ko'rinadi, masalan «1-6 soatda bajariladi»)
- Har bir paketning narxi (so'mda)
- Buyurtmalar ro'yxati, kutilayotganlari tepada va ko'k ramkada

Har bir kutilayotgan buyurtmada izoh maydoni va ikki tugma:
- **✅ Bajarildi** — foydalanuvchiga xabar ketadi
- **❌ Rad etish** — pul balansiga **to'liq qaytariladi**, xabar ketadi

Yangi buyurtma kelganda adminga darhol xabar keladi.

## Himoya choralari

- Pul yechish va buyurtma yozish **bitta tranzaksiyada** — balans yetmasa
  buyurtma umuman yaratilmaydi
- `balance>=?` sharti bilan yechiladi, ya'ni minusga tushmaydi
- Bir vaqtda **5 tadan ortiq** kutilayotgan buyurtma berib bo'lmaydi
- Username formati tekshiriladi
- Buyurtma ikki marta hal qilinmaydi: `status='pending'` sharti bilan
  yangilanadi, allaqachon hal qilingan bo'lsa xato qaytadi
- Narxlar `ts_cfg` sozlamasida JSON bo'lib saqlanadi; begona kalit, manfiy
  son va juda uzun izoh tashlab yuboriladi

## Nima tekshirildi

| Nima | Qanday |
|---|---|
| `ts_cfg()` | Haqiqiy PHP: standart holat, narx qo'yilgan holat, buzuq sozlama |
| `ts_uname()` | 4 xil holat: Telegram obyekti, users qatori, ismsiz, faqat username |
| Foydalanuvchi sahifasi | Brauzerda: narx yo'q · narx bor · bo'lim yopiq |
| Stars bo'limi | Brauzerda alohida |
| Admin paneli | Brauzerda: sozlamalar + buyurtmalar ro'yxati |
| Bosh sahifa katagi | Mavjudligi tekshirildi |
| `s_ts` havolasi | Brauzerda: `p-ts` ochiladi |
| Eski 40 ta havola | Qayta sinaldi — hammasi joyida |

Yo'lda uchta xato topildi va tuzatildi: JavaScript `\U0001F4CB` ko'rinishdagi
eskeypni tushunmaydi (emoji o'rniga matn chiqardi), `fmt()` songa «so'm»
qo'shib yuborardi (Stars soni «250 so'm» bo'lib ko'rinardi), va `ts_uname()`
ismsiz foydalanuvchida ID ni ikki marta yozardi.

## Nima tekshirilmadi

Haqiqiy bazada ishga tushirmadim. Birinchi marta **o'zingizga kichik summали
test buyurtma** bering va rad etib ko'ring — pul qaytganini tekshiring.

Jadval `ts_ord` avtomatik yaratiladi (`mig_ts1` migratsiyasi), alohida
SQL ishga tushirish shart emas.

## Narx qo'yish

Narxlarni **o'zingiz belgilaysiz** — men hech qanday narx yozmadim, hammasi
0 turibdi va bo'lim «narxlar belgilanmagan» deb ko'rsatadi. Narx qo'yishda
Fragment'dagi haqiqiy tannarxni va o'z foydangizni hisobga oling.
