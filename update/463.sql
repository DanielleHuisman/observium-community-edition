ALTER TABLE `bgpPeers` ADD `virtual_name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `reverse_dns`;
