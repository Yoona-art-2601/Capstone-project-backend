CREATE TABLE IF NOT EXISTS `warehouses` (
  `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `warehouses` (`warehouse_id`, `name`, `address`)
SELECT 1, 'Beacon Central Warehouse', 'Central distribution warehouse, Selangor'
WHERE NOT EXISTS (SELECT 1 FROM `warehouses` WHERE `warehouse_id` = 1);

CREATE TABLE IF NOT EXISTS `warehouse_stock` (
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`warehouse_id`, `product_id`),
  CONSTRAINT `warehouse_stock_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `warehouse_stock_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `warehouse_stock_non_negative` CHECK (`stock` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `warehouse_stock` (`warehouse_id`, `product_id`, `stock`)
SELECT 1, `product_id`, 0 FROM `products`;

CREATE TABLE IF NOT EXISTS `factory_receipts` (
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
  CONSTRAINT `factory_receipts_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `factory_receipts_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `warehouse_dispatches` (
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
  CONSTRAINT `warehouse_dispatches_wh_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
  CONSTRAINT `warehouse_dispatches_branch_fk` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`),
  CONSTRAINT `warehouse_dispatches_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
