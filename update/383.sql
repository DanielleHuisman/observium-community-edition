ALTER TABLE `sensors` CHANGE `sensor_status` `sensor_status` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `sensors` CHANGE `sensor_event` `sensor_event` ENUM('ok','warning','alert','ignore') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ignore';
ALTER TABLE `sensors` CHANGE `sensor_last_change` `sensor_last_change` INT(11) NULL DEFAULT NULL;
ALTER TABLE `sensors` CHANGE `sensor_polled` `sensor_polled` INT(11) NULL DEFAULT NULL;
