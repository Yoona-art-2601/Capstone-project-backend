ALTER TABLE `products`
  ADD COLUMN `image_url` varchar(255) DEFAULT NULL AFTER `sale_price`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `image_url`;
