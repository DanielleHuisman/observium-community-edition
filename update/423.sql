ALTER TABLE `ipv4_addresses` ADD `ipv4_type` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `ipv4_prefixlen`;
ALTER TABLE `ipv4_addresses` ADD `vrf_id` INT NULL DEFAULT NULL AFTER `ipv4_network_id`;
ALTER TABLE `ipv4_addresses` CHANGE `ipv4_address` `ipv4_address` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `ipv6_addresses` ADD `ipv6_type` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `ipv6_prefixlen`;
ALTER TABLE `ipv6_addresses` ADD `vrf_id` INT NULL DEFAULT NULL AFTER `ipv6_network_id`;
ALTER TABLE `ipv6_addresses` CHANGE `ipv6_address` `ipv6_address` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, CHANGE `ipv6_compressed` `ipv6_compressed` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, CHANGE `ipv6_origin` `ipv6_origin` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL;
