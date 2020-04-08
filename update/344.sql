UPDATE `status` SET `status_event` = 'ignore' WHERE `status_event` = 'down';
ALTER TABLE `status` CHANGE `status_event` `status_event` ENUM('ok', 'warning', 'alert', 'ignore') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ignore';
ALTER TABLE `status` ADD INDEX `status_oid` (`status_oid`);
ALTER TABLE `sensors` ADD INDEX `sensor_oid` (`sensor_oid`);
