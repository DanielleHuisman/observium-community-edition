ALTER TABLE `storage` ADD `storage_object` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `storage_mib`;
UPDATE `storage` SET `storage_object` = 'volEntry' WHERE `storage_mib` = 'NIMBLE-MIB';
UPDATE `storage` SET `storage_object` = 'nasArrayTable' WHERE `storage_mib` = 'BUFFALO-NAS-MIB';
UPDATE `storage` SET `storage_object` = 'ifsFilesystem' WHERE `storage_mib` = 'ISILON-MIB';
UPDATE `storage` SET `storage_object` = 'gpfsFileSystemPerfEntry' WHERE `storage_mib` = 'GPFS-MIB';
UPDATE `storage` SET `storage_object` = 'swStorageConfig' WHERE `storage_mib` = 'EMBEDDED-NGX-MIB';
