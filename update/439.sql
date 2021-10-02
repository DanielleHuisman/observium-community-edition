-- NOTE update syslog alerts, may be long operation ~10-15min
ALTER TABLE `syslog_alerts` CHANGE `syslog_id` `syslog_id` BIGINT NOT NULL, CHANGE `message` `message` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, CHANGE `program` `program` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
