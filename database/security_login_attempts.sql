CREATE TABLE IF NOT EXISTS `login_attempts` (
  `attempt_id` int(11) NOT NULL AUTO_INCREMENT,
  `kind` enum('login','admin_code','password') NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `kind_email` (`kind`, `email`, `attempted_at`),
  KEY `kind_ip` (`kind`, `ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
