ALTER TABLE `entPhysical` ADD `inventory_mib` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL AFTER `device_id`;
DROP TABLE `entPhysical-state`;
