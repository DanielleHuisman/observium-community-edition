ALTER TABLE `status` ADD `status_value` INT NULL AFTER `status_disable`, ADD `status_polled` INT NOT NULL AFTER `status_value`, ADD `status_last_change` INT NOT NULL AFTER `status_polled`, ADD `status_event` ENUM('ok', 'warning', 'alert', 'down', 'ignore') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ignore' AFTER `status_last_change`, ADD `status_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `status_event`;
UPDATE `status` p, `status-state` s SET p.`status_value` = s.`status_value`, p.`status_polled` = s.`status_polled`, p.`status_last_change` = s.`status_last_change`, p.`status_event` = s.`status_event`, p.`status_name` = s.`status_name` WHERE p.`status_id` = s.`status_id`;
DROP TABLE `status-state`;

