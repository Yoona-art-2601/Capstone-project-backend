ALTER TABLE `branches`
  ADD COLUMN `opening_hours` varchar(100) DEFAULT NULL AFTER `phone`;

UPDATE `branches` SET
  `address` = 'No. 8, Jalan Batu Unjur 1, Taman Bayu Perdana, 41200 Klang, Selangor',
  `phone` = '010-963 3128',
  `opening_hours` = 'Mon - Sat, 8:00am - 6:00pm'
WHERE `branch_id` = 1;

UPDATE `branches` SET
  `address` = '38A, Jalan Anggerik Vanilla W31/W, Kota Kemuning, 40460 Shah Alam, Selangor',
  `phone` = '016-553 8128',
  `opening_hours` = 'Mon - Sat, 8:00am - 6:00pm'
WHERE `branch_id` = 2;
