ALTER TABLE `ipv4_addresses` ADD `device_id` INT(11) NOT NULL AFTER `ipv4_address_id`;
ALTER TABLE `ipv4_addresses` ADD INDEX (`device_id`);
ALTER TABLE `ipv4_addresses` ADD INDEX `ipv4_cache` (`device_id`, `ipv4_address`);
UPDATE `ipv4_addresses` SET `ipv4_addresses`.`device_id` = (SELECT `ports`.`device_id` FROM `ports` WHERE `ports`.`port_id` = `ipv4_addresses`.`port_id`);
ALTER TABLE `ipv6_addresses` ADD `device_id` INT(11) NOT NULL AFTER `ipv6_address_id`;
ALTER TABLE `ipv6_addresses` ADD INDEX (`device_id`);
ALTER TABLE `ipv6_addresses` ADD INDEX `ipv6_cache` (`device_id`, `ipv6_address`);
UPDATE `ipv6_addresses` SET `ipv6_addresses`.`device_id` = (SELECT `ports`.`device_id` FROM `ports` WHERE `ports`.`port_id` = `ipv6_addresses`.`port_id`);
