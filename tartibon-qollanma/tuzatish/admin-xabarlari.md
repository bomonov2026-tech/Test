# Adminga ikkita yangi xabar

## 1. Yangi obunachi qo'shilganda

Odam botga birinchi marta `/start` bosganda adminlarga xabar keladi — xuddi
kanal yoki guruh ulanganda kelgani kabi.

```
🆕 Yangi obunachi: Aziz Karimov (@aura_uzbe) · ID 6798131309
🔗 Manba: instagram
👥 Jami obunachi: 1058
```

**«Manba» qatori** — odam qaysi havoladan kelgani. Bu reklama kampaniyangiz
bilan bevosita bog'liq: Instagram bio'sida `?startapp=ig` tursa, shu yerda
`instagram` emas, `ig` ko'rinadi. YouTube uchun `yt`, bo'lim havolalari uchun
`s_gd`, `s_adp` va hokazo. Manba bo'lmasa, bu qator umuman chiqmaydi.

Faqat **birinchi marta** kelganda yuboriladi. Eski foydalanuvchi qayta
`/start` bossa, xabar ketmaydi — buning uchun `INSERT ... ON DUPLICATE KEY`
ning `rowCount() === 1` natijasi ishlatilgan (ilova bu usulni referal
dasturida allaqachon qo'llaydi).

## 2. Kun oxirida — faollar ro'yxati

Har kuni **23:30 da** (kunlik moliyaviy hisobot bilan birga, lekin alohida
xabar sifatida) shunday ro'yxat keladi:

```
👥 Kunlik faollik — 09.11.2026

🟢 Faol: 80 ta
🆕 Yangi qo'shilgan: 12 ta
📊 Jami obunachi: 1057

🆕 Dilnoza @dilnoza_uz · 21:40
• Akmal Qodirov · 20:15
🆕 ID 333 @shoh · 18:31
• Nodira Juraeva · 08:05
…
… va yana 20 ta
```

- 🆕 belgisi — bugun qo'shilgan yangi odam
- • belgisi — eskidan bor, bugun kirgan
- Oxirgi raqam — oxirgi marta kirgan vaqti
- Eng faol 60 tasi ko'rsatiladi (oxirgi kirgani bo'yicha), qolgani sanoq bilan

Nega 60 ta? 60 qatorda xabar **3 152 belgi** bo'ladi — Telegram chegarasi
4096 dan past. Ismlar uzun bo'lsa 31 belgida kesiladi.

Faollik `users.last_seen` ustuni bo'yicha hisoblanadi — bu ustun ilovada
allaqachon bor va har kirishda yangilanadi.

## O'chirish

Ikkalasi ham sozlama bilan boshqariladi, standart holatda **yoqilgan**:

| Sozlama | Nima | O'chirish |
|---|---|---|
| `nu_on` | Yangi obunachi xabari | `0` qiling |
| `ua_on` | Kunlik faollik ro'yxati | `0` qiling |

`settings` jadvalida:

```sql
INSERT INTO settings (k,v) VALUES ('nu_on','0')
  ON DUPLICATE KEY UPDATE v='0';
```

Vaqtni o'zgartirish: `rp_time` sozlamasi (standart `23:30`) — moliyaviy
hisobot bilan faollik ro'yxati shu vaqtda birga ketadi.

## Nima tekshirildi

- `php -l` toza
- `ua_text()` haqiqiy PHP da ishlatib ko'rildi: oddiy holat, 60 dan ko'p
  faol bo'lgan holat, hech kim kirmagan holat — uchalasi to'g'ri chiqdi
- Yangi obunachi xabari ikki holatda sinaldi: username va manba bor,
  hamda ikkalasi ham yo'q
- 60 qatorli xabar uzunligi o'lchandi: 3 152 belgi

## Nima tekshirilmadi

Haqiqiy bazada ishga tushirmadim — menda sizning MySQL'ingiz yo'q.
Birinchi kuni 23:30 da xabar kelishini kuzating. Kelmasa, `rp_on`
sozlamasi `1` ekanini tekshiring — u butun kunlik hisobotni boshqaradi.
