ALTER TABLE `oids` ADD `oid_symbol` VARCHAR(8) NULL DEFAULT NULL AFTER `oid_unit`;
ALTER TABLE `oids_assoc` CHANGE `raw_value` `raw_value` BIGINT(16) NOT NULL;
