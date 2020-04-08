CREATE TABLE `observium_processes` ( `process_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT , `process_pid` INT NOT NULL , `process_name` VARCHAR(32) NOT NULL , `process_start` INT NOT NULL , `device_id` INT NOT NULL , PRIMARY KEY (`process_id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci;
ALTER TABLE `observium_processes` ADD `process_uid` INT NOT NULL AFTER `process_pid`;
ALTER TABLE `observium_processes` ADD `process_command` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `process_uid`;
ALTER TABLE `observium_processes` ADD INDEX `pid` (`process_pid`, `process_name`, `device_id`);
ALTER TABLE `observium_processes` ADD INDEX `name` (`process_name`, `device_id`);
