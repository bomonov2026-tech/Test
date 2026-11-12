# TartibOn — Telegram Mini App

Telegram kanal va guruhlarni boshqarish ilovasi: avto post, guruh himoyasi,
namoz vaqtlari, dollar kursi, ob-havo, nakrutka, eSIM, Premium, Stars va boshqalar.

## Tuzilma

```
public/index.php            -> /var/www/tgm/public/index.php   (nginx root, yagona ochiq fayl)
app/config.example.php      -> /var/www/tgm/app/config.php     (maxfiy sozlamalar, gitga kirmaydi)
app/tgmenejer-legacy.php    -> eski MadelineProto paneli, endi serverga yuklanmaydi
deploy/setup.sh             -> bir martalik o'rnatish (nginx, php-fpm, HTTPS, cron, ufw)
deploy/update.sh            -> kodni yangilash
tartibon2/                  -> noldan qurilgan modulli versiya (alohida loyiha, pastga qarang)
```

## O'rnatish (Ubuntu 24.04)

1. Domen A-yozuvini serverning IPv4 manziliga yo'naltiring.
2. Serverga kiring:
   ```bash
   apt-get update && apt-get install -y git
   git clone <shu-repo> /opt/tgm && cd /opt/tgm
   sudo bash deploy/setup.sh panel.example.com siz@example.com
   ```
3. `public/index.php` boshidagi sozlamalarni to'ldiring (bot token, baza, admin id).
4. Brauzerda `https://panel.example.com/?setup=KALIT` ni oching — jadvallar va
   webhook o'rnatiladi.
5. BotFather: `/mybots` -> bot -> **Bot Settings -> Menu Button** ->
   `https://panel.example.com/`

## Yangilash

```bash
cd /opt/tgm && git pull && sudo bash deploy/update.sh
```

## Cron

Bitta qator, har daqiqada (setup.sh o'zi qo'yadi):

```
* * * * * www-data /usr/bin/php /var/www/tgm/public/index.php watch
```

Yoki HTTP orqali: `curl -s "https://panel.example.com/?cron=KALIT&job=watch"`

## Tekshirish

- Cron jurnali: `tail -f /var/www/tgm/app/data/cron.log`
- Qo'lda: `sudo -u www-data php /var/www/tgm/public/index.php watch`
- Xatolar: `journalctl -u php8.3-fpm -e`, `/var/log/nginx/error.log`

## Dizayn

Uch rang — uch ma'no:

| Rang | Ma'nosi |
|---|---|
| 🔵 ko'k | siz boshqarasiz (Kanallarim, Statistika, Testlar) |
| 🟢 yashil | ilova o'zi bajaradi — yoniq (Namoz, Avto Post, Himoya) |
| 🟣 binafsha | sotib olinadi (Nakrutka, eSIM, Premium, Stars) |

- **Asosiy** — kataklar. Yoqilgan xizmat katagi yashil + yashil nuqta.
  Tepada: *"5 ta xizmat ishlab turibdi · 19:00"*.
- **Xizmatlar** — Telegram sozlamalari uslubidagi ro'yxat, filtr bilan
  (`Hammasi · Yoniq · O'chiq`). Avtomatik xizmatlar **joyida**, sahifaga
  kirmasdan yoqiladi.

Holat `?api=to_state` dan keladi, yoqish `?api=to_set` orqali —
`chats` jadvalidagi `send_a / send_c / dp_on / wx_on / ev_on / rz_on / bt_on`
ustunlariga yoziladi (barcha tasdiqlangan kanal va guruhlarga birdaniga).

## tartibon2 — modulli versiya

`tartibon/index.php` (1.9 MB, bitta fayl) o'rniga noldan qurilgan toza tuzilma:
`public/` + `src/` + `sql/`, 38 KB. Dizayn tizimi bir xil, lekin xizmatlar
mantig'i hali ko'chirilmagan — batafsil: [`tartibon2/README.md`](tartibon2/README.md).
