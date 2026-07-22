INSERT INTO `stores` (`code`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
('S040', 'Mollyfantasy Aeon Mall Deltamas', 1, NOW(), NOW()),
('S044', 'MollyFantasy Living World', 1, NOW(), NOW()),
('S050', 'Mollyfantasy Cihampelas Walk', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `is_active` = 1, `updated_at` = NOW();

SET @default_store_id = (SELECT `id` FROM `stores` WHERE `code` = 'S040' LIMIT 1);
UPDATE `items` SET `store_id` = @default_store_id WHERE `store_id` IS NULL;
UPDATE `users` SET `store_id` = @default_store_id WHERE `store_id` IS NULL;
UPDATE `redeem_transactions` SET `store_id` = @default_store_id WHERE `store_id` IS NULL;
UPDATE `stock_opnames` SET `store_id` = @default_store_id WHERE `store_id` IS NULL;

ALTER TABLE `items`
  DROP INDEX `items_barcode_unique`,
  ADD UNIQUE INDEX `items_store_barcode_unique` (`store_id`, `barcode`),
  ADD INDEX `items_store_active_index` (`store_id`, `is_active`);

ALTER TABLE `item_stock_movements`
  ADD COLUMN `store_id` BIGINT UNSIGNED NULL AFTER `item_id`,
  ADD INDEX `stock_movements_store_date_index` (`store_id`, `created_at`),
  ADD CONSTRAINT `item_stock_movements_store_id_foreign` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE SET NULL;

UPDATE `item_stock_movements` AS `m`
JOIN `items` AS `i` ON `i`.`id` = `m`.`item_id`
SET `m`.`store_id` = `i`.`store_id`
WHERE `m`.`store_id` IS NULL;
