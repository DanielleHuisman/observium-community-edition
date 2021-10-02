ALTER TABLE `ports_stack` CHANGE `ifStackStatus` `ifStackStatus` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `ports_stack` ADD INDEX `cache_stack` (`device_id`, `port_id_high`, `port_id_low`, `ifStackStatus`); 
ALTER TABLE `ports_stack` DROP INDEX `device_id`;
