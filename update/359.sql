-- NOTE update syslog, may be long operation ~30-45min
ALTER TABLE `syslog` ADD INDEX `device_priority` (`device_id`, `priority`);
ALTER TABLE `syslog` ADD INDEX `device_program` (`device_id`, `program`);
ALTER TABLE `syslog` ADD INDEX `device_timestamp` (`device_id`, `timestamp`);
ALTER TABLE `syslog` DROP INDEX `program_device`;
