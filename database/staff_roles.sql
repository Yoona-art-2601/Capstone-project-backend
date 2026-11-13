ALTER TABLE `users`
  MODIFY `role` enum('Admin','Staff','Customer') NOT NULL DEFAULT 'Customer',
  ADD COLUMN IF NOT EXISTS `branch_id` int(11) DEFAULT NULL AFTER `role`,
  ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `branch_id`;

ALTER TABLE `users`
  ADD FOREIGN KEY IF NOT EXISTS `users_branch_fk` (`branch_id`) REFERENCES `branches` (`branch_id`) ON DELETE SET NULL;
