ALTER TABLE `products`
  ADD COLUMN `sale_start` datetime DEFAULT NULL AFTER `sale_price`,
  ADD COLUMN `sale_end` datetime DEFAULT NULL AFTER `sale_start`;
