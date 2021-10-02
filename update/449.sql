ALTER TABLE `ports_adsl` ADD `device_id` INT NOT NULL DEFAULT '0' AFTER `adsl_id`;
UPDATE `ports_adsl` SET `ports_adsl`.`device_id` = (SELECT `ports`.`device_id` FROM `ports` WHERE `ports`.`port_id` = `ports_adsl`.`port_id`);
ALTER TABLE `ports_adsl` ADD INDEX `cache` (`device_id`, `port_id`);
ALTER TABLE `ports_adsl` DROP INDEX `interface_id`;
