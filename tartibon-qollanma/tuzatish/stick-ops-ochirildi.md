# STICK OPS olib tashlandi

Foydalanuvchi so'rovi bilan o'yin ilovadan butunlay chiqarildi.

## Nima o'chirildi

| Qism | Holat |
|---|---|
| `?ops=client` iframe sahifasi (73 KB o'yin kodi) | O'chirildi |
| `ops_session` API | O'chirildi |
| `ops_session_response()` funksiyasi (chipta beruvchi) | O'chirildi |
| Inline `integration.js` va `integration.css` | O'chirildi |
| `p-ops` sahifasi | O'chirildi |
| Bosh sahifa va xizmatlardagi ikkita STICK OPS tugmasi | O'chirildi |
| Menyudagi «STICK OPS 5v5» yozuvi | O'chirildi |
| `SP_PAGES` dagi `ops` | O'chirildi |
| `?startapp=ops_XXXXXX` xona havolasi ishlovchisi | O'chirildi |
| `go()` ichidagi `opsMount`/`opsUnmount` ilgagi | O'chirildi |

Qidiruv natijasi: `ops_session`, `ops-entry`, `p-ops`, `opsMount`, `ops-frame`,
`STICK OPS`, `ops=client` — **birortasi qolmadi**.

## Tekshirildi

| Nima | Natija |
|---|---|
| `php -l` | Toza |
| Ilova brauzerda ochiladi | Ha, bosh sahifa, 44 ta katak |
| JS xatolari | Yo'q |
| OPS tugmasi / sahifasi | Yo'q |
| Eski 40 ta bo'lim havolasi | **40/40 ishlaydi** |
| Menyu qatori (`['Boshqalar',…`) | Sintaksis to'g'ri |
| Faylni paket bazasi bilan solishtirish | Farq **faqat** STICK OPS qatorlari |

Hajm: 2 127 071 → **2 044 511** bayt (82 KB kamaydi).

## Nima qoldi

- **Yugurish o'yini** (`O'yin` katagi, `s_gm`) — oldin yasagan, tegilmadi
- Premium va Stars, Ramazon, deep linklar, kunlik faollik xabari,
  yangi obunachi xabari, muammoli kanalni o'chirish — hammasi joyida

Stickman Arena ham yo'q (undan oldinroq kechilgan edi).

## Zaxiralar

Kerak bo'lsa qaytarib bo'ladi — scratchpad'da turibdi:
`index.php.bak-ops` (STICK OPS bilan), `index.php.bak-arena` (Arena bilan).
Node serveri arxivi ham foydalanuvchida bor.
