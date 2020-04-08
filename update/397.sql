-- Improve maximum index key size
ALTER TABLE `snmp_errors` CHANGE `snmp_cmd` `snmp_cmd` ENUM('snmpget','snmpwalk') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Latin charset for 1byte chars!';
ALTER TABLE `snmp_errors` CHANGE `snmp_options` `snmp_options` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL COMMENT 'Latin charset for 1byte chars!';
ALTER TABLE `snmp_errors` CHANGE `mib_dir` `mib_dir` VARCHAR(256) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL COMMENT 'Latin charset for 1byte chars!';
ALTER TABLE `snmp_errors` CHANGE `mib` `mib` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL COMMENT 'Latin charset for 1byte chars!';
ALTER TABLE `snmp_errors` CHANGE `oid` `oid` TEXT CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL COMMENT 'Latin charset for 1byte chars!';
ALTER TABLE `snmp_errors` DROP INDEX `error_index`, ADD UNIQUE `error_index` (`device_id`, `error_code`, `snmp_cmd`, `mib`(128), `oid`(512)) USING BTREE;
-- Add device foreign key (device_id type/size must be complete same)
ALTER TABLE `snmp_errors` CHANGE `device_id` `device_id` INT(11) NOT NULL;
-- SET FOREIGN_KEY_CHECKS=0;
DELETE FROM `snmp_errors` WHERE `device_id` NOT IN (SELECT `device_id` FROM `devices`);
ALTER TABLE `snmp_errors` ADD CONSTRAINT `snmp_devices` FOREIGN KEY (`device_id`) REFERENCES `devices`(`device_id`) ON DELETE CASCADE ON UPDATE NO ACTION;
-- SET FOREIGN_KEY_CHECKS=1;
