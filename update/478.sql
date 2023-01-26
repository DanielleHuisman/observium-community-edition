ALTER TABLE `pollers` ADD `poller_version` VARCHAR(32) NULL DEFAULT NULL AFTER `host_uname`;
ALTER TABLE `pollers` ADD INDEX `host` (`host_id`);
