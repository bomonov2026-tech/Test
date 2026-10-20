# TG Menejer

Telegram Mini App panel: akkauntingiz nomidan yopiq kanal/superguruh ochadi va
belgilangan foydalanuvchini admin qiladi (`panel/index.php`).

## O'rnatish (hosting, PHP 8.2+ 64-bit)

```
/home/USER/tgm_app/                   <- vendor/ va data/
/home/USER/public_html/panel/index.php
```

1. `mkdir -p ~/tgm_app && cd ~/tgm_app && composer require danog/madelineproto:^8`
2. `panel/index.php` ni `public_html/panel/` ga qo'ying va tepadagi `$CFG` blokini to'ldiring
   (bot token — @BotFather, `api_id`/`api_hash` — my.telegram.org, `owner_telegram_id` — @userinfobot).
3. @BotFather → Bot Settings → Menu Button → `https://saytingiz/panel/index.php`
4. Botda menyu tugmasini bosing, telefon raqam → kod → (2FA) kiriting.
5. Avto rejim uchun cron (har daqiqa): `php /home/USER/public_html/panel/index.php cron`

Kerakli PHP kengaytmalari: json, xml, dom, mbstring, openssl, fileinfo, pdo_sqlite
(gmp tavsiya etiladi); `proc_open` o'chirilmagan bo'lishi kerak.

`bot.php` — muqobil oddiy variant: Bot API buyruqlari orqali (`/kanal Nomi | @user`).
