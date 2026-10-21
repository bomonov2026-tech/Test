#!/usr/bin/env bash
# Kodni yangilash: git pull dan keyin  sudo bash deploy/update.sh
set -euo pipefail
SRC="$(cd "$(dirname "$0")/.." && pwd)"
install -m 644 -o root -g www-data "$SRC/public/index.php" /var/www/tgm/public/index.php
php -l /var/www/tgm/public/index.php
systemctl reload "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"
echo "Yangilandi."
