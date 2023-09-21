ALTER TABLE `ip_mac` ADD `device_id` INT NOT NULL AFTER `mac_id`;
UPDATE `ip_mac` SET `ip_mac`.`device_id` = (SELECT `ports`.`device_id` FROM `ports` WHERE `ports`.`port_id` = `ip_mac`.`port_id`);
ALTER TABLE `ip_mac` ADD `virtual_name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `port_id`;
ALTER TABLE `ip_mac` CHANGE `mac_address` `mac_address` CHAR(12) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, CHANGE `ip_address` `ip_address` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `ip_mac` CHANGE `port_id` `port_id` INT NULL DEFAULT NULL;
ALTER TABLE `ip_mac` ADD `mac_ifIndex` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `port_id`;
UPDATE `ip_mac` SET `ip_mac`.`mac_ifIndex` = (SELECT `ports`.`ifIndex` FROM `ports` WHERE `ports`.`port_id` = `ip_mac`.`port_id`);
ALTER TABLE `ip_mac` ADD INDEX `cache` (`device_id`, `port_id`);
