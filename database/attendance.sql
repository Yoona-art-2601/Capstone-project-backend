ALTER TABLE `branches`
  ADD COLUMN IF NOT EXISTS `attendance_secret` varchar(64) DEFAULT NULL AFTER `longitude`;

UPDATE `branches` SET `attendance_secret` = SHA2(CONCAT(UUID(), RAND(), `branch_id`), 256) WHERE `attendance_secret` IS NULL;

CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `late_minutes` int(11) NOT NULL DEFAULT 0,
  `early_leave_minutes` int(11) NOT NULL DEFAULT 0,
  `clock_in_ip` varchar(45) DEFAULT NULL,
  `clock_out_ip` varchar(45) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `one_shift_per_day` (`user_id`, `work_date`),
  KEY `branch_date` (`branch_id`, `work_date`),
  CONSTRAINT `attendance_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_branch_fk` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `login_attempts`
  MODIFY `kind` enum('login','admin_code','password','attendance_code') NOT NULL;
