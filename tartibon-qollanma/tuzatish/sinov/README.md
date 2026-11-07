# Deep link sinovi

Havolalar haqiqatan to'g'ri bo'limni ochishini brauzerda tekshiradi.

`app.html` — `index.php` dan ajratib olingan HTML qismi. Unga soxta
`Telegram.WebApp` va soxta `fetch` qo'shilgan. Telegram'ning haqiqiy
`telegram-web-app.js` fayli to'siladi — aks holda u stubni almashtirib
yuboradi va sinov yolg'on natija beradi.

| Skript | Nima tekshiradi |
|---|---|
| `diff.js` | Havola natijasini **katakni bosish** natijasi bilan solishtiradi |
| `gate.js` | Yangi foydalanuvchi: telefon so'ralgandan keyin ham havola ishlashi |
| `show.js` | Tanlangan havolalar qaysi oynani ochishini ko'rsatadi |

## Nega `diff.js` shunday qurilgan

Birinchi sinovimda kutilgan natijani o'zim yozgan jadvaldan olgandim. Jadvalda
xato bor edi (`Kanallarim` → `go('ov')`, aslida `go('my','Kanal')`), shuning
uchun sinov o'tdi, lekin havola noto'g'ri oynani — «Ovoz berish» ni — ochardi.

Endi kutilgan natija ilovaning o'zidan olinadi: bosh sahifadagi katakning
`onclick` i bajariladi, natija yozib olinadi, keyin havola bilan taqqoslanadi.
Sahifadan tashqari `CW` (Kanal/Guruh) va `MK.p` (YouTube/Instagram) holati ham
tekshiriladi — ular bir xil sahifani turli ko'rinishda ochadi.

Natija (2026-11-07):

```
✓ katak bilan bir xil: 40/40
✓ Telefon so'ralgandan keyin ham havola ishladi
```

`app.html` ni qayta yasash: `index.php` dagi ikkinchi `<!doctype` dan oxirigacha
olib, `<?php echo json_encode(BOT_USERNAME); ?>` ni `"TartibOnBot"` ga
almashtiring va `<head>` dan keyin stubni qo'ying.
