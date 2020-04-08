ALTER TABLE `vlans_fdb` DROP INDEX `dev_vlan_mac_port`, ADD INDEX `fdb_cache` (`device_id`, `vlan_id`, `mac_address`, `port_id`) USING BTREE;
ALTER TABLE `vlans_fdb` ADD `fdb_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`fdb_id`);
