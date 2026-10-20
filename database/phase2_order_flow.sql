CREATE TABLE IF NOT EXISTS `order_status_history` (
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

INSERT INTO `order_status_history` (`order_id`, `status`, `note`, `changed_at`)
SELECT o.order_id, o.status, 'Recorded when order history was introduced', o.order_date
FROM `orders` o
WHERE NOT EXISTS (SELECT 1 FROM `order_status_history` h WHERE h.order_id = o.order_id);
