ALTER TABLE `users`
  ADD COLUMN `phone` varchar(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN `street` varchar(255) DEFAULT NULL AFTER `phone`,
  ADD COLUMN `city` varchar(100) DEFAULT NULL AFTER `street`,
  ADD COLUMN `state` varchar(50) DEFAULT NULL AFTER `city`,
  ADD COLUMN `postcode` varchar(10) DEFAULT NULL AFTER `state`;

ALTER TABLE `branches`
  ADD COLUMN `latitude` decimal(10,7) DEFAULT NULL AFTER `opening_hours`,
  ADD COLUMN `longitude` decimal(10,7) DEFAULT NULL AFTER `latitude`;

UPDATE `branches` SET `latitude` = 3.0169609, `longitude` = 101.4358213 WHERE `branch_id` = 1;
UPDATE `branches` SET `latitude` = 3.0048977, `longitude` = 101.5359803 WHERE `branch_id` = 2;

ALTER TABLE `orders`
  ADD COLUMN `delivery_type` enum('Delivery','Pickup') NOT NULL DEFAULT 'Delivery' AFTER `customer_address`,
  ADD COLUMN `payment_method` enum('Stripe','COD','PayAtStore') NOT NULL DEFAULT 'Stripe' AFTER `delivery_type`,
  ADD COLUMN `street` varchar(255) DEFAULT NULL AFTER `payment_method`,
  ADD COLUMN `city` varchar(100) DEFAULT NULL AFTER `street`,
  ADD COLUMN `state` varchar(50) DEFAULT NULL AFTER `city`,
  ADD COLUMN `postcode` varchar(10) DEFAULT NULL AFTER `state`,
  ADD COLUMN `distance_km` decimal(6,2) DEFAULT NULL AFTER `postcode`,
  ADD COLUMN `slot_date` date DEFAULT NULL AFTER `distance_km`,
  ADD COLUMN `slot_time` varchar(20) DEFAULT NULL AFTER `slot_date`,
  ADD COLUMN `sst_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_fee`,
  ADD COLUMN `stock_deducted` tinyint(1) NOT NULL DEFAULT 0 AFTER `sst_amount`;

UPDATE `orders` SET `stock_deducted` = 1 WHERE `payment_status` = 'Paid';

CREATE TABLE IF NOT EXISTS `geocode_cache` (
  `query` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
UPDATE `orders` SET `stock_deducted` = 0 WHERE `status` = 'Cancelled';
