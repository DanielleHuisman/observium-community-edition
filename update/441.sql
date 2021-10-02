ALTER TABLE `wifi_accesspoints` ENGINE = InnoDB;
ALTER TABLE `wifi_sessions` ENGINE = InnoDB;
ALTER TABLE `wifi_radios` ENGINE = InnoDB;
ALTER TABLE `sensors` CHANGE `sensor_value` `sensor_value` DOUBLE(32,16) NULL DEFAULT NULL;
ALTER TABLE `counters` CHANGE `counter_value` `counter_value` DOUBLE(32,16) NULL DEFAULT NULL;