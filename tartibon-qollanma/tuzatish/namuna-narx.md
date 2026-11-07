# Tuzatish: post rasmida «Namuna narx» yozuvi qolib ketadi

## Muammo

`adpDrawCard()` (Savdogar — mahsulot posti generatori) funksiyasida narx tagiga
**«Namuna narx»** yozuvi **shartsiz** chiziladi.

`adpFinal()` ham aynan shu funksiyani chaqiradi (`pv = false`), shuning uchun
yozuv **foydalanuvchi kanalga yuboradigan yakuniy rasmda ham** qoladi — garchi
narx haqiqiy kiritilgan bo'lsa ham.

Natijada post ustida «189 000 so'm», pastida esa «Namuna narx» turadi —
xaridorni chalg'itadi.

## Qayerda

`index.php` → `function adpDrawCard(ctx,W,H,img,title,desc,price,color,pan,pv)`,
`if(pp){ ... }` blokining ichidagi oxirgi qator.

## Hozirgi kod

```js
 if(pp){
  y+=18;txpFit(ctx,pp,124,'900',tw);ctx.fillStyle=pe?GRAY:color;ctx.fillText(pp,tx-4,y);y+=118;
  ctx.fillStyle=pe?GRAY:'#8A94A6';ctx.font='500 28px'+F;ctx.fillText('Namuna narx',tx,y+8);
 }
```

## Tuzatilgan kod

Faqat **bitta qator** o'zgaradi — oxirgi qator:

```js
 if(pp){
  y+=18;txpFit(ctx,pp,124,'900',tw);ctx.fillStyle=pe?GRAY:color;ctx.fillText(pp,tx-4,y);y+=118;
  if(pv&&pe){ctx.fillStyle=GRAY;ctx.font='500 28px'+F;ctx.fillText('Namuna narx',tx,y+8);}
 }
```

## Nima o'zgaradi

| Holat | Oldin | Keyin |
|---|---|---|
| Ilovada, narx hali kiritilmagan (`pv=1`, `pe=1`) | «Namuna narx» ko'rinadi | **O'zgarishsiz** — ko'rinadi (izoh sifatida kerak) |
| Ilovada, narx kiritilgan (`pv=1`, `pe=0`) | «Namuna narx» ko'rinadi | Yo'qoladi |
| Yakuniy rasm — kanalga yuboriladigan (`pv=0`) | «Namuna narx» ko'rinadi | **Yo'qoladi** |

## Joylashuv o'zgaradimi?

Yo'q. `y` o'zgaruvchisi `y+=118` dan keyin boshqa oshirilmaydi, pechat
(`adpStamp`) holati ham `y+22+27` bo'yicha hisoblanadi — ya'ni faqat matn
chizilmaydi, qolgan hamma narsa joyida qoladi.

`blockH` hisobidagi `46` ham o'zgarishsiz qoldirilgan: shunda narx tagida
pechatgacha bo'lgan bo'sh joy saqlanib qoladi va post siqilib qolmaydi.

## Qanday qo'llash

1. `index.php` ni oching.
2. `Namuna narx` matnini qidiring (faylda bitta joyda uchraydi).
3. O'sha qatorni yuqoridagi tuzatilgan variantga almashtiring.
4. Saqlang — boshqa hech narsa o'zgartirish shart emas.
