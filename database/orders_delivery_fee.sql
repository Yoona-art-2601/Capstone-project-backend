ALTER TABLE `orders`
  ADD COLUMN `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`;
