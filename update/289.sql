CREATE TABLE `notifications_queue` ( `notification_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT , `aca_type` ENUM('alert','syslog','web') NOT NULL , `endpoints` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `endpoints_result` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , `message_tags` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , `message_graphs` BLOB NULL , `notification_added` INT NOT NULL , PRIMARY KEY (`notification_id`)) ENGINE = InnoDB;
ALTER TABLE `notifications_queue` ADD `device_id` INT NULL AFTER `notification_id` , ADD `severity` TINYINT NOT NULL DEFAULT '6' AFTER `aca_type`;
ALTER TABLE `notifications_queue` ADD `log_id` INT UNSIGNED NOT NULL AFTER `device_id`;
ALTER TABLE `notifications_queue` ADD `notification_lifetime` INT NOT NULL DEFAULT '300' AFTER `notification_added`;
ALTER TABLE `notifications_queue` ADD `notification_entry` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `notification_lifetime`;
ALTER TABLE `notifications_queue` ADD INDEX `aca_type` (`aca_type`);
ALTER TABLE `alert_log` ADD `notified` BOOLEAN NOT NULL DEFAULT FALSE AFTER `log_type`;
ALTER TABLE `syslog` CHANGE `program` `program` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL, ADD `host` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Hostname or IP received by syslog server' AFTER `device_id`;
