ALTER TABLE `orders`
  ADD COLUMN `customer_address` varchar(255) DEFAULT NULL AFTER `customer_phone`;
