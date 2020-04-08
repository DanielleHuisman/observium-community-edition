ALTER TABLE `devices` ADD `poller_id` INT NOT NULL DEFAULT '0' AFTER `device_id`;
ALTER TABLE `observium_processes` ADD `poller_id` INT NOT NULL DEFAULT '0' AFTER `device_id`;

