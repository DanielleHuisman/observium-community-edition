ALTER TABLE `processors` ADD `processor_mib` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `device_id`, ADD `processor_object` VARCHAR(64) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL AFTER `processor_mib`;
ALTER TABLE `processors` CHANGE `processor_type` `processor_type` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
