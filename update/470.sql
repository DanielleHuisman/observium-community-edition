ALTER TABLE `neighbours` ADD `remote_device_id` INT NULL DEFAULT NULL AFTER `port_id`;
UPDATE `neighbours` INNER JOIN `ports` ON `neighbours`.`remote_port_id` = `ports`.`port_id` SET `neighbours`.`remote_device_id` = `ports`.`device_id`;
