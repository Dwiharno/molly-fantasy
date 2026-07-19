ALTER TABLE `redeem_transactions`
    ADD COLUMN `redeem_type` VARCHAR(20) NOT NULL DEFAULT 'pos' AFTER `transaction_code`,
    ADD COLUMN `member_phone` VARCHAR(25) NULL AFTER `redeem_type`,
    ADD COLUMN `offline_reference` CHAR(36) NULL AFTER `member_phone`,
    ADD INDEX `redeem_transactions_redeem_type_index` (`redeem_type`),
    ADD INDEX `redeem_transactions_member_phone_index` (`member_phone`),
    ADD UNIQUE INDEX `redeem_transactions_offline_reference_unique` (`offline_reference`);
