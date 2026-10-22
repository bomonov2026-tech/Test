# TG Menejer — Hetzner VPS

Telegram Mini App panel (MadelineProto), hacker uslubida.

## Tuzilma
```
public/index.php          -> /var/www/tgm/public/index.php  (nginx root, yagona ochiq fayl)
app/config.example.php    -> /var/www/tgm/app/config.php    (maxfiy sozlamalar, gitga kirmaydi)
app/splash.php            -> /var/www/tgm/app/splash.php    (TartibOn animatsion intro; ?p=home&intro=1 — qayta ko'rish)
app/composer.json         -> MadelineProto
deploy/setup.sh           -> bir martalik o'rnatish (nginx, php8.3-fpm, composer, HTTPS, cron, ufw)
deploy/update.sh          -> kodni yangilash
```

## O'rnatish (Hetzner Cloud, Ubuntu 24.04, CX22 yetarli)
1. Domen (yoki subdomen) A-yozuvini serverning IPv4 manziliga yo'naltiring.
2. Serverga kiring va:
   ```bash
   apt-get update && apt-get install -y git
   git clone <shu-repo> /opt/tgm && cd /opt/tgm
   sudo bash deploy/setup.sh panel.example.com siz@example.com
   ```
3. Sozlamalarni yozing:
   ```bash
   nano /var/www/tgm/app/config.php    # bot_token, api_id, api_hash, owner_telegram_id
   ```
4. BotFather: `/mybots` -> bot -> **Bot Settings -> Menu Button** -> `https://panel.example.com/`

## Yangilash
```bash
cd /opt/tgm && git pull && sudo bash deploy/update.sh
```

## Tekshirish
- Cron jurnali: `tail -f /var/www/tgm/app/data/cron.log`
- Qo'lda tick: `sudo -u www-data TGM_APP_DIR=/var/www/tgm/app php /var/www/tgm/public/index.php cron`
- Xatolar: `journalctl -u php8.3-fpm -e`, `/var/log/nginx/error.log`

> Cron va PHP-FPM ikkalasi ham **www-data** nomidan ishlaydi — MadelineProto sessiya fayllari egasi bir xil bo'lishi shart.
