#!/usr/bin/env bash
# Hetzner Cloud (Ubuntu 24.04) uchun bir martalik o'rnatish.
# Ishlatish:  sudo bash deploy/setup.sh panel.example.com admin@example.com
set -euo pipefail

DOMAIN="${1:?Domen kerak: sudo bash deploy/setup.sh panel.example.com email@example.com}"
EMAIL="${2:?LetsEncrypt uchun email kerak}"
SRC="$(cd "$(dirname "$0")/.." && pwd)"
DST=/var/www/tgm

[ "$(id -u)" = 0 ] || { echo "root sifatida ishga tushiring (sudo)"; exit 1; }

if ! swapon --show | grep -q .; then
  echo ">>> Swap 2G (kichik RAM'da composer qotib qolmasligi uchun)"
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo ">>> Paketlar"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
# Ubuntu versiyasidagi standart PHP (24.04 -> 8.3, 26.04 -> yangiroq) o'rnatiladi
apt-get install -y nginx certbot python3-certbot-nginx ufw unzip git curl \
  php-fpm php-cli php-sqlite3 php-mbstring php-xml php-curl php-gmp php-intl php-zip php-bcmath
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
echo "PHP versiyasi: $PHPV"
# MadelineProto uchun FFI (ixtiyoriy, tezlik uchun) — CLI da yoqiladi
echo "ffi.enable=true" > /etc/php/$PHPV/cli/conf.d/99-tgm.ini

if ! command -v composer >/dev/null; then
  echo ">>> Composer (Ubuntu paketidan)"
  apt-get install -y composer
fi

echo ">>> Fayllar -> $DST"
mkdir -p "$DST/public" "$DST/app/data/php_sessions"
install -m 644 "$SRC/public/index.php" "$DST/public/index.php"
install -m 644 "$SRC/app/composer.json" "$DST/app/composer.json"
install -m 644 "$SRC/app/config.example.php" "$DST/app/config.example.php"
if [ ! -f "$DST/app/config.php" ]; then
  cp "$DST/app/config.example.php" "$DST/app/config.php"
  CRON_KEY="$(head -c 24 /dev/urandom | base64 | tr -dc 'A-Za-z0-9' | head -c 32)"
  sed -i "s/'cron_key'          => ''/'cron_key'          => '$CRON_KEY'/" "$DST/app/config.php"
  NEED_CFG=1
fi

echo ">>> MadelineProto"
chown -R www-data:www-data "$DST/app"
if [ -f "$SRC/deploy/vendor.tar.xz" ]; then
  # Tayyor vendor (ba'zi hostinglarda packagist.org ochilmaydi)
  rm -rf "$DST/app/vendor"
  tar -xJf "$SRC/deploy/vendor.tar.xz" -C "$DST/app"
else
  sudo -u www-data -H composer install -d "$DST/app" --no-dev --no-interaction --prefer-dist
fi
php -r 'require "'"$DST"'/app/vendor/autoload.php"; exit(class_exists(danog\MadelineProto\API::class) ? 0 : 1);' \
  && echo "MadelineProto: OK" || { echo "MadelineProto yuklanmadi"; exit 1; }

chown -R root:www-data "$DST/public"
chown -R www-data:www-data "$DST/app"
chmod 750 "$DST/app"; chmod 700 "$DST/app/data"
chmod 640 "$DST/app/config.php"; chown root:www-data "$DST/app/config.php"

echo ">>> PHP-FPM pool"
install -m 644 "$SRC/deploy/php-fpm-tgm.conf" /etc/php/$PHPV/fpm/pool.d/tgm.conf
systemctl restart php$PHPV-fpm

echo ">>> nginx"
sed "s/DOMAIN/$DOMAIN/g" "$SRC/deploy/nginx.conf" > /etc/nginx/sites-available/tgm
ln -sf /etc/nginx/sites-available/tgm /etc/nginx/sites-enabled/tgm
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo ">>> Firewall"
ufw allow OpenSSH >/dev/null; ufw allow 'Nginx Full' >/dev/null; ufw --force enable >/dev/null

echo ">>> HTTPS (Let's Encrypt) — Mini App faqat https da ishlaydi"
certbot --nginx -d "$DOMAIN" -m "$EMAIL" --agree-tos --no-eff-email --redirect -n

echo ">>> Cron (har daqiqa, www-data nomidan — sessiya fayllari egasi bir xil bo'lishi shart)"
cat > /etc/cron.d/tgm <<CRON
SHELL=/bin/sh
TGM_APP_DIR=$DST/app
* * * * * www-data /usr/bin/php $DST/public/index.php cron >> $DST/app/data/cron.log 2>&1
CRON
chmod 644 /etc/cron.d/tgm

echo
echo "=============================================="
echo " TAYYOR:  https://$DOMAIN"
if [ "${NEED_CFG:-0}" = 1 ]; then
  echo " ENDI:    nano $DST/app/config.php   (bot_token, api_id, api_hash, owner_telegram_id)"
fi
echo " BotFather: /mybots -> Bot Settings -> Menu Button -> https://$DOMAIN/"
echo "=============================================="
