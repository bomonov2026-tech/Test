-- =====================================================================
--  Boshlang'ich ma'lumotlar (demo). schema.sql dan keyin bir marta import qiling.
--  Admin login seed.sql da YARATILMAYDI — uni xavfsiz tarzda bu buyruq bilan yarating:
--      php tools/create_admin.php
-- =====================================================================
SET NAMES utf8mb4;

-- Sozlamalar (admin paneldan o'zgartiriladi)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
 ('restaurant_name', 'Dasturxon'),
 ('restaurant_slogan', 'Milliy taomlar — uyingizgacha'),
 ('restaurant_phone', '+998901234567'),
 ('restaurant_address', 'Toshkent sh., Chilonzor tumani, 12-uy'),
 ('restaurant_about', 'Dasturxon — an’anaviy o‘zbek taomlari, shashlik va somsa. Har kuni yangi tayyorlanadi.'),
 ('contact_telegram', '@dasturxon_support'),
 ('logo', ''),
 ('working_open', '09:00'),
 ('working_close', '23:00'),
 ('days_off', ''),
 ('order_enabled', '1'),
 ('delivery_enabled', '1'),
 ('pickup_enabled', '1'),
 ('delivery_fee', '15000'),
 ('min_order_amount', '50000'),
 ('currency', 'so‘m'),
 ('admin_chat_ids', ''),
 ('bot_token', ''),
 ('order_counter', '1000');

-- Kategoriyalar
INSERT IGNORE INTO categories (slug, name, sort_order, is_active) VALUES
 ('shashlik', 'Shashlik', 1, 1),
 ('somsa', 'Somsa', 2, 1),
 ('kabob', 'Kabob', 3, 1),
 ('milliy-taomlar', 'Milliy taomlar', 4, 1),
 ('osh', 'Osh', 5, 1),
 ('lagmon', 'Lag‘mon', 6, 1),
 ('salatlar', 'Salatlar', 7, 1),
 ('ichimliklar', 'Ichimliklar', 8, 1),
 ('shirinliklar', 'Shirinliklar', 9, 1);

-- Mahsulotlar
INSERT IGNORE INTO products (category_id, name, description, price, unit, is_featured, is_recommended, is_new, is_combo, sort_order, discount_percent)
SELECT c.id, x.name, x.descr, x.price, x.unit, x.feat, x.rec, x.nw, x.combo, x.so, x.disc FROM (
  SELECT 'shashlik' cat, 'Qo‘y go‘shtidan shashlik' name, 'Yosh qo‘y go‘shti, maxsus marinadda 6 soat yetiltirilgan.' descr, 35000 price, '1 dona' unit, 1 feat, 1 rec, 0 nw, 0 combo, 1 so, 0 disc
  UNION ALL SELECT 'shashlik', 'Mol go‘shtidan shashlik', 'Yumshoq mol go‘shti, ko‘mir olovida pishirilgan.', 30000, '1 dona', 0, 0, 0, 0, 2, 0
  UNION ALL SELECT 'shashlik', 'Tovuq shashlik', 'Yog‘siz tovuq filesi, limon va ziravorlar bilan.', 22000, '1 dona', 0, 0, 1, 0, 3, 10
  UNION ALL SELECT 'somsa', 'Go‘shtli somsa', 'Tandirda pishirilgan, qo‘y go‘shti va piyoz bilan.', 7500, 'dona', 1, 1, 0, 0, 1, 0
  UNION ALL SELECT 'somsa', 'Qo‘ziqorinli somsa', 'Yangi qo‘ziqorin va ko‘k piyoz.', 6500, 'dona', 0, 0, 1, 0, 2, 0
  UNION ALL SELECT 'somsa', 'Kartoshkali somsa', 'Yengil va to‘yimli vegetarian variant.', 6000, 'dona', 0, 0, 0, 0, 3, 0
  UNION ALL SELECT 'kabob', 'Lula kabob', 'Qo‘y go‘shtidan, ziravorli qiymali kabob.', 28000, '1 porsiya', 0, 1, 0, 0, 1, 0
  UNION ALL SELECT 'kabob', 'Tovuq kabob', 'Tovuq go‘shti va sabzavotlar bilan.', 24000, '1 porsiya', 0, 0, 0, 0, 2, 0
  UNION ALL SELECT 'milliy-taomlar', 'Manti (6 dona)', 'Qo‘y go‘shti va kartoshkali manti, qatiq bilan.', 42000, 'porsiya', 1, 0, 0, 0, 1, 0
  UNION ALL SELECT 'milliy-taomlar', 'Chuchvara', 'Qaynoq sho‘rva bilan, yengil va mazali.', 38000, 'porsiya', 0, 1, 0, 0, 2, 0
  UNION ALL SELECT 'milliy-taomlar', 'Dimlama', 'Sabzavotlar va go‘sht, past olovda dimlangan.', 55000, 'porsiya', 0, 1, 0, 0, 3, 0
  UNION ALL SELECT 'osh', 'Toshkent oshi', 'Mazali guruch, go‘sht, sabzi va nohat bilan.', 45000, 'porsiya', 1, 0, 0, 0, 1, 0
  UNION ALL SELECT 'osh', 'Osh + salat + choy (kombo)', 'Toshkent oshi, achichuk salati va ko‘k choy.', 60000, 'to‘plam', 0, 1, 0, 1, 2, 5
  UNION ALL SELECT 'lagmon', 'Qo‘lda cho‘zilgan lag‘mon', 'Qo‘lda cho‘zilgan xamir, sabzavotli qovurdoq.', 40000, 'porsiya', 1, 0, 0, 0, 1, 0
  UNION ALL SELECT 'salatlar', 'Achichuk', 'Pomidor, piyoz va ko‘kat, o‘simlik moyi bilan.', 15000, 'porsiya', 0, 0, 0, 0, 1, 0
  UNION ALL SELECT 'salatlar', 'Sezar salat', 'Tovuq, parmesan va maxsus sous bilan.', 32000, 'porsiya', 0, 0, 1, 0, 2, 0
  UNION ALL SELECT 'ichimliklar', 'Ayron', 'Sovuq, yangi tayyorlangan ayron.', 8000, 'shisha', 0, 0, 0, 0, 1, 0
  UNION ALL SELECT 'ichimliklar', 'Coca-Cola 0.5 l', 'Sovutilgan ichimlik.', 10000, 'dona', 0, 0, 0, 0, 2, 0
  UNION ALL SELECT 'ichimliklar', 'Ko‘k choy', 'Choynak, yangi damlangan.', 12000, 'choynak', 0, 0, 0, 0, 3, 0
  UNION ALL SELECT 'ichimliklar', 'Mevali sharbat', 'Mavsumiy mevalardan tayyorlangan.', 15000, 'stakan', 0, 0, 1, 0, 4, 0
  UNION ALL SELECT 'shirinliklar', 'Pahlava', 'Yong‘oqli, asal bilan.', 25000, '100 g', 1, 0, 0, 0, 1, 0
  UNION ALL SELECT 'shirinliklar', 'Chak-chak', 'An’anaviy o‘zbek shirinligi.', 30000, '500 g', 0, 1, 0, 0, 2, 0
) x JOIN categories c ON c.slug = x.cat;

-- Qo'shimchalar: Qo'y shashlik uchun
INSERT INTO product_options (product_id, name, option_type, is_required, sort_order)
SELECT p.id, 'Qo‘shimchalar', 'multiple', 0, 1 FROM products p WHERE p.name = 'Qo‘y go‘shtidan shashlik';
INSERT INTO product_option_values (option_id, name, price, sort_order)
SELECT o.id, v.name, v.price, v.so FROM product_options o
JOIN products p ON p.id = o.product_id AND p.name = 'Qo‘y go‘shtidan shashlik' AND o.name = 'Qo‘shimchalar'
JOIN (SELECT 'Piyoz' name, 0 price, 1 so UNION ALL SELECT 'Achchiq sous', 2000, 2 UNION ALL SELECT 'Non', 3000, 3) v;

-- Qo'shimchalar: Lula kabob uchun
INSERT INTO product_options (product_id, name, option_type, is_required, sort_order)
SELECT p.id, 'Qo‘shimchalar', 'multiple', 0, 1 FROM products p WHERE p.name = 'Lula kabob';
INSERT INTO product_option_values (option_id, name, price, sort_order)
SELECT o.id, v.name, v.price, v.so FROM product_options o
JOIN products p ON p.id = o.product_id AND p.name = 'Lula kabob'
JOIN (SELECT 'Piyoz' name, 0 price, 1 so UNION ALL SELECT 'Non', 3000, 2 UNION ALL SELECT 'Achchiq sous', 2000, 3) v;

-- Qo'shimchalar: Ko'k choy uchun (majburiy tanlov)
INSERT INTO product_options (product_id, name, option_type, is_required, sort_order)
SELECT p.id, 'Shakar', 'single', 1, 1 FROM products p WHERE p.name = 'Ko‘k choy';
INSERT INTO product_option_values (option_id, name, price, sort_order)
SELECT o.id, v.name, v.price, v.so FROM product_options o
JOIN products p ON p.id = o.product_id AND p.name = 'Ko‘k choy'
JOIN (SELECT 'Shakarli' name, 0 price, 1 so UNION ALL SELECT 'Shakarsiz', 0, 2 UNION ALL SELECT 'Asalli', 3000, 3) v;

-- Promo-kodlar
INSERT IGNORE INTO promo_codes (code, discount_type, discount_value, min_order, max_uses, is_active) VALUES
 ('WELCOME10', 'percent', 10, 50000, 1000, 1),
 ('FIX5000', 'fixed', 5000, 30000, NULL, 1);

-- Reklama bannerlari (rasm yuklanguncha matnli kartochka ko‘rinadi)
INSERT INTO banners (title, subtitle, product_id, sort_order, is_active)
SELECT 'Yangi: Tandir somsa', 'Har kuni yangi tayyorlanadi', p.id, 1, 1 FROM products p WHERE p.name = 'Go‘shtli somsa';
INSERT INTO banners (title, subtitle, product_id, sort_order, is_active)
SELECT 'Kombo: osh + salat + choy', 'Maxsus narx — 5% chegirma', p.id, 2, 1 FROM products p WHERE p.name = 'Osh + salat + choy (kombo)';
