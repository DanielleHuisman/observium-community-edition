ALTER TABLE `probes` CHANGE `probe_cli` `probe_args` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `probes` ADD `probe_no_default` BOOLEAN NOT NULL DEFAULT FALSE AFTER `probe_args`;
ALTER TABLE `probes` ADD `probe_reset` BOOLEAN NOT NULL DEFAULT FALSE AFTER `probe_no_default`;
ALTER TABLE `probes` ADD `probe_descr` VARCHAR(64) NULL DEFAULT NULL AFTER `device_id`;
