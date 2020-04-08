ALTER TABLE `sensors` ADD `sensor_value` float(14,5) DEFAULT NULL;
ALTER TABLE `sensors` ADD `sensor_event` enum('ok','warning','alert','ignore') NOT NULL;
ALTER TABLE `sensors` ADD `sensor_status` varchar(64) NOT NULL;
ALTER TABLE `sensors` ADD `sensor_polled` int(11) NOT NULL;
ALTER TABLE `sensors` ADD `sensor_last_change` int(11) NOT NULL;
UPDATE `sensors` p, `sensors-state` s SET p.`sensor_value` = s.`sensor_value`,p.`sensor_event` = s.`sensor_event`,p.`sensor_status` = s.`sensor_status`,p.`sensor_polled` = s.`sensor_polled`,p.`sensor_last_change` = s.`sensor_last_change` WHERE p.`sensor_id` = s.`sensor_id`;
DROP TABLE `sensors-state`
