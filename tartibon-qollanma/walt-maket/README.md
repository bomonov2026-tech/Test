# TartibOn — Walt (Telegram Wallet) uslubidagi maket

Telegram'ning «Walt» mini-ilovasi dizayni TartibOn mazmuniga o'tkazilgan
uchta ekran. 1080×2340, chinakam iOS o'lchovlari bilan.

| Fayl | Ekran |
|---|---|
| `01-asosiy.png` | Bosh sahifa — tezkor tugmalar, balans, kanallar, xizmatlar |
| `02-xizmatlar.png` | Xizmatlar ro'yxati — avtomatik xabarlar, pul ishlash |
| `03-kanal.png` | Bitta kanal sahifasi — statistika va boshqaruv |

## Uslubning qoidalari

Walt dizayni aslida iOS'ning «grouped list» uslubi. Uchta narsa uni
taniqli qiladi:

1. **Har bir qator — alohida oq kartochka.** Ichida ajratuvchi chiziq yo'q,
   kartochkalar orasida 20 px bo'shliq. Fon — `#EFEFF4`.
2. **Bo'lim sarlavhasi kartochkadan tashqarida**, kulrang, kichik
   («Kanal va guruhlarim», «Xizmatlar», «Kashf qiling»).
3. **Chap tomonda rangli doira ichidagi oq belgi.** Rang bo'limni
   anglatadi: ko'k — kanal, yashil — pul, to'q sariq — reklama,
   binafsha — savdo, kulrang — hisobot.

Yuqorida: X tugmasi, markazda nom va ostida kichik izoh, o'ngda «...».
Pastda — suzib turuvchi oq panel, faol bo'lim ochiq kulrang kapsula ichida.

## TartibOn mazmuni qayerga tushdi

| Walt | TartibOn |
|---|---|
| O'tkazish / To'ldirish / Yechib olish / Almashtirish | Kanal / Guruh / Post / To'ldirish |
| Dollar · USDT balansi | Balans · so'm |
| Kripto (Oltin, DeFi) | Kanal va guruhlarim |
| Kashf qiling (Aksiyalar, Tarix) | Kashf qiling (SHASHKA, Qo'llanma) |
| Walt / Savdo / Daromad / Bonuslar | Asosiy / Kanallar / Xizmatlar / Bonuslar |

## Nimani bilib qo'yish kerak

Bu uslub **ko'proq joy oladi**: har bir qator alohida kartochka bo'lgani
uchun bitta ekranga 6–7 qatordan ortig'i sig'maydi. Hozirgi TartibOn bosh
sahifasida esa 40 dan ortiq bo'lim bor. Shuning uchun o'tkazilsa, bosh
sahifa qisqartirilib, qolgani «Xizmatlar» ichiga yig'ilishi kerak —
maketda aynan shunday qilingan.

Yaxshi tomoni: Telegram foydalanuvchisi bu uslubni allaqachon biladi,
o'rganish kerak emas.

## Qayta chizish

```bash
cd kod && node shot.js    # t1.png, t2.png, t3.png
```

`ui.js` — ranglar, kartochka va belgilar. `page.html` — uchta ekran mazmuni.
