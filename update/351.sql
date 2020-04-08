ALTER TABLE `oids` ADD `oid_autodiscover` BOOLEAN NOT NULL DEFAULT TRUE AFTER `oid_symbol`, ADD `oid_thresh_scheme` VARCHAR(16) NULL DEFAULT NULL AFTER `oid_autodiscover`, ADD `oid_alert_low` BIGINT NULL DEFAULT NULL AFTER `oid_thresh_scheme`, ADD `oid_warn_low` BIGINT NULL DEFAULT NULL AFTER `oid_alert_low`, ADD `oid_warn_high` BIGINT NULL DEFAULT NULL AFTER `oid_warn_low`, ADD `oid_alert_high` BIGINT NULL DEFAULT NULL AFTER `oid_warn_high`;
ALTER TABLE `oids_assoc` ADD `event` ENUM('ok','warn','alert','ignore') NOT NULL DEFAULT 'ignore' AFTER `raw_value`;
ALTER TABLE `oids_assoc` DROP `status`;
RENAME TABLE `oids_assoc` TO `oids_entries`;
ALTER TABLE `oids_entries` CHANGE `oid_assoc_id` `oid_entry_id` INT(11) NOT NULL AUTO_INCREMENT;
