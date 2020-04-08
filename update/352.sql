ALTER TABLE `oids_entries` ADD `deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `event`;
ALTER TABLE `oids_entries` ADD `alert_low` BIGINT NULL DEFAULT NULL AFTER `deleted`, ADD `warn_low` BIGINT NULL DEFAULT NULL AFTER `alert_low`, ADD `warn_high` BIGINT NULL DEFAULT NULL AFTER `warn_low`, ADD `alert_high` BIGINT NULL DEFAULT NULL AFTER `warn_high`;
