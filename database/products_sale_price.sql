ALTER TABLE `products`
  ADD COLUMN `sale_price` decimal(10,2) DEFAULT NULL AFTER `price`;

UPDATE `products` SET `price` = 175.00, `sale_price` = 98.00 WHERE `name` = 'Organic Manuka Honey MGO100+ (500g)';
UPDATE `products` SET `price` = 18.50, `sale_price` = 14.80 WHERE `name` = 'Wild Caught Sabah Indian Mackerel (Ikan Kembung)';
UPDATE `products` SET `price` = 21.50, `sale_price` = 18.30 WHERE `name` = 'PappaRich 2 in 1 White Coffee';
UPDATE `products` SET `price` = 128.00, `sale_price` = 118.00 WHERE `name` = 'Seaweed Chicken Pure Essence (80ml x 6 packs)';
