# TartibOn + STICK OPS (5v5)

Bu paket siz yuborgan `index.php` asosida tayyorlangan. PHP hosting ilovani va Telegram akkaunti bilan kirishni boshqaradi. Alohida Node.js server jonli janglarni hisoblaydi. O‘yinni to‘liq onlayn ishlatish uchun ikkala qismini joylash va bir xil maxfiy kalit bilan bog‘lash kerak.

## Paket ichida

- `hosting/`: hozirgi PHP hostingingizga yuklanadigan yangilangan `index.php`, `ops-config.example.php` va `stick-ops/` papkasi.
- `game-server/`: Node.js 22+ ishlatadigan alohida WebSocket server, umumiy jang dvigateli va testlar.
- `docs/`: integratsiyani qayta qo‘llash skripti va texnik eslatmalar.

## O‘rnatish: 1. Jang serveri

Node.js 22+ va doimiy ishlaydigan WebSocket xizmatini qo‘llaydigan hosting, PaaS yoki VPS kerak. Oddiy PHP hostingda buni cron orqali ishga tushirmang. Ochiq manzil HTTPS/WSS bo‘lishi kerak. Render kabi xizmatdan foydalansangiz, uyquga ketmaydigan tarif va WebSocket qo‘llovini tekshiring; narx va tarif cheklovlari xizmatga bog‘liq.

1. `game-server/` tarkibini Node.js xizmatiga joylang. Ishchi papka — shu papka.
2. `npm ci --omit=dev` bajaring.
3. Quyidagi buyruq bilan tasodifiy kalit yarating. Uni chatga yubormang:

   ```sh
   node -e "console.log(require('node:crypto').randomBytes(32).toString('hex'))"
   ```

4. Xizmatning environment sozlamalariga yozing:

   ```dotenv
   OPS_SHARED_SECRET=YUQORIDA_YARATILGAN_KALIT
   OPS_ALLOWED_ORIGINS=https://ilovangiz-domeni.uz
   PORT=8080
   ```

   `OPS_ALLOWED_ORIGINS` — PHP Mini App manzilining faqat origin qismi: `https://domen.uz`. Oxirida `/`, papka yoki `index.php` bo‘lmasin. Ilova subpapkada bo‘lsa ham origin o‘zgarmaydi. Bir nechta aniq origin vergul bilan berilishi mumkin. `*` ishlatilmaydi. `PORT` uchun PaaS avtomatik bergan qiymat bo‘lsa, o‘sha ishlatiladi.

5. Ishga tushirish buyrug‘i: `npm start`.
6. Server manzili `https://jang.example-host.com` bo‘lsa, WebSocket manzil `wss://jang.example-host.com/ws`. `/health` ochilganda holat JSON qaytadi.

Server bitta Node.js instansiyasida ishlaydi. PaaS’da instance/replica sonini **1** qiling. Jang xonalari operativ xotirada; restart/deploy bo‘lsa faol janglar tugaydi. Bu boshlang‘ich versiya uchun ongli cheklov. Bir nechta instansiyaga chiqishdan oldin umumiy xona state’i yoki xona egasi bo‘yicha marshrutlash kerak.

## O‘rnatish: 2. PHP hosting

1. Saytingizdagi hozirgi `index.php` faylining zaxira nusxasini oling.
2. Paketning `hosting/` papkasidagi `index.php` va `stick-ops/` papkasini hozirgi ilova yoniga yuklang. Mavjud `config.php`, baza, cron va webhook sozlamalarini saqlang.
3. `ops-config.example.php` faylidan nusxa oling, nomini `ops-config.php` qiling va `index.php` yoniga qo‘ying:

   ```php
   <?php
   return array(
       'ws_url' => 'wss://SIZNING-JANG-SERVERINGIZ/ws',
       'shared_secret' => 'NODE_SERVERDAGI_XUDDI_SHU_KALIT'
   );
   ```

4. Telegram Mini App’ni qayta oching. Bosh sahifada, xizmatlar menyusida va mavjud O‘yin sahifasida **STICK OPS** tugmasi bor.
5. Avval **Mashq** bilan boshqaruvni sinang. Keyin xona ochib, taklif havolasini guruhga yuboring. Har jamoada 5 tadan haqiqiy o‘yinchi bo‘lganda mezbon boshlaydi.

Kalit frontendga berilmaydi. PHP mavjud Telegram `initData` tekshiruvi va akkauntga qo‘yilgan cheklovlardan so‘ng 2 daqiqalik bir martalik kirish chiptasini chiqaradi. PHP va Node server soatlari to‘g‘ri bo‘lsin. Bot tokenini Node xizmatiga berish shart emas.

Server hali sozlanmaganida online bo‘lim buning sababini ko‘rsatadi; **botlar bilan mashq** ishlaydi. `.js` fayllarini hosting `text/javascript` yoki `application/javascript` turida berishi kerak. CSP sozlangan bo‘lsa, Mini App originida shu iframe’ga `frame-src 'self'`, jang WSS manziliga esa `connect-src` ruxsatini bering.

## O‘yin

- 5 ko‘k himoyachi va 5 qizil hujumchi; A/B bomba nuqtalari.
- Xarid 10 soniya, boshlanish sanog‘i 3 soniya, raund 90 soniya.
- Hujumchi bombani nuqtaga olib borib **Amal / E** tugmasini ushlab o‘rnatadi. Himoyachi shu tugma bilan zararsizlantiradi.
- Birinchi 5 raundni olgan jamoa yutadi.
- Pistolet, SMG, avtomat, drobovik, snayper va pichoq. Xarid pullari jang ichida serverda hisoblanadi.
- Telefonda: chap joystick yurish, o‘ng joystick nishon va otish; o‘q almashtirish va bomba amali alohida.
- Kompyuterda: WASD, sichqoncha bilan nishon/otish, R — o‘q almashtirish, E — bomba amali.
- Qurilmaga gorizontal holat tavsiya etiladi.

Ilovaning so‘mdagi xizmat balansi va yugurish reytingi o‘z vazifasida qoladi. Jang ichidagi qurol xarid pullari o‘sha o‘yinga tegishli; hozirgi paketda doimiy terilar do‘koni, naqd to‘lov yoki umumiy reyting bazasi yo‘q.

## Tekshirish

`game-server/` ichida `npm ci` va `npm test` bajaring. Natijalar va shu muhitda bajarilgan tekshiruvlar `docs/VALIDATION.md` faylida yozilgan. Hostingdagi `config.php`, MySQL, haqiqiy Telegram sessiyalari va tashqi WSS domeni bu muhitdan tekshirilmagan; joylagandan keyin 10 alohida akkaunt bilan yakuniy sinov kerak.

## Xatoliklarni topish

- **Online server sozlanmagan:** `ops-config.php` to‘ldirilganini va `.example` manzil almashtirilganini tekshiring.
- **Ulanish uzildi:** WSS manzili, xizmatning ishlashi va sertifikatini tekshiring.
- **Origin rad etildi:** Node’dagi `OPS_ALLOWED_ORIGINS` PHP ilova originiga aynan teng bo‘lsin.
- **Chipta noto‘g‘ri:** ikki joydagi kalit, server soati va yangi Telegram sessiyasini tekshiring.
- **Boshlash ishlamaydi:** xonada aynan 10 kishi, har jamoada 5 kishi va boshlayotgan kishi mezbon bo‘lishi kerak.
- **Blank ekran:** hostingdagi `stick-ops/client/` va `stick-ops/shared/` fayllari manzili, JS MIME turi va brauzer konsolini tekshiring.

## Orqaga qaytarish

Zaxiradagi original `index.php` faylini qaytaring. STICK OPS qo‘shilishi mavjud MySQL jadvallarini o‘zgartirmaydi.
