ALTER TABLE `oids_entries` ADD INDEX `device_id` (`device_id`);
ALTER TABLE `notifications_queue` CHANGE `aca_type` `aca_type` ENUM('alert','syslog','web') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `notifications_queue` ADD INDEX `device_id` (`device_id`);
ALTER TABLE `syslog_rules` ADD INDEX `la_cache` (`la_disable`);
ALTER TABLE `cef_prefix` ADD INDEX `cef_cache` (`device_id`);
ALTER TABLE `wifi_aps` ADD INDEX `device_id` (`device_id`);
ALTER TABLE `wifi_aps_members` ADD INDEX `device_id` (`device_id`);
ALTER TABLE `groups` ADD INDEX `entity_type` (`entity_type`);
ALTER TABLE `ports` ADD INDEX `port_descr_type` (`port_id`, `device_id`, `port_descr_type`);
