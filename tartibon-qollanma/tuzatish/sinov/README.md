# Deep link sinovi

Havolalar haqiqatan ishlashini brauzerda tekshiradigan skriptlar.

`app.html` — `index.php` dan ajratib olingan HTML qismi (faqat bitta PHP tegi
bor edi, u almashtirildi). Unga stub qo'shilgan: soxta `Telegram.WebApp` va
soxta `fetch`. Telegram'ning haqiqiy `telegram-web-app.js` fayli to'siladi —
aks holda u stubni almashtirib yuboradi.

| Skript | Nima tekshiradi |
|---|---|
| `run.js` | 41 ta bo'lim havolasi + 5 ta noto'g'ri qiymat |
| `gate.js` | Yangi foydalanuvchi: telefon so'ralgandan keyin ham havola ishlashi |

Natija (2026-11-07):

```
✓ to'g'ri ochildi: 46/46
✓ Telefon so'ralgandan keyin ham havola ishladi — Savdogar ochildi
```

`app.html` ni qayta yasash kerak bo'lsa: `index.php` dagi ikkinchi `<!doctype`
dan oxirigacha olib, `<?php echo json_encode(BOT_USERNAME); ?>` ni
`"TartibOnBot"` ga almashtiring va `<head>` dan keyin stubni qo'ying.
