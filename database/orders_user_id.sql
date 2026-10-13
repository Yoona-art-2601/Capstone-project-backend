ALTER TABLE `orders`
  ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `order_id`,
  ADD KEY `user_id` (`user_id`),
  ADD CONSTRAINT `orders_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
