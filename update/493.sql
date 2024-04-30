ALTER TABLE `ospf_ports` ADD `ospfVersionNumber` ENUM('version2','version3') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'version2' AFTER `port_id`;
UPDATE `ospf_ports` SET `ospfVersionNumber` = 'version3' WHERE `ospf_port_id` REGEXP '^[[:digit:]]+\.[[:digit:]]+$';
ALTER TABLE `ospf_ports` DROP INDEX `device_id`;
ALTER TABLE `ospf_ports` ADD UNIQUE `device_ports` (`device_id`, `ospfVersionNumber`, `ospf_port_id`);
ALTER TABLE `ospf_nbrs` ADD `ospfVersionNumber` ENUM('version2','version3') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'version2' AFTER `port_id`;
UPDATE `ospf_nbrs` SET `ospfVersionNumber` = 'version3' WHERE `ospf_nbr_id` REGEXP '^[[:digit:]]+(\.[[:digit:]]+){2}$';
ALTER TABLE `ospf_nbrs` DROP INDEX `device_id`;
ALTER TABLE `ospf_nbrs` ADD UNIQUE `device_nbrs` (`device_id`, `ospfVersionNumber`, `ospf_nbr_id`);
