ALTER TABLE `pollers` CHANGE `poller_version` `poller_version` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL, CHANGE `poller_stats` `poller_stats` TEXT CHARACTER SET latin1 COLLATE latin1_general_ci NULL DEFAULT NULL;
ALTER TABLE `pollers` ADD `sysName` VARCHAR(253) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `host_id`;
ALTER TABLE `pollers` ADD `timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `poller_stats`;
