ALTER TABLE `observium_processes` ADD `process_ppid` INT NOT NULL AFTER `process_pid`;
ALTER TABLE `neighbours` CHANGE `remote_platform` `remote_platform` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
