# Dasturxon — Telegram Mini App restoran tizimi

Restoran uchun onlayn buyurtma tizimi: mijoz Telegram Mini App orqali menyuni ko‘radi, savatga qo‘shadi,
buyurtma beradi va naqd pul bilan to‘laydi. Oshxona ekrani, admin panel va Telegram bot bildirishnomalari kiritilgan.

- **Backend:** PHP 8.2+ (framework yo‘q), PDO, MySQL 8.0 / MariaDB 10.4+
- **Frontend:** HTML5, CSS3, vanilla JavaScript, Telegram WebApp SDK, Fetch API, Lucide ikonkalari (o‘zimizda)
- **Hosting:** oddiy PHP hosting / cPanel, HTTPS, Cron Job

To‘liq o‘rnatish, bot sozlash, cron, admin hisobi va test qilish yo‘riqnomasi:
**[docs/QOLLANMA.md](docs/QOLLANMA.md)**

## Tez boshlash (qisqacha)

```bash
cp config/config.sample.php config/config.php   # so‘ng config.php ni to‘ldiring
# phpMyAdmin: database/schema.sql, keyin database/seed.sql
php tools/create_admin.php                       # admin/oshpaz hisobi
php tools/setup_telegram.php                     # webhook + menyu tugmasi
# cPanel Cron (har 2 daqiqada): php cron/tasks.php
```

Document Root: `restoran/public_html`. `config/`, `includes/`, `database/`, `cron/`, `tools/` web-ochiq emas.
