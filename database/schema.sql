CREATE DATABASE IF NOT EXISTS `beaconmart_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `beaconmart_db`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `user_role` varchar(20) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(30) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_stock` (
  `branch_stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 10,
  PRIMARY KEY (`branch_stock_id`),
  UNIQUE KEY `unique_branch_product` (`branch_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `branch_stock_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `branch_stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `branch_id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `opening_hours` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  PRIMARY KEY (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `factory_receipts` (
  `receipt_id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `supplier` varchar(150) NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `received_at` datetime NOT NULL,
  `received_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`receipt_id`),
  KEY `received_at` (`received_at`),
  KEY `factory_receipts_wh_fk` (`warehouse_id`),
  KEY `factory_receipts_product_fk` (`product_id`),
  CONSTRAINT `factory_receipts_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `factory_receipts_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geocode_cache` (
  `query` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`query`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `kind` enum('login','admin_code','password') NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `kind_email` (`kind`,`email`,`attempted_at`),
  KEY `kind_ip` (`kind`,`ip_address`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `audience` enum('Admin','Customer') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(30) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(500) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `ref_key` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `audience_user` (`audience`,`user_id`,`is_read`),
  KEY `ref_key` (`ref_key`),
  KEY `notifications_user_fk` (`user_id`),
  CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `changed_by` varchar(150) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_status_history_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_address` varchar(255) DEFAULT NULL,
  `delivery_type` enum('Delivery','Pickup') NOT NULL DEFAULT 'Delivery',
  `payment_method` enum('Stripe','COD','PayAtStore') NOT NULL DEFAULT 'Stripe',
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL,
  `slot_date` date DEFAULT NULL,
  `slot_time` varchar(20) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sst_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `branch_id` int(11) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Unpaid',
  `stripe_session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `branch_id` (`branch_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`review_id`),
  UNIQUE KEY `one_review_per_order_item` (`order_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `product_reviews_user_fk` (`user_id`),
  CONSTRAINT `product_reviews_order_fk` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `product_reviews_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `product_reviews_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `rating_range` CHECK (`rating` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sale_start` datetime DEFAULT NULL,
  `sale_end` datetime DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  UNIQUE KEY `one_alert` (`user_id`,`product_id`,`branch_id`),
  KEY `stock_alerts_product_fk` (`product_id`),
  KEY `stock_alerts_branch_fk` (`branch_id`),
  CONSTRAINT `stock_alerts_branch_fk` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `stock_alerts_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `stock_alerts_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `change_type` varchar(20) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `log_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `branch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `product_id` (`product_id`),
  KEY `branch_id` (`branch_id`),
  CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `stock_logs_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfers` (
  `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `from_branch_id` int(11) NOT NULL,
  `to_branch_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `transfer_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transfer_id`),
  KEY `product_id` (`product_id`),
  KEY `from_branch_id` (`from_branch_id`),
  KEY `to_branch_id` (`to_branch_id`),
  CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `postcode` varchar(10) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Customer') NOT NULL DEFAULT 'Customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_dispatches` (
  `dispatch_id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('In Transit','Received','Cancelled') NOT NULL DEFAULT 'In Transit',
  `dispatched_at` datetime NOT NULL,
  `expected_arrival` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `dispatched_by` varchar(150) DEFAULT NULL,
  `received_by` varchar(150) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`dispatch_id`),
  KEY `status` (`status`),
  KEY `warehouse_dispatches_wh_fk` (`warehouse_id`),
  KEY `warehouse_dispatches_branch_fk` (`branch_id`),
  KEY `warehouse_dispatches_product_fk` (`product_id`),
  CONSTRAINT `warehouse_dispatches_branch_fk` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `warehouse_dispatches_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `warehouse_dispatches_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouse_stock` (
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`warehouse_id`,`product_id`),
  KEY `warehouse_stock_product_fk` (`product_id`),
  CONSTRAINT `warehouse_stock_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `warehouse_stock_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `warehouse_stock_non_negative` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warehouses` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` (`branch_id`, `branch_name`, `address`, `phone`, `opening_hours`, `latitude`, `longitude`) VALUES (1,'Beacon Mart Klang','No. 8, Jalan Batu Unjur 1, Taman Bayu Perdana, 41200 Klang, Selangor','010-963 3128','Mon - Sat, 8:00am - 6:00pm',3.0169609,101.4358213);
INSERT INTO `branches` (`branch_id`, `branch_name`, `address`, `phone`, `opening_hours`, `latitude`, `longitude`) VALUES (2,'Beacon Mart Shah Alam','38A, Jalan Anggerik Vanilla W31/W, Kota Kemuning, 40460 Shah Alam, Selangor','016-553 8128','Mon - Sat, 8:00am - 6:00pm',3.0048977,101.5359803);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;

/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (1,'Organic Chicken Breast 500g','Beacon Chicken',15.90,NULL,NULL,NULL,NULL,'Lean, skinless chicken breast from Beacon Chicken. Versatile for grilling, stir-frying or meal prep.','2026-09-16 05:32:13',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (3,'Wild Caught Salmon 300g','Seafood',28.90,NULL,NULL,NULL,NULL,'Wild caught salmon portions, frozen to lock in freshness. Rich in omega-3 and easy to pan-sear or bake.','2026-09-16 06:05:10',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (7,'Brown Rice 1kg (Organic)','Dried Goods',9.50,NULL,NULL,NULL,NULL,'Organic whole-grain brown rice with a nutty flavour. A higher-fibre alternative to white rice.','2026-09-26 03:56:27',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (8,'Test Item','Test',1.00,NULL,NULL,NULL,NULL,NULL,'2026-10-13 06:27:45',0);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (9,'Test Item 2','test',2.00,NULL,NULL,NULL,NULL,NULL,'2026-10-13 11:27:24',0);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (10,'Seaweed Chicken Drumettes (10 pcs)','Beacon Chicken',10.00,NULL,NULL,NULL,'uploads/products/10_seed.webp','10 Beacon seaweed chicken drumettes per pack. Raised on a natural feed with seaweed and probiotics. Great for roasting, air frying or soups.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (11,'Seaweed Chicken Wingettes (10 pcs)','Beacon Chicken',10.00,NULL,NULL,NULL,'uploads/products/11_seed.webp','10 Beacon seaweed chicken wingettes per pack. Tender and juicy, perfect for BBQ, air frying or braising.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (12,'Marinated Seaweed Chicken Wingettes (5 pcs)','Ready to Cook',9.90,NULL,NULL,NULL,'uploads/products/12_seed.webp','Ready to cook: 5 seaweed chicken wingettes pre-marinated and frozen. Just air fry or pan fry for a quick meal.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (13,'Marinated Seaweed Chicken Breast (270g)','Ready to Cook',11.90,NULL,NULL,NULL,'uploads/products/13_seed.webp','Ready to cook: marinated seaweed chicken breast, frozen. Cook straight from frozen for an easy high-protein meal.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (14,'Seaweed Chicken Nuggets','Ready to Cook',11.80,NULL,NULL,NULL,'uploads/products/14_seed.webp','Ready to cook nuggets made with Beacon seaweed chicken. A healthier snack for kids, ready in minutes in the air fryer.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (15,'Chicken Cocktail Sausages (250g)','Ready to Cook',13.90,NULL,NULL,NULL,'uploads/products/15_seed.webp','Chicken cocktail sausages made with real Beacon chicken meat, with no added MSG or preservatives. Pre-cooked and frozen.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (16,'Sabah Deep Sea Flower Prawn 31/38 (750g)','Seafood',59.90,NULL,NULL,NULL,'uploads/products/16_seed.webp','Wild caught deep sea flower prawns from Sabah, size 31/38 per kg. Frozen soon after catch to keep them sweet and firm.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (17,'Sabah Deep Sea Tiger Prawn 9/12 (4 pcs)','Seafood',62.20,NULL,NULL,NULL,'uploads/products/17_seed.webp','Large wild caught Sabah tiger prawns, size 9/12 per kg, 4 pieces per pack. Ideal for grilling or special occasions.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (18,'Wild Caught Sabah Pink Prawn Meat (250g)','Seafood',21.90,NULL,NULL,NULL,'uploads/products/18_seed.webp','Peeled wild caught Sabah pink prawn meat, ready to use in fried rice, noodles, omelettes and soups.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (19,'Wild Caught Sabah Indian Mackerel (Ikan Kembung)','Seafood',18.50,14.80,NULL,NULL,'uploads/products/19_seed.webp','Wild caught Sabah Indian mackerel (ikan kembung), cleaned and frozen. Great for frying, grilling or asam pedas.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (20,'Sabah Fish Belly (150g)','Seafood',5.00,NULL,NULL,NULL,'uploads/products/20_seed.webp','Wild caught Sabah fish belly, frozen. Tender and flavourful, perfect for steaming, frying or curry.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (21,'Organic Manuka Honey MGO100+ (500g)','Health & Wellness',175.00,98.00,NULL,NULL,'uploads/products/21_seed.webp','Certified organic New Zealand Manuka honey with MGO 100+. Enjoy a spoonful daily or add to warm drinks.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (22,'Seaweed Chicken Pure Essence (80ml x 6 packs)','Health & Wellness',128.00,118.00,NULL,NULL,'uploads/products/22_seed.webp','Pure chicken essence brewed from Beacon seaweed chicken, 6 x 80ml packs. Ready to drink, warm or chilled.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (23,'Angkor Bird Nest (70ml x 6 bottles)','Health & Wellness',348.00,299.00,NULL,NULL,'uploads/products/23_seed.webp','Ready to drink bird nest, 6 bottles of 70ml. A traditional tonic for daily nourishment.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (24,'PappaRich 2 in 1 White Coffee','Beverages',21.50,18.30,NULL,NULL,'uploads/products/24_seed.webp','Classic Malaysian white coffee in convenient 2 in 1 sachets, with no added sugar.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (25,'PappaRich Stevia White Coffee','Beverages',19.90,NULL,NULL,NULL,'uploads/products/25_seed.webp','Creamy white coffee sweetened with stevia instead of sugar, for a lower-sugar daily cup.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (26,'Organic Pure Cranberry Juice (946ml)','Beverages',69.00,NULL,NULL,NULL,'uploads/products/26_seed.webp','Organic pure cranberry juice, not from concentrate and with no added sugar. Dilute to taste.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (27,'Lakewood Pure Tart Cherry Juice (946ml)','Beverages',88.00,68.00,NULL,NULL,'uploads/products/27_seed.webp','Lakewood pure tart cherry juice, not from concentrate. Naturally tart and rich in antioxidants.','2026-10-15 05:43:35',1);
INSERT INTO `products` (`product_id`, `name`, `category`, `price`, `sale_price`, `sale_start`, `sale_end`, `image_url`, `description`, `created_at`, `is_active`) VALUES (28,'Organic Shoyu (Radiant)','Pantry',13.90,NULL,NULL,NULL,'uploads/products/28_seed.webp','Naturally brewed organic soy sauce. Adds rich umami flavour to stir-fries, marinades and dips.','2026-10-15 05:43:35',1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;

/*!40000 ALTER TABLE `branch_stock` DISABLE KEYS */;
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (1,1,1,74,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (2,2,1,77,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (3,1,3,29,5);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (4,2,3,29,5);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (14,1,7,40,15);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (15,2,7,45,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (16,1,8,5,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (17,2,8,5,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (18,1,9,5,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (19,2,9,5,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (20,1,10,45,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (21,1,11,40,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (22,1,12,30,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (23,1,13,27,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (24,1,14,35,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (25,1,15,3,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (26,1,16,12,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (27,1,17,8,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (28,1,18,20,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (29,1,19,25,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (30,1,20,40,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (31,1,21,8,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (32,1,22,15,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (33,1,23,4,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (34,1,24,29,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (35,1,25,24,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (36,1,26,10,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (37,1,27,8,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (38,1,28,18,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (51,2,10,38,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (52,2,11,4,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (53,2,12,25,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (54,2,13,22,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (55,2,14,30,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (56,2,15,18,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (57,2,16,10,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (58,2,17,0,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (59,2,18,15,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (60,2,19,20,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (61,2,20,35,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (62,2,21,7,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (63,2,22,12,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (64,2,23,3,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (65,2,24,26,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (66,2,25,20,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (67,2,26,8,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (68,2,27,2,10);
INSERT INTO `branch_stock` (`branch_stock_id`, `branch_id`, `product_id`, `stock`, `low_stock_threshold`) VALUES (69,2,28,15,10);
/*!40000 ALTER TABLE `branch_stock` ENABLE KEYS */;

/*!40000 ALTER TABLE `warehouses` DISABLE KEYS */;
INSERT INTO `warehouses` (`warehouse_id`, `name`, `address`, `created_at`) VALUES (1,'Beacon Central Warehouse','Central distribution warehouse, Selangor','2026-10-20 09:19:20');
/*!40000 ALTER TABLE `warehouses` ENABLE KEYS */;

/*!40000 ALTER TABLE `warehouse_stock` DISABLE KEYS */;
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,1,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,3,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,7,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,8,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,9,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,10,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,11,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,12,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,13,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,14,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,15,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,16,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,17,0,'2026-10-20 09:23:21');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,18,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,19,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,20,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,21,0,'2026-10-20 09:23:21');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,22,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,23,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,24,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,25,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,26,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,27,0,'2026-10-20 09:19:20');
INSERT INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`, `updated_at`) VALUES (1,28,0,'2026-10-20 09:19:20');
/*!40000 ALTER TABLE `warehouse_stock` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

UPDATE `products` SET `image_url` = NULL;
