ALTER TABLE `devices_mibs` DROP INDEX `mib`;
ALTER TABLE `devices_mibs` ADD INDEX `mib` (`mib`(64), `table_name`(64), `oid`(64));
