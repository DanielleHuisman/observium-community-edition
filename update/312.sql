ALTER TABLE `ports` ADD `ifDiscards_rate` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `ifOutDiscards_rate`;
# more indexes
ALTER TABLE `entity_attribs` DROP INDEX `device_type`;
ALTER TABLE `entity_attribs` ADD INDEX `attribs_cache` (`entity_type`, `entity_id`, `attrib_type`(50));
ALTER TABLE `eventlog` ADD INDEX `eventlog_cache` (`device_id`, `entity_type`, `severity`);
