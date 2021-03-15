ALTER TABLE `ipv4_addresses` ADD `ipv4_binary` VARBINARY(4) NULL DEFAULT NULL AFTER `ipv4_address`;
UPDATE `ipv4_addresses` SET `ipv4_binary` = UNHEX(HEX(INET_ATON(`ipv4_address`)));
ALTER TABLE `ipv4_addresses` ADD INDEX `ipv4_binary` (`device_id`, `ipv4_binary`);
ALTER TABLE `ipv6_addresses` ADD `ipv6_binary` VARBINARY(16) NULL DEFAULT NULL AFTER `ipv6_address`;
ALTER TABLE `ipv6_addresses` ADD INDEX `ipv6_binary` (`device_id`, `ipv6_binary`);
ALTER TABLE `ipv6_addresses` DROP INDEX `ipv6_address`, ADD INDEX `ipv6_address` (`ipv6_address`, `ipv6_compressed`);
-- ERROR_IGNORE
UPDATE `ipv6_addresses` SET `ipv6_binary` = UNHEX(HEX(INET6_ATON(`ipv6_address`)));
