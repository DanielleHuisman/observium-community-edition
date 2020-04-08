ALTER TABLE `ipv4_addresses` CHANGE `port_id` `port_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `ipv4_addresses` ADD `ifIndex` INT NULL DEFAULT NULL AFTER `port_id`, ADD INDEX `ifIndex` (`ifIndex`);
UPDATE `ipv4_addresses` SET `ifIndex` = (SELECT `ports`.`ifIndex` FROM `ports` WHERE `ports`.`port_id` = `ipv4_addresses`.`port_id`);
ALTER TABLE `ipv6_addresses` CHANGE `port_id` `port_id` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `ipv6_addresses` ADD `ifIndex` INT NULL DEFAULT NULL AFTER `port_id`, ADD INDEX `ifIndex` (`ifIndex`);
UPDATE `ipv6_addresses` SET `ifIndex` = (SELECT `ports`.`ifIndex` FROM `ports` WHERE `ports`.`port_id` = `ipv6_addresses`.`port_id`);
ALTER TABLE `vlans` ADD `ifIndex` INT NULL DEFAULT NULL AFTER `device_id`, ADD INDEX `ifIndex` (`ifIndex`);
