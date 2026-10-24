UPDATE `notifications`
SET `link` = CONCAT('inventory?branch=', SUBSTRING_INDEX(SUBSTRING_INDEX(`ref_key`, ':', 2), ':', -1))
WHERE `link` = 'inventory' AND `ref_key` LIKE 'low_stock:%';
