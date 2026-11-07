# Bo'limlarga to'g'ridan-to'g'ri havolalar

Har bir qo'llanma video ostiga shu havolani qo'ysangiz, bosgan odam ilovaning
**aynan o'sha bo'limiga** tushadi — bosh sahifadan qidirib o'tirmaydi.

---

## Qanday ishlaydi

Telegram mini-ilovasida `?startapp=` parametri bor. Ilova ochilganda uni o'qiydi
va kerakli sahifaga o'tadi.

```
https://t.me/TartibOnBot?startapp=s_nk     →  Nakrutka bo'limi
https://t.me/TartibOnBot?startapp=s_gd     →  Guruh himoyasi
```

**Bonus:** bu havolalar admin hisobotida **manba** sifatida qayd etiladi
(`rp_src`). Ya'ni qaysi video qancha yangi foydalanuvchi olib kelganini
ko'rasiz — har bir yangi foydalanuvchi uchun birinchi havola saqlanadi.

---

## Havolalar

### Bosh sahifa

| Bo'lim | Havola | Video |
|---|---|---|
| Bosh sahifa | `https://t.me/TartibOnBot?startapp=s_home` | — |

### Adminlar uchun

| Bo'lim | Havola | Video |
|---|---|---|
| Kanallarim | `https://t.me/TartibOnBot?startapp=s_mykanal` | 2-dars |
| Guruhlarim | `https://t.me/TartibOnBot?startapp=s_myguruh` | 2-dars |
| Guruh himoyasi | `https://t.me/TartibOnBot?startapp=s_gd` | 2-dars |
| Rejalashtirilgan postlar | `https://t.me/TartibOnBot?startapp=s_po` | 2-dars |
| Taklif havolalari | `https://t.me/TartibOnBot?startapp=s_rf` | 2-dars |
| Statistika | `https://t.me/TartibOnBot?startapp=s_st` | 2-dars |
| Testlar | `https://t.me/TartibOnBot?startapp=s_psj` | 2-dars |
| Pullik kanallar | `https://t.me/TartibOnBot?startapp=s_pc` | 2-dars |
| Konkurs | `https://t.me/TartibOnBot?startapp=s_gw` | 2-dars |

### Kundalik xabarlar

| Bo'lim | Havola | Video |
|---|---|---|
| Dollar kursi | `https://t.me/TartibOnBot?startapp=s_pcb` | 3-dars |
| Ob-havo | `https://t.me/TartibOnBot?startapp=s_pwx` | 3-dars |
| Tongi xabar | `https://t.me/TartibOnBot?startapp=s_pdp` | 3-dars |
| Tungi xabar | `https://t.me/TartibOnBot?startapp=s_pev` | 3-dars |
| Bayram tabriklari | `https://t.me/TartibOnBot?startapp=s_bt` | 3-dars |
| Ramazon taqvimi | `https://t.me/TartibOnBot?startapp=s_rz` | — |
| Namoz vaqtlari | `https://t.me/TartibOnBot?startapp=s_pnm` | 42 |

### Xizmatlar

| Bo'lim | Havola | Video |
|---|---|---|
| Nakrutka | `https://t.me/TartibOnBot?startapp=s_nk` | 4-dars |
| eSIM | `https://t.me/TartibOnBot?startapp=s_vs` | 4-dars |
| Prava | `https://t.me/TartibOnBot?startapp=s_dv` | 4-dars |
| O'zaro reklama | `https://t.me/TartibOnBot?startapp=s_vz` | 4-dars |
| Kanal Savdo · Telegram | `https://t.me/TartibOnBot?startapp=s_sale` | 4-dars |
| Kanal Savdo · YouTube | `https://t.me/TartibOnBot?startapp=s_ksyt` | 4-dars |
| Kanal Savdo · Instagram | `https://t.me/TartibOnBot?startapp=s_ksig` | 4-dars |
| Reklama birjasi | `https://t.me/TartibOnBot?startapp=s_auc` | 4-dars |
| Kanallar reytingi | `https://t.me/TartibOnBot?startapp=s_rt` | 4-dars |

### Reklama Post Tayorlash

| Bo'lim | Havola | Video |
|---|---|---|
| Savdogar | `https://t.me/TartibOnBot?startapp=s_adp` | 5-dars |
| Taksi e'loni | `https://t.me/TartibOnBot?startapp=s_txp` | 5-dars |
| Avto savdo | `https://t.me/TartibOnBot?startapp=s_avt` | 5-dars |
| Sayohat e'loni | `https://t.me/TartibOnBot?startapp=s_trv` | 5-dars |
| O'quv markazi | `https://t.me/TartibOnBot?startapp=s_edu` | 5-dars |
| Ko'chmas mulk | `https://t.me/TartibOnBot?startapp=s_rlt` | 5-dars |
| Vakansiya | `https://t.me/TartibOnBot?startapp=s_vak` | 5-dars |
| Restoran va kafe | `https://t.me/TartibOnBot?startapp=s_rst` | 5-dars |
| Salon va klinika | `https://t.me/TartibOnBot?startapp=s_sln` | 5-dars |
| Ochiq byudjet | `https://t.me/TartibOnBot?startapp=s_obd` | — |
| Statistikam | `https://t.me/TartibOnBot?startapp=s_ast` | 5-dars |
| Brend to'plami | `https://t.me/TartibOnBot?startapp=s_brand` | 5-dars |

### Ilova

| Bo'lim | Havola | Video |
|---|---|---|
| Balans | `https://t.me/TartibOnBot?startapp=s_bal` | 6-dars |
| Bot yaratish | `https://t.me/TartibOnBot?startapp=s_bty` | 6-dars |
| Savol va takliflar | `https://t.me/TartibOnBot?startapp=s_sup` | 6-dars |
| Ilova haqida | `https://t.me/TartibOnBot?startapp=s_ab` | 6-dars |
| Reklama berish | `https://t.me/TartibOnBot?startapp=s_ads` | — |

---

**Jami 42 ta havola.**

## Qo'shilgan kod

`stp()` funksiyasi kengaytirildi. Avval u faqat `don_<id>` ni tushunardi
(qo'llab-quvvatlash tugmasi). Endi `s_<bo'lim>` ni ham tushunadi.

```js
var SP_PAGES='home gd po rf st psj pc gw pcb pwx pdp pev bt rz pnm nk vs dv vz
 sale auc rt adp txp trv rlt avt vak edu rst sln obd ast brand bal bty ab ads'.split(' ');
var SP_FN={mykanal:…, myguruh:…, ksyt:…, ksig:…, sup:…};
```

**Xavfsizlik:** faqat ro'yxatdagi bo'limlar ochiladi. Boshqa qiymat yuborilsa,
ilova e'tiborsiz qoldiradi va bosh sahifada qoladi — ya'ni havola orqali
ixtiyoriy kod ishga tushirib bo'lmaydi.

**Eski havolalar ishlayveradi:** hamkor dasturi (`p<raqam>`) va qo'llab-quvvatlash
(`don_<id>`) havolalariga tegilmadi.

---

## Ishlatish bo'yicha maslahat

- **Telegram kanalida:** post tagiga inline tugma qo'ying — matn «Bo'limni ochish»,
  havola esa jadvaldan.
- **Instagramda:** havolani tavsifga qo'yib bo'lmaydi, shuning uchun bitta umumiy
  havolani profil shapkasiga qo'ying, videoda esa «profildagi havola» deng.
- **YouTube'da:** tavsifga to'g'ridan-to'g'ri qo'yiladi.

### Statistikani qanday ko'rasiz

Admin panel → hisobotlarda `src` ustuni bo'yicha. Masalan `s_nk` — Nakrutka
videosidan kelganlar. Shunda qaysi video ishlayotganini aniq bilasiz.
