# Ramazon taqvimini qanday ishga tushirish

Kodni tekshirdim. Qisqa javob: **kattaroq qismi allaqachon yozilgan va ishlaydi.**
«Tez kunda» yozuvi faqat **bitta** qismga tegishli.

---

## Nima allaqachon tayyor

| Qism | Holati |
|---|---|
| Ramazon sanasini aniqlash (`rz_range`) | ✅ hijriy taqvimdan avtomatik |
| Saharlik / iftor vaqtlari (`rz_data`) | ✅ bomdod va shom vaqtidan |
| Taqvim sahifasi (`p-rz`) va bosh sahifa katagi | ✅ ishlaydi |
| Hudud tanlash | ✅ |
| Shaxsiy eslatma: obuna (`rz_sub` API + jadval) | ✅ |
| Eslatma yuborish (`rz_run`) | ✅ cron'ga ulangan |
| Eslatma vaqtini tanlash (10/15/30/45 daqiqa) | ✅ |
| Saharlik va iftor duolari | ✅ `RZ_DUA_S`, `RZ_DUA_I` |
| Admin: Ramazon sanasini qo'lda kiritish | ✅ `rm_start`, `rm_days` |

Ya'ni foydalanuvchi bosh sahifadan «Ramazon taqvimi» ni ochadi, hududini
tanlaydi, eslatmani yoqadi — va Ramazon kelganda saharlikdan oldin hamda
iftor vaqtida shaxsiy xabar oladi.

## «Tez kunda» aslida nimaga tegishli

Faqat **kanal va guruhga joylash** qismiga.

Chat sozlamalaridagi o'sha qator haqiqiy kalit emas — u shunchaki xabar
chiqaradigan bo'sh element:

```html
<div class="lr in tq-off" onclick="toast('🌙 Ramazon taqvimi tez kunda ishga tushadi')">
```

Ya'ni **shaxsiy eslatma ishlaydi, kanalga avtomatik post hali yo'q.**

---

## Ishga tushirish — 2 qadam

### 1-qadam. Ramazon sanasini kiriting (tavsiya etiladi)

Admin panelda «Ramazon 1-kuni» va «Necha kun (29 yoki 30)» maydonlari bor.

Bo'sh qoldirsangiz ham ishlaydi — kod hijriy taqvimdan o'zi hisoblaydi.
Lekin kodning o'z izohida yozilgan:

> *hijriy sana (jadvalli hisob, **±1 kun**)*

Ramazonda bir kun xato — jiddiy. Shuning uchun rasmiy e'lon qilinganda
aniq sanani qo'lda kiriting.

### 2-qadam. Boshqa hech narsa qilish shart emas

`rz_run()` har daqiqa ishlaydi va o'zi tekshiradi:

```php
$rg = rz_range($today);
if ($today < $rg[0] || $today > $rg[1]) return 0;   // Ramazon emas — hech narsa qilmaydi
```

Ramazon boshlangan kuni eslatmalar o'z-o'zidan keta boshlaydi.
Hozir hech narsa yubormaydi — chunki Ramazon emas.

---

## Namoz vaqtlari kodini o'chirmaganim shuning uchun to'g'ri bo'ldi

Ramazon taqvimi namoz kodiga **to'g'ridan-to'g'ri tayanadi**:

- `rz_data()` → `pr_get()` — saharlik = **bomdod**, iftor = **shom**
- `rz_run()` → `pr_get()` — eslatma vaqtini hisoblash uchun
- `rz_region()` → `$PR_REG` — hududlar ro'yxati

Agar `pr_*` funksiyalarini o'chirganimda, Ramazon taqvimi butunlay ishlamay
qolardi.

### Vaqtlar qayerdan olinadi (muhim nuqta)

Men cron'dan namoz vazifalarini olib tashladim. Demak islom.uz dan
avtomatik yuklash endi yo'q. Lekin `pr_get()` ichida **zaxira yo'l** bor:

```php
if (!is_array($r) && (!$row || !$row['times'] || $calc) && ($c = pr_calc($region, $ymd))) {
    // vaqtlarni botning o'zi hisoblaydi va keshga yozadi
}
```

Ya'ni API bo'lmasa, bot vaqtlarni **o'zi hisoblaydi**. Ramazon eslatmalari
baribir ishlaydi — faqat vaqtlar islom.uz rasmiy jadvalidan emas,
hisoblangan bo'ladi (farq odatda 1–2 daqiqa).

**Agar rasmiy vaqtlarni xohlasangiz** — ayting, cron'ga kichik bir qator
qo'shaman: u faqat Ramazon davrida ishlaydi va kunda bir marta islom.uz dan
vaqtlarni yangilaydi. Qolgan vaqtda umuman tegmaydi.

---

## Kanalga joylash qismini yozish kerakmi?

Agar «Tez kunda» ni ham yopmoqchi bo'lsangiz, kerak bo'ladi:

1. `chats` jadvaliga ustun — masalan `rz_on TINYINT DEFAULT 0`
2. Chat sozlamalaridagi bo'sh `div` o'rniga haqiqiy kalit (boshqa qatorlar
   kabi: `['rz','rz_on', ...]`)
3. Cron vazifasi — kuniga bir marta kanalga taqvim posti yuborish
4. Post rasmi — `png_prayer()` ga o'xshash generator (saharlik/iftor jadvali)

Taxminiy hajm: o'rtacha. Mavjud `rz_data()` hamma ma'lumotni tayyor beradi,
asosiy ish — rasm chizish va yuborish.

Xohlasangiz, buni ham qilib beraman — ayting.
