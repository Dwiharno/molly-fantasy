-- Jalankan sekali pada database produksi melalui phpMyAdmin sebelum deploy.
ALTER TABLE `redeem_transactions`
    ADD COLUMN `pos_number` TINYINT UNSIGNED NULL AFTER `redeem_type`,
    ADD INDEX `redeem_transactions_pos_number_index` (`pos_number`);
