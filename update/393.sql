ALTER TABLE `neighbours` ADD `device_id` INT NULL DEFAULT '0' AFTER `neighbour_id`, ADD INDEX `device_id` (`device_id`);
UPDATE `neighbours` INNER JOIN `ports` ON `neighbours`.`port_id` = `ports`.`port_id` SET `neighbours`.`device_id` = `ports`.`device_id`;
DELETE FROM `neighbours` WHERE `device_id` = '0';
